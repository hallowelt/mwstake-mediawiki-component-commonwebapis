-- This file is automatically generated using maintenance/generateSchemaChangeSql.php.
-- Source: components/mwstake-mediawiki-component-commonwebapis/sql/mws_category_index_patch_page_title.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
DROP INDEX `primary` ON /*_*/mws_category_index;
ALTER TABLE /*_*/mws_category_index
  ADD mci_page_title VARBINARY(255) DEFAULT '' NOT NULL;
