<?php
/**
 * Class to represent a wiki page that can be evaluated by checkers.
 */
class SmiteSpamWikiPage extends WikiPage {
	/**
	 * Array of metadata that can be shared by checkers to evaluate the page
	 * @var array
	 */
	private $metadata;

	/**
	 * The Revision object of the oldest revision
	 * @var Revision|null
	 */
	private $oldestRevision;

	/**
	 * A probability-like value representing how likely this page is a spam page.
	 * @var float
	 */
	public $spamProbability;

	/**
	 * @param int $pageID
	 */
	public function __construct( $pageID ) {
		$title = Title::newFromID( $pageID );
		if ( !$title ) {
			return null;
		}
		parent::__construct( $title );
		$this->metadata = array();
	}

	public function getOldestRevision() {
		if ( !$this->oldestRevision ) {
			$this->oldestRevision = parent::getOldestRevision();
		}
		return $this->oldestRevision;
	}

	/**
	 * Return particular field of metadata
	 * @param  string $key
	 * @throws MWException If an invalid key is passed
	 * @return mixed
	 */
	public function getMetadata( $key ) {
		if ( isset( $this->metadata[$key] ) ) {
			return $this->metadata[$key];
		}

		switch ( $key ) {
			case 'content':
				$this->metadata['content'] = $this->getContent()->getNativeData();
				break;

			case 'numWords':
				$content = $this->getMetadata( 'content' );
				$this->metadata['numWords'] = str_word_count( $content );
				break;

			case 'externalLinks':
				$content = $this->getMetadata( 'content' );
				$templates = $this->getMetadata( 'templates' );
				// Don't want to consider links within templates
				foreach ( $templates as $template ) {
					$content = str_replace( "{{$template}}", '', $content );
				}
				$matches = array();
				preg_match_all( '/(' . wfUrlProtocols() . ')([^\s\]\"]*)/', $content, $matches );
				$this->metadata['externalLinks'] = $matches[0];
				break;

			case 'internalLinks':
				$content = $this->getMetadata( 'content' );
				$matches = array();
				preg_match_all( '/\[\[(.*?)\]\]/', $content, $matches );
				$this->metadata['internalLinks'] = $matches[1];
				break;

			case 'isNew':
				$this->metadata['isNew'] = $this->getTitle()->isNewPage();
				break;

			case 'headings':
				$content = $this->getContent()->getNativeData();
				$matches = array();
				preg_match_all( '/^==?=?\s*(.*?)\s*==?=?\s*$/m', $content, $matches );
				$this->metadata['headings'] = $matches[1];
				break;

			case 'templates':
				$content = $this->getContent()->getNativeData();
				$matches = array();
				preg_match_all( '/{{(.*?)}}/s', $content, $matches );
				$this->metadata['templates'] = $matches[1];
				break;

			default:
				throw new MWException( "Cannot fetch metadata '$key'." );
				break;
		}

		return $this->metadata[$key];
	}
}
