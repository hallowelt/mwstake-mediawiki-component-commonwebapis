<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleQueryStore;

use MWStake\MediaWiki\Component\DataStore\ISecondaryDataProvider;
use MWStake\MediaWiki\Component\DataStore\Record;
use Title;

class SecondaryDataProvider implements ISecondaryDataProvider {
	/** @var \TitleFactory */
	protected $titleFactory;
	/** @var \Language */
	protected $language;
	/** @var \PageProps */
	protected $pageProps;

	/**
	 * @param \TitleFactory $titleFactory
	 * @param \Language $language
	 * @param \PageProps $pageProps
	 */
	public function __construct( $titleFactory, \Language $language, \PageProps $pageProps ) {
		$this->titleFactory = $titleFactory;
		$this->language = $language;
		$this->pageProps = $pageProps;
	}

	/**
	 * @param array $dataSets
	 *
	 * @return \MWStake\MediaWiki\Component\DataStore\Record[]
	 */
	public function extend( $dataSets ) {
		foreach ( $dataSets as $dataSet ) {
			$title = $this->titleFromRecord( $dataSet );
			$this->extendWithTitle( $dataSet, $title );
		}

		return $dataSets;
	}

	/**
	 * @param Record $dataSet
	 * @param Title $title
	 *
	 * @return void
	 */
	protected function extendWithTitle( Record $dataSet, Title $title ) {
		$dataSet->set( TitleRecord::PAGE_TITLE, $title->getText() );
		$dataSet->set( TitleRecord::PAGE_PREFIXED, $title->getPrefixedText() );
		$dataSet->set( TitleRecord::PAGE_URL, $title->getLocalURL() );
		$dataSet->set(
			TitleRecord::PAGE_NAMESPACE_TEXT, $this->language->getNsText( $title->getNamespace() )
		);
		$dataSet->set( TitleRecord::PAGE_DISPLAY_TITLE, $this->getDisplayTitle( $title ) );
		$dataSet->set( TitleRecord::PAGE_IS_REDIRECT, $title->isRedirect() );
	}

	/**
	 * @param Record $record
	 *
	 * @return Title|null
	 */
	protected function titleFromRecord( $record ) {
		return $this->titleFactory->makeTitle(
			$record->get( TitleRecord::PAGE_NAMESPACE ), $record->get( TitleRecord::PAGE_DBKEY )
		);
	}

	/**
	 * @param Title $title
	 *
	 * @return string
	 */
	protected function getDisplayTitle( Title $title ) {
		if ( !$title->exists() || !$title->canExist() ) {
			return '';
		}
		$display = $this->pageProps->getProperties( $title, 'displaytitle' );
		if ( isset( $display[$title->getId()] ) ) {
			return str_replace( '_', ' ', $display[$title->getId()] );
		}
		return '';
	}
}
