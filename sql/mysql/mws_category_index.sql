-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: components/mwstake-mediawiki-component-commonwebapis/sql/mws_category_index.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE /*_*/mws_category_index (
  mci_cat_id INT UNSIGNED NOT NULL,
  mci_title VARBINARY(255) NOT NULL,
  mci_page_title VARBINARY(255) DEFAULT '' NOT NULL,
  mci_count INT UNSIGNED DEFAULT 0 NOT NULL
) /*$wgDBTableOptions*/;
