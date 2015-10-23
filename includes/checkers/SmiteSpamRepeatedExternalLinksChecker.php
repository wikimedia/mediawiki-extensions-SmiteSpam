<?php
/**
 * Evaluates a SmiteSpamWikiPage based on the number of repeated external links
 * in the page.
 */
class SmiteSpamRepeatedExternalLinksChecker {
	/**
	 * @param  SmiteSpamWikiPage $page
	 * @return float
	 */
	public function getValue( SmiteSpamWikiPage $page ) {
		$links = $page->getMetadata( 'externalLinks' );
		$linkFrequencies = array_count_values( $links );

		// Remove links occurring only once
		$duplicateLinks = array_filter( $linkFrequencies,
			function ( $value ) {
				return $value > 1;
			}
		);

		// Subtract one from the frequency of each link to represent only number of
		// duplicates.
		$numDuplicatesArray = array_map(
			function ( $value ) {
				return $value - 1;
			},
			$duplicateLinks
		);

		$numDuplicates = array_sum( $numDuplicatesArray );

		if ( $numDuplicates == 0 ) {
			return 0;
		} elseif ( $numDuplicates == 1 ) {
			return 2;
		} else {
			return 3;
		}
	}
}
