<?php
/**
 * Evaluates an SmiteSpamWikiPage based on the number of internal links.
 */
class SmiteSpamInternalLinksChecker {
	/**
	 * @param  SmiteSpamWikiPage $page
	 * @return float
	 */
	public function getValue( SmiteSpamWikiPage $page ) {
		$internalLinks = $page->getMetadata( 'internalLinks' );

		$numLinks = count( $internalLinks );

		switch ( $numLinks ) {
			case 0:
				return 3;
			case 1:
				return 2.5;
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
