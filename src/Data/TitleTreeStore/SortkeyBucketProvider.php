<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleTreeStore;

use Collation;
use MediaWiki\Collation\CollationFactory;
use MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleQueryStore\TitleRecord;

class SortkeyBucketProvider {

	/** @var Collation */
	private $collation;

	/**
	 * @param CollationFactory $collationFactory
	 */
	public function __construct( CollationFactory $collationFactory ) {
		$this->collation = $collationFactory->getCategoryCollation();
	}

	/**
	 * Get the ordered list of distinct first-letter buckets from a set of TitleRecords,
	 * each with the continue value of the first matching record.
	 *
	 * @param TitleRecord[] $records
	 * @return array[] Array of [ 'letter' => string, 'continue' => array ]
	 */
	public function getBuckets( array $records ): array {
		$buckets = [];
		foreach ( $records as $record ) {
			$letter = $record->get( TitleRecord::SORTKEY );
			if ( $letter === '' ) {
				continue;
			}
			if ( !isset( $buckets[$letter] ) ) {
				$buckets[$letter] = $record->getContinueValue();
			}
		}

		$letters = array_keys( $buckets );
		usort( $letters, function ( $a, $b ) {
			return strcmp(
				$this->collation->getSortKey( $a ),
				$this->collation->getSortKey( $b )
			);
		} );

		$sortedBuckets = [];
		foreach ( $letters as $letter ) {
			$sortedBuckets[$letter] = $buckets[$letter];
		}

		return $sortedBuckets;
	}
}

