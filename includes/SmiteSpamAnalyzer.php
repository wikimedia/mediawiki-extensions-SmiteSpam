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

	public function __construct( $sort = true ) {
		global $wgSmiteSpamCheckers, $wgSmiteSpamThreshold;
		global $wgSmiteSpamIgnorePagesWithNoExternalLinks;
		global $wgSmiteSpamIgnoreSmallPages;
		$this->config = array(
			'checkers' => $wgSmiteSpamCheckers,
			'threshold' => $wgSmiteSpamThreshold,
			'ignorePagesWithNoExternalLinks' => $wgSmiteSpamIgnorePagesWithNoExternalLinks,
			'ignoreSmallPages' => $wgSmiteSpamIgnoreSmallPages,
			'sort' => $sort,
		);
	}
	/**
	 * Retrieves a list of pages in the wiki based on the offset and limit
	 * and runs checks on each of them. Pages whose evaluated value exceeds the
	 * threshold defined in the configuration are returned as an array.
	 * @todo Perform DB queries in batches, else prone to timeouts
	 *
	 * @return array
	 */
	public function run( $offset = 0, $limit = 500 ) {
		$dbr = wfGetDB( DB_SLAVE );

		$usersResult = $dbr->select(
			array( 'smitespam_trusted_user' ),
			'trusted_user_id'
		);

		$trustedUsers = array();

		foreach ( $usersResult as $row ) {
			$trustedUsers[] = $row->trusted_user_id;
		}

		$result = $dbr->select(
			array( 'page' ),
			'page_id',
			array(
				'page_is_redirect = 0',
			),
			__METHOD__,
			array(
				"ORDER BY" => "page_id ASC",
				"OFFSET" => $offset,
				"LIMIT" => $limit,
			)
		);

		$checkers = $this->config['checkers'];

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

			$creatorID = $page->getOldestRevision()->getUser( Revision::RAW );

			if ( in_array( $creatorID, $trustedUsers ) ) {
				continue;
			}

			if ( $this->config['ignorePagesWithNoExternalLinks']
			    && count( $page->getMetadata( 'externalLinks' ) ) == 0 ) {
			    continue;
			}

			if ( $this->config['ignoreSmallPages']
				&& count( $page->getMetadata( 'externalLinks' ) ) == 0
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
		if ( $this->config['sort'] ) {
			usort(
				$spamPages,
				function( $pageA, $pageB ) {
					return $pageA->spamProbability < $pageB->spamProbability;
				}
			);
		}
		return $spamPages;
	}
}
