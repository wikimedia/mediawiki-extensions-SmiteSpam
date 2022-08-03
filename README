SmiteSpam extension

        Version 0.4
        Vivek Ghaisas and Yaron Koren

This is free software licensed under the GNU General Public License. Please
see http://www.gnu.org/copyleft/gpl.html for further details, including the
full text and terms of the license.

== Overview ==

SmiteSpam is an extension to MediaWiki that defines a special page,
Special:SmiteSpam, which analyzes the content on the wiki to try to identify
pages containing spam, and provides an interface to let administrators
delete any such pages, and block the user accounts or IP addresses that
created them.

For more information, see the extension homepage at:
https://www.mediawiki.org/wiki/Extension:SmiteSpam

== Requirements ==

This version of the SmiteSpam extension requires MediaWiki 1.35 or higher.

== Installation ==

To install the extension, place the entire 'SmiteSpam' directory
within your MediaWiki 'extensions' directory, then add the following
line to your 'LocalSettings.php' file:
```
wfLoadExtension( 'SmiteSpam' );
```

By default, access to Special:SmiteSpam is only given to those in the 'sysop'
group. To allow others, for example bureaucrats, to access it, add the
following to LocalSettings.php:
```
$wgGroupPermissions['bureaucrat']['smitespam'] = true;
```

SmiteSpam assigns a probability, from 0 to 1, that any specific page holds
spam. By default, it only shows pages with a probability rating of 0.7 or
higher of being spam. To change this value, you can add a line like the
following:
```
$wgSmiteSpamThreshold = 0.5;
```

By default, SmiteSpam will ignore pages with fewer than 500 characters. To
analyze such pages as well, add the following to LocalSettings.php:
```
$wgSmiteSpamIgnoreSmallPages = false;
```

By default, pages containing no external links will also be ignored. To
include them, add the following:
```
$wgSmiteSpamIgnorePagesWithNoExternalLinks = false;
```

See the extension homepage for several other allowed settings.

== Authors ==
This extension was created by Vivek Ghaisas as part of the 2015 Google Summer of Code. It
is maintained by Vivek Ghaisas and Yaron Koren.

== Contact ==

Most comments, questions, suggestions and bug reports should be sent to
the main MediaWiki mailing list:

     https://lists.wikimedia.org/mailman/listinfo/mediawiki-l
