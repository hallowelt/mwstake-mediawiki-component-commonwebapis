<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Maintenance;

use MediaWiki\Maintenance\LoggedUpdateMaintenance;
use MediaWiki\MediaWikiServices;
use MediaWiki\WikiMap\WikiMap;

$maintPath = dirname( __DIR__, 5 ) . '/maintenance/Maintenance.php';
if ( file_exists( $maintPath ) ) {
	require_once $maintPath;
}

class PopulateTitleIndex extends LoggedUpdateMaintenance {
	/**
	 * @return bool
	 */
	public function doDBUpdates() {
		$db = $this->getDB( DB_PRIMARY );
		$wikiId = strtolower( WikiMap::getCurrentWikiId() );
		if ( !$db->tableExists( 'mws_title_index_full', __METHOD__ ) ) {
			return false;
		}
		$db->newDeleteQueryBuilder()
			->delete( 'mws_title_index_full' )
			->where( 'mti_wiki_id = ' . $db->addQuotes( $wikiId ) )
			->caller( __METHOD__ )
			->execute();

		$titles = $db->select(
			[ 'p' => 'page', 'pp' => 'page_props' ],
			[ 'page_id', 'page_namespace', 'page_content_model', 'page_title', 'pp_value' ],
			[],
			__METHOD__,
			[],
			[ 'pp' => [ 'LEFT OUTER JOIN', [ 'p.page_id = pp.pp_page', 'pp.pp_propname' => 'displaytitle' ] ] ]
		);

		$toInsert = [];
		$cnt = 0;
		$batch = 250;
		foreach ( $titles as $title ) {
			$titleObject = $this->getServiceContainer()->getTitleFactory()->newFromRow( $title );
			$toInsert[] = [
				'mti_page_id' => $title->page_id,
				'mti_namespace' => mb_strtolower( $title->page_namespace ),
				'mti_title' => mb_strtolower( str_replace( '_', ' ', $title->page_title ) ),
				'mti_dbkey' => $title->page_title,
				'mti_content_model' => $title->page_content_model,
				'mti_displaytitle' => mb_strtolower( str_replace( '_', ' ', $title->pp_value ?? '' ) ),
				'mti_wiki_id' => strtolower( WikiMap::getCurrentWikiId() ),
				'mti_prefixed' => $titleObject->getPrefixedText(),
				'mti_namespace_text' => $titleObject->getNsText()
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

	/**
	 * @param array $batch
	 */
	private function insertBatch( array $batch ) {
		$db = $this->getDB( DB_PRIMARY );
		$db->insert(
			'mws_title_index_full',
			$batch,
			__METHOD__,
			[ 'IGNORE' ]
		);
	}

	/**
	 * @return string
	 */
	protected function getUpdateKey() {
		return 'mws-title-index-init-with-wiki-id';
	}
}

$maintClass = PopulateTitleIndex::class;
require_once RUN_MAINTENANCE_IF_MAIN;
