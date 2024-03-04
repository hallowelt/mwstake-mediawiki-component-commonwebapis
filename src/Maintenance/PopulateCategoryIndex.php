<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Maintenance;

use LoggedUpdateMaintenance;

class PopulateCategoryIndex extends LoggedUpdateMaintenance {
	/**
	 * @return bool
	 */
	protected function doDBUpdates() {
		$db = $this->getDB( DB_REPLICA );

		if ( $db->tableExists( 'mws_category_index' ) ) {
			// Truncate first, if exists
			$db->delete( 'mws_category_index', '*', __METHOD__ );
		}
		$links = $db->select(
			'category',
			[ 'cat_id', 'cat_title', 'cat_pages' ],
			[],
			__METHOD__
		);

		$toInsert = [];
		$cnt = 0;
		$batch = 250;
		foreach ( $links as $link ) {
			$toInsert[] = [
				'mci_cat_id' => $link->cat_id,
				'mci_title' => mb_strtolower( str_replace( '_', ' ', $link->cat_title ) ),
				'mci_count' => $link->cat_pages
			];
			if ( $cnt % $batch === 0 ) {
				$this->insertBatch( $toInsert );
				$toInsert = [];
			}
			$cnt++;
		}
		if ( !empty( $toInsert ) ) {
			$this->insertBatch( $toInsert );
		}

		$this->output( "Indexed $cnt categories\n" );

		return true;
	}

	/**
	 * @param array $batch
	 */
	private function insertBatch( array $batch ) {
		$db = $this->getDB( DB_PRIMARY );
		$db->insert(
			'mws_category_index',
			$batch,
			__METHOD__,
			[ 'IGNORE' ]
		);
	}

	/**
	 * @return string
	 */
	protected function getUpdateKey() {
		return 'mws-category-index-init';
	}
}
