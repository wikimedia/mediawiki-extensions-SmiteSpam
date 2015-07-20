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
$wgSpecialPages['SmiteSpamTrustedUsers'] = 'SpecialSmiteSpamTrustedUsers';

$wgAvailableRights[] = 'smitespam';
$wgGroupPermissions['sysop']['smitespam'] = true;

$wgAPIModules['smitespamanalyze'] = 'SmiteSpamApiQuery';
$wgAPIModules['smitespamtrustuser'] = 'SmiteSpamApiTrustUser';

$wgHooks['LoadExtensionSchemaUpdates'][] = 'SmiteSpamHooks::createTables';

$wgResourceModules['ext.SmiteSpam.retriever'] = array(
	'scripts' => 'js/ext.smitespam.js',
	'localBasePath' => "$ssRoot/static",
	'remoteExtPath' => 'SmiteSpam/static',
	'dependencies' => array(
		'mediawiki.jqueryMsg',
	),
	'messages' => array(
		'smitespam-page',
		'smitespam-probability',
		'smitespam-created-by',
		'smitespam-preview-text',
		'smitespam-delete',
		'table_pager_prev',
		'table_pager_next',
		'smitespam-loading',
		'smitespam-delete-page-success-msg',
		'smitespam-delete-page-failure-msg',
		'smitespam-deleted-reason',
		'powersearch-toggleall',
		'powersearch-togglenone',
		'smitespam-select',
	),
);

// Config options

// List of enabled checkers and respective weights
$wgSmiteSpamCheckers = array(
	'ExternalLinks' => 1,
	'RepeatedExternalLinks' => 1,
	'Wikitext' => 1,
);

// Threshold (tolerance)
// Pages analyzed as having a spam "probability" higher than this will be shown on Special Page
$wgSmiteSpamThreshold = 0.7;

// Ignore pages smaller than 500 characters?
$wgSmiteSpamIgnoreSmallPages = true;

// Should SmiteSpam ignore all pages that don't have any external links
// outside of template calls?
$wgSmiteSpamIgnorePagesWithNoExternalLinks = true;

// Number of pages to analyze in one AJAX request
$wgQueryPageSize = 500;

// Number of pages to display in one paginated page
$wgDisplayPageSize = 250;
