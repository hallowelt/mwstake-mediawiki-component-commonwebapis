<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleTreeStore;

use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use MWStake\MediaWiki\Component\DataStore\Schema;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ResultWrapper;

class PrimaryDataProvider extends \MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleQueryStore\PrimaryDataProvider {
	/** @var string|null */
	private $query = null;
	/** @var array|null */
	private $expandPaths = null;

	/** @var \NamespaceInfo */
	private $nsInfo;

	/**
	 * @inheritDoc
	 */
	public function __construct( IDatabase $db, Schema $schema, \Language $language, \NamespaceInfo $nsInfo ) {
		parent::__construct( $db, $schema, $language, $nsInfo );
		$this->nsInfo = $nsInfo;
	}

	/**
	 * @param TitleTreeReaderParams $params
	 *
	 * @return \MWStake\MediaWiki\Component\DataStore\Record[]
	 */
	public function makeData( $params ) {
		if ( $params->getExpandPaths() ) {
			$this->expandPaths = $params->getExpandPaths();
		}
		if ( $params->getNode() !== '' ) {
			$node = $params->getNode();
			$node = $this->splitNode( $node );
			if ( $node ) {
				return $this->dataFromNode( $node );
			}
		}

		return array_values( parent::makeData( $params ) );
	}

	/**
	 * @param ReaderParams $params
	 *
	 * @return array
	 */
	protected function makePreFilterConds( ReaderParams $params ) {
		if ( $params->getQuery() !== '' ) {
			$this->query = mb_strtolower( str_replace( '_', ' ', $params->getQuery() ) );
		}
		return parent::makePreFilterConds( $params );
	}

	/**
	 * @param \stdClass $row
	 *
	 * @return void
	 */
	protected function appendRowToData( \stdClass $row ) {
		$indexTitle = $row->mti_title;
		$uniqueId = $this->getUniqueId( $row );
		if ( $this->isSubpage( $indexTitle ) ) {
			if (
				$this->queryMatchesSubpage( $indexTitle )
			) {
				$this->insertParents( $row, $uniqueId, true );
			} else {
				// If page is a subpage, but does not match the query, check if we need to insert parent
				// as a red link (parent doesnt exist)
				// This only happens when node is not specified (first-level pages)
				$nonExistingRootParent = $this->parentsNoExist( $row );
				if ( $nonExistingRootParent ) {
					$this->insertNonExistingPage( $row, $nonExistingRootParent );
				}
			}

			return;
		}
		if ( $this->isExpandRequested( $row->page_title, (int)$row->page_namespace ) ) {
			$this->expand( $row, $uniqueId, false );
		}

		// Adding root pages
		$this->data[$uniqueId] = $this->makeRecord( $row, $uniqueId, false, false );
	}

	/**
	 * @param \stdClass $row
	 * @param string $uniqueId
	 * @param bool $expanded
	 * @param bool $loaded
	 *
	 * @return TitleTreeRecord
	 */
	private function makeRecord( $row, string $uniqueId, bool $expanded, bool $loaded ) {
		return new TitleTreeRecord( (object)[
			TitleTreeRecord::ID => $uniqueId,
			TitleTreeRecord::PAGE_NAMESPACE => (int)$row->page_namespace,
			TitleTreeRecord::PAGE_TITLE => $row->page_title,
			TitleTreeRecord::PAGE_DBKEY => $row->page_title,
			TitleTreeRecord::IS_CONTENT_PAGE => in_array( $row->page_namespace, $this->contentNamespaces ),
			TitleTreeRecord::LEAF => false,
			TitleTreeRecord::EXPANDED => $expanded,
			TitleTreeRecord::LOADED => $loaded,
			TitleTreeRecord::CHILDREN => property_exists( $row, 'children' ) ? $row->children : []
		] );
	}

	/**
	 * @param \stdClass $row
	 *
	 * @return string
	 */
	private function getUniqueId( $row ): string {
		return (int)$row->page_namespace . ':' . $row->page_title;
	}

