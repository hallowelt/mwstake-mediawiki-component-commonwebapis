<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleQueryStore;

use MWStake\MediaWiki\Component\DataStore\ISecondaryDataProvider;
use MWStake\MediaWiki\Component\DataStore\Record;

class SecondaryDataProvider implements ISecondaryDataProvider {
	/** @var \TitleFactory */
	protected $titleFactory;
	/** @var \Language */
	protected $language;

	/**
	 * @param \TitleFactory $titleFactory
	 * @param \Language $language
	 */
	public function __construct( $titleFactory, \Language $language ) {
		$this->titleFactory = $titleFactory;
		$this->language = $language;
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
	 * @param \Title $title
	 *
	 * @return void
	 */
	protected function extendWithTitle( Record $dataSet, \Title $title ) {
		$dataSet->set( TitleRecord::PAGE_TITLE, $title->getText() );
		$dataSet->set( TitleRecord::PAGE_PREFIXED, $title->getPrefixedText() );
		$dataSet->set( TitleRecord::PAGE_URL, $title->getLocalURL() );
		$dataSet->set(
			TitleRecord::PAGE_NAMESPACE_TEXT, $this->language->getNsText( $title->getNamespace() )
		);
	}

	/**
	 * @param Record $record
	 *
	 * @return \Title|null
	 */
	protected function titleFromRecord( $record ) {
		return $this->titleFactory->makeTitle(
			$record->get( TitleRecord::PAGE_NAMESPACE ), $record->get( TitleRecord::PAGE_TITLE )
		);
	}
}
