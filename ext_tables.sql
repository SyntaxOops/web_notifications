CREATE TABLE tx_webnotifications_domain_model_device (
    identifier varchar(64) DEFAULT '' NOT NULL,
    endpoint text NOT NULL,
    public_key text NOT NULL,
    auth_token text NOT NULL,
    content_encoding varchar(32) DEFAULT 'aes128gcm' NOT NULL
);

CREATE TABLE tx_webnotifications_domain_model_notifications (
    bodytext mediumtext,
    status smallint unsigned DEFAULT '0' NOT NULL
);
