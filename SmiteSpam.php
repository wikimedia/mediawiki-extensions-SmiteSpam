<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not a valid entry point.' );
}

$wgExtensionCredits['antispam'][] = array(
	'path' => __FILE__,
	'name' => 'SmiteSpam',
	'namemsg' => 'smitespam',
	'author' => 'Vivek Ghaisas',
	'descriptionmsg'  => 'smitespam-desc',
	'license-name' => 'GPL-2.0'
);

$ssRoot = __DIR__;

require_once "$ssRoot/autoload.php";

$wgMessagesDirs['SmiteSpam'] = "$ssRoot/i18n";
$wgExtensionMessagesFiles['SmiteSpamAlias'] = "$ssRoot/SmiteSpam.alias.php";
$wgSpecialPages['SmiteSpam'] = 'SpecialSmiteSpam';

$wgAvailableRights[] = 'smitespam';
$wgGroupPermissions['sysop']['smitespam'] = true;

// Config options

// Maximum number of pages SmiteSpam should process in one run
// Necessary to prevent timeouts, etc.
$wgSmiteSpamQueryLimit = 1000;

// Should SmiteSpam select random pages?
// Should be set to false if $wgSmiteSpamQueryLimit is higher than number of pages
$wgSmiteSpamRandomize = true;

// List of enabled checkers and respective weights
$wgSmiteSpamCheckers = array(
	'ExternalLinks' => 1,
	'RepeatedExternalLinks' => 1,
	'InternalLinks' => 1,
	'Headings' => 1,
	'Templates' => 1,
);

// Threshold (tolerance)
// Pages analyzed as having a spam "probability" higher than this will be shown on Special Page
$wgSmiteSpamThreshold = 0.7;
