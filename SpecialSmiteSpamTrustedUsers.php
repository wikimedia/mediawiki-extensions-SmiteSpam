<?php

class SpecialSmiteSpamTrustedUsers extends SpecialPage {

	public function __construct() {
		parent::__construct( 'SmiteSpamTrustedUsers', 'smitespam' );
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
						// TODO i18n
						$out->addHTML(
							'<div class="errorbox">' .
							"<p>User '$username' already trusted.</p>" .
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
						// TODO i18n
						$out->addHTML(
							'<div class="successbox">' .
							"<p>Trusted user '$username'.</p>" .
							'</div>'
						);
					}
				} else {
					// TODO i18n
					$out->addHTML(
						'<div class="errorbox">' .
						"<p>User '$username' does not exist.</p>" .
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
						// TODO i18n
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

		$out->addHTML( '<label>Add user: <input type="text" name="username"></label>' .
			' <input type="submit" value="Add" name="add">' );

		// TODO i18n
		$out->addHTML( '<table class="wikitable"><tr>' .
			'<th>Trusted User</th>' .
			'<th>Timestamp</th>' .
			'<th>Trusting Admin</th>' .
			'<th>Remove</th>' .
			'</tr>'
		);
		foreach ( $result as $row ) {
			$trustedUser = User::newFromID( $row->trusted_user_id )->getName();
			$trustedUserContribsLink = Linker::link(
				SpecialPage::getTitleFor( 'Contributions', $trustedUser ),
				Sanitizer::escapeHtmlAllowEntities( $trustedUser ),
				array( 'target' => '_blank' )
			);
			$timestamp = wfTimestamp( TS_RFC2822, $row->trusted_user_timestamp );
			$admin = User::newFromID( $row->trusted_user_admin_id )->getName();
			$adminContribsLink = Linker::link(
				SpecialPage::getTitleFor( 'Contributions', $admin ),
				Sanitizer::escapeHtmlAllowEntities( $admin ),
				array( 'target' => '_blank' )
			);
			$out->addHTML(
				"<tr><td>$trustedUserContribsLink</td>" .
				"<td>$timestamp</td>" .
				"<td>$adminContribsLink</td>" .
				"<td><button type=\"submit\" name=\"remove\" value=\"$trustedUser\">Remove</button></tr>"
			);
		}
		$out->addHTML( '</table>' );
		$out->addHTML( "</form>" );
	}
}