	/**
	 * @param string $indexTitle
	 *
	 * @return bool
	 */
	private function isSubpage( string $indexTitle ): bool {
		return strpos( $indexTitle, '/' ) !== false;
	}

	/**
	 * @param string $indexTitle
	 *
	 * @return bool
	 */
	private function queryMatchesSubpage( string $indexTitle ): bool {
		if ( !$this->query ) {
			return false;
		}
		$exploded = explode( '/', $indexTitle );
		// Get rid of the first element, which is the root page name
		array_shift( $exploded );
		return strpos( $indexTitle, $this->query ) !== false;
	}

	/**
	 * @param \stdClass $row
	 * @param string $uniqueId
	 *
	 * @return void
	 */
	private function expand( \stdClass $row, string $uniqueId ) {
		$row->children = $this->getChildren( $row, null );
		$this->insertParents( $row, $this->getUniqueId( $row ) );
	}

	/**
	 * @param \stdClass $row
	 * @param string $uniqueId
	 * @param bool|null $fromQuery True if row comes from the query, not from traversing the tree
	 *
	 * @return void
	 */
	private function insertParents( \stdClass $row, string $uniqueId, ?bool $fromQuery = false ): void {
		$title = $row->page_title;
		$bits = explode( '/', $title );
		if ( count( $bits ) === 1 ) {
			$this->data[$uniqueId] = $this->makeRecord( $row, $uniqueId, !$fromQuery, !$fromQuery );
			return;
		}
		array_pop( $bits );
		$parentTitle = implode( '/', $bits );
		$parentRow = new \stdClass();
		$parentRow->page_title = $parentTitle;
		$parentRow->page_namespace = $row->page_namespace;
		$parentRow->children = $this->getChildren(
			$parentRow,
			$this->makeRecord( $row, $uniqueId, !$fromQuery, !$fromQuery, )
		);
		$this->insertParents( $parentRow, $this->getUniqueId( $parentRow ) );
	}

	/**
	 * @param \stdClass $row
	 * @param TitleTreeRecord|null $loadedChild
	 *
	 * @return TitleTreeRecord[]
	 */
	private function getChildren( \stdClass $row, ?TitleTreeRecord $loadedChild ): array {
		$childRows = $this->getSubpages( $row );
		$children = $loadedChild ? [ $loadedChild ] : [];
		foreach ( $childRows as $childRow ) {
			$uniqueChildId = $this->getUniqueId( $childRow );
			if ( $loadedChild && $loadedChild->get( TitleTreeRecord::ID ) === $uniqueChildId ) {
				continue;
			}
			if ( !$this->isDirectChildOf( $row->page_title, $childRow->page_title ) ) {
				continue;
			}
			$child = $this->makeRecord( $childRow, $uniqueChildId, false, false );
			$children[] = $child;
		}

		return $children;
	}

	/**
	 * @param \stdClass $row
	 *
	 * @return \Wikimedia\Rdbms\IResultWrapper
	 */
	private function getSubpages( \stdClass $row ) {
		$res = $this->db->select(
			[ 'page' ],
			[ 'page_title', 'page_namespace' ],
			[
				'page_namespace' => $row->page_namespace,
				'page_title LIKE ' . $this->db->addQuotes( $row->page_title . '/%' )
			],
			__METHOD__,
			[ 'ORDER BY' => 'page_title' ]
		);

		$pages = [];
		foreach ( $res as $subpageRow ) {
			$pages[] = $subpageRow;
		}
		$pages = $this->insertNonExistingPages( $pages, $row->page_title );
		usort( $pages, function ( $a, $b ) {
			return strcmp( $a->page_title, $b->page_title );
		} );

		return $pages;
	}

