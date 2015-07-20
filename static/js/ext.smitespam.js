( function ( $ ) {
	// config options
	var numPages = mw.config.get( 'numPages' );
	var querySize = mw.config.get( 'queryPageSize' );
	var displaySize = mw.config.get( 'displayPageSize' );

	// Data
	var results = []; // pages
	var displayOffset = 0;

	var pagesToDelete = [];
	var pagesToDeleteIndex = 0;
	var pagesToDeleteTitleTexts = [];
	var pagesFailedToDelete = [];

	var usersToBlock = [];
	var usersToBlockIndex = 0;
	var usersFailedToBlock = [];

	/*
	key value pairs of type:
	username: {
		blocked: false,
		link: ''
	}
	*/
	var users = {};

	var ajaxQueries = {}; // namespace for AJAX queries
	ajaxQueries.baseUrl = mw.config.get( 'wgScriptPath' );
	ajaxQueries.editToken = '';

	ajaxQueries.pages = {
		numSent: 0,
		send: function () {
			var url = ajaxQueries.baseUrl + '/api.php?action=smitespamanalyze&format=json';
			$.getJSON( url +
				'&offset=' + ajaxQueries.pages.numSent * querySize +
				'&limit=' + querySize,
				ajaxQueries.pages.processResponse );
			ajaxQueries.pages.numSent++;
		},
		processResponse: function ( data ) {
			if ( 'smitespamanalyze' in data ) {
				var receivedPages = data.smitespamanalyze.pages;
				$.extend( users, data.smitespamanalyze.users );
				$.merge( results, receivedPages );
				displayResults();
			} else if ( 'error' in data ) {
				if ( data.error.code === 'internal_api_error_DBQueryError' ) {
					createErrorbox();
					$( '<p>' ).text( 'Database error! Did you forget to run maintenance/update.php?' ).appendTo( '#ajax-errorbox' );
				}
			}
		}
	};

	ajaxQueries.deletePage = {
		send: function () {
			if ( pagesToDeleteIndex >= pagesToDelete.length ) {
				return;
			}
			$.post( ajaxQueries.baseUrl + '/api.php?action=delete&format=json',
				{
					token: ajaxQueries.editToken,
					pageid: pagesToDelete[pagesToDeleteIndex],
					reason: mw.msg( 'smitespam-deleted-reason' )
				},
				'json'
			).done( ajaxQueries.deletePage.processResponse );
		},
		processResponse: function ( data ) {
			var pageID = pagesToDelete[pagesToDeleteIndex];
			var pageTitleText = pagesToDeleteTitleTexts[pagesToDeleteIndex];
			var row = $( '#result-row-page-' + pageID );
			if ( 'delete' in data ) {
				for ( var i = 0; i < results.length; i++ ) {
					// force both to string
					if ( results[i].id.toString() === pageID.toString() ) {
						results.splice( i, 1 );
						break;
					}
				}
				if ( row.length ) {
					row.remove();
				}
				refreshRangeDisplayer();
				createSuccessbox();
				// TODO i18n
				$( '#ajax-successbox' ).append( '<p>Page "' + pageTitleText + '" deleted.</p>' );
			} else if ( 'error' in data ) {
				pagesFailedToDelete.push( pageID );
				if ( row.length ) {
					row.find( 'td' ).eq( 3 ).text( mw.msg( 'smitespam-delete-page-failure-msg' ) );
				}
				createErrorbox();
				// TODO i18n
				$( '#ajax-errorbox' ).append( '<p>Failed to delete page "' + pageTitleText + '".</p>' );
			}
			pagesToDeleteIndex++;
			if ( pagesToDeleteIndex < pagesToDelete.length ) {
				ajaxQueries.deletePage.send();
			}
		}
	};

	ajaxQueries.blockUser = {
		send: function () {
			if ( usersToBlockIndex >= usersToBlock.length ) {
				ajaxQueries.deletePage.send();
				return;
			}
			$.post( ajaxQueries.baseUrl + '/api.php?action=block&format=json',
				{
					user: usersToBlock[usersToBlockIndex],
					token: ajaxQueries.editToken,
					nocreate: '',
					noemail: '',
					autoblock: '',
					// TODO i18n
					reason: 'Spamming'
				},
				'json'
			).done( ajaxQueries.blockUser.processResponse );
		},
		processResponse: function ( data ) {
			var username = usersToBlock[usersToBlockIndex];
			if ( 'block' in data ) {
				users[username].blocked = true;
				$( '#smitespam-page-list th .block-checkbox-container' ).each( function () {
					var $this = $( this );
					if ( $this.parent().data( 'username' )  === username ) {
						$this.empty();
						// TODO i18n
						$this.append( ' &middot; (Blocked)' );
						$this.parent().find( '.trust-user-button-container' ).remove();
						return false;
					}
				} );
				createSuccessbox();
				// TODO i18n
				$( '#ajax-successbox' ).append( '<p>User "' + username + '" blocked.</p>' );
			} else if ( 'error' in data ) {
				usersFailedToBlock.push( username );
				$( '#smitespam-page-list .block-checkbox-container' ).each( function () {
					var $this = $( this );
					if ( $this.parent().data( 'username' )  === username ) {
						$this.empty();
						// TODO i18n
						$this.append( ' &middot; (Failed to block)' );
						return false;
					}
				} );
				createErrorbox();
				// TODO i18n
				$( '#ajax-errorbox' ).append( '<p>Failed to block user "' + username + '".</p>' );
			}
			usersToBlockIndex++;
			if ( usersToBlockIndex < usersToBlock.length ) {
				ajaxQueries.blockUser.send();
			} else {
				ajaxQueries.deletePage.send();
			}
		}
	};

	function groupPagesByCreator( pages ) {
		var creators = {};
		for ( var i = 0; i < pages.length; ++i ) {
			var page = pages[i];
			if ( !( page.creator in creators ) ) {
				creators[page.creator] = {};
				creators[page.creator].pages = [];
				creators[page.creator].totalSpamValue = 0;
			}
			creators[page.creator].pages.push( page );
			creators[page.creator].totalSpamValue += page['spam-probability-value'];
		}
		var groupedPages = [];
		$.each( creators, function ( key, value ) {
			value.creator = key;
			value.pages.sort( function ( a, b ) {
				return b['spam-probability-value'] - a['spam-probability-value'];
			} );
			groupedPages.push( value );
		} );
		return groupedPages;
	}

	function displayResults() {
		if ( displayOffset + displaySize > results.length &&
			ajaxQueries.pages.numSent * querySize < numPages ) {
			$( '#smitespam-loading' ).show();
			ajaxQueries.pages.send();
			return;
		}
		$( '#smitespam-loading' ).hide();

		var resultsToDisplay = results.slice( displayOffset, displayOffset + displaySize );

		var i;
		var page;

		var groupedPages = groupPagesByCreator( resultsToDisplay );

		groupedPages.sort( function ( a, b ) {
			return b.totalSpamValue - a.totalSpamValue;
		} );

		function onPageCheckboxChange() {
			var id = $( this ).val();
			if ( this.checked ) {
				pagesToDelete.push( id );
				var titleText = $( this )
					.parent() // td
					.parent() // tr
					.find( 'td' )
					.eq( 0 ) // first cell
					.find( 'a' )
					.text();
				pagesToDeleteTitleTexts.push( titleText );
			} else {
				var index = $.inArray( id, pagesToDelete );
				pagesToDelete.splice( index, 1 );
				pagesToDeleteTitleTexts.splice( index, 1 );
			}
		}

		function onBlockCheckboxChange() {
			var username = $( this ).val();
			if ( this.checked ) {
				usersToBlock.push( username );
			} else {
				var index = $.inArray( username, usersToBlock );
				usersToBlock.splice( index, 1 );
			}
		}

		function onTrustUserButtonClick() {
			var $this = $( this );
			var username = $this
				.parent() // button container
				.parent() // creator cell
				.data( 'username' );

			$.getJSON( mw.config.get( 'wgScriptPath' ) + '/api.php?action=smitespamtrustuser&format=json&username=' + username,
				function ( data ) {
					if ( 'smitespamtrustuser' in data ) {
						$this.parent().parent().find( '.block-checkbox-container' ).remove();
						// TODO i18n
						$this.parent().append( 'Trusted' );
						$this.remove();
						createSuccessbox();

						$( '#ajax-successbox' ).append( '<p>Trusted user "' + username + '".</p>' );
					} else {
						// TODO i18n
						$this.parent().append( 'Failed to trust' );
						$this.remove();
						createErrorbox();
						$( '#ajax-errorbox' ).append( '<p>Failed to trust user "' + username + '".</p>' );
					}
				}
			);
		}

		$( '#smitespam-page-list' ).empty();
		for ( i = 0; i < groupedPages.length; i++ ) {
			var group = groupedPages[i].pages;
			var groupCreator = groupedPages[i].creator;
			var $creatorCell = $( '<th>' ).attr( 'colspan', 5 )
				.html( mw.msg( 'smitespam-created-by' ) + ' ' +
					( users[groupCreator] ? users[groupCreator].link : groupCreator ) )
				.data( 'username', groupCreator );
			if ( users[groupCreator] ) {
				if ( users[groupCreator].blocked ) {
					// TODO i18n
					$creatorCell.append( ' &middot; (Blocked)' );
				} else if ( $.inArray( groupCreator, usersFailedToBlock ) !== -1 ) {
					// TODO i18n
					$creatorCell.append( ' &middot; (Failed to block)' );
				} else {
					var $blockCheckboxContainer = $( '<span>' ).addClass( 'block-checkbox-container' );
					var $blockCheckbox = $( '<input>', {
						type: 'checkbox',
						value: groupCreator
					} )
						.on( 'change', onBlockCheckboxChange );
					if ( $.inArray( $blockCheckbox.val(), usersToBlock ) !== -1 ) {
						$blockCheckbox.attr( 'checked', 'checked' );
					}
					$blockCheckboxContainer.append( ' &middot; ' );
					$blockCheckboxContainer.append( $blockCheckbox );
					// TODO i18n
					$blockCheckboxContainer.append( 'Block' );
					$creatorCell.append( $blockCheckboxContainer );

					var $trustUserButtonContainer = $( '<span>' ).addClass( 'trust-user-button-container' );
					$trustUserButtonContainer.append( ' &middot; ' );
					// TODO i18n
					var $trustUserButton = $( '<button>' ).text( 'Trust' )
						.on( 'click', onTrustUserButtonClick );

					$trustUserButtonContainer.append( $trustUserButton );
					$creatorCell.append( $trustUserButtonContainer );
				}
			}
			var $creatorRow = $( '<tr>' ).append( $creatorCell );
			$( '#smitespam-page-list' ).append( $creatorRow );
			$( '#smitespam-page-list' ).append( '<tr>' +
				'<th>' + mw.msg( 'smitespam-page' ) + '</th>' +
				'<th>' + mw.msg( 'smitespam-probability' ) + '</th>' +
				'<th>' + mw.msg( 'smitespam-preview-text' ) + '</th>' +
				'<th>' + mw.msg( 'smitespam-delete' ) + '</th>' +
				'</tr>' );
			for ( var j = 0; j < group.length; j++ ) {
				page = group[j];
				var $row = $( '<tr>' ).attr( 'id', 'result-row-page-' + page.id );
				$row.addClass( 'result-row' );
				$( '<td></td>' ).html( page.link ).appendTo( $row );
				$( '<td></td>' ).text( page['spam-probability-text'] ).appendTo( $row );
				$( '<td></td>' ).text( page.preview ).appendTo( $row );
				if ( $.inArray( page.id.toString(), pagesFailedToDelete ) !== -1 ) {
					$( '<td></td>' ).text( mw.msg( 'smitespam-delete-page-failure-msg' ) ).appendTo( $row );
				} else {
					var $checkbox = $( '<input>', {
						type: 'checkbox',
						value: page.id
					} )
						.on( 'change', onPageCheckboxChange );
					if ( $.inArray( $checkbox.val(), pagesToDelete ) !== -1 ) {
						$checkbox.attr( 'checked', 'checked' );
					}
					$( '<td></td>' ).append( $checkbox ).appendTo( $row );
				}
				$( '#smitespam-page-list' ).append( $row );
			}
		}
		refreshRangeDisplayer();
		refreshPager();
	}

	function refreshPager() {
		$( '#ajax-successbox' ).remove();
		$( '#ajax-errorbox' ).remove();
		$( '#smitespam-pager-prev-container' ).empty();
		if ( displayOffset === 0 ) {
			$( '<span>' )
				.text( mw.msg( 'table_pager_prev' ) )
				.appendTo( $( '#smitespam-pager-prev-container' ) );
		} else {
			$( '<a>', { href: '#', id: 'smitespam-pager-prev' } )
				.text( mw.msg( 'table_pager_prev' ) )
				.on( 'click', function () {
					displayOffset -= displaySize;
					if ( displayOffset < 0 ) {
						displayOffset = 0;
					}
					displayResults();
				} )
				.appendTo( $( '#smitespam-pager-prev-container' ) );
		}
		$( '#smitespam-pager-next-container' ).empty();
		// disable "next" if all queries sent and last page
		if ( ajaxQueries.pages.numSent * querySize >= numPages &&
			displayOffset + displaySize > results.length ) {
			$( '<span>' )
				.text( mw.msg( 'table_pager_next' ) )
				.appendTo( $( '#smitespam-pager-next-container' ) );
		} else {
			// Next page pager link
			$( '<a>', { href: '#', id: 'smitespam-pager-next' } )
				.text( mw.msg( 'table_pager_next' ) )
				.on( 'click', function () {
					var jump = $( '#smitespam-page-list .result-row' ).length;
					displayOffset += jump;
					displayResults();
				} )
				.appendTo( $( '#smitespam-pager-next-container' ) );
		}
	}

	function refreshRangeDisplayer() {
		var fromPageIndex = displayOffset + 1;
		$( '#smitespam-displayed-range-from' ).text( fromPageIndex );
		var numDisplayed = $( '.result-row' ).length;
		$( '#smitespam-displayed-range-to' ).text( fromPageIndex + numDisplayed - 1 );
		$( '#smitespam-displayed-range' ).show();
	}

	function createSuccessbox() {
		if ( $( '#ajax-successbox' ).length === 0 ) {
			var $successbox = $( '<div>', { id: 'ajax-successbox' } )
				.addClass( 'successbox' );
			$( '#pagination' ).append( $successbox );
			$( '#pagination' ).append( '<br>' );
		}
	}

	function createErrorbox() {
		if ( $( '#ajax-errorbox' ).length === 0 ) {
			var $errorbox = $( '<div>', { id: 'ajax-errorbox' } )
				.addClass( 'errorbox' );
			$( '#pagination' ).append( $errorbox );
			$( '#pagination' ).append( '<br>' );
		}
	}

	function init() {
		var $pagination = $( '#pagination' );
		// TODO i18n
		$( '<input>', { type: 'submit', value: 'Smite Spam!' } ).prependTo( '#smitespam-delete-pages' );
		$( '#smitespam-delete-pages' ).on( 'submit', function () {
			ajaxQueries.blockUser.send();
			return false;
		} );
		// Display from (page) - to (page)
		var $rangeDisplayer = $( '<span>', { id: 'smitespam-displayed-range' } ).hide();
		$( '<span>', { id: 'smitespam-displayed-range-from' } ).appendTo( $rangeDisplayer );
		$rangeDisplayer.append( ' - ' );
		$( '<span>', { id: 'smitespam-displayed-range-to' } ).appendTo( $rangeDisplayer );
		$pagination.append( $rangeDisplayer );

		// TODO i18n
		$( '<span>', { id: 'smitespam-loading' } ).text( ' (Loading more pages...)' ).appendTo( $pagination );

		var $pager = $( '<p>' ).addClass( 'pager' );
		$( '<span>', { id: 'smitespam-pager-prev-container' } ).appendTo( $pager );
		$pager.append( ' &middot; ' );
		$( '<span>', { id: 'smitespam-pager-next-container' } ).appendTo( $pager );
		$pagination.append( $pager );
		refreshPager();

		$.getJSON( mw.config.get( 'wgScriptPath' ) + '/api.php?action=query&meta=tokens&format=json',
			function ( data ) {
				ajaxQueries.editToken = data.query.tokens.csrftoken;
				ajaxQueries.pages.send();
			}
		);
	}
	init();
} )( jQuery );