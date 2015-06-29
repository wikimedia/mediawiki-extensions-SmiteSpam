<?php
/**
 * Evaluates a SmiteSpamWikiPage based on wikitext: internal links, headings, templates
 */
class SmiteSpamWikitextChecker {
	/**
	 * @param  SmiteSpamWikiPage $page
	 * @return float
	 */
	public function getValue( SmiteSpamWikiPage $page ) {
		$numHeadings = count( $page->getMetadata( 'headings' ) );
		$numLinks = count( $page->getMetadata( 'internalLinks' ) );
		$numTemplates = count( $page->getMetadata( 'templates' ) );

		$wikiStuffCount = $numHeadings + $numLinks + $numTemplates;
		switch ( $wikiStuffCount ) {
			case 0:
				return 3;
			case 1:
				return 2.5;
			case 2:
				return 2;
			case 3:
				return 1.5;
			case 4:
				return 1;
			default:
				return 0;
		}
	}
}
