/* global wp, _, mediaTheque, wpApiSettings */

// Make sure the wp object exists.
window.wp = window.wp || {};
window.mediaTheque = window.mediaTheque || _.extend( {}, _.pick( window.wp, 'Backbone', 'template' ) );

( function( $ ) {

	mediaTheque.Models      = mediaTheque.Models || {};
	mediaTheque.Collections = mediaTheque.Collections || {};
	mediaTheque.Views       = mediaTheque.Views || {};

	mediaTheque.App = {
		init: function( restUrl ) {
			this.views        = new Backbone.Collection();
			this.users        = new wp.api.collections.Users();
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

			this.rootView = new mediaTheque.Views.Root( {
				el:           $( '#mediatheque-container' ),
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

		mediaTheque.App.init( restUrl );
	} );

} )( jQuery );
