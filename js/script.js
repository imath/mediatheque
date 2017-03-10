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

} )( wp, jQuery );;
