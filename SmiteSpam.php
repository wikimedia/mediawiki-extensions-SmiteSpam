<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not a valid entry point.' );
}

$ssRoot = __DIR__;

$wgAutoloadClasses['SpecialSmiteSpam'] = "$ssRoot/SpecialSmiteSpam.php";
$wgMessagesDirs['SmiteSpam'] = "$ssRoot/i18n";
$wgExtensionMessagesFiles['SmiteSpamAlias'] = "$ssRoot/SmiteSpam.alias.php";
$wgSpecialPages['SmiteSpam'] = 'SpecialSmiteSpam';
