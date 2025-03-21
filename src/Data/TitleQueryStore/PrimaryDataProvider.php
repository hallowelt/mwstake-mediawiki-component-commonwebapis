<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleQueryStore;

use MediaWiki\Language\Language;
use MediaWiki\Title\NamespaceInfo;
use MWStake\MediaWiki\Component\DataStore\Filter;
use MWStake\MediaWiki\Component\DataStore\PrimaryDatabaseDataProvider;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use MWStake\MediaWiki\Component\DataStore\Schema;
use Wikimedia\Rdbms\IDatabase;

class PrimaryDataProvider extends PrimaryDatabaseDataProvider {

	/** @var Language */
	protected $language;

	/** @var array */
	protected $contentNamespaces;

	/**
	 * @param IDatabase $db
	 * @param Schema $schema
	 * @param Language $language
	 * @param NamespaceInfo $nsInfo
	 */
	public function __construct(
		IDatabase $db, Schema $schema, Language $language, NamespaceInfo $nsInfo
	) {
		parent::__construct( $db, $schema );
		$this->language = $language;
		$this->contentNamespaces = $nsInfo->getContentNamespaces();
	}

	/**
	 * @param ReaderParams $params
	 *
	 * @return array
	 */
	protected function makePreFilterConds( ReaderParams $params ) {
		$filters = $params->getFilter();
		$conds = parent::makePreFilterConds( $params );
		$query = $params->getQuery();
		$nsFilter = [];
		foreach ( $filters as $filter ) {
			if (
				in_array( $filter->getField(), [
					TitleRecord::PAGE_DBKEY, TitleRecord::PAGE_DISPLAY_TITLE, TitleRecord::PAGE_TITLE
				] )
			) {
				$filter->setApplied( true );
				if ( !$query && $filter->getComparison() === Filter::COMPARISON_CONTAINS ) {
					$query = $filter->getValue();
				}
			}

 			if ( $filter->getField() === TitleRecord::PAGE_NAMESPACE ) {
				if ( !( $filter instanceof Filter\ListValue ) ) {
					$filter = new Filter\StringValue( [
						Filter::KEY_FIELD => TitleRecord::PAGE_NAMESPACE,
						Filter::KEY_VALUE => [ $filter->getValue() ],
						Filter::KEY_COMPARISON => 'in'
					] );
				}
				$nsFilter = array_merge( $nsFilter, $filter->getValue() );
				$filter->setApplied( true );
			}

			if ( $filter->getField() === TitleRecord::IS_CONTENT_PAGE ) {
				if ( $filter->getValue() ) {
					$nsFilter = array_merge( $nsFilter, $this->contentNamespaces );
				} else {
					$conds[] = 'mti_namespace NOT IN (' . $this->db->makeList( $this->contentNamespaces ) . ')';
				}
			}
			if ( $filter->getField() === TitleRecord::PAGE_CONTENT_MODEL ) {
				if ( !( $filter instanceof Filter\ListValue ) ) {
					$filter = new Filter\StringValue( [
						Filter::KEY_FIELD => TitleRecord::PAGE_CONTENT_MODEL,
						Filter::KEY_VALUE => [ $filter->getValue() ],
						Filter::KEY_COMPARISON => 'in'
					] );
				}
				$filter->setApplied( true );
				$conds[] = 'mti_content_model IN (' . $this->db->makeList( $filter->getValue() ) . ')';
			}
		}

		if ( $query !== '' ) {
			$query = mb_strtolower( str_replace( '_', ' ', $query ) );
			$titleQuery = 'mti_title ' . $this->db->buildLike(
				$this->db->anyString(), $query, $this->db->anyString()
			);
			$displayTitleQuery = 'mti_displaytitle ' . $this->db->buildLike(
				$this->db->anyString(), $query, $this->db->anyString()
			);
			$conds[] = "($titleQuery OR $displayTitleQuery)";
		}

		if ( !empty( $nsFilter ) ) {
			$conds[] = 'mti_namespace IN (' . $this->db->makeList( $nsFilter ) . ')';
		}

		return $conds;
	}

	/**
	 * Replace filters for page title with normalized field
	 *
	 * @param array &$conds
	 * @param Filter $filter
	 *
	 * @return void
	 */
	protected function appendPreFilterCond( &$conds, Filter $filter ) {
		if ( in_array( $filter->getField(), [ TitleRecord::PAGE_DBKEY, TitleRecord::PAGE_TITLE ] ) ) {
			$filter = new Filter\StringValue( [
				Filter::KEY_FIELD => 'mti_title',
				Filter::KEY_VALUE => mb_strtolower( str_replace( '_', ' ', $filter->getValue() ) ),
				Filter::KEY_COMPARISON => $filter->getComparison()
			] );
		}
		parent::appendPreFilterCond( $conds, $filter );
	}

	/**
	 * @inheritDoc
	 */
	protected function getFields() {
		return [
			'mti_page_id', 'mti_title', 'mti_wiki_id', 'mti_prefixed', 'mti_displaytitle', 'mti_namespace_text',
			'mti_namespace', 'mti_dbkey', 'mti_content_model'
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function skipPreFilter( Filter $filter ) {
		return in_array( $filter->getField(), [
			TitleRecord::PAGE_NAMESPACE, TitleRecord::PAGE_NAMESPACE_TEXT,
			TitleRecord::IS_CONTENT_PAGE
		] );
	}

	/**
	 * @param \stdClass $row
	 *
	 * @return void
	 */
	protected function appendRowToData( \stdClass $row ) {
		$this->data[] = new TitleRecord( (object)[
			TitleRecord::PAGE_ID => (int)$row->mti_page_id,
			TitleRecord::PAGE_NAMESPACE => (int)$row->mti_namespace,
			TitleRecord::PAGE_PREFIXED => $row->mti_prefixed,
			TitleRecord::PAGE_DBKEY => $row->mti_dbkey,
			TitleRecord::PAGE_CONTENT_MODEL => $row->mti_content_model,
			TitleRecord::IS_CONTENT_PAGE => in_array( $row->mti_namespace, $this->contentNamespaces ),
			TitleRecord::PAGE_EXISTS => true,
			TitleRecord::PAGE_WIKI_ID => $row->mti_wiki_id,
			TitleRecord::PAGE_NAMESPACE_TEXT => $row->mti_namespace_text,
		] );
	}

	/**
	 * @return string[]
	 */
	protected function getTableNames() {
		return [ 'mws_title_index_full' ];
	}
}
