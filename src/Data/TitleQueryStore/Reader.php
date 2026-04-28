<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleQueryStore;

use MediaWiki\Language\Language;
use MediaWiki\Page\PageProps;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Title\NamespaceInfo;
use MediaWiki\Title\TitleFactory;
use MWStake\MediaWiki\Component\CommonWebAPIs\Data\PermissionTrimmer;
use MWStake\MediaWiki\Component\DataStore\ISecondaryDataProvider;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use MWStake\MediaWiki\Component\DataStore\ResultSet;
use Wikimedia\Rdbms\ILoadBalancer;

/*
 * @stable to extend
 */
class Reader extends \MWStake\MediaWiki\Component\DataStore\Reader {
	/** @var ILoadBalancer */
	protected $lb;
	/** @var TitleFactory */
	protected $titleFactory;
	/** @var Language */
	protected $language;
	/** @var NamespaceInfo */
	protected $nsInfo;
	/** @var PageProps */
	protected $pageProps;
	/** @var PermissionManager */
	protected $permissionManager;

	/**
	 * @param ILoadBalancer $lb
	 * @param TitleFactory $titleFactory
	 * @param Language $language
	 * @param NamespaceInfo $nsInfo
	 * @param PageProps $pageProps
	 * @param PermissionManager|null $permissionManager
	 */
	public function __construct(
		ILoadBalancer $lb, TitleFactory $titleFactory, Language $language,
		NamespaceInfo $nsInfo, PageProps $pageProps, ?PermissionManager $permissionManager = null
	) {
		parent::__construct();
		$this->lb = $lb;
		$this->titleFactory = $titleFactory;
		$this->language = $language;
		$this->nsInfo = $nsInfo;
		$this->pageProps = $pageProps;
		$this->permissionManager = $permissionManager;
		if ( $this->permissionManager === null ) {
			$this->permissionManager = \MediaWiki\MediaWikiServices::getInstance()->getPermissionManager();
		}
	}

	/**
	 * @return TitleSchema
	 */
	public function getSchema() {
		return new TitleSchema();
	}

	/**
	 * @param ReaderParams $params
	 *
	 * @return PrimaryDataProvider
	 */
	public function makePrimaryDataProvider( $params ) {
		return new PrimaryDataProvider(
			$this->lb->getConnection( DB_REPLICA ), $this->getSchema(), $this->language,
			$this->nsInfo, $this->permissionManager
		);
	}

	/**
	 * @return SecondaryDataProvider
	 */
	public function makeSecondaryDataProvider() {
		return new SecondaryDataProvider( $this->titleFactory, $this->language, $this->pageProps );
	}

	/**
	 * @param ReaderParams $params
	 * @return PermissionTrimmer
	 */
	protected function makeTrimmer( $params ) {
		return new PermissionTrimmer(
			$params->getLimit(),
			$params->getStart(),
			$this->permissionManager
		);
	}

	/**
	 * @inheritDoc
	 */
	public function read( $params ) {
		$primaryDataProvider = $this->makePrimaryDataProvider( $params );
		$dataSets = $primaryDataProvider->makeData( $params );

		$filterer = $this->makeFilterer( $params );
		$dataSets = $filterer->filter( $dataSets );

		// Use a separate COUNT query for accurate total (pre-permission).
		// When a query is set, reranking may exclude rows, so use the in-memory count.
		if ( $params->getQuery() !== '' ) {
			$total = count( $dataSets );
		} else {
			$total = $primaryDataProvider->getTotal( $params );
		}

		$sorter = $this->makeSorter( $params );
		$dataSets = $sorter->sort(
			$dataSets,
			$this->getSchema()->getUnsortableFields()
		);

		$trimmer = $this->makeTrimmer( $params );
		$dataSets = $trimmer->trim( $dataSets );

		$secondaryDataProvider = $this->makeSecondaryDataProvider();
		if ( $secondaryDataProvider instanceof ISecondaryDataProvider ) {
			$dataSets = $secondaryDataProvider->extend( $dataSets );
		}

		return new ResultSet( $dataSets, $total );
	}
}
