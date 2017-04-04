/* global wp, _ */

// Make sure the wp object exists.
window.wp = window.wp || {};
window.wpUserMedia = window.wpUserMedia || _.extend( {}, _.pick( window.wp, 'Backbone', 'template' ) );

( function( $ ) {

	wpUserMedia.Models      = wpUserMedia.Models || {};
	wpUserMedia.Collections = wpUserMedia.Collections || {};
	wpUserMedia.Views       = wpUserMedia.Views || {};

	wpUserMedia.App = {
		init: function( restUrl ) {
			this.views        = new Backbone.Collection();
			this.users        = new wp.api.collections.Users();
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

			var rootView = new wpUserMedia.Views.Root( {
				el:           $( '#wp-user-media-container' ),
				users:        this.users,
				media:        this.userMedia,
				overrides:    this.overrides,
				toolbarItems: this.toolbarItems,
				queryVars:    this.queryVars,
				trailItems:   this.trailItems
			} ).render();
		}
	};

	wp.api.loadPromise.done( function( api ) {
		var restUrl;

		if ( api.get( 'apiRoot' ) && api.get( 'versionString' ) ) {
			restUrl = api.get( 'apiRoot' ) + api.get( 'versionString' ) + 'user-media';
		}

		wpUserMedia.App.init( restUrl );
	} );

} )( jQuery );
