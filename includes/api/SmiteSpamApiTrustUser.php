<?php

class SmiteSpamApiTrustUser extends ApiBase {
	public function execute() {
		$this->checkUserRightsAny( 'smitespam' );

		$username = $this->getMain()->getVal( 'username' );

		$user = User::newFromName( $username );
		if ( !$user || $user->getId() === 0 ) {
			$this->dieWithError( [ 'apierror-invaliduser', wfEscapeWikiText( $username ) ] );
		}

		$dbr = wfGetDB( DB_REPLICA );

		$result = $dbr->selectRow(
			[ 'smitespam_trusted_user' ],
			'trusted_user_id',
			[ 'trusted_user_id = ' . $user->getId() ]
		);

		if ( $result ) {
			$this->dieWithError( 'apierror-smitespam-duplicate', 'duplicate' );
		}

		$dbw = wfGetDB( DB_MASTER );

		$dbw->insert(
			'smitespam_trusted_user',
			[
				'trusted_user_id' => $user->getId(),
				'trusted_user_timestamp' => $dbw->timestamp(),
				'trusted_user_admin_id' => $this->getUser()->getId()
			]
		);

		$result = $this->getResult();
		$result->addValue(
			null,
			$this->getModuleName(),
			[ 'success' => 1 ]
		);
		return true;
	}

	/** @inheritDoc */
	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), [
			'username' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			]
		] );
	}

	/** @inheritDoc */
	public function getExamples() {
		return [
			'api.php?action=smitespamtrustuser&username=Admin'
			=> 'Trust user "Admin"'
		];
	}
}
