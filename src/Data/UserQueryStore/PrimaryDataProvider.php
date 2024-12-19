<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Data\UserQueryStore;

use GlobalVarConfig;
use MediaWiki\Message\Message;
use MWStake\MediaWiki\Component\DataStore\Filter;
use MWStake\MediaWiki\Component\DataStore\PrimaryDatabaseDataProvider;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use MWStake\MediaWiki\Component\DataStore\Schema;
use Wikimedia\Rdbms\IDatabase;

class PrimaryDataProvider extends PrimaryDatabaseDataProvider {
	/** @var array */
	private $groups = [];
	/** @var array */
	private $blocks = [];

	/** @var GlobalVarConfig */
	protected $mwsgConfig;

	/**
	 * @param IDatabase $db
	 * @param Schema $schema
	 * @param GlobalVarConfig $mwsgConfig
	 */
	public function __construct( IDatabase $db, Schema $schema, GlobalVarConfig $mwsgConfig ) {
		parent::__construct( $db, $schema );
		$this->mwsgConfig = $mwsgConfig;
	}

	/**
	 * @inheritDoc
	 */
	public function makeData( $params ) {
		$this->getSupportingData( $params );
		return parent::makeData( $params );
	}

	/**
	 * Get supporting data for the user records
	 * @return void
	 */
	private function getSupportingData() {
		$this->groups = $this->getGroups();
		$this->blocks = $this->getBlocks();
	}

	/**
	 * @return array
	 */
	public function getGroupBuckets(): array {
		$groupBlacklist = $this->mwsgConfig->get( 'CommonWebAPIsComponentUserStoreExcludeGroups' );
		$res = $this->db->select(
			'user_groups',
			[ 'DISTINCT ug_group' ],
			[
				'ug_group NOT IN (' . $this->db->makeList( $groupBlacklist ) . ')',
			],
			__METHOD__,
			[ 'GROUP BY' => 'ug_group' ]
		);
		$groups = [];
		foreach ( $res as $row ) {
			$msg = Message::newFromKey( 'group-' . $row->ug_group );
			$groups[$row->ug_group] = $msg->exists() ? $msg->text() : $row->ug_group;
		}

		return $groups;
	}

	/**
	 * @return array
	 */
	private function getGroups() {
		$groupBlacklist = $this->mwsgConfig->get( 'CommonWebAPIsComponentUserStoreExcludeGroups' );
		$res = $this->db->select(
			'user_groups',
			[ 'ug_user', 'ug_group' ],
			[
				'ug_group NOT IN (' . $this->db->makeList( $groupBlacklist ) . ')',
			],
			__METHOD__
		);
		$groups = [];
		foreach ( $res as $row ) {
			$groups[$row->ug_user][] = $row->ug_group;
		}
		return $groups;
	}

	/**
	 * @return array
	 */
	private function getBlocks() {
		$res = $this->db->select(
			[ 'b' => 'block', 'bt' => 'block_target' ],
			[ 'bt_user' ],
			[],
			__METHOD__,
			[],
			[ 'b' => [ 'INNER JOIN', 'bt.bt_id = b.bl_target' ] ]
		);
		$blocks = [];
		foreach ( $res as $row ) {
			$blocks[] = (int)$row->bt_user;
		}

		return $blocks;
	}

	/**
	 * @param ReaderParams $params
	 *
	 * @return array
	 */
	protected function makePreFilterConds( ReaderParams $params ) {
		$filters = $params->getFilter();
		$conds = parent::makePreFilterConds( $params );
		$query = $params->getQuery();
		foreach ( $filters as $filter ) {
			if ( $filter->getField() === 'user_name' || $filter->getField() == 'user_real_name' ) {
				// Incompatible with query, takes priority
				$query = '';
				$this->addIndexedNameFilter( $filter, $conds );
				$filter->setApplied( true );
			}
		}
		if ( $query !== '' ) {
			$query = mb_strtolower( $query );
			$conds[] = $this->db->makeList(
				[
					'mui_user_name ' . $this->db->buildLike(
						$this->db->anyString(), $query, $this->db->anyString()
					),
					'mui_user_real_name ' . $this->db->buildLike(
						$this->db->anyString(), $query, $this->db->anyString()
					)
				],
				LIST_OR
			);
		}
		// General system user identifier
		$conds[] = 'user_token NOT ' . $this->db->buildLike(
			$this->db->anyString(), 'INVALID', $this->db->anyString()
		);

		$userBlacklist = $this->mwsgConfig->get( 'CommonWebAPIsComponentUserStoreExcludeUsers' );
		if ( is_array( $userBlacklist ) && count( $userBlacklist ) > 0 ) {
			$conds[] = 'user_name NOT IN (' . $this->db->makeList( $userBlacklist ) . ')';
		}

		return $conds;
	}

	/**
	 * @param Filter $filter
	 * @param array &$conds
	 * @return void
	 */
	private function addIndexedNameFilter( Filter $filter, array &$conds ) {
		$fieldMapping = [
			'user_name' => 'mui_user_name',
			'user_real_name' => 'mui_user_real_name'
		];
		$filterValue = str_replace( '_', ' ', $filter->getValue() );
		if (
			$filter->getComparison() === Filter::COMPARISON_CONTAINS ||
			$filter->getComparison() === Filter::COMPARISON_LIKE
		) {
			$field = $fieldMapping[$filter->getField()];
			$conds[] = "$field " . $this->db->buildLike(
				$this->db->anyString(), $filterValue, $this->db->anyString()
			);
		} elseif ( $filter->getComparison() === Filter::COMPARISON_EQUALS ) {
			$conds[$filter->getField()] = $filterValue;
		} elseif ( $filter->getComparison() === Filter::COMPARISON_NOT_EQUALS ) {
			$conds[$filter->getField()] = $filterValue;
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function skipPreFilter( Filter $filter ) {
		return $filter->getField() === 'user_name' ||
			$filter->getField() === 'user_real_name' ||
			$filter->getField() === 'enabled';
	}

	/**
	 * @param int $userId
	 *
	 * @return bool
	 */
	protected function isUserBlocked( int $userId ) {
		return in_array( (int)$userId, $this->blocks );
	}

	/**
	 * @param \stdClass $row
	 *
	 * @return void
	 */
	protected function appendRowToData( \stdClass $row ) {
		$resultRow = [
			'user_id' => (int)$row->user_id,
			'user_name' => $row->user_name,
			'user_real_name' => $row->user_real_name,
			'user_registration' => $row->user_registration,
			'user_editcount' => (int)$row->user_editcount,
			'user_email' => $row->user_email,
			'groups' => isset( $this->groups[$row->user_id] ) ? $this->groups[$row->user_id] : [],
			'groups_raw' => isset( $this->groups[$row->user_id] ) ? $this->groups[$row->user_id] : [],
			'enabled' => !$this->isUserBlocked( (int)$row->user_id ),
			// legacy fields
			'display_name' => $row->user_real_name == null ? $row->user_name : $row->user_real_name,
		];
		$this->data[] = new UserRecord( (object)$resultRow );
	}

	/**
	 * @inheritDoc
	 */
	protected function getJoinConds( ReaderParams $params ) {
		return [
			'mws_user_index' => [
				'INNER JOIN', [ 'user_id = mui_user_id' ]
			]
		];
	}

	/**
	 * @return string[]
	 */
	protected function getTableNames() {
		return [ 'user', 'mws_user_index' ];
	}
}
