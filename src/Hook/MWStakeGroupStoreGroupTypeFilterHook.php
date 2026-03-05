<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Hook;

interface MWStakeGroupStoreGroupTypeFilterHook {
	/**
	 * This hook is called when filter for the group type is applied to the group store
	 *
	 * @param array $types
	 * @return void
	 * @since 1.35
	 *
	 */
	public function onMWStakeGroupStoreGroupTypeFilter( array &$types );
}
