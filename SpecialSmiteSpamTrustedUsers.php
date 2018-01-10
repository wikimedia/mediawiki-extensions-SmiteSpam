<?php

class SpecialSmiteSpamTrustedUsers extends SpecialPage {

	public function __construct() {
		parent::__construct( 'SmiteSpamTrustedUsers', 'smitespam' );
	}

	/** @inheritDoc */
	public function doesWrites() {
		return true;
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
		$request = $this->getRequest();

		if ( $request->wasPosted() ) {
			if ( $request->getVal( 'add' ) ) {
				$username = $request->getText( 'username' );
				$user = User::newFromName( $username );
				if ( $user && $user->getId() !== 0 ) {
					$dbr = wfGetDB( DB_REPLICA );
					$result = $dbr->selectRow(
						[ 'smitespam_trusted_user' ],
						'trusted_user_id',
						[ 'trusted_user_id = ' . $user->getId() ]
					);

					if ( $result ) {
						$out->addHTML(
							'<div class="errorbox">' .
							"<p>" . $this->msg( 'smitespam-already-trusted', $username )->escaped() . "</p>" .
							'</div>'
						);
					} else {
						$dbw = wfGetDB( DB_MASTER );

						$dbw->insert(
							'smitespam_trusted_user',
							[
								'trusted_user_id' => $user->getId(),
								'trusted_user_timestamp' => $dbw->timestamp(),
								'trusted_user_admin_id' => $this->getUser()->getID()
							]
						);
						$out->addHTML(
							'<div class="successbox">' .
							"<p>" . $this->msg( 'smitespam-trusted-user-message', $username )->escaped() . "</p>" .
							'</div>'
						);
					}
				} else {
					$out->addHTML(
						'<div class="errorbox">' .
						"<p>" . $this->msg( 'smitespam-userdoesnotexist', $username )->escaped() . "</p>" .
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
							[ 'trusted_user_id = ' . $user->getId() ]
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

		$dbr = wfGetDB( DB_REPLICA );
		$result = $dbr->select(
			[ 'smitespam_trusted_user' ],
			[ 'trusted_user_id', 'trusted_user_timestamp', 'trusted_user_admin_id' ],
			[],
			__METHOD__,
			[
				"ORDER BY" => "trusted_user_timestamp ASC",
			]
		);

		$out->addHTML( "<form method=\"post\">" );

		$out->addHTML( '<label>' . $this->msg( 'smitespam-add-user-label' )->escaped() .
			'<input type="text" name="username"></label>' .
			' <input type="submit" value="' . $this->msg( 'smitespam-trust' )->escaped() .
			'" name="add">' );

		$out->addHTML( '<table class="wikitable"><tr>' .
			'<th>' . $this->msg( 'smitespam-trusted-user' )->escaped() . '</th>' .
			'<th>' . $this->msg( 'smitespam-timestamp' )->escaped() . '</th>' .
			'<th>' . $this->msg( 'smitespam-trusting-admin' )->escaped() . '</th>' .
			'<th>' . $this->msg( 'smitespam-remove' )->escaped() . '</th>' .
			'</tr>'
		);
		$linkRenderer = $this->getLinkRenderer();
		foreach ( $result as $row ) {
			$trustedUser = User::newFromID( $row->trusted_user_id )->getName();
			$trustedUserContribsLink = $linkRenderer->makeLink(
				SpecialPage::getTitleFor( 'Contributions', $trustedUser ),
				$trustedUser,
				[ 'target' => '_blank' ]
			);
			$timestamp = $this->getLanguage()->userTimeAndDate(
				$row->trusted_user_timestamp,
				$this->getUser()
			);
			$admin = User::newFromID( $row->trusted_user_admin_id )->getName();
			$adminContribsLink = $linkRenderer->makeLink(
				SpecialPage::getTitleFor( 'Contributions', $admin ),
				$admin,
				[ 'target' => '_blank' ]
			);

			// TODO i18n
			$out->addHTML(
				"<tr><td>$trustedUserContribsLink</td>" .
				"<td>$timestamp</td>" .
				"<td>$adminContribsLink</td>" .
				"<td><button type=\"submit\" name=\"remove\" value=\"$trustedUser\">" .
				$this->msg( 'smitespam-remove' )->escaped() . "</button></tr>"
			);
		}
		$out->addHTML( '</table>' );
		$out->addHTML( "</form>" );
	}
}
