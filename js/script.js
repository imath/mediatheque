/* global wp, _ */

// Make sure the wp object exists.
window.wp = window.wp || {};
window.wpUserMedia = window.wpUserMedia || _.extend( {}, _.pick( window.wp, 'Backbone', 'template' ) );

( function( exports, $ ) {

	_.extend( wpUserMedia, _.pick( window.wp, 'media' ) );
	wpUserMedia.post = wpUserMedia.media.view.MediaFrame.Post;

	wpUserMedia.media.controller.UserMedia = wp.media.controller.State.extend( {
		defaults: {
			id:       'user-media',
			title:    'User Media',
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

	wpUserMedia.media.view.Toolbar.UserMedia = wpUserMedia.media.view.Toolbar.Select.extend( {
		initialize: function() {
			_.defaults( this.options, {
				text: 'Insert the User Media',
				requires: false
			} );

			this.userMediaSelection = this.controller.state().get( 'userMediaSelection' );
			this.userMediaSelection.on( 'add remove reset', this.refresh, this );

			// Call 'initialize' directly on the parent class.
			wpUserMedia.media.view.Toolbar.Select.prototype.initialize.apply( this, arguments );
		},

		refresh: function() {
			this.get( 'select' ).model.set( 'disabled', ! this.userMediaSelection.length );

			/**
			 * call 'refresh' directly on the parent class
			 */
			wpUserMedia.media.view.Toolbar.Select.prototype.refresh.apply( this, arguments );
		}
	} );

	wpUserMedia.media.view.mainUserMedia = wpUserMedia.media.View.extend( {
		className: 'user-media-content',
		template : wpUserMedia.template( 'user-media-main' ),

		initialize: function() {
			this.on( 'ready', this.loadApp, this );
		},

		loadApp: function() {
			var app = wpUserMedia.App;

			if ( _.isUndefined( this.views._views[''] ) || ! this.views._views[''].length ) {
				this.views.add( new wpUserMedia.Views.Root( {
					el:           $( '#wp-user-media-container' ),
					media:        app.userMedia,
					overrides:    app.overrides,
					toolbarItems: app.toolbarItems,
					queryVars:    app.queryVars,
					trailItems:   app.trailItems,
					context:      'wp-editor',
					selection:    this.controller.state().get( 'userMediaSelection' )
				} ) );
			}
		}
	} );

	wp.media.view.MediaFrame.Post = wpUserMedia.post.extend( {
		initialize: function() {
			// Call 'initialize' directly on the parent class.
			wpUserMedia.post.prototype.initialize.apply( this, arguments );
		},

		createStates: function() {
			// Call 'createStates' directly on the parent class.
			wpUserMedia.post.prototype.createStates.apply( this, arguments );

			this.states.add(
				new wpUserMedia.media.controller.UserMedia()
			);
		},

		bindHandlers: function() {
			// Call 'bindHandlers' directly on the parent class.
			wpUserMedia.post.prototype.bindHandlers.apply( this, arguments );

			this.on( 'menu:render:default',            this.menuSeparator,        this );
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
			toolbar.view = new wpUserMedia.media.view.Toolbar.UserMedia( {
				controller: this
			} );
		},

		userMediaContent: function() {
			var view = new wpUserMedia.media.view.mainUserMedia( {
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
				_.defaults( userMedia, {
					title:   model.get( 'title' ).rendered,
					linkUrl: model.get( 'link' ),
					align:   'none',
					url:     model.get( 'background' )
				} );

				wpUserMedia.media.editor.insert( wpUserMedia.media.string.image( userMedia ) );
			}
		}
	} );

	/**
	 * wpUserMedia.media.personalAvatar
	 * @namespace
	 */
	wpUserMedia.media.personalAvatar = {

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

			this._frame = wpUserMedia.media( {
				state: 'user-media',
				states: [ new wpUserMedia.media.controller.UserMedia() ]
			} );

			this.activeEditor = editor || 'peronal_avatar';

			this._frame.on( 'toolbar:create:main-user-media', function( toolbar ) {
				toolbar.view = new wpUserMedia.media.view.Toolbar.UserMedia( {
					controller: this,
					text:       'Set Avatar'
				} );
			}, this._frame );

			this._frame.on( 'content:render:user-media', function() {
				var selection = this.state( 'user-media' ).get( 'selection' ),
					view = new wpUserMedia.media.view.mainUserMedia({
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

			wpUserMedia.media.personalAvatar.updateAvatar( model );
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
				wpUserMedia.media.personalAvatar.frame( editor ).open();

			} ).on( 'click', '.mediabrary-remove', function() {
				wpUserMedia.media.personalAvatar.updateAvatar( 0 );
				return false;
			} );
		}
	};

	$( wpUserMedia.media.personalAvatar.init );

	wpUserMedia.App = {
		init: function( restUrl ) {
			this.views        = new Backbone.Collection();
			this.userMedia    = new wp.api.collections.UserMedia();
			this.toolbarItems = new Backbone.Collection();
			this.queryVars    = new Backbone.Model();
			this.trailItems   = new Backbone.Collection();

			this.overrides = {
				url: restUrl,
				'file_data_name': 'wp_user_media_upload',
				headers: {
					'X-WP-Nonce' : wpApiSettings.nonce
				}
			};
		}
	};

	wp.api.loadPromise.done( function( api ) {
		var restUrl;

		if ( api.get( 'apiRoot' ) && api.get( 'versionString' ) ) {
			restUrl = api.get( 'apiRoot' ) + api.get( 'versionString' ) + 'user-media';
		}

		wpUserMedia.App.init( restUrl );
	} );

} )( wp, jQuery );
