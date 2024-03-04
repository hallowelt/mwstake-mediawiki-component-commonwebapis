<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Data\CategoryQueryStore;

use MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleQueryStore\PrimaryDataProvider as TitlePrimaryProvider;
use MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleQueryStore\TitleRecord;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;

class PrimaryDataProvider extends TitlePrimaryProvider {

	/** @var bool */
	private $parentDone = false;

	/**
	 * @inheritDoc
	 */
	public function makeData( $params ) {
		$params = CategoryReaderParams::newFromOtherReaderParams( $params );

		// First we run a normal title query to get categories that are not assigned to any page
		// but still exist in NS_CATEGORY
		parent::makeData( $params );

		// Then we run query into categorylinks to get categories that are assigned to pages
		$this->parentDone = true;
		$res = $this->db->select(
			'categorylinks',
			[
				'0 as mti_page_id',
				'\'\' as page_content_model',
				'cl_to as page_title',
				NS_CATEGORY . ' as page_namespace'
			],
			$this->makePreFilterConds( $params ),
			__METHOD__,
			$this->makePreOptionConds( $params )
		);

		$parentTitles = array_map( static function ( $record ) {
			return $record->get( TitleRecord::PAGE_TITLE );
		}, $this->data );

		foreach ( $res as $row ) {
			if ( in_array( $row->page_title, $parentTitles ) ) {
				continue;
			}
			$this->appendRowToData( $row );
		}

		return $this->data;
	}

	/**
	 * @param ReaderParams $params
	 * @return array|string[]
	 */
	protected function makePreFilterConds( ReaderParams $params ) {
		if ( !$this->parentDone ) {
			return parent::makePreFilterConds( $params );
		}
		$query = $params->getQuery();
		$conds = [];
		if ( $query ) {
			$query = mb_strtolower( str_replace( ' ', '_', $query ) );
			$field = "REPLACE( LOWER( CONVERT(cl_to USING utf8mb4) ), '_', ' ' )";
			$conds = [
				$field . $this->db->buildLike(
					$this->db->anyString(), $query, $this->db->anyString()
				)
			];
		}

		return $conds;
	}
}
