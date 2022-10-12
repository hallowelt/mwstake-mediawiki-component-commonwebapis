<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Hook;

use MWStake\MediaWiki\Component\CommonWebAPIs\Rest\QueryStore;
use MWStake\MediaWiki\Component\DataStore\ResultSet;

// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
interface CommonWebAPIs_QueryStoreResult {
	/**
	 * This hook is called after a query store has been executed
	 *
	 * @since 1.35
	 *
	 * phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
	 * @param QueryStore $store
	 * @param ResultSet &$result
	 * @return void
	 */
	public function onCommonWebAPIs__QueryStoreResult( $store, &$result );
}
