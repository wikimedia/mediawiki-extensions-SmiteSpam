( function ( $ ) {
	var Counter = function () {
		var value = 0;
		this.update = function ( newValue ) {
			value = newValue;
			if ( typeof this.onUpdate === 'function' ) {
				this.onUpdate();
			}
		};

		this.getValue = function () {
			return value;
		};

		this.increment = function ( by ) {
			if ( by ) {
				this.update( value + by );
			} else {
				this.update( value + 1 );
			}
		};

		this.decrement = function ( by ) {
			if ( by ) {
				this.update( value - by );
			} else {
				this.update( value - 1 );
			}
		};
	};

	var queriesSent = new Counter();

	var numPages = mw.config.get( 'numPages' );
	var queryPageSize = mw.config.get( 'queryPageSize' );
	var displayPageSize = mw.config.get( 'displayPageSize' );
	var results = [];
	results.push( [] );

	var resultPageToDisplay = new Counter();
	resultPageToDisplay.notify = function () {
		if ( queriesSent.getValue() * queryPageSize >= numPages ||
			results[this.getValue()].length >= displayPageSize ) {
			$( '#results-loading' ).hide();
			pagination.refresh();
		} else {
			$( '#results-loading' ).show();
			sendNextQuery();
		}
	};
	resultPageToDisplay.onUpdate = resultPageToDisplay.notify;

	function sendNextQuery() {
		var url = mw.config.get( 'wgScriptPath' ) + '/api.php?action=smitespamanalyze&format=json';
		$.getJSON( url +
			'&offset=' + queriesSent.getValue() * queryPageSize +
			'&limit=' + queryPageSize,
			processResponse );
		queriesSent.increment();
	}

	function processResponse( data ) {
		var receivedPages = data.smitespamanalyze.pages;

		while ( receivedPages.length ) {
			var remaining = displayPageSize - results[results.length - 1].length;
			var toAppend = receivedPages.slice( 0, remaining );
			for ( var i = 0; i < toAppend.length; i++ ) {
				results[results.length - 1].push( toAppend[i] );
			}
			receivedPages = receivedPages.splice( remaining );
			if ( results[results.length - 1].length === displayPageSize ) {
				results[results.length - 1].sort( function ( a, b ) {
					return b['spam-probability-value'] - a['spam-probability-value'];
				} );
				results.push( [] );
			}
		}
		resultPageToDisplay.notify();
	}

	var pagination = {
		data: {
			pagesToDelete: []
		},
		handlersAttached: false,
		attachHandlers: function () {
			if ( pagination.handlersAttached ) {
				return;
			}
			$( '<p>' + mw.msg( 'smitespam-page' ) + ' <span id="displayed-page-number">0</span></p>' )
				.appendTo( '#pagination' );
			$( '<button>', { id: 'smitespam-pagination-prev' } )
				.html( '&lt;&lt; ' + mw.msg( 'table_pager_prev' ) )
				.appendTo( '#pagination' );
			$( '#smitespam-pagination-prev' ).on( 'click', function () {
				resultPageToDisplay.decrement();
			} );

			$( '<button>', { id: 'smitespam-pagination-next' } )
				.html( mw.msg( 'table_pager_next' ) + ' &gt;&gt;' )
				.appendTo( '#pagination' );
			$( '#smitespam-pagination-next' ).on( 'click', function () {
				resultPageToDisplay.increment();
			} );
			pagination.handlersAttached = true;
		},
		refresh: function () {
			pagination.attachHandlers();
			if ( resultPageToDisplay.getValue() === 0 ) {
				$( '#smitespam-pagination-prev' ).attr( 'disabled', 'disabled' );
			} else {
				$( '#smitespam-pagination-prev' ).removeAttr( 'disabled' );
			}

			if ( queriesSent.getValue() * queryPageSize >= numPages &&
				resultPageToDisplay.getValue() === results.length - 1 ) {
				$( '#pagination #smitespam-pagination-next' ).attr( 'disabled', 'disabled' );
			} else {
				$( '#pagination #smitespam-pagination-next' ).removeAttr( 'disabled' );
			}
			pagination.displayResults();
			$( '#displayed-page-number' ).text( resultPageToDisplay.getValue() + 1 );
		},
		displayResults: function () {
			$( '#smitespam-page-list' ).empty();
			$( '#smitespam-page-list' ).append( '<tr>' +
				'<th>' + mw.msg( 'smitespam-page' ) + '</th>' +
				'<th>' + mw.msg( 'smitespam-probability' ) + '</th>' +
				'<th>' + mw.msg( 'smitespam-created-by' ) + '</th>' +
				'<th>' + mw.msg( 'smitespam-preview-text' ) + '</th>' +
				'<th>' + mw.msg( 'smitespam-delete' ) + '</th>' +
				'</tr>' );
			function checkboxChanged() {
				var id = $( this ).val();
				if ( this.checked ) {
					pagination.data.pagesToDelete.push( id );
				} else {
					var index = $.inArray( id, pagination.data.pagesToDelete );
					pagination.data.pagesToDelete.splice( index, 1 );
				}
			}
			for ( var i = 0; i < displayPageSize; ++i ) {
				var page = results[resultPageToDisplay.getValue()][i];
				var $row = $( '<tr>' );
				$( '<td></td>' ).html( page.link ).appendTo( $row );
				$( '<td></td>' ).text( page['spam-probability-text'] ).appendTo( $row );
				$( '<td></td>' ).html( page['creator-link'] ).appendTo( $row );
				$( '<td></td>' ).text( page.preview ).appendTo( $row );

				var $checkbox = $( '<input>', {
					type: 'checkbox',
					value: page.id
				} )
					.on( 'change', checkboxChanged );
				if ( $.inArray( $checkbox.val(), pagination.data.pagesToDelete ) !== -1 ) {
					$checkbox.attr( 'checked', 'checked' );
				}
				$( '<td></td>' ).append( $checkbox ).appendTo( $row );
				$( '#smitespam-page-list' ).append( $row );
			}
		}
	};

	$( '#smitespam-delete-pages' ).on( 'submit', function () {
		var toDelete = pagination.data.pagesToDelete;
		$( '#smitespam-page-list' ).empty();
		var $this = $( this );
		for ( var i = 0; i < toDelete.length; ++i ) {
			$( '<input>', {
				type: 'checkbox',
				name: 'delete[]',
				value: toDelete[i],
				checked: 'checked'
			} ).hide().appendTo( $this );
		}
	} );

	$( '<p id="results-loading"></p>' ).text( 'Loading...' )
		.appendTo( '#pagination' );
	resultPageToDisplay.notify();

} )( jQuery );
