<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleTreeStore;

class Sorter extends \MWStake\MediaWiki\Component\DataStore\Sorter {

	/**
	 * Sorts the children of each record as well, so every level of the tree is in the requested order
	 *
	 * @param \MWStake\MediaWiki\Component\DataStore\Record[] $dataSets
	 * @param array $unsortableProps
	 *
	 * @return \MWStake\MediaWiki\Component\DataStore\Record[]
	 */
	public function sort( $dataSets, $unsortableProps = [] ) {
		$dataSets = parent::sort( $dataSets, $unsortableProps );
		foreach ( $dataSets as $dataSet ) {
			$children = $dataSet->get( TitleTreeRecord::CHILDREN );
			if ( $children ) {
				$dataSet->set( TitleTreeRecord::CHILDREN, $this->sort( $children, $unsortableProps ) );
			}
		}
		return $dataSets;
	}
}
