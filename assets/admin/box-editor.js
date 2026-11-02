/* Peyvanda — box editor tabs and design field switching (admin only). */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var root = document.querySelector( '.pvd-editor' );
		if ( ! root ) {
			return;
		}

		var tabs = root.querySelectorAll( '.pvd-editor__tab' );

		function activate( tab ) {
			tabs.forEach( function ( other ) {
				var selected = other === tab;
				other.setAttribute( 'aria-selected', selected ? 'true' : 'false' );
				other.tabIndex = selected ? 0 : -1;
				var panel = document.getElementById( other.getAttribute( 'aria-controls' ) );
				if ( panel ) {
					panel.hidden = ! selected;
				}
			} );
		}

		tabs.forEach( function ( tab, index ) {
			tab.tabIndex = index === 0 ? 0 : -1;
			tab.addEventListener( 'click', function () {
				activate( tab );
			} );
			tab.addEventListener( 'keydown', function ( event ) {
				var dir = 0;
				if ( event.key === 'ArrowRight' ) {
					dir = document.dir === 'rtl' ? -1 : 1;
				} else if ( event.key === 'ArrowLeft' ) {
					dir = document.dir === 'rtl' ? 1 : -1;
				}
				if ( dir ) {
					event.preventDefault();
					var next = tabs[ ( index + dir + tabs.length ) % tabs.length ];
					activate( next );
					next.focus();
				}
			} );
		} );

		function showDesignFields( slug ) {
			root.querySelectorAll( '.pvd-editor__design-fields' ).forEach( function ( table ) {
				table.hidden = table.getAttribute( 'data-design' ) !== slug;
			} );
		}

		root.querySelectorAll( 'input[name="pvd[design]"]' ).forEach( function ( radio ) {
			radio.addEventListener( 'change', function () {
				if ( radio.checked ) {
					showDesignFields( radio.value );
				}
			} );
		} );
	} );
}() );
