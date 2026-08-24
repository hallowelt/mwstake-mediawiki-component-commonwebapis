<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Hook;

interface MWStakeUserStoreVisibleGroupsTypeFilterHook {

	/**
	 * @param array &$types
	 * @return void
	 */
	public function onMWStakeUserStoreVisibleGroupsTypeFilter( array &$types );
}
