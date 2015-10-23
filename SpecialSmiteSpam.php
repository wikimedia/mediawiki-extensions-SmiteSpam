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

		$out->addHTML(
			Linker::link(
				SpecialPage::getTitleFor( 'SmiteSpamTrustedUsers' ),
				wfMessage( 'smitespam-view-trusted-users' )->text(),
				array( 'target' => '_blank' )
			)
		);

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
		$out->addHTML( Html::openElement( 'div', array(
			'id' => 'smitespam-page-list',
		) ) );

		$out->addHTML( Html::closeElement( 'div' ) );
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
		return 'spam';
	}
}
