<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Maintenance;

class PopulateTitleIndex extends \LoggedUpdateMaintenance {
	/**
	 * @return bool
	 */
	protected function doDBUpdates() {
		$db = $this->getDB( DB_REPLICA );

		$titles = $db->select(
			'page',
			[ 'page_id', 'page_namespace', 'page_title' ],
			[],
			__METHOD__
		);

		$toInsert = [];
		$cnt = 0;
		$batch = 250;
		foreach ( $titles as $title ) {
			$toInsert[] = [
				'mti_page_id' => $title->page_id,
				'mti_namespace' => mb_strtolower( $title->page_namespace ),
				'mti_title' => mb_strtolower( str_replace( '_', ' ', $title->page_title ) )
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

		$this->output( "Indexed $cnt pages\n" );

		return true;
	}

	private function insertBatch( array $batch ) {
		$db = $this->getDB( DB_PRIMARY );
		$db->insert(
			'mws_title_index',
			$batch,
			__METHOD__,
			[ 'IGNORE' ]
		);
	}

	/**
	 * @return string
	 */
	protected function getUpdateKey() {
		return 'mws-title-index-init';
	}
}
