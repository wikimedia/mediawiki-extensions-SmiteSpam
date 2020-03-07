--
-- List of trusted users for SmiteSpam to ignore
--
CREATE TABLE /*_*/smitespam_trusted_user (
	-- User ids of trusted users
	trusted_user_id int unsigned NOT NULL,

	-- Timestamp of when a user was marked as trusted
	trusted_user_timestamp binary(14) NOT NULL default '',

	-- User ID of admin who marked a user as trusted
	trusted_user_admin_id int unsigned NOT NULL
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/trusted_user_id ON /*_*/smitespam_trusted_user (trusted_user_id);
