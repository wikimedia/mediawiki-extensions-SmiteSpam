<?php

class SmiteSpamHooks {
	/**
	 * Schema updates for update.php
	 * @param DatabaseUpdater $updater
	 * @return true
	 */
	public static function createTables( DatabaseUpdater $updater ) {
		$updater->addExtensionTable( 'smitespam_trusted_user',
			__DIR__ . '/../sql/smitespam.sql' );
		return true;
	}

	/**
	 * @param ALTree &$adminLinksTree
	 */
	public static function addToAdminLinks( ALTree &$adminLinksTree ) {
		$spamToolsMessage = wfMessage( 'specialpages-group-spam' )->text();

		$spamToolsSection = $adminLinksTree->getSection( $spamToolsMessage );
		if ( $spamToolsSection === null ) {
			$spamToolsSection = new ALSection( $spamToolsMessage );
			$adminLinksTree->addSection( $spamToolsSection );
		}

		$extensionsRow = $spamToolsSection->getRow( 'extensions' );
		if ( $extensionsRow === null ) {
			$extensionsRow = new ALRow( 'extensions' );
			$spamToolsSection->addRow( $extensionsRow );
		}

		$extensionsRow->addItem( ALItem::newFromSpecialPage( 'SmiteSpam' ) );
		$extensionsRow->addItem( ALItem::newFromSpecialPage( 'SmiteSpamTrustedUsers' ) );
	}
}
