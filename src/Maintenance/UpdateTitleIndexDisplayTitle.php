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

		$success = $fail = 0;
		foreach ( $titles as $title ) {
			$display = $this->getDisplayTitle( $title );
			if ( !$display ) {
				continue;
			}
			if ( $this->update( $title->page_id, $display ) ) {
				$success++;
			} else {
				$fail++;
			}
		}

		$this->output( "Added display title in title index for $success page(s), $fail failed\n" );

		return true;
	}

	/**
	 * @param string $pageId
	 * @param string $display
	 *
	 * @return bool
	 */
	private function update( string $pageId, string $display ) {
		$db = $this->getDB( DB_PRIMARY );
		return $db->update(
			'mws_title_index',
			[ 'mti_displaytitle' => $display ],
			[ 'mti_page_id' => $pageId ],
			__METHOD__,
			[ 'IGNORE' ]
		);
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
