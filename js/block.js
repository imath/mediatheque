/* global wp, _, mediaTheque */

// Make sure the wp object exists.
window.wp = window.wp || {};
window.mediaTheque = window.mediaTheque || _.extend( {}, _.pick( window.wp, 'Backbone', 'template' ) );

( function( $ ) {
	var el = wp.element.createElement,
	    registerBlockType = wp.blocks.registerBlockType,
	    InspectorControls = wp.blocks.InspectorControls,
	    BlockControls     = wp.blocks.BlockControls,
	    AlignmentToolbar  = wp.blocks.AlignmentToolbar,
	    EditToolbar       = wp.components.Toolbar;

	registerBlockType( 'mediatheque/usermedia', {
		title: 'MediaThèque',
		icon: 'admin-media',
		category: 'common',
		attributes: {
			link: {
				type: 'string'
			},
			title: {
				type: 'string'
			},
			alignment: {
				type: 'string'
			}
		},

		edit: function( props ) {
			var alignment = props.attributes.alignment,
			    focus     = props.focus;

			var requestUserMedia = function( link ) {
				wp.ajax.post( 'parse-embed', {
					post_ID: wp.media.view.settings.post.id,
					type: 'embed',
					shortcode: '[embed]' + link + '[/embed]'
				} )
				.done( function( response ) {
					// Avoids fetching more than once
					props.setAttributes( { userMediaFetched: true } );

					if ( response.body ) {
						$( '#' + props.id ).parent().removeClass( 'components-placeholder' );
						$( '#' + props.id ).before( $( response.body ).html() ).remove();
					} else {
						// @todo output a generic error
					}
				} )
				.fail( function( response ) {
					// Avoids fetching more than once
					props.setAttributes( { userMediaFetched: true } );

					// @todo output the response error.
				} );
			};

			var selectUserMedia = function( event ) {
				event.preventDefault();

				var block = $( event.currentTarget ),
				    options = {
							frame:           'post',
							state:           'user-media',
							title:           wp.media.view.l10n.addMedia,
							isUserMediaOnly: true,
							gutenbergBlock : true,
						};

				/**
				 * Overrides to make sure:
				 * - only the User Media UI is loaded by disabling the router.
				 * - the Drag & Drop is disabled to avoid Gutenberg to duplicate User Media
				 * in regular media.
				 */
				mediaThequeSettings.common.isUserMediaOnly = true;
				mediaThequeSettings.params = _.omit( mediaThequeSettings.params, 'dropzone' );

				// Launch the WordPress Media Editor
				wp.media.editor.open( '.gutenberg', options );

				$( '.media-frame-uploader' ).css( {
					display: 'none'
				} );

				wp.media.frame.on( 'select', function() {
					if ( block.data( 'link' ) && ! props.attributes.link ) {
						var link = block.data( 'link' );

						props.setAttributes( {
							link: link,
						} );

						var title = block.data( 'title' );
						if ( title ) {
							props.setAttributes( {
								title: title,
							} );
						}
					}
				} );
			};

			var onChangeAlignment = function( newAlignment ) {
				props.setAttributes( { alignment: newAlignment } );
			};

			var onClickEdit = function() {
				var frame = wp.media.embed.edit( props.attributes.link, true );

				frame.state( 'embed' ).props.on( 'change:url', function( model, url ) {
					if ( url && model.get( 'url' ) ) {
						frame.state( 'embed' ).metadata = model.toJSON();
					}
				} );

				frame.state( 'embed' ).on( 'select', function() {
					var data = frame.state( 'embed' ).metadata,
					    placeholder = $( '.editor-visual-editor__block.is-selected .editor-visual-editor__block-edit div' );

					if ( data && data.url !== props.attributes.link ) {
						/**
						 * @todo Check if this can be improved Using the wp.element once.
						 */
						placeholder
							.addClass( 'components-placeholder' )
							.html( $( '<div></div>' )
								.addClass( 'wp-block-embed is-loading' )
								.html( $('<span></span>' )
									.addClass( 'spinner is-active' )
								)
								.prop( 'id', props.id )
							);

						props.setAttributes( {
							link: data.url,
						} );

						requestUserMedia( props.attributes.link );
					}
				} );

				frame.on( 'close', function() {
					frame.detach();
				} );

				frame.open();
			};

			// No User Media were inserted yet.
			if ( ! props || ! props.attributes.link ) {
				return el(
					'div', {
						className: 'components-placeholder'
					}, el(
						'button', { type: 'button', id: props.id, className: 'mediatheque-block button button-large', onClick:selectUserMedia }, 'Insérer un Media Utilisateur.'
					)
				);

			// It's a private User Media.
			} else if ( props.attributes.title ) {
				return [
					!! focus && el(
						InspectorControls,
						{ key: 'controls' },
						[
							el( 'h3', {
								key: 'label',
							}, 'User Media Alignment' ),
							el(
								AlignmentToolbar,
								{
									key: 'aligncontrol',
									value: alignment,
									onChange: onChangeAlignment
								}
							)
						]
					),
					el(
						'p', {
							key: 'editable',
							className: 'mediatheque-private',
							focus: focus,
							style: { textAlign: alignment },
							onFocus: props.setFocus
						}, el(
							'a', {
								href: props.attributes.link
							},
							 props.attributes.title
						)
					)
				];

			// It's a public User Media, fetch the output.
			} else if ( ! props.attributes.userMediaFetched ) {
				requestUserMedia( props.attributes.link );
			}

			// Output the public User Media.
			return el(
				'div', {
					className: 'components-placeholder'
				}, [
					el(
						'div', {
							id: props.id,
							key: 'loading',
							className: 'wp-block-embed is-loading'
						}, el( 'span', {
							className: 'spinner is-active'
						} )
					),
					!! focus && el(
						BlockControls,
						{ key: 'controls' },
						el(
							EditToolbar,
							{
								controls: [
									{
										icon: 'edit',
										title: 'Edit',
										onClick: onClickEdit
									}
								]
							}
						)
					)
				]
			);
		},

		save: function( props ) {
			if ( ! props || ! props.attributes.link ) {
				return;
			}

			// Content to save for a Private User Media
			if ( props.attributes.title ) {
				return el(
					'p', {
						className: 'mediatheque-private',
						style: { textAlign: props.attributes.alignment },
					}, el(
						'a', {
							href: props.attributes.link
						},
						 props.attributes.title
					)
				);
			}

			// Content to save for a Public User Media
			return '<p>' + props.attributes.link + '</p>';
		}
	} );

} )( jQuery );
