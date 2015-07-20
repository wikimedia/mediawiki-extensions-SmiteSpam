<?php

class SmiteSpamHooks {
	// Schema updates for update.php
	public static function createTables( DatabaseUpdater $updater ) {
		$updater->addExtensionTable( 'smitespam_trusted_user',
			__DIR__ . '/smitespam.sql' );
		return true;
	}
}
