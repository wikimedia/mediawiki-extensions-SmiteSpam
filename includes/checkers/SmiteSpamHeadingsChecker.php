<?php
/**
 * Evaluates a SmiteSpamWikiPage based on the number of wikitext headings.
 */
class SmiteSpamHeadingsChecker {
	/**
	 * @param  SmiteSpamWikiPage $page
	 * @return float
	 */
	public function getValue( SmiteSpamWikiPage $page ) {
		$headings = $page->getMetadata( 'headings' );

		$numHeadings = count( $headings );

		switch ( $numHeadings ) {
			case 0:
				return 3;
			case 1:
				return 2;
			case 2:
				return 1;
			case 3:
				return 0.8;
			case 4:
				return 0.6;
			case 5:
				return 0.5;
			default:
				return 0;
		}
	}
}
