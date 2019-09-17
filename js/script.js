/* global wp, _, mediaTheque, mediaThequeSettings, JSON */

// Make sure the wp object exists.
window.wp = window.wp || {};
window.mediaTheque = window.mediaTheque || _.extend( {}, _.pick( window.wp, 'Backbone', 'template' ) );

( function( exports, $ ) {

	_.extend( mediaTheque, _.pick( window.wp, 'media' ) );
	mediaTheque.post  = mediaTheque.media.view.MediaFrame.Post;
	mediaTheque.embed = mediaTheque.media.controller.Embed;

	wp.media.controller.Embed = mediaTheque.embed.extend( {
		initialize: function( options ) {
			var isMediaTheque = false, match;

			if ( options.metadata && ! _.isUndefined( options.metadata.url ) ) {
				match = options.metadata.url.match( /user-media\/(.*?)\/\?attached=true/ );

				if ( ! _.isNull( match ) && ! _.isUndefined( match[1] ) ) {
					isMediaTheque = true;

					var params = options.metadata || { url: '' };
					_.extend( params, { isMediaTheque: isMediaTheque } );

					this.props = new Backbone.Model( params );
				}
			}

			if ( ! isMediaTheque ) {

				// Call 'initialize' directly on the parent class.
				mediaTheque.embed.prototype.initialize.apply( this, arguments );
			}
		},

		activate: function() {
			// Call 'activate' directly on the parent class.
			mediaTheque.embed.prototype.activate.apply( this, arguments );

			if ( this.props.get( 'isMediaTheque' ) ) {
				/**
				 * Customize the title to inform the user can define
				 * display preferences for the user media
				 */
				this.set( { title: mediaThequeSettings.common.embedTitle } );

				/**
				 * Workaround to hide the main menu.
				 * @todo  improve this!
				 */
				this.frame.$el.addClass( 'mediatheque-hide-menu' );
			}
		}
	} );

	mediaTheque.media.controller.UserMedia = wp.media.controller.State.extend( {
		defaults: {
			id:       'user-media',
			title:    mediaThequeSettings.common.frameTitle,
			content:  'user-media',
			menu:     'default',
			toolbar:  'main-user-media',
			priority: 220
		},

		initialize: function() {
			this.set( 'userMediaSelection', new Backbone.Collection() );
		},

		// Temporarly disable the window-wide wpUploader
		activate: function() {
			$( '.media-frame-uploader' ).css( {
				display: 'none'
			} );

			if ( ! _.isUndefined( this.frame.uploader.uploader ) ) {
				this.dropElement = this.frame.uploader.uploader.uploader.getOption( 'drop_element' );
				this.frame.uploader.uploader.uploader.setOption( 'drop_element', '' );
			}
		},

		// Restore the window-wide wpUploader
		deactivate: function() {
			$( '.media-frame-uploader' ).css( {
				display: 'block'
			} );

			if ( ! _.isUndefined( this.frame.uploader.uploader ) ) {
				this.frame.uploader.uploader.uploader.setOption( 'drop_element', this.dropElement );
			}
		}
	} );

	mediaTheque.media.view.Toolbar.UserMedia = mediaTheque.media.view.Toolbar.Select.extend( {
		initialize: function() {
			_.defaults( this.options, {
				text: mediaThequeSettings.common.insertBtn,
				requires: false
			} );

			this.userMediaSelection = this.controller.state().get( 'userMediaSelection' );
			this.userMediaSelection.on( 'add remove reset', this.refresh, this );

			// Call 'initialize' directly on the parent class.
			mediaTheque.media.view.Toolbar.Select.prototype.initialize.apply( this, arguments );
		},

		refresh: function() {
			this.get( 'select' ).model.set( 'disabled', ! this.userMediaSelection.length );

			/**
			 * call 'refresh' directly on the parent class
			 */
			mediaTheque.media.view.Toolbar.Select.prototype.refresh.apply( this, arguments );
		}
	} );

	mediaTheque.media.view.mainUserMedia = mediaTheque.media.View.extend( {
		className: 'user-media-content',
		template : mediaTheque.template( 'mediatheque-main' ),

		initialize: function() {
			this.on( 'ready', this.loadApp, this );
		},

		loadApp: function() {
			var app = mediaTheque.App;

			if ( _.isUndefined( this.views._views[''] ) || ! this.views._views[''].length ) {
				this.views.add( new mediaTheque.Views.Root( {
					el:           $( '#mediatheque-container' ),
					media:        app.userMedia,
					overrides:    app.overrides,
					toolbarItems: app.toolbarItems,
					queryVars:    app.queryVars,
					trailItems:   app.trailItems,
					uiType:      'wp-editor',
					selection:    this.controller.state().get( 'userMediaSelection' )
				} ) );
			}
		}
	} );

	mediaTheque.media.view.customizeImage = mediaTheque.media.view.EmbedImage.extend( {
		className: 'user-media-preferences',
		template : mediaTheque.template( 'image-details' ),

		initialize: function() {
			var media = this.model.get( 'media' ), url = media.get( 'guid' ).rendered,
				fileName, mediaDetails = media.get( 'media_details');

			// Temporarly disable imageEdit.
			if ( window.imageEdit ) {
				this.imageEdit   = _.clone( window.imageEdit );
				window.imageEdit = false;
			}

			_.extend( mediaDetails.sizes, { full: {
				width       : mediaDetails.width,
				height      : mediaDetails.height,
				'mime_type' : media.get( 'mime_type'),
				file        : mediaDetails.file
			} } );

			this.options.attachment.set( {
				sizes: mediaDetails.sizes,
				url  : media.get( 'link' ) + mediaThequeSettings.common.downloadSlug + '/'
			}, { silent: true } );

			if ( mediaDetails.sizes.medium ) {
				fileName = mediaDetails.file.split( '/' );
				url = url.replace( fileName[ fileName.length - 1 ], mediaDetails.sizes.medium.file );
			}

			this.model.set( {
				url        : url,
				'base_url' : media.get( 'link' )
			}, { silent: true } );

			this.queryString = _.defaults(
				_.pick( this.model, ['align', 'size'] ),
				{
					attached: true
				}
			);

			mediaTheque.media.view.EmbedImage.prototype.initialize.apply( this, arguments );

			this.on( 'ready', this.setFormElements, this );
		},

		prepare: function() {
			var attachment = false;

			if ( this.options.attachment ) {
				attachment = this.options.attachment.toJSON();
			}

			return _.defaults( {
				model: this.model.toJSON(),
				attachment: attachment
			}, this.options );
		},

		setFormElements: function() {
			this.$el.find( '.caption, .alt-text, .advanced-toggle, #alt-text-description' ).css( { display: 'none' } );
			this.$el.find( 'input.link-to-custom' ).val( this.options.attachment.get( 'url' ) );
			this.$el.find( 'select.size' ).val( 'full' );
			this.$el.find( 'select.size option[value="full"]' ).prop( 'selected', 'selected' );
			this.$el.find( 'select.size option[value="custom"]' ).remove();
			this.$el.find( '.link-to select option[value="custom"]' ).remove();

			if ( this.imageEdit ) {
				window.imageEdit = _.clone( this.imageEdit );
				delete this.imageEdit;
			}
		},

		updateChanges: function( model ) {
			mediaTheque.media.view.EmbedImage.prototype.updateChanges.apply( this, arguments );

			_.extend( this.queryString, _.pick( model.attributes, ['align', 'link', 'size'] ) );
			this.model.metadata = {
				url: this.model.get( 'base_url' ) + '?' + $.param( this.queryString )
			};
		}
	} );

	mediaTheque.media.view.customizeUserMedia = mediaTheque.media.view.Settings.extend( {
		className: 'user-media-preferences',

		initialize: function() {
			var media = this.model.get( 'media' ), attr = {};
			this.options.query_keys = this.options.query_keys || ['align', 'preload', 'loop', 'autoplay'];

			// Video Template
			if ( 'video' === media.get( 'media_type' ) ) {
				this.template = mediaTheque.template( 'video-details' );
				_.extend( attr, _.pick( media.get( 'media_details'), ['width', 'height'] ) );

			// Audio Template
			} else if ( 'audio' === media.get( 'media_type' ) ) {
				this.template = mediaTheque.template( 'audio-details' );
			}

			_.defaults( attr, {
				src:         media.get( 'guid' ).rendered,
				'base_url' : media.get( 'link' )
			} );

			this.model.set( attr, { silent: true } );

			this.queryString = _.defaults(
				_.pick( this.model.attributes, this.options.query_keys ),
				{ attached: true }
			);

			mediaTheque.media.view.Settings.prototype.initialize.apply( this, arguments );

			if ( -1 !== _.indexOf( ['video', 'audio'], media.get( 'media_type' ) ) ) {
				this.on( 'ready', this.setFormElements, this );
			}
		},

		setFormElements: function() {
			var media = this.model.get( 'media' );

			if ( 'video' === media.get( 'media_type' ) ) {
				this.$el.find( '.wp-video-holder .setting' ).first().remove();
				this.$el.find( '[data-setting="content"]' ).remove();
				this.$el.find( '.setting' ).first().remove();

				var alignButtons = _.chain( mediaThequeSettings.common.alignBtns )
					.map( function( label, value ) {
						return $( '<button></button>' ).addClass( 'button' ).val( value ).html( label );
					} )
					.value();

				this.$el.find( '.embed-video-settings' ).append(
					$( '<div></div>' )
						.addClass( 'setting align' )
						.html(
							$( '<div></div>' ).addClass( 'button-group button-large' ).attr( 'data-setting', 'align' ).html( _.each( alignButtons, function( button ) {
									return $( button ).html();
								} )
							)
						)
						.prepend( $( '<span></span>' ).html( mediaThequeSettings.common.alignLabel ) )
				);
			} else {
				this.$el.find( 'audio' ).css( { visibility: 'visible' } );
				this.$el.find( '.embed-audio-settings label.setting' ).first().remove();
				this.$el.find( '.embed-audio-settings div.setting' ).first().remove();
			}
		},

		updateChanges: function( model ) {
			mediaTheque.media.view.Settings.prototype.updateChanges.apply( this, arguments );

			_.extend( this.queryString, _.pick( model.attributes, this.options.query_keys ) );

			// Loop to update checkboxes.
			_.each( this.options.query_keys, function( key ) {
				if ( ! model.get( key ) ) {
					this.$el.find( '[data-setting="' + key + '"]' ).prop( 'checked', false );
				}
			}, this );

			this.model.metadata = {
				url: this.model.get( 'base_url' ) + '?' + $.param( this.queryString )
			};
		}
	} );

	mediaTheque.media.view.customizeFile = mediaTheque.media.view.customizeUserMedia.extend( {
		initialize: function() {
			var o = this.options || {}, position = 0, media = o.model.get( 'media' ),
			    fields = o.fields || [], query_vars = {};

			this.options.query_keys = [];
			this.collection = new Backbone.Collection();

			mediaTheque.media.view.customizeUserMedia.prototype.initialize.apply( this, arguments );

			if ( this.model.props && this.model.props.get( 'url' ) ) {
				query_vars = mediaTheque.App.getURLparams( this.model.props.get( 'url' ) );
			}

			this.views.add( new mediaTheque.View( {
				id:        'mediatheque-file-preferences',
				className: 'media-embed media-embed-details'
			} ) );

			this.views.add( '#mediatheque-file-preferences', new mediaTheque.View( { className: 'embed-media-settings' } ) );

			_.each( fields, function( field, id ) {
				position += 1;

				if ( field.validate && -1 === _.indexOf( field.validate, media.get( 'mime_type' ) ) ) {
					return;
				}

				this.collection.add( {
					id:       id,
					name:     field.name,
					caption:  field.caption || '',
					options:  field.options || [],
					type:     field.type || '',
					position: field.position || position,
					classes : field.classes || [ 'setting', id ],
					value   : ! _.isUndefined( query_vars[ id ] ) ? JSON.parse( query_vars[ id ] ) : false
				} );

				this.options.query_keys.push( id );

				this.addField( this.collection.get( id ) );
			}, this );
		},

		addField: function( field ) {
			this.views.add( '.embed-media-settings', new mediaTheque.Views.Field(
				{ model: field },
				{ at: field.position }
			) );
		}
	} );

	wp.media.view.MediaFrame.Post = mediaTheque.post.extend( {
		initialize: function() {
			_.extend( this.options, _.pick( mediaThequeSettings.common, 'isUserMediaOnly' ) );

			// Call 'initialize' directly on the parent class.
			mediaTheque.post.prototype.initialize.apply( this, arguments );
		},

		createStates: function() {
			var options = this.options, params = { priority: 20 },
			    states = [];

			if ( ! options.isUserMediaOnly ) {
				 params.priority = 220;

				// Call 'createStates' directly on the parent class.
				mediaTheque.post.prototype.createStates.apply( this, arguments );
			} else if ( ! options.gutenbergBlock ) {
				states.push( new wp.media.controller.Embed( { metadata: options.metadata } ) );
			} else {
				params.menu = false;
			}

			states.unshift( new mediaTheque.media.controller.UserMedia( params ) );

			this.states.add( states );
		},

		bindHandlers: function() {
			// Call 'bindHandlers' directly on the parent class.
			mediaTheque.post.prototype.bindHandlers.apply( this, arguments );

			if ( ! this.options.isUserMediaOnly ) {
				this.on( 'menu:render:default', this.menuSeparator, this );
			}

			this.on( 'toolbar:create:main-user-media', this.mainUserMediaToolbar, this );
			this.on( 'content:render:user-media',      this.userMediaContent,     this );

			this.state( 'user-media' ).on( 'select', this.insertUserMedia );
		},

		menuSeparator: function( view ) {
			view.set( {
				'wordpress-separator': new wp.media.View({
					className: 'separator',
					priority: 200
				} )
			} );
		},

		mainUserMediaToolbar: function( toolbar ) {
			toolbar.view = new mediaTheque.media.view.Toolbar.UserMedia( {
				controller: this
			} );
		},

		userMediaContent: function() {
			if ( this.options.isUserMediaOnly && ! _.isUndefined( this.uploader.uploader ) ) {
				this.uploader.uploader.uploader.setOption( 'drop_element', '' );
			}

			var view = new mediaTheque.media.view.mainUserMedia( {
				controller: this,
				model:      this.state()
			} ).render();

			this.content.set( view );
		},

		insertUserMedia: function() {
			var selection  = this.get( 'userMediaSelection' ),
				hasTinymce = typeof window.tinymce !== 'undefined',
				userMedia, link, title, output;

			if ( ! selection.length ) {
				return false;
			}

			userMedia = _.first( selection.models );
			link      = userMedia.get( 'link' );

			if ( 'publish' === userMedia.get( 'status' ) ) {
				link  += '?attached=true';

				if ( hasTinymce && window.tinymce.activeEditor && ! window.tinymce.activeEditor.isHidden() ) {
					output = '<p>' + link + '</p>';
				} else {
					output = "\n\n" + link + "\n\n"; // jshint ignore:line
				}
			} else {
				title = userMedia.get( 'title' ).rendered;

				output = wp.media.string.link( {
					linkUrl: link,
					title:   title
				} );
			}

			if ( this.frame.options.gutenbergBlock ) {
				var block = $( '.editor-block-list__block.is-selected .mediatheque-block' ).get( 0 );

				block.dataset.link = link;

				// Used for private UserMedia
				if ( title ) {
					block.dataset.title = title;
				}
			} else {
				mediaTheque.media.editor.insert( output );
			}
		},

		editMedia: function( collection ) {
			var media, queryString, state = this.state();

			if ( _.isUndefined( collection.models ) || collection.models.length !== 1 ) {
				return;
			}

			media       = _.first( collection.models );
			queryString = mediaTheque.App.getURLparams( state.props.get( 'url' ) );
			media.set( _.pick( queryString, ['size', 'align'] ) );
			state.set( { media: media } );

			if ( 'image' === media.get( 'media_type' ) ) {
				this.customizeImageDisplay();
			} else {
				this.customizeUserMediaDisplay();
			}
		},

		// Attach a specific view to let user choose some display preferences for images.
		customizeImageDisplay: function() {
			var state = this.state(),
				view = new mediaTheque.media.view.customizeImage( {
					model: state,
					attachment: state.get( 'media' ),
					controller: this,
					priority: 40
				} ).render();

			this.content.set( view );
		},

		// Attach a specific view to let user choose some display preferences for audio and video.
		customizeUserMediaDisplay: function() {
			var state = this.state(), view, media = state.get( 'media' );

			if ( -1 !== _.indexOf( ['video', 'audio'], media.get( 'media_type') ) ) {
				view = new mediaTheque.media.view.customizeUserMedia( {
					model: state,
					controller: this,
					priority: 40
				} ).render();
			} else {
				view = new mediaTheque.media.view.customizeFile( {
					model: state,
					fields: mediaThequeSettings.fields,
					controller: this,
					priority: 40
				} ).render();
			}

			this.content.set( view );
		},

		embedContent: function() {
			var state = this.state(), userMediaSlug, userMedia;

			if ( state.props.get( 'isMediaTheque' ) ) {
				userMediaSlug = _.first( _.map(
					state.props.get( 'url' )
						.replace( mediaTheque.App.getRootURL(), '' )
						.split( '?' ), function( part, i ) {
							if ( 0 === i ) {
								return part.replace( '/', '' );
							}
						}
				) );

				if ( userMediaSlug ) {
					userMedia = new wp.api.collections.UserMedia();
					userMedia.fetch( {
						data: { slug: userMediaSlug, 'user_media_context' : 'display-preferences' },
						success: _.bind( this.editMedia, this )
					} );
				}
			} else {
				// Call 'embedContent' directly on the parent class.
				mediaTheque.post.prototype.embedContent.apply( this, arguments );
			}
		},

		mainEmbedToolbar: function( toolbar ) {
			var params = { controller: this }, state = this.state();

			if ( state.props.get( 'isMediaTheque' ) ) {
				_.extend( params, { text: mediaThequeSettings.common.embedBtn } );
			}

			toolbar.view = new mediaTheque.media.view.Toolbar.Embed( params );
		}
	} );

	/**
	 * mediaTheque.media.personalAvatar
	 * @namespace
	 */
	mediaTheque.media.personalAvatar = {

		updateAvatar: function( model ) {
			var personalAvatarId = 0, me;

			if ( _.isObject( model ) ) {
				personalAvatarId = model.get( 'id' );
			}

			me    = new wp.api.models.UsersMe();
			me.save(
				{ meta: { 'personal_avatar': personalAvatarId } },
				{ success: _.bind( this.avatarUpdated, this ) }
			);
		},

		avatarUpdated: function( model ) {
			var prop = {}, avatar_urls = model.get( 'avatar_urls' ) || {};

			if ( ! avatar_urls[96] ) {
				return;
			}

			prop = { src: avatar_urls[96] };

			if ( ! avatar_urls[192] ) {
				prop.srcset = avatar_urls[96];
			} else {
				prop.srcset = avatar_urls[192];
			}

			$( '.user-profile-picture' ).find( 'img' ).first().prop( prop );

			$( '#mediabrary-remove-message' ).remove();
		},

		/**
		 * The Personal Avatar workflow
		 *
		 * @returns {wp.media.view.MediaFrame.Select} A media workflow.
		 */
		frame: function( editor ) {
			if ( this._frame ) {
				wp.media.frame = this._frame;
				return this._frame;
			}

			this._frame = mediaTheque.media( {
				state: 'user-media',
				states: [ new mediaTheque.media.controller.UserMedia() ]
			} );

			this.activeEditor = editor || 'personal_avatar';

			this._frame.on( 'toolbar:create:main-user-media', function( toolbar ) {
				toolbar.view = new mediaTheque.media.view.Toolbar.UserMedia( {
					controller: this,
					text:       mediaThequeSettings.common.avatarBtn
				} );
			}, this._frame );

			this._frame.on( 'content:render:user-media', function() {
				var view = new mediaTheque.media.view.mainUserMedia( {
						controller: this,
						model:      this.state()
					} ).render();

				this.content.set( view );

			}, this._frame );

			this._frame.on( 'uploader:ready', function() {
				if ( ! this.uploader.uploader.uploader.getOption( 'drop_element' ) ) {
					return;
				}

				$( '.media-frame-uploader' ).css( {
					display: 'none'
				} );

				this.uploader.uploader.uploader.setOption( 'drop_element', '' );
			}, this._frame );

			this._frame.state( 'user-media' ).on( 'select', this.select );
			return this._frame;
		},

		/**
		 * 'select' callback for Personal Avatar workflow, triggered when
		 *  the 'Insert Avatar' button is clicked in the media modal.
		 */
		select: function() {
			var selection = this.get( 'userMediaSelection' ), model;

			if ( ! selection.length ) {
				return false;
			}

			model = _.first( selection.models );

			mediaTheque.media.personalAvatar.updateAvatar( model );
		},

		/**
		 * Open the content media manager to the 'User Media' tab when
		 * the Personal Avatar is clicked.
		 */
		init: function() {
			$( '.user-profile-picture td p.description' ).after( $( '#personal-avatar-editor' ) );

			$( '#personal-avatar-editor' ).on( 'click', '.mediabrary-insert', function( event ) {
				event.preventDefault();
				// Stop propagation to prevent thickbox from activating.
				event.stopPropagation();

				var editor = $( event.currentTarget ).data( 'editor' );
				mediaTheque.media.personalAvatar.frame( editor ).open();

			} ).on( 'click', '.mediabrary-remove', function() {
				mediaTheque.media.personalAvatar.updateAvatar( 0 );
				return false;
			} );
		}
	};

	$( mediaTheque.media.personalAvatar.init );

	mediaTheque.media.lightEditor = {
		init: function() {

			if ( $( '.mediatheque-buttons' ).length ) {
				var editorId = $( '.mediatheque-buttons' ).data( 'editor' ), css = {},
				    editorTools = $( '.mediatheque-buttons' ).prev( '#wp-' + editorId + '-editor-tools' ).find( '.wp-editor-tabs' ).first();

				$( editorTools ).before( $( '.mediatheque-buttons:visible' ) );

				if ( $( '.mediatheque-insert .dashicons' ).prop( 'style' ).cssText ) {
					css.background = $( '.mediatheque-insert .dashicons' ).prop( 'style' ).cssText;
				}

				$( '.mediatheque-insert .dashicons' ).css( _.extend( css, {
					display:          'inline-block',
					width:            '18px',
					height:           '18px',
					'vertical-align': 'text-bottom',
					margin:           '0 2px'
				} ) );
			}

			$( document.body )
				.on( 'click', '.mediatheque-insert', function( event ) {
					var elem = $( event.currentTarget ),
						editor = elem.data( 'editor' ),
						options = {
							frame:          'post',
							state:          'user-media',
							title:           wp.media.view.l10n.addMedia,
							isUserMediaOnly: true
						};

					event.preventDefault();

					wp.media.editor.open( editor, options );

					$( '.media-frame-uploader' ).css( {
						display: 'none'
					} );
				} );
		}
	};

	$( mediaTheque.media.lightEditor.init );

	mediaTheque.App = {
		init: function( restUrl, rootUrl, restNonce ) {
			this.views        = new Backbone.Collection();
			this.userMedia    = new wp.api.collections.UserMedia();
			this.toolbarItems = new Backbone.Collection();
			this.queryVars    = new Backbone.Model();
			this.trailItems   = new Backbone.Collection();

			this.overrides = {
				url: restUrl,
				'file_data_name': 'mediatheque_upload',
				headers: {
					'X-WP-Nonce' : restNonce
				}
			};

			this.rootUrl = rootUrl.replace( 'wp-json', mediaThequeSettings.common.rootSlug );
		},

		getURLparams: function( url, param ) {
			var qs;

			if ( url ) {
				qs = ( -1 !== url.indexOf( '?' ) ) ? '?' + url.split( '?' )[1] : '';
			} else {
				qs = document.location.search;
			}

			if ( ! qs ) {
				return null;
			}

			var params = qs.replace( /(^\?)/, '' ).split( '&' ).map( function( n ) {
				return n = n.split( '=' ), this[n[0]] = n[1], this;
			}.bind( {} ) )[0];

			if ( param ) {
				return params[param];
			}

			return params;
		},

		getRootURL: function() {
			if ( mediaThequeSettings.common.networkRootUrl ) {
				this.rootUrl = mediaThequeSettings.common.networkRootUrl;
			}

			return this.rootUrl;
		}
	};

	wp.api.loadPromise.done( function( api ) {
		var restUrl, rootUrl, restNonce;

		if ( api.get( 'apiRoot' ) && api.get( 'versionString' ) ) {
			rootUrl = api.get( 'apiRoot' );
			restUrl = rootUrl + api.get( 'versionString' ) + 'user-media';
			restNonce = api.get( 'nonce' );

			// If Gutenberg is active, we have to set the nonce.
			if ( ! restNonce ) {
				restNonce = mediaThequeSettings.restNonce;
				api.set( 'nonce', restNonce );
			}
		}

		mediaTheque.App.init( restUrl, rootUrl, restNonce );
	} );

} )( wp, jQuery );
