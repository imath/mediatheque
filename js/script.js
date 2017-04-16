/* global wp, _ */

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

				if ( match.length && match[1] ) {
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

	mediaTheque.media.view.customizeUserMedia = mediaTheque.media.view.EmbedImage.extend( {
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
			this.$el.find( '.caption, .alt-text, .advanced-toggle' ).css( { display: 'none' } );
			this.$el.find( 'input.link-to-custom' ).val( this.options.attachment.get( 'url' ) );
			this.$el.find( 'select.size' ).val( 'full' );
			this.$el.find( 'select.size option[value="full"]' ).prop( 'selected', 'selected' );
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

	wp.media.view.MediaFrame.Post = mediaTheque.post.extend( {
		initialize: function() {
			// Call 'initialize' directly on the parent class.
			mediaTheque.post.prototype.initialize.apply( this, arguments );
		},

		createStates: function() {
			var priority = 20, options = this.options,
			    states = [];

			if ( ! options.isUserMediaOnly ) {
				priority = 220;

				// Call 'createStates' directly on the parent class.
				mediaTheque.post.prototype.createStates.apply( this, arguments );
			} else {
				states.push( new wp.media.controller.Embed( { metadata: options.metadata } ) )
			}

			states.unshift( new mediaTheque.media.controller.UserMedia( { priority: priority } ) );

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
			var selection = this.get( 'userMediaSelection' ),
			    userMedia = {};

			if ( ! selection.length ) {
				return false;
			}

			model = _.first( selection.models );

			if ( 'image' === model.get( 'media_type' ) ) {
				mediaTheque.media.editor.insert( '<p>' +  model.get( 'link' ) + '?attached=true' + '</p>' );
			}
		},

		editMedia: function( collection ) {
			var media, queryString, state = this.state();

			if ( _.isUndefined( collection.models) || collection.models.length !== 1 ) {
				return;
			}

			media       = _.first( collection.models );
			queryString = mediaTheque.App.getURLparams( state.props.get( 'url' ) );
			media.set( _.pick( queryString, ['size', 'align'] ) );

			if ( 'image' === media.get( 'media_type' ) ) {
				state.set( { media: media } );

				this.customizeUserMediaDisplay();
			}
		},

		// Attach a specific view to let user choose some display preferences.
		customizeUserMediaDisplay: function() {
			var state = this.state(),
				view = new mediaTheque.media.view.customizeUserMedia( {
					model: state,
					attachment: state.get( 'media' ),
					controller: this,
					priority: 40
				} ).render();

			this.content.set( view );
		},

		embedContent: function() {
			var state = this.state(), userMediaSlug, userMedia;

			if ( state.props.get( 'isMediaTheque' ) ) {
				userMediaSlug = _.first( _.map(
					state.props.get( 'url' )
						.replace( wp.api.utils.getRootUrl() + 'user-media/', '' )
						.split( '?' ), function( part, i ) {
							if ( 0 === i ) {
								return part.replace( '/', '' );
							}
						}
				) );

				if ( userMediaSlug ) {
					userMedia = new wp.api.collections.UserMedia();
					userMedia.fetch( {
						data: { slug: userMediaSlug },
						success: _.bind( this.editMedia, this )
					} );
				}
			} else {
				// Call 'embedContent' directly on the parent class.
				mediaTheque.post.prototype.embedContent.apply( this, arguments );
			}
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

			this.activeEditor = editor || 'peronal_avatar';

			this._frame.on( 'toolbar:create:main-user-media', function( toolbar ) {
				toolbar.view = new mediaTheque.media.view.Toolbar.UserMedia( {
					controller: this,
					text:       mediaThequeSettings.common.avatarBtn
				} );
			}, this._frame );

			this._frame.on( 'content:render:user-media', function() {
				var selection = this.state( 'user-media' ).get( 'selection' ),
					view = new mediaTheque.media.view.mainUserMedia({
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
			var selection = this.get( 'userMediaSelection' );

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
				var editorId = $( '.mediatheque-buttons' ).data( 'editor' );
				    editorTools = $( '.mediatheque-buttons' ).prev( '#wp-' + editorId + '-editor-tools' ).find( '.wp-editor-tabs' ).first();

				$( editorTools ).before( $( '.mediatheque-buttons:visible' ) );

				$( '.mediatheque-insert .dashicons' ).css( {
					display:          'inline-block',
					width:            '18px',
					height:           '18px',
					'vertical-align': 'text-bottom',
					margin:           '0 2px'
				} );
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
		init: function( restUrl ) {
			this.views        = new Backbone.Collection();
			this.userMedia    = new wp.api.collections.UserMedia();
			this.toolbarItems = new Backbone.Collection();
			this.queryVars    = new Backbone.Model();
			this.trailItems   = new Backbone.Collection();

			this.overrides = {
				url: restUrl,
				'file_data_name': 'mediatheque_upload',
				headers: {
					'X-WP-Nonce' : wpApiSettings.nonce
				}
			};
		},

		getURLparams: function( url, param ) {
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
		}
	};

	wp.api.loadPromise.done( function( api ) {
		var restUrl;

		if ( api.get( 'apiRoot' ) && api.get( 'versionString' ) ) {
			restUrl = api.get( 'apiRoot' ) + api.get( 'versionString' ) + 'user-media';
		}

		mediaTheque.App.init( restUrl );
	} );

} )( wp, jQuery );
