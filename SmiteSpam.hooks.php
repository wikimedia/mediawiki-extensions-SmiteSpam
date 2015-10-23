<?php

class SmiteSpamHooks {
	// Schema updates for update.php
	public static function createTables( DatabaseUpdater $updater ) {
		$updater->addExtensionTable( 'smitespam_trusted_user',
			__DIR__ . '/smitespam.sql' );
		return true;
	}

	public static function addToAdminLinks( ALTree &$adminLinksTree ) {
		$spamToolsMessage = wfMessage( 'specialpages-group-spam' )->text();

		$spamToolsSection = $adminLinksTree->getSection( $spamToolsMessage );
		if ( is_null( $spamToolsSection ) ) {
			$spamToolsSection = new ALSection( $spamToolsMessage );
			$adminLinksTree->addSection( $spamToolsSection );
		}

		$extensionsRow = $spamToolsSection->getRow( 'extensions' );
		if ( is_null( $extensionsRow ) ) {
			$extensionsRow = new ALRow( 'extensions' );
			$spamToolsSection->addRow( $extensionsRow );
		}

		$extensionsRow->addItem( ALItem::newFromSpecialPage( 'SmiteSpam' ) );
		$extensionsRow->addItem( ALItem::newFromSpecialPage( 'SmiteSpamTrustedUsers' ) );
	}
}
