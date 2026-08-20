<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Hook;

use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use Wikimedia\Rdbms\SelectQueryBuilder;

interface MWStakeCommonWebAPIsTitleStoreQueryHook {
	/**
	 * This hook is called after a query store has been executed
	 *
	 * @param SelectQueryBuilder $query
	 * @param ReaderParams $params
	 * @return void
	 */
	public function onMWStakeCommonWebAPIsTitleStoreQuery( SelectQueryBuilder $query, ReaderParams $params );
}
