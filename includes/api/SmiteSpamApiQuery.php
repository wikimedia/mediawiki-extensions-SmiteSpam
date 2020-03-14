<?php

use MediaWiki\MediaWikiServices;

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

		$ss = new SmiteSpamAnalyzer( false );
		$spamPages = $ss->run( $offset, $limit );

		$pages = [];
		$users = [];

		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
		foreach ( $spamPages as $page ) {
			$title = $page->getTitle();

			$titleLink = $linkRenderer->makeLink(
				$title,
				null,
				[ 'target' => '_blank' ]
			);

			$oldestRevision = $page->getOldestRevision();
			$timestamp = '';

			if ( $oldestRevision ) {
				$timestamp = $this->getLanguage()->userTimeAndDate(
					$oldestRevision->getTimestamp(),
					$this->getUser()
				);
				$creator = $oldestRevision->getUserText( Revision::RAW );
				if ( $creator ) {
					if ( !isset( $users[$creator] ) ) {
						$blocked = false;
						if ( Block::newFromTarget( $creator, $creator ) ) {
							$blocked = true;
						}
						$users[$creator] = [
							'link' => $linkRenderer->makeLink(
								SpecialPage::getTitleFor( 'Contributions', $creator ),
								$creator,
								[ 'target' => '_blank' ]
							),
							'blocked' => (int)$blocked,
						];
					}
				} else {
					$creator = '-';
				}
			} else {
				$creator = '-';
			}

			if ( $page->spamProbability <= 0.5 ) {
				$spamProbabilityLevel = 0;
			} elseif ( $page->spamProbability <= 1 ) {
				$spamProbabilityLevel = 1;
			} elseif ( $page->spamProbability <= 2 ) {
				$spamProbabilityLevel = 2;
			} else {
				$spamProbabilityLevel = 3;
			}

			$previewText = Sanitizer::escapeHtmlAllowEntities(
				mb_substr( $page->getMetadata( 'content' ), 0, 150 )
			);

			if ( strlen( $page->getMetadata( 'content' ) ) > 150 ) {
				$previewText .= '...';
			}

			$pages[] = [
				'id' => $page->getID(),
				'link' => $titleLink,
				'creator' => $creator,
				'spam-probability-value' => $page->spamProbability,
				'spam-probability-level' => $spamProbabilityLevel,
				'preview' => $previewText,
				'timestamp' => $timestamp
			];
		}

		$result = $this->getResult();
		$result->addValue(
			null,
			$this->getModuleName(),
			[
				'pages' => $pages,
				'users' => $users,
			] );
		return true;
	}

	/** @inheritDoc */
	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), [
			'offset' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true
			],
			'limit' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false
			]
		] );
	}

	/** @inheritDoc */
	public function getExamples() {
		return [
			'api.php?action=smitespamanalyze&offset=5&limit=10'
			=> 'Analyze pages from 5 to 10'
		];
	}
}
