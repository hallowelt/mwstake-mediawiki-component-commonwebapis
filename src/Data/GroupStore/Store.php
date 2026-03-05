<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Data\GroupStore;

use MediaWiki\Config\GlobalVarConfig;
use MediaWiki\HookContainer\HookContainer;
use MWStake\MediaWiki\Component\DataStore\IStore;
use MWStake\MediaWiki\Component\Utils\UtilityFactory;

class Store implements IStore {
	/** @var \MWStake\MediaWiki\Component\Utils\Utility\GroupHelper */
	protected $groupHelper;

	/** @var GlobalVarConfig */
	protected $mwsgConfig;

	/** @var HookContainer */
	protected $hookContainer;

	/**
	 * @param UtilityFactory $utilityFactory
	 * @param GlobalVarConfig $mwsgConfig
	 * @param HookContainer $hookContainer
	 */
	public function __construct(
		UtilityFactory $utilityFactory, GlobalVarConfig $mwsgConfig, HookContainer $hookContainer
	) {
		$this->groupHelper = $utilityFactory->getGroupHelper();
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
	 * @return PrimaryDataProvider
	 */
	public function getReader() {
		return new Reader( $this->groupHelper, $this->mwsgConfig, $this->hookContainer );
	}

	/**
	 * @return PrimaryDataProvider
	 */
	public function getWriter() {
		throw new NotImplementedException();
	}
}
