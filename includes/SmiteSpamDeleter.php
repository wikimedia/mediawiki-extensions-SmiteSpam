<?php
/**
 * The class performing the actual deletion of selected pages.
 */
class SmiteSpamDeleter {
	/**
	 * Deletes a list of pages
	 * @param  array $pageIDs
	 * @return array A list of messages to be displayed.
	 */
	public function deletePages( $pageIDs ) {
		// @todo change to use Jobs?
		$messages = array();
		$successMessage = wfMessage( 'smitespam-delete-page-success-msg' )->text();
		$failureMessage = wfMessage( 'smitespam-delete-page-failure-msg' )->text();
		foreach ( $pageIDs as $pageID ) {
			if ( ctype_digit( $pageID ) || is_int( $pageID ) ) {
				$article = Article::newFromID( $pageID );
				if ( $article ) {
					$ok = $article->doDeleteArticle( wfMessage( 'smitespam-deleted-reason' ) );
					$titleText = Sanitizer::escapeHtmlAllowEntities( $article->getTitle()->getText() );
					if ( $ok ) {
						$messages['success'][] = "$successMessage '$titleText'.";
					}
					else {
						$messages['error'][] = "$failureMessage '$titleText'.";
					}
					continue;
				}
			}
			$pageID = Sanitizer::escapeHtmlAllowEntities( $pageID );
			$messages['error'][] = "$failureMessage '$pageID'.";
		}
		return $messages;
	}
}
