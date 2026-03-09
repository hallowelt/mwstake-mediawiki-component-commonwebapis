<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Data\GroupStore;

use MediaWiki\Config\GlobalVarConfig;
use MediaWiki\HookContainer\HookContainer;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use MWStake\MediaWiki\Component\Utils\Utility\GroupHelper;

/*
 * @stable to extend
 */
class Reader extends \MWStake\MediaWiki\Component\DataStore\Reader {
	/** @var \MWStake\MediaWiki\Component\Utils\Utility\GroupHelper */
	protected $groupHelper;

	/** @var GlobalVarConfig */
	protected $mwsgConfig;

	/** @var HookContainer */
	protected $hookContainer;

	/**
	 * @param GroupHelper $groupHelper
	 * @param GlobalVarConfig $mwsgConfig
	 * @param HookContainer $hookContainer
	 */
	public function __construct( GroupHelper $groupHelper, GlobalVarConfig $mwsgConfig, HookContainer $hookContainer ) {
		$this->groupHelper = $groupHelper;
		$this->mwsgConfig = $mwsgConfig;
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @return UserSchema
	 */
	public function getSchema() {
		return new GroupSchema();
	}

	/**
	 * @param ReaderParams $params
	 *
	 * @return PrimaryDataProvider
	 */
	public function makePrimaryDataProvider( $params ) {
		return new PrimaryDataProvider( $this->groupHelper, $this->mwsgConfig, $this->hookContainer );
	}

	/**
	 * @inheritDoc
	 */
	public function makeSecondaryDataProvider() {
		return null;
	}
}
