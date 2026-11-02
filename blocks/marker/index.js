/* Peyvanda marker block (editor only — renders an invisible placeholder on the front end). */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;

	wp.blocks.registerBlockType( 'peyvanda/marker', {
		edit: function () {
			var blockProps = wp.blockEditor.useBlockProps( { className: 'pvd-marker-placeholder' } );
			return el( 'div', blockProps,
				el( 'strong', null, __( 'Peyvanda', 'peyvanda' ) ),
				el( 'span', null, __( 'The automatic related box will appear here instead of after paragraph N.', 'peyvanda' ) )
			);
		},
		save: function () {
			return null;
		},
	} );
}( window.wp ) );
