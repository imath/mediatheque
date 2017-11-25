/* global _, mediaThequeSettings, mediaThequeBlock */

( function( $, wp ) {
	var el                = wp.element.createElement,
	    registerBlockType = wp.blocks.registerBlockType,
	    InspectorControls = wp.blocks.InspectorControls,
	    BlockControls     = wp.blocks.BlockControls,
	    AlignmentToolbar  = wp.blocks.AlignmentToolbar,
	    EditToolbar       = wp.components.Toolbar;

	registerBlockType( 'mediatheque/usermedia', {

		// Block Title
		title: 'MediaThèque',

		// Block Icon
		icon: el( 'svg', {
			'aria-hidden': true,
			role: 'img',
			focusable: 'dashicons mediatheque-icon',
			width: '20',
			height: '20',
			viewBox: '0 0 20 20',
			xmlns: 'http://www.w3.org/2000/svg'
		}, [
				el( 'path', {
					key: 'camera',
					d: 'M 13 11 L 13 4 C 13 3.45 12.55 3 12 3 L 10.33 3 L 9 1 L 5 1 L 3.67 3 L 2 3 C 1.45 3 1 3.45 1 4 L 1 11 C 1 11.55 1.45 12 2 12 L 12 12 C 12.55 12 13 11.55 13 11 Z'
				} ),
				el( 'path', {
					key: 'sound',
					d: 'M 14 6 L 19 6 L 19 16.5 C 19 17.88 17.88 19 16.5 19 C 15.12 19 14 17.88 14 16.5 C 14 15.12 15.12 14 16.5 14 C 16.67 14 16.84 14.02 17 14.05 L 17 9 L 14 9 L 14 6 Z'
				} ),
				el( 'path', {
					key: 'user-head',
					d: 'M 7 4.5 C 8.38 4.5 9.5 5.62 9.5 7 C 9.5 8.38 8.38 9.5 7 9.5 C 5.62 9.5 4.5 8.38 4.5 7 C 4.5 5.62 5.62 4.5 7 4.5 Z',
					style: { fill: 'rgb(255, 255, 255)' }
				} ),
				el( 'path', {
					key: 'user-body',
					d: 'M 7.006 11.465 L 9.121 10.05 C 10.979 10.05 12.636 11.861 12.636 13.573 L 12.636 15.508 C 12.636 15.508 9.797 16.386 7.006 16.386 C 4.168 16.386 1.376 15.508 1.376 15.508 L 1.376 13.573 C 1.376 11.823 2.885 10.089 4.852 10.089 Z',
					style: { stroke: 'rgb(255, 255, 255)' }
				} )
			]
		),

		// Block Category
		category: 'common',

		// Block Attributes
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

			var outputUserMedia = function( usermedia ) {
				$( '#' + props.id ).parent().removeClass( 'components-placeholder' );
				$( '#' + props.id ).before( usermedia ).remove();
			};

			var requestUserMedia = function( link ) {
				wp.ajax.post( 'parse-embed', {
					post_ID: wp.media.view.settings.post.id,
					type: 'embed',
					shortcode: '[embed]' + link + '[/embed]'
				} )
				.done( function( response ) {
					var userMediaFetched = true;

					if ( response.body ) {
						userMediaFetched = $( response.body ).html();

						outputUserMedia( userMediaFetched );
					} else {
						// @todo output a generic error
					}

					// Avoids fetching more than once
					props.setAttributes( { userMediaFetched: userMediaFetched } );
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
							gutenbergBlock : true
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
							link: link
						} );

						var title = block.data( 'title' );
						if ( title ) {
							props.setAttributes( {
								title: title
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
							userMediaFetched: false
						} );
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
						'button', { type: 'button', id: props.id, className: 'mediatheque-block button button-large', onClick:selectUserMedia }, mediaThequeBlock.insertBtn
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
								key: 'label'
							}, mediaThequeBlock.alignmentLabel ),
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

			// Wait a few milliseconds before outputting the User Media.
			} else {
				window.setTimeout( function() {
					outputUserMedia( props.attributes.userMediaFetched );
				}, 500 );
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
										title: mediaThequeBlock.editTitle,
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
						style: { textAlign: props.attributes.alignment }
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

} )( jQuery, window.wp || {} );
