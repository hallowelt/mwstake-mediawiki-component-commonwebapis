<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleTreeStore;

use MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleQueryStore\TitleRecord;
use MWStake\MediaWiki\Component\DataStore\ISecondaryDataProvider;
use MWStake\MediaWiki\Component\DataStore\Record;

class SecondaryDataProvider extends \MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleQueryStore\SecondaryDataProvider {

	public function extend( $dataSets ) {
		$extended = parent::extend( $dataSets );
		foreach ( $extended as $dataSet ) {
			$this->extend( $dataSet->get( TitleTreeRecord::CHILDREN ) );
		}
		return $extended;
	}

	/**
	 * @param Record $dataSet
	 * @param \Title $title
	 *
	 * @return void
	 */
	protected function extendWithTitle( Record $dataSet, \Title $title ) {
		parent::extendWithTitle( $dataSet, $title );
		if ( $title->getNamespace() === NS_MAIN ) {
			$dataSet->set( TitleTreeRecord::ID, ':' . $title->getDBkey() );
		} else {
			$dataSet->set( TitleTreeRecord::ID, $title->getPrefixedDBkey() );
		}
		$dataSet->set( TitleTreeRecord::PAGE_EXISTS, $title->exists() );
		if (
			(
				$dataSet->get( TitleTreeRecord::LOADED ) &&
				empty( $dataSet->get( TitleTreeRecord::CHILDREN ) )
			) ||
			!$title->hasSubpages()
		) {
			$dataSet->set( TitleTreeRecord::LEAF, true );
		}
	}
}
