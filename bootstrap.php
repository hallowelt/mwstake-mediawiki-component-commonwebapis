<?php

if ( defined( 'MWSTAKE_MEDIAWIKI_COMPONENT_COMMONWEBAPIS_VERSION' ) ) {
	return;
}

define( 'MWSTAKE_MEDIAWIKI_COMPONENT_COMMONWEBAPIS_VERSION', '2.0.6' );

MWStake\MediaWiki\ComponentLoader\Bootstrapper::getInstance()
	->register( 'commonwebapis', static function () {
		$GLOBALS['wgExtensionFunctions'][]
			= "\\MWStake\\MediaWiki\\Component\\CommonWebAPIs\\Setup::onExtensionFunctions";
		$GLOBALS['wgServiceWiringFiles'][] = __DIR__ . '/includes/ServiceWiring.php';

		$GLOBALS['wgExtensionFunctions'][] = static function () {
			$lb = \MediaWiki\MediaWikiServices::getInstance()->getDBLoadBalancer();
			$titleIndexUpdater = new \MWStake\MediaWiki\Component\CommonWebAPIs\TitleIndexUpdater( $lb );
			$userIndexUpdater = new \MWStake\MediaWiki\Component\CommonWebAPIs\UserIndexUpdater( $lb );
			$hookContainer = \MediaWiki\MediaWikiServices::getInstance()->getHookContainer();

			$hookContainer->register( 'LoadExtensionSchemaUpdates', static function ( $updater ) {
				$updater->addExtensionTable(
					'mws_user_index',
					__DIR__ . "/sql/mws_user_index.sql"
				);
				$updater->addExtensionTable(
					'mws_title_index',
					__DIR__ . "/sql/mws_title_index.sql"
				);

				$updater->addPostDatabaseUpdateMaintenance(
					\MWStake\MediaWiki\Component\CommonWebAPIs\Maintenance\PopulateUserIndex::class
				);
				$updater->addPostDatabaseUpdateMaintenance(
					\MWStake\MediaWiki\Component\CommonWebAPIs\Maintenance\PopulateTitleIndex::class
				);
			} );

			$hookContainer->register( 'UserSaveSettings', [ $userIndexUpdater, 'store' ] );

			$hookContainer->register( 'PageSaveComplete', [ $titleIndexUpdater, 'onPageSaveComplete' ] );
			$hookContainer->register( 'PageMoveComplete', [ $titleIndexUpdater, 'onPageMoveComplete' ] );
			$hookContainer->register( 'PageDeleteComplete', [ $titleIndexUpdater, 'onPageDeleteComplete' ] );
			$hookContainer->register( 'ArticleUndelete', [ $titleIndexUpdater, 'onArticleUndelete' ] );
			$hookContainer->register( 'AfterImportPage', [ $titleIndexUpdater, 'onAfterImportPage' ] );
		};

		$GLOBALS['wgResourceModules']['ext.mws.commonwebapis'] = [
			'scripts' => [
				'api.js'
			],
			'localBasePath' => __DIR__ . '/resources'
		];

		// Exclude users from these groups in user store
		$GLOBALS['mwsgCommonWebAPIsComponentUserStoreExcludeGroups'] = [ 'bot' ];
		// Exclude porticular users from user store
		$GLOBALS['mwsgCommonWebAPIsComponentUserStoreExcludeUsers'] = array_merge(
			$GLOBALS['wgReservedUsernames'] ?? [], [ 'Mediawiki default' ]
		);
		// Exclude these groups from group store
		$GLOBALS['mwsgCommonWebAPIsComponentGroupStoreExcludeGroups'] = [ 'bot' ];
	} );
