<?php

class SpecialSmiteSpamTrustedUsers extends SpecialPage {

	public function __construct() {
		parent::__construct( 'SmiteSpamTrustedUsers', 'smitespam' );
	}

	public function doesWrites() {
		return true;
	}

	public function execute( $subPage ) {
		if ( !$this->userCanExecute( $this->getUser() ) ) {
			$this->displayRestrictionError();
			return;
		}
		$this->setHeaders();
		$out = $this->getOutput();
		$request = $this->getRequest();

		if ( $request->wasPosted() ) {
			if ( $request->getVal( 'add' ) ) {
				$username = $request->getText( 'username' );
				$user = User::newFromName( $username );
				if ( $user && $user->getId() !== 0 ) {
					$dbr = wfGetDB( DB_SLAVE );
					$result = $dbr->selectRow(
						array( 'smitespam_trusted_user' ),
						'trusted_user_id',
						array( 'trusted_user_id = ' . $user->getId() )
					);

					if ( $result ) {
						$out->addHTML(
							'<div class="errorbox">' .
							"<p>" . wfMessage( 'smitespam-already-trusted', $username )->text() . "</p>" .
							'</div>'
						);
					} else {
						$dbw = wfGetDB( DB_MASTER );

						$dbw->insert(
							'smitespam_trusted_user',
							array(
								'trusted_user_id' => $user->getId(),
								'trusted_user_timestamp' => $dbw->timestamp(),
								'trusted_user_admin_id' => $this->getUser()->getID()
							)
						);
						$out->addHTML(
							'<div class="successbox">' .
							"<p>" . wfMessage( 'smitespam-trusted-user-message', $username )->escaped() . "</p>" .
							'</div>'
						);
					}
				} else {
					$out->addHTML(
						'<div class="errorbox">' .
						"<p>" . wfMessage( 'smitespam-userdoesnotexist', $username )->escaped() . "</p>" .
						'</div>'
					);
				}
			} else {
				$usernameToDelete = $request->getText( 'remove' );
				if ( $usernameToDelete ) {
					$user = User::newFromName( $usernameToDelete );
					if ( $user && $user->getId() !== 0 ) {
						$dbw = wfGetDB( DB_MASTER );
						$dbw->delete(
							'smitespam_trusted_user',
							array( 'trusted_user_id = ' . $user->getId() )
						);
						$out->addHTML(
							'<div class="successbox">' .
							"<p>Removed user '$usernameToDelete' from trusted users.</p>" .
							'</div>'
						);
					}
				}
			}
		}

		$dbr = wfGetDB( DB_SLAVE );
		$result = $dbr->select(
			array( 'smitespam_trusted_user' ),
			array( 'trusted_user_id', 'trusted_user_timestamp', 'trusted_user_admin_id' ),
			array(),
			__METHOD__,
			array(
				"ORDER BY" => "trusted_user_timestamp ASC",
			)
		);

		$out->addHTML( "<form method=\"post\">" );

		$out->addHTML( '<label>' . wfMessage( 'smitespam-add-user-label' )->text() .
			'<input type="text" name="username"></label>' .
			' <input type="submit" value="' . wfMessage( 'smitespam-trust' )->text() .
			'" name="add">' );

		$out->addHTML( '<table class="wikitable"><tr>' .
			'<th>' . wfMessage( 'smitespam-trusted-user' )->text() . '</th>' .
			'<th>' . wfMessage( 'smitespam-timestamp' )->text() . '</th>' .
			'<th>' . wfMessage( 'smitespam-trusting-admin' )->text() . '</th>' .
			'<th>' . wfMessage( 'smitespam-remove' )->text() . '</th>' .
			'</tr>'
		);
		foreach ( $result as $row ) {
			$trustedUser = User::newFromID( $row->trusted_user_id )->getName();
			$trustedUserContribsLink = Linker::link(
				SpecialPage::getTitleFor( 'Contributions', $trustedUser ),
				Sanitizer::escapeHtmlAllowEntities( $trustedUser ),
				array( 'target' => '_blank' )
			);
			$timestamp = $this->getLanguage()->userTimeAndDate(
				$row->trusted_user_timestamp,
				$this->getUser()
			);
			$admin = User::newFromID( $row->trusted_user_admin_id )->getName();
			$adminContribsLink = Linker::link(
				SpecialPage::getTitleFor( 'Contributions', $admin ),
				Sanitizer::escapeHtmlAllowEntities( $admin ),
				array( 'target' => '_blank' )
			);

			// TODO i18n
			$out->addHTML(
				"<tr><td>$trustedUserContribsLink</td>" .
				"<td>$timestamp</td>" .
				"<td>$adminContribsLink</td>" .
				"<td><button type=\"submit\" name=\"remove\" value=\"$trustedUser\">" .
				wfMessage( 'smitespam-remove' )->text() . "</button></tr>"
			);
		}
		$out->addHTML( '</table>' );
		$out->addHTML( "</form>" );
	}
}
