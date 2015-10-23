<?php
/**
 * Evaluates a SmiteSpamWikiPage based on the ratio of external links to words in the
 * page.
 */
class SmiteSpamExternalLinksChecker {
	/**
	 * @param  SmiteSpamWikiPage $page
	 * @return float
	 */
	public function getValue( SmiteSpamWikiPage $page ) {
		$numLinks = count( $page->getMetadata( 'externalLinks' ) );
		$numWords = $page->getMetadata( 'numWords' );

		if ( $numWords == 0 ) {
			return false;
		}
		$ratio = $numLinks/$numWords;

		if ( $ratio < 0.02 ) {
			return 0;
		} elseif ( $ratio < 0.03 ) {
			return 0.5 * ( ( $ratio * 100 ) - 2 );
		} elseif ( $ratio < 0.04 ) {
			return 0.5 + 0.5 * ( $ratio * 100 - 3 );
		} elseif ( $ratio < 0.1 ) {
			return 1 + 2 * ( $ratio * 100 - 4 )/6;
		} else {
			return 3;
		}
	}
}
