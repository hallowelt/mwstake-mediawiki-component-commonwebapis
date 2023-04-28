<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleQueryStore;

use MWStake\MediaWiki\Component\DataStore\Filter;
use MWStake\MediaWiki\Component\DataStore\PrimaryDatabaseDataProvider;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use MWStake\MediaWiki\Component\DataStore\Schema;
use Wikimedia\Rdbms\IDatabase;

class PrimaryDataProvider extends PrimaryDatabaseDataProvider {

	/** @var \Language */
	protected $language;

	/** @var array */
	protected $contentNamespaces;

	/**
	 * @param IDatabase $db
	 * @param Schema $schema
	 * @param \Language $language
	 * @param \NamespaceInfo $nsInfo
	 */
	public function __construct(
		IDatabase $db, Schema $schema, \Language $language, \NamespaceInfo $nsInfo
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
		foreach ( $filters as $filter ) {
			if ( in_array( $filter->getField(), [ TitleRecord::PAGE_DBKEY, TitleRecord::PAGE_TITLE ] ) ) {
				$query = $filter->getValue();
				$filter->setApplied( true );
			}
			if ( $filter->getField() === TitleRecord::PAGE_NAMESPACE_TEXT ) {
				$id = $this->language->getNsIndex( $filter->getValue() );
				if ( $id !== false ) {
					$conds['mti_namespace'] = $id;
				}
				$filter->setApplied( true );
			}
			if ( $filter->getField() === TitleRecord::PAGE_NAMESPACE ) {
				if ( !is_array( $filter->getValue() ) ) {
					$filter->setValue( [ $filter->getValue() ] );
				}
				$conds[] = 'mti_namespace IN (' . $this->db->makeList( $filter->getValue() ) . ')';
				$filter->setApplied( true );
			}

			if ( $filter->getField() === TitleRecord::IS_CONTENT_PAGE ) {
				if ( $filter->getValue() ) {
					$conds[] = 'mti_namespace IN (' . $this->db->makeList( $this->contentNamespaces ) . ')';
				} else {
					$conds[] = 'mti_namespace NOT IN (' . $this->db->makeList( $this->contentNamespaces ) . ')';
				}
			}
		}

		if ( $query !== '' ) {
			$query = mb_strtolower( str_replace( '_', ' ', $query ) );
			$conds[] = 'mti_title ' . $this->db->buildLike(
				$this->db->anyString(), $query, $this->db->anyString()
			);
		}

		return $conds;
	}

	/**
	 * @inheritDoc
	 */
	protected function getFields() {
		return [ 'mti_page_id', 'page_namespace', 'page_title', 'page_content_model', 'page_lang' ];
	}

	/**
	 * @inheritDoc
	 */
	protected function skipPreFilter( Filter $filter ) {
		return in_array( $filter->getField(), [
			TitleRecord::PAGE_DBKEY, TitleRecord::PAGE_TITLE,
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
			TitleRecord::PAGE_NAMESPACE => (int)$row->page_namespace,
			TitleRecord::PAGE_DBKEY => $row->page_title,
			TitleRecord::PAGE_CONTENT_MODEL => $row->page_content_model,
			TitleRecord::IS_CONTENT_PAGE => in_array( $row->page_namespace, $this->contentNamespaces ),
		] );
	}

	/**
	 * @inheritDoc
	 */
	protected function getJoinConds( ReaderParams $params ) {
		return [
			'page' => [
				'INNER JOIN', [ 'mti_page_id = page_id' ]
			]
		];
	}

	/**
	 * @return string[]
	 */
	protected function getTableNames() {
		return [ 'mws_title_index', 'page' ];
	}
}