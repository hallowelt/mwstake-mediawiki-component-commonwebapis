<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleQueryStore;

use MWStake\MediaWiki\Component\DataStore\IStore;
use Wikimedia\Rdbms\ILoadBalancer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\PermissionManager;

class Store implements IStore {
	/** @var ILoadBalancer */
	protected $lb;
	/** @var \TitleFactory */
	protected $titleFactory;
	/** @var \Language */
	protected $language;
	/** @var \NamespaceInfo */
	protected $nsInfo;
	/** @var \PageProps */
	protected $pageProps;
	/** @var \PermissionManager */
	protected $permissionManager;

	/**
	 * @param ILoadBalancer $lb
	 * @param \TitleFactory $titleFactory
	 * @param \Language $language
	 * @param \NamespaceInfo $nsInfo
	 * @param \PageProps|null $pageProps
	 * @param \PermissionManager|null $permissionManager
	 */
	public function __construct(
		ILoadBalancer $lb, \TitleFactory $titleFactory, \Language $language,
		\NamespaceInfo $nsInfo, \PageProps $pageProps = null, ?PermissionManager $permissionManager = null
	) {
		$this->lb = $lb;
		$this->titleFactory = $titleFactory;
		$this->language = $language;
		$this->nsInfo = $nsInfo;

		// Unintentionally, interface has changed in 2.0.9, so we need to check for the PageProps
		if ( !$pageProps ) {
			$pageProps = MediaWikiServices::getInstance()->getPageProps();
		}

		$this->pageProps = $pageProps;
		$this->permissionManager = $permissionManager;
		if ( $this->permissionManager === null ) {
			$this->permissionManager = MediaWikiServices::getInstance()->getPermissionManager();
		}
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
			$this->lb, $this->titleFactory, $this->language, $this->nsInfo, $this->pageProps
		);
	}

	/**
	 * @return PrimaryDataProvider
	 */
	public function getWriter() {
		throw new NotImplementedException();
	}
}
