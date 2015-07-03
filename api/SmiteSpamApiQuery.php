<?php

class SmiteSpamApiQuery extends ApiBase {
	public function execute() {
		$offset = $this->getMain()->getVal( 'offset' );
		$limit = $this->getMain()->getVal( 'limit', 500 );

		if ( !ctype_digit( $offset ) && !is_int( $offset ) ) {
			$this->dieUsage( 'Offset parameter must be integer.', 'badparams' );
		}

		if ( !ctype_digit( $limit ) && !is_int( $limit ) ) {
			$this->dieUsage( 'Limit parameter must be integer.', 'badparams' );
		}

		$ss = new SmiteSpamAnalyzer();
		$spamPages = $ss->run( $offset, $limit );

		$pages = array();
		$users = array();

		foreach ( $spamPages as $page ) {
			$title = $page->getTitle();

			$titleLink = Linker::link(
				$title,
				null,
				array( 'target' => '_blank' )
			);

			$oldestRevision = $page->getOldestRevision();

			if ( $oldestRevision ) {
				$creator = $oldestRevision->getUserText( Revision::RAW );
				if ( $creator ) {
					if ( !isset( $users[$creator] ) ) {
						$users[$creator] = array(
							'link' => Linker::link(
								SpecialPage::getTitleFor( 'Contributions', $creator ),
								Sanitizer::escapeHtmlAllowEntities( $creator ),
								array( 'target' => '_blank' )
							),
							'blocked' => (int)User::newFromId( $oldestRevision->getUser( Revision::RAW ) )->isBlocked(),
						);
					}
				} else {
					$creator = '-';
				}
			} else {
				$creator = '-';
			}

			if ( $page->spamProbability <= 0.5 ) {
				$spamProbability = wfMessage( 'smitespam-probability-low' )->text();
			} elseif ( $page->spamProbability <= 1 ) {
				$spamProbability = wfMessage( 'smitespam-probability-medium' )->text();
			} elseif ( $page->spamProbability <= 2 ) {
				$spamProbability = wfMessage( 'smitespam-probability-high' )->text();
			} else {
				$spamProbability = wfMessage( 'smitespam-probability-very-high' )->text();
			}

			$previewText = Sanitizer::escapeHtmlAllowEntities(
				substr( $page->getMetadata( 'content' ), 0, 50 )
			);

			if ( strlen( $page->getMetadata( 'content' ) ) > 50  ) {
				$previewText .= '...';
			}

			$pages[] = array(
				'id' => $page->getID(),
				'link' => $titleLink,
				'creator' => $creator,
				'spam-probability-value' => $page->spamProbability,
				'spam-probability-text' => $spamProbability,
				'preview' => $previewText
			);
		}

		$result = $this->getResult();
		$result->addValue(
			null,
			$this->getModuleName(),
			array (
				'pages' => $pages,
				'users' => $users,
			) );
		return true;
	}

	// Description
	public function getDescription() {
		return 'Get pages analyzed in the SmiteSpam way.';
	}

	// Face parameter.
	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'offset' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true
			),
			'limit' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false
			)
		) );
	}

	// Describe the parameter
	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'offset' => 'SQL OFFSET.',
			'limit' => 'SQL LIMIT.'
		) );
	}

	// Get examples
	public function getExamples() {
		return array(
			'api.php?action=smitespamanalyze&offset=5&limit=10'
			=> 'Analyze pages from 5 to 10'
		);
	}
}
