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

		$numPages = SiteStats::pages();

		$request = $this->getRequest();

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

		$out->addHTML( '<h2>' . $this->msg( 'smitespam-spam-pages-list-heading' )->text() . '</h2>' );

		$out->addHTML( '<div id="pagination"></div>' );

		$out->addHTML(
			Html::openElement( 'form', array(
					'method' => 'post',
					'id' => 'smitespam-delete-pages',
				)
			)
		);

		$out->addHTML( '<div id="smitespam-select-options"></div>' );

		$out->addHTML( '<input type="submit" value="'
			. $this->msg( 'smitespam-delete-selected' ) . '" style="display:none;">' );
		$out->addHTML( Html::openElement( 'table', array(
			'class' => 'wikitable',
			'id' => 'smitespam-page-list',
		) ) );

		$out->addHTML( Html::closeElement( 'table' ) );
		$out->addHTML( '<input type="submit" value="'
			. $this->msg( 'smitespam-delete-selected' ) . '" style="display:none;">' );
		$out->addHTML( Html::closeElement( 'form' ) );

		$out->addModules( 'ext.SmiteSpam.retriever' );
		global $wgQueryPageSize, $wgDisplayPageSize;
		$out->addJsConfigVars( array(
			'numPages' => $numPages,
			'queryPageSize' => $wgQueryPageSize,
			'displayPageSize' => $wgDisplayPageSize,
		) );
	}

	function getGroupName() {
		return 'maintenance';
	}
}
