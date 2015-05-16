<?php

class SpecialSmiteSpam extends SpecialPage {

	public function __construct() {
		parent::__construct( 'SmiteSpam' );
	}

	public function execute() {
		$output = $this->getOutput();
		$output->setPageTitle( $this->msg('smitespam') );
	}

	function getGroupName() {
		   return 'maintenance';
	}
}
