<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Data\TitleQueryStore;

use MWStake\MediaWiki\Component\DataStore\FieldType;
use MWStake\MediaWiki\Component\DataStore\Schema;

class TitleSchema extends Schema {
	public function __construct() {
		parent::__construct( [
			TitleRecord::PAGE_ID => [
				self::FILTERABLE => false,
				self::SORTABLE => false,
				self::TYPE => FieldType::INT
			],
			TitleRecord::PAGE_TITLE => [
				self::FILTERABLE => true,
				self::SORTABLE => true,
				self::TYPE => FieldType::STRING
			],
			TitleRecord::PAGE_PREFIXED => [
				self::FILTERABLE => false,
				self::SORTABLE => false,
				self::TYPE => FieldType::STRING
			],
			TitleRecord::PAGE_DBKEY => [
				self::FILTERABLE => true,
				self::SORTABLE => true,
				self::TYPE => FieldType::STRING
			],
			TitleRecord::PAGE_NAMESPACE => [
				self::FILTERABLE => true,
				self::SORTABLE => true,
				self::TYPE => FieldType::INT
			],
			TitleRecord::PAGE_NAMESPACE_TEXT => [
				self::FILTERABLE => true,
				self::SORTABLE => true ,
				self::TYPE => FieldType::STRING
			],
			TitleRecord::PAGE_EXISTS => [
				self::FILTERABLE => false,
				self::SORTABLE => false ,
				self::TYPE => FieldType::BOOLEAN
			],
			TitleRecord::PAGE_CONTENT_MODEL => [
				self::FILTERABLE => true,
				self::SORTABLE => true ,
				self::TYPE => FieldType::STRING
			],
			TitleRecord::PAGE_URL => [
				self::FILTERABLE => false,
				self::SORTABLE => false ,
				self::TYPE => FieldType::STRING
			],
			TitleRecord::IS_CONTENT_PAGE => [
				self::FILTERABLE => true,
				self::SORTABLE => true ,
				self::TYPE => FieldType::BOOLEAN
			]
		] );
	}
}
