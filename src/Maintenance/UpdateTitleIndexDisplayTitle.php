<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Maintenance;

use MediaWiki\MediaWikiServices;

class UpdateTitleIndexDisplayTitle extends \LoggedUpdateMaintenance {
	/**
	 * @return bool
	 */
	protected function doDBUpdates() {
		$db = $this->getDB( DB_REPLICA );

		$titles = $db->select(
			[ 'page', 'mws_title_index' ],
			[ 'page_id', 'page_namespace', 'page_title' ],
			[ 'page_id=mti_page_id' ],
			__METHOD__
		);

		$toUpdate = [];
		$cnt = 0;
		$batch = 250;
		foreach ( $titles as $title ) {
			$display = $this->getDisplayTitle( $title );
			if ( !$display ) {
				continue;
			}
			$toUpdate[] = [
				'values' => [ 'mti_displaytitle' => $display ],
				'conds' => [ 'mti_page_id' => $title->page_id ],
			];
			$cnt++;
			if ( $cnt % $batch === 0 ) {
				$this->updateBatch( $toUpdate );
				$toUpdate = [];
			}
		}
		if ( !empty( $toUpdate ) ) {
			$this->updateBatch( $toUpdate );
		}

		$this->output( "Updated index for $cnt pages\n" );

		return true;
	}

	/**
	 * @param array $batch
	 */
	private function updateBatch( array $batch ) {
		$db = $this->getDB( DB_PRIMARY );
		foreach ( $batch as $update ) {
			$db->update(
				'mws_title_index',
				$update['values'],
				$update['conds'],
				__METHOD__,
				[ 'IGNORE' ]
			);
		}
	}

	/**
	 * @param \stdClass $title
	 *
	 * @return string
	 */
	private function getDisplayTitle( $title ): string {
		$pageProps = MediaWikiServices::getInstance()->getPageProps();
		$display = $pageProps->getProperties(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromRow( $title ), 'displaytitle'
		);
		if ( isset( $display[(int)$title->page_id] ) ) {
			return mb_strtolower( str_replace( '_', ' ', $display[(int)$title->page_id] ) );
		}
		return '';
	}

	/**
	 * @return string
	 */
	protected function getUpdateKey() {
		return 'mws-title-index-update-display-title';
	}
}
