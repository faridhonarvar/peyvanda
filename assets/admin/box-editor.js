/* Peyvanda — box editor: tabs, design fields and live preview (admin only). */
( function () {
	'use strict';

	var cfg = window.peyvandaBoxEditor || { i18n: {} };
	var t = cfg.i18n;

	/* Turns "pvd[style][thumb-list][gap]" form fields into a nested object. */
	function collectFields( form ) {
		var out = {};
		new window.FormData( form ).forEach( function ( value, name ) {
			if ( 0 !== name.indexOf( 'pvd[' ) ) {
				return;
			}
			var keys = name.slice( 3 ).match( /\[([^\]]*)\]/g ).map( function ( k ) {
				return k.slice( 1, -1 );
			} );
			var node = out;
			keys.forEach( function ( key, i ) {
				var last = i === keys.length - 1;
				if ( '' === key ) {
					return;
				}
				var nextIsList = ! last && '' === keys[ i + 1 ];
				if ( last ) {
					node[ key ] = value;
				} else if ( nextIsList ) {
					node[ key ] = node[ key ] || [];
					node[ key ].push( value );
				} else {
					node[ key ] = node[ key ] || {};
					node = node[ key ];
				}
			} );
		} );
		return out;
	}

	var loadedStyles = {};
	function loadStyles( urls ) {
		( urls || [] ).forEach( function ( url ) {
			if ( loadedStyles[ url ] ) {
				return;
			}
			loadedStyles[ url ] = true;
			var link = document.createElement( 'link' );
			link.rel = 'stylesheet';
			link.href = url;
			document.head.appendChild( link );
		} );
	}

	function initTabs( root ) {
		var tabs = Array.prototype.slice.call( root.querySelectorAll( '.pvd-editor__tab' ) );

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
			root.dispatchEvent( new window.CustomEvent( 'pvd:tab', { detail: tab.id } ) );
		}

		tabs.forEach( function ( tab, index ) {
			tab.tabIndex = 0 === index ? 0 : -1;
			tab.addEventListener( 'click', function () {
				activate( tab );
			} );
			tab.addEventListener( 'keydown', function ( event ) {
				var rtl = 'rtl' === document.dir;
				var dir = 0;
				if ( 'ArrowRight' === event.key ) {
					dir = rtl ? -1 : 1;
				} else if ( 'ArrowLeft' === event.key ) {
					dir = rtl ? 1 : -1;
				}
				if ( dir ) {
					event.preventDefault();
					var next = tabs[ ( index + dir + tabs.length ) % tabs.length ];
					activate( next );
					next.focus();
				}
			} );
		} );
	}

	function initDesignFields( root ) {
		root.querySelectorAll( 'input[name="pvd[design]"]' ).forEach( function ( radio ) {
			radio.addEventListener( 'change', function () {
				if ( ! radio.checked ) {
					return;
				}
				root.querySelectorAll( '.pvd-editor__design-fields' ).forEach( function ( table ) {
					table.hidden = table.getAttribute( 'data-design' ) !== radio.value;
				} );
			} );
		} );
	}

	function initPreview( root ) {
		var form = document.getElementById( 'post' );
		var preview = root.querySelector( '.pvd-preview' );
		if ( ! form || ! preview || ! window.wp || ! window.wp.apiFetch ) {
			return;
		}
		var page = preview.querySelector( '.pvd-preview__page' );
		var viewport = preview.querySelector( '.pvd-preview__viewport' );
		var slot = preview.querySelector( '.pvd-preview__slot' );
		var status = preview.querySelector( '.pvd-preview__status' );
		var sample = preview.querySelector( '.pvd-preview__sample' );
		var boxId = parseInt( root.getAttribute( 'data-box-id' ), 10 ) || 0;
		var width = 760;
		var timer = null;
		var thumbTimer = null;
		var request = 0;
		var thumbsDirty = true;

		page.dir = document.dir || 'ltr';

		function fit() {
			var available = viewport.clientWidth;
			var scale = Math.min( 1, available / width );
			page.style.width = width + 'px';
			page.style.transform = 'scale(' + scale + ')';
			viewport.style.height = Math.ceil( page.scrollHeight * scale ) + 'px';
		}

		function show( html ) {
			slot.innerHTML = '';
			if ( html ) {
				/* Box markup is generated and escaped by the plugin's own templates. */
				slot.innerHTML = html;
			} else {
				var empty = document.createElement( 'p' );
				empty.className = 'pvd-preview__empty';
				empty.textContent = t.empty;
				slot.appendChild( empty );
			}
			window.requestAnimationFrame( fit );
			slot.querySelectorAll( 'img' ).forEach( function ( img ) {
				if ( ! img.complete ) {
					img.addEventListener( 'load', fit );
				}
			} );
		}

		function refresh() {
			var current = ++request;
			preview.classList.add( 'is-loading' );
			status.textContent = t.updating;
			window.wp.apiFetch( {
				path: cfg.path,
				method: 'POST',
				data: { box_id: boxId, fields: collectFields( form ) },
			} ).then( function ( res ) {
				if ( current !== request ) {
					return;
				}
				loadStyles( res.styles );
				show( res.html );
				sample.textContent = res.sample ? t.sample.replace( '%s', res.sample ) : '';
				status.textContent = '';
				preview.classList.remove( 'is-loading' );
			} ).catch( function () {
				if ( current === request ) {
					status.textContent = t.error;
					preview.classList.remove( 'is-loading' );
				}
			} );
		}

		function refreshThumbs() {
			var panel = root.querySelector( '.pvd-editor__designs' );
			if ( ! panel || panel.closest( '[hidden]' ) || ! thumbsDirty ) {
				return;
			}
			thumbsDirty = false;
			window.wp.apiFetch( {
				path: cfg.path,
				method: 'POST',
				data: { box_id: boxId, fields: collectFields( form ), all: true },
			} ).then( function ( res ) {
				loadStyles( res.styles );
				Object.keys( res.designs || {} ).forEach( function ( slug ) {
					var holder = panel.querySelector( '.pvd-editor__thumb[data-design="' + slug + '"] .pvd-editor__thumb-inner' );
					if ( holder ) {
						holder.dir = document.dir || 'ltr';
						holder.innerHTML = res.designs[ slug ] || '';
					}
				} );
			} ).catch( function () {
				thumbsDirty = true;
			} );
		}

		function schedule( event ) {
			var target = event && event.target;
			if ( target && target.name && 0 !== target.name.indexOf( 'pvd[' ) ) {
				return;
			}
			window.clearTimeout( timer );
			timer = window.setTimeout( refresh, 350 );
			if ( ! target || 'pvd[design]' !== target.name ) {
				thumbsDirty = true;
				window.clearTimeout( thumbTimer );
				thumbTimer = window.setTimeout( refreshThumbs, 900 );
			}
		}

		root.addEventListener( 'input', schedule );
		root.addEventListener( 'change', schedule );
		root.addEventListener( 'pvd:tab', function () {
			window.setTimeout( refreshThumbs, 50 );
		} );

		preview.querySelectorAll( '.pvd-preview__device' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				preview.querySelectorAll( '.pvd-preview__device' ).forEach( function ( other ) {
					other.setAttribute( 'aria-pressed', other === button ? 'true' : 'false' );
				} );
				width = parseInt( button.getAttribute( 'data-width' ), 10 ) || 760;
				fit();
			} );
		} );

		if ( window.ResizeObserver ) {
			new window.ResizeObserver( fit ).observe( viewport );
		}
		window.addEventListener( 'load', fit );

		refresh();
		refreshThumbs();
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var root = document.querySelector( '.pvd-editor' );
		if ( ! root ) {
			return;
		}
		initTabs( root );
		initDesignFields( root );
		initPreview( root );
	} );
}() );
