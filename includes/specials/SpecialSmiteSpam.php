<?php
/**
 * Main special page for the SmiteSpam extension.
 */
class SpecialSmiteSpam extends SpecialPage {

	public function __construct() {
		parent::__construct( 'SmiteSpam', 'smitespam' );
	}

	/**
	 * @param string|null $subPage
	 */
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
			$this->getLinkRenderer()->makeLink(
				SpecialPage::getTitleFor( 'SmiteSpamTrustedUsers' ),
				$this->msg( 'smitespam-view-trusted-users' )->text(),
				[ 'target' => '_blank' ]
			)
		);

		$out->addHTML( '<h2>' . $this->msg( 'smitespam-spam-pages-list-heading' )->escaped() . '</h2>' );

		$out->addHTML( '<div id="pagination"></div>' );

		$out->addHTML(
			Html::openElement( 'form', [
					'method' => 'post',
					'id' => 'smitespam-delete-pages',
				]
			)
		);

		$out->addHTML( '<div id="smitespam-select-options"></div>' );

		$out->addHTML( '<input type="submit" value="'
			. $this->msg( 'smitespam-delete-selected' )->escaped() . '" style="display:none;">' );
		$out->addHTML( Html::openElement( 'div', [
			'id' => 'smitespam-page-list',
		] ) );

		$out->addHTML( Html::closeElement( 'div' ) );
		$out->addHTML( '<input type="submit" value="'
			. $this->msg( 'smitespam-delete-selected' )->escaped() . '" style="display:none;">' );
		$out->addHTML( Html::closeElement( 'form' ) );

		$out->addModules( 'ext.SmiteSpam.retriever' );
		global $wgSmiteSpamQueryPageSize, $wgSmiteSpamDisplayPageSize;
		$out->addJsConfigVars( [
			'numPages' => $numPages,
			'queryPageSize' => $wgSmiteSpamQueryPageSize,
			'displayPageSize' => $wgSmiteSpamDisplayPageSize,
		] );
	}

	/** @inheritDoc */
	function getGroupName() {
		return 'spam';
	}
}
