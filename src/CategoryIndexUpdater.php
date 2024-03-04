<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs;

use Category;
use MediaWiki\Page\Hook\CategoryAfterPageAddedHook;
use MediaWiki\Page\Hook\CategoryAfterPageRemovedHook;
use Wikimedia\Rdbms\ILoadBalancer;

class CategoryIndexUpdater implements CategoryAfterPageAddedHook, CategoryAfterPageRemovedHook {

	/**
	 * @var ILoadBalancer
	 */
	private $lb;

	/**
	 * @param ILoadBalancer $lb
	 */
	public function __construct( ILoadBalancer $lb ) {
		$this->lb = $lb;
	}

	/**
	 * @inheritDoc
	 */
	public function onCategoryAfterPageAdded( $category, $wikiPage ) {
		$this->updateForCategory( $category );
	}

	/**
	 * @inheritDoc
	 */
	public function onCategoryAfterPageRemoved( $category, $wikiPage, $id ) {
		$this->updateForCategory( $category );
	}

	/**
	 * @param Category $category
	 * @return void
	 */
	private function updateForCategory( Category $category ) {
		$categoryKey = $category->getPage()->getDBkey();
		$this->delete( $categoryKey );
		$info = $this->getCategoryInfo( $categoryKey );
		if ( $info ) {
			$this->insert( $info );
		}
	}

	/**
	 * @param string $categoryKey
	 * @return void
	 */
	private function delete( string $categoryKey ) {
		$dbw = $this->lb->getConnection( DB_PRIMARY );
		$dbw->delete( 'mws_category_index', [ 'mci_title' => $categoryKey ] );
	}

	/**
	 * @param string $categoryKey
	 * @return array|null
	 */
	private function getCategoryInfo( string $categoryKey ): ?array {
		$dbr = $this->lb->getConnection( DB_REPLICA );
		$row = $dbr->selectRow(
			'category',
			[ 'cat_id', 'cat_pages' ],
			[ 'cat_title' => $categoryKey ],
			__METHOD__
		);

		if ( $row ) {
			return [
				'mci_cat_id' => $row->cat_id,
				'mci_title' => mb_strtolower( str_replace( '_', ' ', $categoryKey ) ),
				'mci_count' => $row->cat_pages
			];
		}
		return null;
	}

	/**
	 * @param array $info
	 * @return void
	 */
	private function insert( array $info ) {
		$dbw = $this->lb->getConnectionRef( DB_PRIMARY );
		$dbw->insert( 'mws_category_index', $info );
	}

}
