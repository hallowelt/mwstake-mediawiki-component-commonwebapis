CREATE TABLE /*$wgDBprefix*/mws_category_index (
    mci_cat_id int unsigned NOT NULL PRIMARY KEY,
	mci_title VARCHAR(255) NOT NULL,
    mci_count int unsigned NOT NULL DEFAULT 0
) /*$wgDBTableOptions*/;
