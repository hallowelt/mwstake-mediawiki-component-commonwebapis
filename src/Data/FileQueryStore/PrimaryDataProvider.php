<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Data\FileQueryStore;

use MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleQueryStore\PrimaryDataProvider
	as TitlePrimaryDataProvider;
use MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleQueryStore\TitleRecord;
use MWStake\MediaWiki\Component\DataStore\Filter;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;

class PrimaryDataProvider extends TitlePrimaryDataProvider {

	/**
	 * @param ReaderParams $params
	 *
	 * @return array
	 */
	protected function makePreFilterConds( ReaderParams $params ) {
		$filters = $params->getFilter();
		$conds = parent::makePreFilterConds( $params );
		$conds[] = 'mti_namespace = ' . NS_FILE;
		foreach ( $filters as $filter ) {
			if ( $filter->getField() === FileRecord::FILE_EXTENSION ) {
				$extensions = $filter->getValue();
				if ( !is_array( $extensions ) ) {
					$extensions = [ $extensions ];
				}
				$conds[] = $this->db->makeList( array_map( static function ( $extension ) {
					return 'mti_title LIKE "%.' . trim( strtolower( $extension ) ) . '"';
				}, $extensions ), LIST_OR );
			}
		}
		return $conds;
	}

	/**
	 * @inheritDoc
	 */
	protected function getFields() {
		return array_merge( parent::getFields(), [ 'img_actor', 'img_major_mime', 'img_minor_mime', 'comment_text', "GROUP_CONCAT( cl_to SEPARATOR '|') categories" ] );
	}

	/**
	 * @inheritDoc
	 */
	protected function skipPreFilter( Filter $filter ) {
		return in_array( $filter->getField(), [
			TitleRecord::PAGE_DBKEY, TitleRecord::PAGE_TITLE,
			TitleRecord::PAGE_NAMESPACE, TitleRecord::PAGE_NAMESPACE_TEXT,
			TitleRecord::IS_CONTENT_PAGE, FileRecord::FILE_EXTENSION
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
			TitleRecord::PAGE_NAMESPACE => NS_FILE,
			TitleRecord::PAGE_TITLE => $row->page_title,
			TitleRecord::PAGE_DBKEY => $row->page_title,
			TitleRecord::PAGE_CONTENT_MODEL => $row->page_content_model,
			TitleRecord::IS_CONTENT_PAGE => in_array( $row->page_namespace, $this->contentNamespaces ),
			FileRecord::FILE_EXTENSION => $this->getExtension( $row->page_title ),
			FileRecord::MIME_MAJOR => $row->img_major_mime,
			FileRecord::MIME_MINOR => $row->img_minor_mime,
			FileRecord::FILE_AUTHOR_ID => $row->img_actor,
			FileRecord::FILE_COMMENT => $row->comment_text,
			FileRecord::FILE_CATEGORIES =>  $row->categories
		] );
	}

	/**
	 * @inheritDoc
	 */
	protected function getJoinConds( ReaderParams $params ) {
		return [
			'page' => [
				'INNER JOIN', [ 'mti_page_id = page_id' ]
			],
			'image' => [
				'INNER JOIN', [ 'page_title = img_name' ]
			],
			'comment' => [
				'INNER JOIN', [ 'img_description_id = comment_id' ]
			],
			'categorylinks' => [
				'LEFT JOIN', [ 'page_id = cl_from' ]
			]
		];
	}

	/**
	 * @return string[]
	 */
	protected function getTableNames() {
		return [ 'mws_title_index', 'page', 'image', 'comment', 'categorylinks' ];
	}

		/**
	 *
	 * @return array
	 */
	protected function getDefaultOptions() {
		return [ 'GROUP BY' => 'page_id' ];
	}

	/**
	 * @param string $title
	 *
	 * @return string
	 */
	private function getExtension( string $title ): string {
		$bits = explode( '.', $title );
		return strtolower( array_pop( $bits ) );
	}
}
