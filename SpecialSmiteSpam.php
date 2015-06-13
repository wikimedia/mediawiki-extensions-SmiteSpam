<?php
/**
 * Main special page for the SmiteSpam extension.
 */
class SpecialSmiteSpam extends SpecialPage {

	public function __construct() {
		parent::__construct( 'SmiteSpam', 'smitespam' );
	}

	public function execute( $subPage ) {
		if ( !$this->userCanExecute( $this->getUser() ) ) {
			$this->displayRestrictionError();
			return;
		}
		$this->setHeaders();
		$out = $this->getOutput();
		$out->setPageTitle( $this->msg( 'smitespam' )->text() );
		$request = $this->getRequest();

		$ss = new SmiteSpamAnalyzer();
		if ( $request->wasPosted() ) {
			$ssDeleter = new SmiteSpamDeleter();
			$pageIDs = $request->getArray( 'delete' );
			if ( $pageIDs ) {
				$messages = $ssDeleter->deletePages( $pageIDs );
				if ( isset( $messages['error'] ) ) {
					$out->addHTML( '<div class="errorbox">' );
					foreach ( $messages['error'] as $message ) {
						$out->addHTML( '<p>' . $message . '</p>' );
					}
					$out->addHTML( '</div>' );
				}
				if ( isset( $messages['success'] ) ) {
					$out->addHTML( '<div class="successbox">' );
					foreach ( $messages['success'] as $message ) {
						$out->addHTML( '<p>' . $message . '</p>' );
					}
					$out->addHTML( '</div>' );
				}
			}
		}

		$spamPages = $ss->run();

		$out->addHTML( '<h2>' . $this->msg( 'smitespam-spam-pages-list-heading' )->text() . '</h2>' );

		$out->addHTML(
			Html::openElement( 'form', array(
					'method' => 'post'
				)
			)
		);
		$out->addHTML( Html::openElement( 'table' ) );
		$out->addHTML( '<tr><th>'
			. $this->msg( 'smitespam-page' )->text()
			. '</th><th>'
			. $this->msg( 'smitespam-probability' )->text()
			. '</th><th>'
			. $this->msg( 'smitespam-delete' )->text()
			. '</th></tr>' );
		foreach ( $spamPages as $page ) {
			$title = $page->getTitle();
			$out->addHTML( '<tr>' );
			$out->addHTML( '<td>' );
			$out->addHTML(
				Html::openElement( 'a', array(
						'href' => $title->getLocalUrl(),
						'target' => '_blank',
					)
				)
			);
			$nsText = $title->getNsText();
			if ( $nsText ) {
				$out->addHTML( Sanitizer::escapeHtmlAllowEntities( $nsText ) . ':' );
			}
			$out->addHTML( Sanitizer::escapeHtmlAllowEntities( $title->getText() ) );
			$out->addHTML( '</a>' );
			$out->addHTML( '</td>' );
			$out->addHTML( '<td>' );
			// @todo Colour code
			if ( $page->spamProbability <= 0.5 ) {
				$out->addHTML( 'Low' );
			} elseif ( $page->spamProbability <= 1 ) {
				$out->addHTML( 'Medium' );
			} elseif ( $page->spamProbability <= 2 ) {
				$out->addHTML( 'High' );
			} else {
				$out->addHTML( 'Very high' );
			}
			$out->addHTML( '</td>' );
			$out->addHTML( '<td>' );
			$out->addHTML( Html::check(
				'delete[]', false, array(
					'value' => $page->getID(),
				)
			) );
			$out->addHTML( '</td>' );
			$out->addHTML( Html::closeElement( 'tr' ) );
		}

		$out->addHTML( Html::closeElement( 'table' ) );
		$out->addHTML( Html::element( 'input', array(
			'type' => 'submit',
			'value' => $this->msg( 'smitespam-delete-selected' )->text()
		) ) );
		$out->addHTML( Html::closeElement( 'form' ) );
	}

	function getGroupName() {
		return 'maintenance';
	}
}
