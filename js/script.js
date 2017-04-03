/* global wp, _ */

// Make sure the wp object exists.
window.wp = window.wp || {};

( function( exports, $ ) {

	var wpUserMedia      = wpUserMedia || _.extend( {}, _.pick( exports, 'Backbone', 'template', 'media' ) );
		wpUserMedia.post = wpUserMedia.media.view.MediaFrame.Post;

	wpUserMedia.media.controller.UserMedia = wp.media.controller.State.extend( {
		defaults: {
			id:       'user-media',
			title:    'User Media',
			content:  'user-media',
			menu:     'default',
			toolbar:  'main-user-media',
			priority: 220
		}
	} );

	wpUserMedia.media.view.Toolbar.UserMedia = wpUserMedia.media.view.Toolbar.Select.extend( {
		initialize: function() {
			_.defaults( this.options, {
				text: 'Insert the User Media',
				requires: false
			} );

			// Call 'initialize' directly on the parent class.
			wpUserMedia.media.view.Toolbar.Select.prototype.initialize.apply( this, arguments );
		},

		refresh: function() {
			/**
			 * call 'refresh' directly on the parent class
			 */
			wpUserMedia.media.view.Toolbar.Select.prototype.refresh.apply( this, arguments );
		}
	} );

	wpUserMedia.media.view.mainUserMedia = wpUserMedia.media.View.extend( {
		className: 'user-media-content',
		template : wpUserMedia.template( 'user-media-main' )
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
			var view = new wpUserMedia.media.view.mainUserMedia({
				controller: this,
				model:      this.state()
			}).render();

			this.content.set( view );
		}
	} );

	/**
	 * wpUserMedia.media.personalAvatar
	 * @namespace
	 */
	wpUserMedia.media.personalAvatar = {

		set: function( id ) {
			$( '#' + this.activeEditor ).val( id );
		},

		remove: function() {
			$( '#' + this.activeEditor ).val( -1 );
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
				this.createSelectToolbar( toolbar, {
					text: 'Insert Avatar'
				});

			}, this._frame );

			this._frame.on( 'content:render:user-media', function() {
				var selection = this.state( 'user-media' ).get( 'selection' ),
					view = new wpUserMedia.media.view.mainUserMedia({
						controller: this,
						model:      this.state()
					} ).render();

				this.content.set( view );

			}, this._frame );

			this._frame.state( 'user-media' ).on( 'select', this.select );
			return this._frame;
		},

		/**
		 * 'select' callback for Personal Avatar workflow, triggered when
		 *  the 'Insert Avatar' button is clicked in the media modal.
		 */
		select: function() {
			var selection = 'avatar';

			wpUserMedia.media.personalAvatar.set( selection ? selection : -1 );
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
				wpUserMedia.media.personalAvatar.remove();
				return false;
			} );
		}
	};

	$( wpUserMedia.media.personalAvatar.init );

} )( wp, jQuery );
