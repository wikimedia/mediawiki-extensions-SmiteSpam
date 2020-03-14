<?php
// phpcs:disable Generic.Files.LineLength.TooLong
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'SmiteSpam' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['SmiteSpam'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['SmiteSpamAlias'] = __DIR__ . '/SmiteSpam.alias.php';
	wfWarn(
		'Deprecated PHP entry point used for the SmiteSpam extension. ' .
		'Please use wfLoadExtension() instead, ' .
		'see https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the SmiteSpam extension requires MediaWiki 1.29+' );
}
