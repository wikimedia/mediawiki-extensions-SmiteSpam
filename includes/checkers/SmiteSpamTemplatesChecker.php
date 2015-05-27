<?php
/**
 * Evaluates an SmiteSpamWikiPage based on the number of wikitext headings.
 */
class SmiteSpamTemplatesChecker {
	/**
	 * @param  SmiteSpamWikiPage $page
	 * @return float
	 */
	public function getValue( SmiteSpamWikiPage $page ) {
		$templates = $page->getMetadata( 'templates' );

		$numTemplates = count( $templates );

		switch ( $numTemplates ) {
			case 0:
				return 2;
			case 1:
				return 0.8;
			case 2:
				return 0.6;
			default:
				return 0;
		}
	}
}
