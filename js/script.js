// Make sure the wp object exists.
window.wp = window.wp || {};

( function( $ ) {

	var wpUserMedia      = wpUserMedia || {};
		_.extend( wpUserMedia, _.pick( window.wp, 'Backbone', 'template' ) );

	console.log( wpUserMedia );

} )( jQuery );
