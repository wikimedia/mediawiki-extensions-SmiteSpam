<?php
/**
 * The class performing the evaluation for all wiki pages. The run() method is
 * called to check and return a list of probable spam pages.
 */
class SmiteSpamAnalyzer {
	/**
	 * @var array
	 */
	protected $config;

	public function __construct() {
		global $wgSmiteSpamQueryLimit, $wgSmiteSpamRandomize;
		global $wgSmiteSpamCheckers, $wgSmiteSpamThreshold;
		global $wgSmiteSpamIgnorePagesWithNoExternalLinks;
		$this->config = array(
			'checkers' => $wgSmiteSpamCheckers,
			'threshold' => $wgSmiteSpamThreshold,
			'queryLimit' => $wgSmiteSpamQueryLimit,
			'randomize' => $wgSmiteSpamRandomize,
			'ignorePagesWithNoExternalLinks' => $wgSmiteSpamIgnorePagesWithNoExternalLinks,
		);
	}
	/**
	 * This function retrieves a list of all the pages in the wiki and runs
	 * checks on each of them. Pages whose value exceeds the threshold defined in
	 * the configuration are returned as an array.
	 * @todo Perform DB queries in batches, else prone to timeouts
	 *
	 * @return array
	 */
	public function run() {
		$dbr = wfGetDB( DB_SLAVE );
		$limit = $this->config['queryLimit'];
		// add a tiny bias towards the centre

		if ( $this->config['randomize'] ) {
			$random = ( wfRandom() + wfRandom() )/2;
			$result = $dbr->select(
				array( 'page' ),
				'page_id',
				array(
					'page_is_redirect = 0',
					"page_random >= $random",
				),
				__METHOD__,
				array(
					'ORDER BY' => 'page_random',
					"LIMIT" => $limit,
				)
			);
		} else {
			$result = $dbr->select(
				array( 'page' ),
				'page_id',
				array(
					'page_is_redirect = 0',
				),
				__METHOD__,
				array(
					"LIMIT" => $limit,
				)
			);
		}

		$checkers = $this->config['checkers'];
		$totalWeight = array_sum( $checkers );
		$spamPages = array();

		foreach ( $result as $row ) {
			$page = new SmiteSpamWikiPage( $row->page_id );

			if ( !$page || !$page->exists() ) {
				continue;
			}

			if ( $page->getTitle()->getContentModel() !== CONTENT_MODEL_WIKITEXT
				|| !$page->getContent()
				|| !method_exists( $page->getContent(), 'getNativeData' ) ) {
				// Page does not contain regular wikitext
				// or cannot get content
				continue;
			}

			if ( $this->config['ignorePagesWithNoExternalLinks']
			    && count( $page->getMetadata( 'externalLinks' ) ) == 0 ) {
			    continue;
			}

			if ( count( $page->getMetadata( 'externalLinks' ) ) == 0
				&& strlen( $page->getMetadata( 'content' ) ) < 500 ) {
				// Ignore small pages with no external links
				continue;
			}

			$value = 0;
			$checkersUsed = 0;
			foreach ( $checkers as $checker => $weight ) {
				$checker = 'SmiteSpam' . $checker . 'Checker';
				$check = new $checker;
				$checkvalue = $check->getValue( $page );
				if ( $checkvalue !== false ) {
					$value += $checkvalue * $weight;
					$checkersUsed++;
				}
			}

			$page->spamProbability = $value/$checkersUsed;
			if ( $page->spamProbability >= $this->config['threshold'] ) {
				$spamPages[] = $page;
			}
		}
		/**
		 * @todo check compatibility of inline function
		 */
		usort(
			$spamPages,
			function( $pageA, $pageB ) {
				return $pageA->spamProbability < $pageB->spamProbability;
			}
		);
		// Return only top 20% of $wgSmiteSpamQueryLimit pages
		return array_slice( $spamPages, 0, floor( $this->config['queryLimit']/5 ) );
	}
}
