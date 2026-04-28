<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Data;

use MediaWiki\Context\RequestContext;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Title\Title;
use MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleQueryStore\TitleRecord;
use MWStake\MediaWiki\Component\DataStore\ITrimmer;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use MWStake\MediaWiki\Component\DataStore\Record;

/**
 * A trimmer that combines permission filtering with pagination.
 *
 * Instead of checking permissions on ALL records and then trimming,
 * this iterates through the sorted dataset and collects only permitted
 * records until the page is filled.
 */
class PermissionTrimmer implements ITrimmer {
	/** @var int */
	private $limit;

	/** @var int */
	private $offset;

	/** @var PermissionManager */
	private $permissionManager;

	/**
	 * @param int $limit
	 * @param int $offset
	 * @param PermissionManager $permissionManager
	 */
	public function __construct( int $limit, int $offset, PermissionManager $permissionManager ) {
		$this->limit = $limit;
		$this->offset = $offset;
		$this->permissionManager = $permissionManager;
	}

	/**
	 * @param Record[] $dataSets
	 * @return Record[]
	 */
	public function trim( $dataSets ) {
		$user = RequestContext::getMain()->getUser();
		$result = [];
		$permittedCount = 0;

		foreach ( $dataSets as $record ) {
			if ( !$this->canRead( $record, $user ) ) {
				continue;
			}
			$permittedCount++;
			if ( $permittedCount <= $this->offset ) {
				continue;
			}
			$result[] = $record;
			if ( $this->limit !== ReaderParams::LIMIT_INFINITE && count( $result ) >= $this->limit ) {
				break;
			}
		}

		return $result;
	}

	/**
	 * @param Record $record
	 * @param \MediaWiki\User\User $user
	 * @return bool
	 */
	private function canRead( Record $record, $user ): bool {
		$ns = $record->get( TitleRecord::PAGE_NAMESPACE );
		$dbKey = $record->get( TitleRecord::PAGE_DBKEY );
		if ( $ns === null || $dbKey === null ) {
			return true;
		}
		$title = Title::makeTitle( $ns, $dbKey );
		if ( !$title ) {
			return true;
		}
		return $this->permissionManager->userCan( 'read', $user, $title );
	}
}
