/* Peyvanda — manual item picker (admin only). */
( function () {
	'use strict';

	var settings = window.peyvandaManual || { i18n: {} };
	var i18n = settings.i18n;

	function init( root ) {
		var search = root.querySelector( '.pvd-manual__search' );
		var results = root.querySelector( '.pvd-manual__results' );
		var selected = root.querySelector( '.pvd-manual__selected' );
		var status = root.querySelector( '.pvd-manual__status' );
		var max = parseInt( root.getAttribute( 'data-max' ), 10 ) || 0;
		var postType = root.getAttribute( 'data-post-type' );
		var postId = parseInt( root.getAttribute( 'data-post-id' ), 10 ) || 0;
		var timer = null;
		var requestId = 0;

		function selectedIds() {
			return Array.prototype.map.call( selected.querySelectorAll( '.pvd-manual__chip' ), function ( li ) {
				return parseInt( li.getAttribute( 'data-id' ), 10 );
			} );
		}

		function isFull() {
			return max > 0 && selectedIds().length >= max;
		}

		function setStatus( text ) {
			status.textContent = text || '';
		}

		function addChip( item ) {
			if ( isFull() ) {
				setStatus( i18n.limit );
				return;
			}
			if ( selectedIds().indexOf( item.id ) !== -1 ) {
				return;
			}
			var li = document.createElement( 'li' );
			li.className = 'pvd-manual__chip';
			li.setAttribute( 'data-id', String( item.id ) );

			var input = document.createElement( 'input' );
			input.type = 'hidden';
			input.name = 'peyvanda_manual_items[]';
			input.value = String( item.id );

			var title = document.createElement( 'span' );
			title.className = 'pvd-manual__chip-title';
			title.textContent = item.title;

			var remove = document.createElement( 'button' );
			remove.type = 'button';
			remove.className = 'pvd-manual__remove button-link';
			remove.setAttribute( 'aria-label', i18n.remove );
			remove.textContent = '×';

			li.appendChild( input );
			li.appendChild( title );
			li.appendChild( remove );
			selected.appendChild( li );
			setStatus( i18n.added );
			results.innerHTML = '';
			search.value = '';
		}

		function renderResults( items ) {
			results.innerHTML = '';
			if ( ! items.length ) {
				setStatus( i18n.noResults );
				return;
			}
			setStatus( '' );
			items.forEach( function ( item ) {
				var li = document.createElement( 'li' );
				var button = document.createElement( 'button' );
				button.type = 'button';
				button.className = 'pvd-manual__result';
				button.setAttribute( 'role', 'option' );
				button.textContent = item.title;
				if ( item.date ) {
					var date = document.createElement( 'small' );
					date.textContent = item.date;
					button.appendChild( date );
				}
				button.addEventListener( 'click', function () {
					addChip( item );
				} );
				li.appendChild( button );
				results.appendChild( li );
			} );
		}

		function runSearch() {
			var term = search.value.trim();
			if ( term.length < 2 ) {
				results.innerHTML = '';
				setStatus( '' );
				return;
			}
			if ( isFull() ) {
				setStatus( i18n.limit );
				return;
			}
			var current = ++requestId;
			var skip = selectedIds();
			if ( postId ) {
				skip.push( postId );
			}
			var query = '?search=' + encodeURIComponent( term ) + '&post_type=' + encodeURIComponent( postType );
			skip.forEach( function ( id ) {
				query += '&skip[]=' + id;
			} );
			setStatus( i18n.searching );
			window.wp.apiFetch( { path: settings.path + query } ).then( function ( items ) {
				if ( current === requestId ) {
					renderResults( items || [] );
				}
			} ).catch( function () {
				if ( current === requestId ) {
					setStatus( i18n.error );
				}
			} );
		}

		search.addEventListener( 'input', function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( runSearch, 300 );
		} );

		search.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Enter' ) {
				event.preventDefault();
			}
		} );

		selected.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( '.pvd-manual__remove' );
			if ( button ) {
				button.closest( '.pvd-manual__chip' ).remove();
				setStatus( '' );
			}
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.pvd-manual' ).forEach( init );
	} );
}() );
