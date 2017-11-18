/* global wp, _, mediaTheque */

// Make sure the wp object exists.
window.wp = window.wp || {};
window.mediaTheque = window.mediaTheque || _.extend( {}, _.pick( window.wp, 'Backbone', 'template' ) );

( function( $ ) {
	var el = wp.element.createElement,
	    registerBlockType = wp.blocks.registerBlockType;

	registerBlockType( 'mediatheque/usermedia', {
		title: 'MediaThèque',
		icon: 'admin-media',
		category: 'common',
		attributes: { output: { type: 'string' } },

		edit: function( props ) {
			var selectUserMedia = function( event ) {
				event.preventDefault();

				var block   = $( event.currentTarget ),
					options = {
						frame:           'post',
						state:           'user-media',
						title:           wp.media.view.l10n.addMedia,
						isUserMediaOnly: true,
						gutenbergBlock : block,
					};

				mediaThequeSettings.common.isUserMediaOnly = true;

				// Launch the WordPress Media Editor
				wp.media.editor.open( '.gutenberg', options );

				$( '.media-frame-uploader' ).css( {
					display: 'none'
				} );

				wp.media.frame.on( 'select', function() {
					if ( block.data( 'output' ) && ! props.attributes.output ) {
						props.setAttributes( {
							output: block.data( 'output' ),
						} );

						return el( 'figure', { className: props.className }, props.attributes.output.replace( '<p>', "\n" ).replace( '</p>', "\n" ) );
					}
				} );
			}

			if ( ! props || ! props.attributes.output ) {
				return el(
					'div', {
						className: 'components-placeholder'
					}, el(
						'button', { type: 'button', className: 'mediatheque-block button button-large', onClick:selectUserMedia }, 'Insérer un Media Utilisateur.'
					)
				);
			}

			return el( 'figure', { className: props.className }, props.attributes.output.replace( '<p>', "\n" ).replace( '</p>', "\n" ) );
		},

		save: function( props ) {
			if ( ! props || ! props.attributes.output ) {
				return;
			}

			return el( 'figure', { className: props.className }, props.attributes.output.replace( '<p>', "\n" ).replace( '</p>', "\n" ) );
		}
	} );

} )( jQuery );
