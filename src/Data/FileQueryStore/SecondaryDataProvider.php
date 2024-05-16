<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Data\FileQueryStore;

use MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleQueryStore\SecondaryDataProvider
	as TitleSecondaryDataProvider;
use MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleQueryStore\TitleRecord;

class SecondaryDataProvider extends TitleSecondaryDataProvider {

	/** @var \TitleFactory */
	protected $titleFactory;
	/** @var \Language */
	protected $language;
	/** @var \PageProps */
	protected $pageProps;
	/** @var \RepoGroup */
	protected $repoGroup;
	/** @var \RequestContext|null */
	protected $context;

	/**
	 * @param \TitleFactory $titleFactory
	 * @param \Language $language
	 * @param \PageProps $pageProp
	 * @param \RepoGroup $repoGroup
	 */
	public function __construct( $titleFactory, \Language $language, \PageProps $pageProps, \RepoGroup $repoGroup ) {
		$this->titleFactory = $titleFactory;
		$this->language = $language;
		$this->pageProps = $pageProps;
		$this->repoGroup = $repoGroup;
		$this->context = \RequestContext::getMain();
	}

	/**
	 * @param array $dataSets
	 *
	 * @return \MWStake\MediaWiki\Component\DataStore\Record[]
	 */
	public function extend( $dataSets ) {
		$dataSets = parent::extend( $dataSets );
		foreach ( $dataSets as $dataSet ) {
			$title = $this->titleFromRecord( $dataSet );
			$dataSet->set( TitleRecord::PAGE_PREFIXED, $title->getText() );
			$file = $this->repoGroup->getLocalRepo()->newFile( $title );

			$timestamp = $file->getTimestamp();
			$dataSet->set(
				FileRecord::FILE_TIMESTAMP,
				$timestamp
			);
			$dataSet->set(
				FileRecord::FILE_TIMESTAMP_FORMATTED,
				$this->context->getLanguage()->userDate( $timestamp, $this->context->getUser() )
			);
			$dataSet->set(
				FileRecord::FILE_SIZE,
				$this->context->getLanguage()->formatSize( $file->getSize() )
			);
			$dataSet->set(
				FileRecord::FILE_THUMBNAIL_URL,
				$file->createThumb( 40 )
			);
			$dataSet->set(
				FileRecord::FILE_THUMBNAIL_URL_PREVIEW,
				$file->createThumb( 120 )
			);
			$actorId = $dataSet->get( FileRecord::FILE_AUTHOR_ID );
			$dataSet->set(
				FileRecord::FILE_AUTHOR_NAME,
				\MediaWiki\MediaWikiServices::getInstance()->getUserFactory()->newFromActorId( $actorId )->getName()
			);
		}

		return $dataSets;
	}
}
