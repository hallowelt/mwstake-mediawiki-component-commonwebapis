<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleTreeStore;

use MWStake\MediaWiki\Component\DataStore\IStore;
use Wikimedia\Rdbms\ILoadBalancer;

class Store implements IStore {
	/** @var ILoadBalancer */
	protected $lb;
	/** @var \TitleFactory */
	protected $titleFactory;
	/** @var \Language */
	protected $language;
	/** @var \NamespaceInfo */
	protected $nsInfo;

	/**
	 * @param ILoadBalancer $lb
	 * @param \TitleFactory $titleFactory
	 * @param \Language $language
	 * @param \NamespaceInfo $nsInfo
	 */
	public function __construct(
		ILoadBalancer $lb, \TitleFactory $titleFactory, \Language $language, \NamespaceInfo $nsInfo
	) {
		$this->lb = $lb;
		$this->titleFactory = $titleFactory;
		$this->language = $language;
		$this->nsInfo = $nsInfo;
	}

	/**
	 * @return UserSchema
	 */
	public function getSchema() {
		return new TitleSchema();
	}

	/**
	 * @return Reader
	 */
	public function getReader() {
		return new Reader(
			$this->lb, $this->titleFactory, $this->language, $this->nsInfo
		);
	}

	/**
	 * @return PrimaryDataProvider
	 */
	public function getWriter() {
		throw new NotImplementedException();
	}
}
