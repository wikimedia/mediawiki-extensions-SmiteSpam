( function ( $ ) {
	// config options
	var numPages = mw.config.get( 'numPages' );
	var querySize = mw.config.get( 'queryPageSize' );
	var displaySize = mw.config.get( 'displayPageSize' );

	// Data
	var results = []; // pages
	var displayOffset = 0;
	var oldDisplayOffset = -1;

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
			var row = $( '#result-card-page-' + pageID );
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
				$( '#ajax-successbox' ).append( '<p>' + mw.msg( 'smitespam-delete-page-success-msg', pageTitleText ) + '</p>' );
			} else if ( 'error' in data ) {
				pagesFailedToDelete.push( pageID );
				if ( row.length ) {
					row.find( 'td' ).eq( 3 ).text( mw.msg( 'smitespam-delete-page-failure-msg' ) );
				}
				createErrorbox();
				$( '#ajax-errorbox' ).append( '<p>' + mw.msg( 'smitespam-delete-page-failure-msg', pageTitleText ) + '".</p>' );
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
					reason: mw.msg( 'smitespam-block-reason' )
				},
				'json'
			).done( ajaxQueries.blockUser.processResponse );
		},
		processResponse: function ( data ) {
			var username = usersToBlock[usersToBlockIndex];
			if ( 'block' in data ) {
				users[username].blocked = true;
				$( '#smitespam-page-list .creator-card .block-checkbox-container' ).each( function () {
					var $this = $( this );
					if ( $this.parent().data( 'username' )  === username ) {
						$this.empty();
						$this.append( ' &middot; (' + mw.msg( 'smitespam-blocked' ) + ')' );
						$this.parent().find( '.trust-user-button-container' ).remove();
						return false;
					}
				} );
				createSuccessbox();
				$( '#ajax-successbox' ).append( '<p>' + mw.msg( 'smitespam-blocked-user-success-msg', username ) + '</p>' );
			} else if ( 'error' in data ) {
				usersFailedToBlock.push( username );
				$( '#smitespam-page-list .creator-card .block-checkbox-container' ).each( function () {
					var $this = $( this );
					if ( $this.parent().data( 'username' )  === username ) {
						$this.empty();
						$this.append( ' &middot; (' + mw.msg( 'smitespam-block-failed' ) + ')' );
						return false;
					}
				} );
				createErrorbox();
				$( '#ajax-errorbox' ).append( '<p>' + mw.msg( 'smitespam-blocked-user-failure-msg', username ) + '</p>' );
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
		if ( oldDisplayOffset === displayOffset ) {
			return;
		}
		if ( displayOffset + displaySize > results.length &&
			ajaxQueries.pages.numSent * querySize < numPages ) {
			$( '#smitespam-loading' ).show();
			$( '.smitespam-submit-button' ).hide();
			ajaxQueries.pages.send();
			return;
		}
		$( '#smitespam-loading' ).hide();
		$( '.smitespam-submit-button' ).show();

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
					.closest( '.card' )
					.find( '.smitespam-page-title a' )
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
			var $userCard = $( this ).closest( '.creator-card' ).next();
			var $checkboxes = $userCard.nextUntil( ':not(.card)' )
				.find( 'input[type=checkbox]' );
			if ( this.checked ) {
				usersToBlock.push( username );
				$checkboxes.prop( 'checked', true ).change();
			} else {
				var index = $.inArray( username, usersToBlock );
				usersToBlock.splice( index, 1 );
				$checkboxes.prop( 'checked', false ).change();
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
						$this.parent().append( mw.msg( 'smitespam-trusted' ) );
						$this.remove();
						createSuccessbox();
						$( '#ajax-successbox' ).append( '<p>' + mw.msg( 'smitespam-trusted-user-success-msg', username ) + '</p>' );
					} else {
						$this.parent().append( 'Failed to trust' );
						$this.remove();
						createErrorbox();
						// TODO i18n
						$( '#ajax-errorbox' ).append( '<p>' + mw.msg( 'smitespam-trusted-user-failure-msg', username ) + '</p>' );
					}
				}
			);
		}

		$( '#smitespam-page-list' ).empty();
		for ( i = 0; i < groupedPages.length; i++ ) {
			var group = groupedPages[i].pages;
			var groupCreator = groupedPages[i].creator;
			var $userGroup = $( '<div>' ).addClass( 'user-group' );
			$userGroup.append( '<hr>' );
			var $creatorCard = $( '<div>' )
				.addClass( 'creator-card' )
				.html( mw.msg(
						'smitespam-created-by',
						users[groupCreator] ? users[groupCreator].link : groupCreator
					)
				)
				.data( 'username', groupCreator );
			if ( users[groupCreator] ) {
				if ( users[groupCreator].blocked ) {
					$creatorCard.append( ' &middot; (' + mw.msg( 'smitespam-blocked' ) + ')' );
				} else if ( $.inArray( groupCreator, usersFailedToBlock ) !== -1 ) {
					$creatorCard.append( ' &middot; (' + mw.msg( 'smitespam-block-failed' ) + ')' );
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
					$blockCheckboxContainer.append( mw.msg( 'smitespam-block' ) );
					$creatorCard.append( $blockCheckboxContainer );

					var $trustUserButtonContainer = $( '<span>' ).addClass( 'trust-user-button-container' );
					$trustUserButtonContainer.append( ' &middot; ' );
					var $trustUserButton = $( '<button>' ).text( mw.msg( 'smitespam-trust' ) )
						.on( 'click', onTrustUserButtonClick );

					$trustUserButtonContainer.append( $trustUserButton );
					$creatorCard.append( $trustUserButtonContainer );
				}
			}
			$userGroup.append( $creatorCard );
			$userGroup.append( '<hr>' );
			$( '#smitespam-page-list' ).append( $userGroup );
			for ( var j = 0; j < group.length; j++ ) {
				page = group[j];
				var $card = $( '<div>' ).attr( 'id', 'result-card-page-' + page.id );
				$card.addClass( 'card' );
				$card = $card.append( '<div>' ).addClass( 'row' );
				var $cardInfoSection = $( '<div>' ).addClass( 'card-info-section' )
					.appendTo( $card );
				var $cardDataSection = $( '<div>' ).addClass( 'card-data-section' )
					.appendTo( $card );
				$( '<h3>' ).addClass( 'smitespam-page-title' )
					.html( page.link ).appendTo( $cardDataSection );
				$( '<p>' ).text( page.preview ).appendTo( $cardDataSection );
				var $spamLevelTag = $( '<span>' )
					.addClass( 'info-tag' )
					.appendTo( $cardInfoSection );
				if ( page['spam-probability-level'] === 0 ) {
					$spamLevelTag
						.text( mw.msg( 'smitespam-probability-low' ) )
						.addClass( 'probability-low' );
				} else if ( page['spam-probability-level'] === 1 ) {
					$spamLevelTag
						.text( mw.msg( 'smitespam-probability-medium' ) )
						.addClass( 'probability-medium' );
				} else if ( page['spam-probability-level'] === 2 ) {
					$spamLevelTag
						.text( mw.msg( 'smitespam-probability-high' ) )
						.addClass( 'probability-high' );
				} else if ( page['spam-probability-level'] === 3 ) {
					$spamLevelTag
						.text( mw.msg( 'smitespam-probability-very-high' ) )
						.addClass( 'probability-very-high' );
				} else {
					$spamLevelTag.text( '-' );
				}
				$cardInfoSection.append( '<br>' );
				$( '<span>' )
					.text( page.timestamp )
					.appendTo( $cardInfoSection );
				$cardInfoSection.append( '<br>' );
				if ( $.inArray( page.id.toString(), pagesFailedToDelete ) !== -1 ) {
					$( '<td></td>' ).text( mw.msg( 'smitespam-delete-page-failure-msg' ) ).appendTo( $cardInfoSection );
				} else {
					var $checkbox = $( '<input>', {
						type: 'checkbox',
						value: page.id
					} )
						.on( 'change', onPageCheckboxChange );
					if ( $.inArray( $checkbox.val(), pagesToDelete ) !== -1 ) {
						$checkbox.attr( 'checked', 'checked' );
					}
					$( '<label>' ).append( $checkbox )
						.append( mw.msg( 'smitespam-delete' ) )
						.appendTo( $cardInfoSection );
				}
				$userGroup.append( $card );
			}
		}
		refreshRangeDisplayer();
		refreshPager();
		oldDisplayOffset = displayOffset;
		if ( displayOffset + displaySize * 2 > results.length &&
			ajaxQueries.pages.numSent * querySize < numPages ) {
			ajaxQueries.pages.send();
		}
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
					var jump = $( '#smitespam-page-list .card' ).length;
					displayOffset += jump;
					displayResults();
				} )
				.appendTo( $( '#smitespam-pager-next-container' ) );
		}
	}

	function refreshRangeDisplayer() {
		var fromPageIndex = displayOffset + 1;
		$( '#smitespam-displayed-range-from' ).text( fromPageIndex );
		var numDisplayed = $( '.card' ).length;
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
		var $submitButton = $( '<input>', { type: 'submit', value: 'Smite Spam!' } ).addClass( 'smitespam-submit-button' ).prependTo( '#smitespam-delete-pages' );
		$submitButton.clone().insertAfter( '#smitespam-page-list' );
		$( '.smitespam-submit-button' ).hide();

		$( '#smitespam-delete-pages' ).on( 'submit', function () {
			ajaxQueries.blockUser.send();
			return false;
		} );

		$( '#smitespam-select-options' ).append( 'Select: ' );
		$( '<a>', { href: '#' } )
			.text( mw.msg( 'powersearch-toggleall' ) )
			.on( 'click', function () {
				$( '.creator-card input[type="checkbox"]' ).prop( 'checked', true ).change();
				$( '.card-info-section input[type="checkbox"]:not(:checked)' ).prop( 'checked', true ).change();
			} )
			.appendTo( '#smitespam-select-options' );
		$( '#smitespam-select-options' ).append( ' &middot; ' );
		$( '<a>', { href: '#' } )
			.text( mw.msg( 'powersearch-togglenone' ) )
			.on( 'click', function () {
				$( '.creator-card input[type="checkbox"]' ).prop( 'checked', false ).change();
				$( '.card-info-section input[type="checkbox"]:checked' ).prop( 'checked', false ).change();
			} )
			.appendTo( '#smitespam-select-options' );

		// Display from (page) - to (page)
		var $rangeDisplayer = $( '<span>', { id: 'smitespam-displayed-range' } ).hide();
		$( '<span>', { id: 'smitespam-displayed-range-from' } ).appendTo( $rangeDisplayer );
		$rangeDisplayer.append( ' - ' );
		$( '<span>', { id: 'smitespam-displayed-range-to' } ).appendTo( $rangeDisplayer );
		$pagination.append( $rangeDisplayer );

		$( '<span>', { id: 'smitespam-loading' } )
			.html( '&nbsp;' )
			.append( $.createSpinner() )
			.appendTo( $pagination );

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