	/**
	 * @param string $parent
	 * @param string $child
	 *
	 * @return bool
	 */
	private function isDirectChildOf( string $parent, string $child ): bool {
		$parentBits = explode( '/', $parent );
		$childBits = explode( '/', $child );
		if ( count( $childBits ) !== count( $parentBits ) + 1 ) {
			return false;
		}
		for ( $i = 0; $i < count( $parentBits ); $i++ ) {
			if ( $parentBits[$i] !== $childBits[$i] ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param string $node
	 *
	 * @return array|null
	 */
	private function splitNode( string $node ): ?array {
		$bits = explode( ':', $node );
		if ( count( $bits ) === 1 ) {
			return '0:' . $bits[0];
		}
		$ns = $bits[0];
		$nsIndex = $this->language->getNsIndex( $ns );
		if ( $nsIndex === null ) {
			return null;
		}
		return [
			'page_namespace' => $nsIndex,
			'page_title' => implode( ':', array_slice( $bits, 1 ) )
		];
	}

	/**
	 * @param array $node
	 *
	 * @return array|TitleTreeRecord[]
	 */
	private function dataFromNode( array $node ): array {
			return $this->getChildren( (object)$node, null );
	}

	/**
	 * @param string $dbkey
	 * @param int $ns
	 *
	 * @return bool
	 */
	private function isExpandRequested( string $dbkey, int $ns ): bool {
		if ( !$this->expandPaths ) {
			return false;
		}
		foreach ( $this->expandPaths as $path ) {
			$pathParts = $this->splitNode( $path );
			if ( $dbkey === $pathParts['page_title'] && $ns === $pathParts['page_namespace'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param \stdClass $row
	 *
	 * @return string|null
	 */
	private function parentsNoExist( \stdClass $row ): ?string {
		$title = $row->page_title;
		$bits = explode( '/', $title );
		if ( count( $bits ) === 1 ) {
			// Short-circuit
			return false;
		}
		$rootParent = array_shift( $bits );
		$exists = $this->db->selectRow(
			'page',
			[ 'page_title' ],
			[
				'page_namespace' => $row->page_namespace,
				'page_title' => $rootParent
			],
		);

		if ( !$exists ) {
			return $rootParent;
		}
		return null;
	}

	/**
	 * @param array $res
	 * @param string $parentNode
	 *
	 * @return array
	 */
	private function insertNonExistingPages( array $res, string $parentNode ): array {
		foreach ( $res as $row ) {
			$nonExistingPage = $this->getNonExistingChildOd( $row->page_title, $parentNode, (int) $row->page_namespace );
			if ( $nonExistingPage ) {
				$res[] = (object) [
					'page_title' => $nonExistingPage,
					'page_namespace' => $row->page_namespace
				];
			}
		}

		return $res;
	}

	/**
	 * @param string $child
	 * @param string $parent
	 * @param int $namespace
	 *
	 * @return mixed|null
	 */
	private function getNonExistingChildOd( string $child, string $parent, int $namespace ): ?string {
		$regex = '/^' . preg_quote( $parent, '/' ) . '+\/[^\/]+(?=\/|$)/';
		$matches = [];
		preg_match( $regex, $child, $matches );
		if ( count( $matches ) === 0 ) {
			return null;
		}
		$exists = $this->db->selectRow(
			'page',
			[ 'page_title' ],
			[
				'page_namespace' => $namespace,
				'page_title' => $matches[0]
			],
		);

		if ( !$exists ) {
			return $matches[0];
		}
		return null;

	}

	/**
	 * @param \stdClass $row
	 * @param string $nonExistingPageName
	 *
	 * @return void
	 */
	private function insertNonExistingPage( \stdClass $row, string $nonExistingPageName ) {
		$nonExistingNode = clone $row;
		$nonExistingNode->page_title = $nonExistingPageName;
		$uniqueId = $this->getUniqueId( $nonExistingNode );
		$this->data[$uniqueId] = $this->makeRecord( $nonExistingNode, $uniqueId, false, false );
	}
}
