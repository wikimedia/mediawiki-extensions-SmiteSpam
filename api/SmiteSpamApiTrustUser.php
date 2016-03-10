<?php

class SmiteSpamApiTrustUser extends ApiBase {
	public function execute() {

		if ( !in_array( 'smitespam', $this->getUser()->getRights() ) ) {
			$this->dieUsage( 'Permission error.', 'permissiondenied' );
		}
		$username = $this->getMain()->getVal( 'username' );

		$user = User::newFromName( $username );
		if ( !$user || $user->getId() === 0 ) {
			$this->dieUsage( 'Not a valid username.', 'badparams' );
		}

		$dbr = wfGetDB( DB_SLAVE );

		$result = $dbr->selectRow(
			array( 'smitespam_trusted_user' ),
			'trusted_user_id',
			array( 'trusted_user_id = ' . $user->getId() )
		);

		if ( $result ) {
			$this->dieUsage( 'User already trusted.', 'duplicate' );
		}

		$dbw = wfGetDB( DB_MASTER );

		$dbw->insert(
			'smitespam_trusted_user',
			array(
				'trusted_user_id' => $user->getId(),
				'trusted_user_timestamp' => $dbw->timestamp(),
				'trusted_user_admin_id' => $this->getUser()->getID()
			)
		);

		$result = $this->getResult();
		$result->addValue(
			null,
			$this->getModuleName(),
			array( 'success' => 1 )
		);
		return true;
	}

	// Face parameter.
	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'username' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			)
		) );
	}

	// Get examples
	public function getExamples() {
		return array(
			'api.php?action=smitespamtrustuser&username=Admin'
			=> 'Trust user "Admin"'
		);
	}
}
