<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleTreeStore;

use MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleQueryStore\TitleRecord;
use MWStake\MediaWiki\Component\DataStore\Record;

class TitleTreeRecord extends TitleRecord {
	public const ID = 'id';
	public const EXPANDED = 'expanded';
	public const LOADED = 'loaded';
	public const LEAF = 'leaf';
	public const CHILDREN = 'children';
}
