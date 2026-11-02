/* Peyvanda box block (editor only — no front-end script). */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var __ = wp.i18n.__;
	var blockEditor = wp.blockEditor;
	var components = wp.components;
	var ServerSideRender = wp.serverSideRender;

	function useBoxes() {
		var state = useState( null );
		var boxes = state[ 0 ];
		var setBoxes = state[ 1 ];

		useEffect( function () {
			var alive = true;
			wp.apiFetch( { path: '/peyvanda/v1/boxes' } ).then( function ( result ) {
				if ( alive ) {
					setBoxes( result || [] );
				}
			} ).catch( function () {
				if ( alive ) {
					setBoxes( [] );
				}
			} );
			return function () {
				alive = false;
			};
		}, [] );

		return boxes;
	}

	function BoxSelect( props ) {
		var boxes = props.boxes;
		if ( null === boxes ) {
			return el( components.Spinner );
		}
		if ( ! boxes.length ) {
			return el( 'p', null, __( 'No boxes yet. Create one under Peyvanda → New box.', 'peyvanda' ) );
		}
		var options = [ { value: 0, label: __( '— Select a box —', 'peyvanda' ) } ].concat(
			boxes.map( function ( box ) {
				return {
					value: box.id,
					label: box.active ? box.name : box.name + ' ' + __( '(inactive)', 'peyvanda' ),
				};
			} )
		);
		return el( components.SelectControl, {
			label: __( 'Box', 'peyvanda' ),
			value: props.value,
			options: options,
			onChange: function ( value ) {
				props.onChange( parseInt( value, 10 ) || 0 );
			},
			__nextHasNoMarginBottom: true,
		} );
	}

	wp.blocks.registerBlockType( 'peyvanda/box', {
		edit: function ( props ) {
			var boxes = useBoxes();
			var boxId = props.attributes.boxId;
			var blockProps = blockEditor.useBlockProps();
			var setBox = function ( id ) {
				props.setAttributes( { boxId: id } );
			};

			var inspector = el(
				blockEditor.InspectorControls,
				null,
				el( components.PanelBody, { title: __( 'Settings', 'peyvanda' ) },
					el( BoxSelect, { boxes: boxes, value: boxId, onChange: setBox } )
				)
			);

			var body = boxId ?
				el( components.Disabled, null,
					el( ServerSideRender, {
						block: 'peyvanda/box',
						attributes: props.attributes,
						EmptyResponsePlaceholder: function () {
							return el( components.Placeholder, {
								icon: 'screenoptions',
								label: __( 'Peyvanda box', 'peyvanda' ),
								instructions: __( 'This box has nothing to show here yet (no matching items, or the box is inactive).', 'peyvanda' ),
							} );
						},
					} )
				) :
				el( components.Placeholder, {
					icon: 'screenoptions',
					label: __( 'Peyvanda box', 'peyvanda' ),
					instructions: __( 'Choose which box to show here.', 'peyvanda' ),
				}, el( BoxSelect, { boxes: boxes, value: boxId, onChange: setBox } ) );

			return el( 'div', blockProps, inspector, body );
		},
		save: function () {
			return null;
		},
	} );
}( window.wp ) );
