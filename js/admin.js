/* global wp, _ */

// Make sure the wp object exists.
window.wp = window.wp || {};

( function( $ ) {

	var wpUserMedia = wpUserMedia || _.extend( {}, _.pick( window.wp, 'Backbone', 'template' ) );
	    wpUserMedia.Models      = wpUserMedia.Models || {};
		wpUserMedia.Collections = wpUserMedia.Collections || {};
		wpUserMedia.Views       = wpUserMedia.Views || {};

	// Extend wp.Backbone.View with .prepare() and .inject()
	wpUserMedia.View = wpUserMedia.Backbone.View.extend( {
		inject: function( selector ) {
			this.render();
			$(selector).html( this.el );
			this.views.ready();
		},

		prepare: function() {
			if ( ! _.isUndefined( this.model ) && _.isFunction( this.model.toJSON ) ) {
				return this.model.toJSON();
			} else {
				return {};
			}
		}
	} );

	wpUserMedia.Views.User = wpUserMedia.View.extend( {
		tagName:    'li',
		className:  'user',
		template: wpUserMedia.template( 'wp-user-media-user' )
	} );

	wpUserMedia.Views.Users = wpUserMedia.View.extend( {
		tagName:   'ul',
		className: 'users',

		initialize: function() {
			this.collection.on( 'add', this.addUserView, this );
		},

		addUserView: function( user ) {
			this.views.add( new wpUserMedia.Views.User( { model: user } ) );
		}
	} );

	wpUserMedia.App = {
		init: function() {
			this.views = new Backbone.Collection();
			this.users = new wp.api.collections.Users();

			this.displayUsers();
			this.users.fetch();
		},

		displayUsers: function() {
			var users = new wpUserMedia.Views.Users( {
				collection: this.users
			} );

			// Add it to views
			this.views.add( { id: 'users_view', view: users } );

			// Display it
			users.inject( '#wp-user-media-container' );
		},
	};

	wp.api.loadPromise.done( function() {
		wpUserMedia.App.init();
	} );

} )( jQuery );
