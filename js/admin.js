/* global wp, _ */

// Make sure the wp object exists.
window.wp = window.wp || {};

( function( $ ) {

	var wpUserMedia = wpUserMedia || _.extend( {}, _.pick( window.wp, 'Backbone', 'template' ) );
	    wpUserMedia.Models      = wpUserMedia.Models || {};
		wpUserMedia.Collections = wpUserMedia.Collections || {};
		wpUserMedia.Views       = wpUserMedia.Views || {};

	// Create a very generic Model for files
	wpUserMedia.Models.File = Backbone.Model.extend( {
		file: {}
	} );

	wpUserMedia.Uploader = function( options ) {
		var self = this, overrides;

		if ( options.overrides ) {
			overrides = options.overrides;
			delete options.overrides;
		}

		wp.Uploader.call( this, options );

		if ( overrides ) {
			_.each( overrides, function( prop, key ) {
				self.uploader.settings[ key ] = prop;

				if ( key === 'headers' ) {
					delete self.uploader.settings.multipart_params['_wpnonce'];
					_.extend( self.uploader.settings.multipart_params, prop, { action: 'upload_user_media' } );
				}
			} );
		}

		this.filesQueue    = new Backbone.Collection();
		this.filesUploaded = new Backbone.Collection();
		this.filesError    = new Backbone.Collection();

		// Unbind all Plupload events from the WP Uploader.
		this.uploader.unbind( 'FilesAdded, UploadProgress, FileUploaded, Error' );

		/**
		 * User feedback callback.
		 *
		 * @param  {string}        message
		 * @param  {object}        data
		 * @param  {plupload.File} file     File that was uploaded.
		 */
		error = function( message, data, file ) {
			if ( file.userMedia ) {
				file.userMedia.destroy();
			}

			self.filesError.add( {
				feedback: message || pluploadL10n.default_error,
				data    : data,
				file    : file
			} );

			self.error( message, data, file );
		};

		this.uploader.bind( 'FilesAdded', function( uploader, files ) {
			_.each( files, function( file ) {
				var attributes, image;

				// Ignore failed uploads.
				if ( plupload.FAILED === file.status ) {
					return;
				}

				// Generate attributes for a new `Attachment` model.
				attributes = _.extend( {
					id:        file.id,
					file:      file,
					uploading: true,
					date:      new Date(),
					filename:  file.name,
					menuOrder: 0,
				}, _.pick( file, 'loaded', 'size', 'percent' ) );

				// Handle early mime type scanning for images.
				image = /(?:jpe?g|png|gif)$/i.exec( file.name );

				// For images set the model's type and subtype attributes.
				if ( image ) {
					attributes.type = 'image';

					// `jpeg`, `png` and `gif` are valid subtypes.
					// `jpg` is not, so map it to `jpeg`.
					attributes.subtype = ( 'jpg' === image[0] ) ? 'jpeg' : image[0];
				}

				// Create a model for the attachment, and add it to the Upload queue collection
				// so listeners to the upload queue can track and display upload progress.
				file.userMedia = new wpUserMedia.Models.File( attributes );
				self.filesQueue.add( file.userMedia );

				self.added( file.userMedia );
			} );

			uploader.refresh();
			uploader.start();
		} );

		this.uploader.bind( 'UploadProgress', function( uploader, file ) {
			file.userMedia.set( _.pick( file, 'loaded', 'percent' ) );
		} );

		this.uploader.bind( 'FileUploaded', function( uploader, file, response ) {
			var status;

			try {
				status   = response.status;
				response = JSON.parse( response.response );
			} catch ( e ) {
				return error( pluploadL10n.default_error, e, file );
			}

			if ( ! _.isObject( response ) || _.isUndefined( status ) ) {
				return error( pluploadL10n.default_error, null, file );
			} else if ( 201 !== status ) {
				return error( response.data && response.data.message, response.data, file );
			}

			_.each( ['file','loaded','size','percent'], function( key ) {
				file.userMedia.unset( key );
			} );

			file.userMedia.set( _.extend( response, { uploading: false } ) );

			// Add the file to the Uploaded ones
			self.filesUploaded.add( file.userMedia );

			self.success( file.userMedia );
		} );

		/**
		 * Trigger an event to inform a new upload is being processed
		 *
		 * @event BeforeUpload
		 * @param {plupload.Uploader} uploader Uploader instance.
		 * @param {Array}             files    Array of file objects that were added to queue by the user.
		 */
		this.uploader.bind( 'BeforeUpload', function( uploader, files ) {
			$( self ).trigger( 'wp-user-media-new-upload', uploader, files );
		} );

		/**
		 * Reset the filesQueue once the upload is complete
		 *
		 * @event BeforeUpload
		 * @param {plupload.Uploader} uploader Uploader instance.
		 * @param {Array}             files    Array of file objects that were added to queue by the user.
		 */
		this.uploader.bind( 'UploadComplete', function( uploader, files ) {
			$( self ).trigger( 'wp-user-media-upload-complete', uploader, files );
			self.filesQueue.reset();
		} );

		this.uploader.bind( 'Error', function( uploader, pluploadError ) {
			var message = pluploadL10n.default_error,
				key;

			// Check for plupload errors.
			for ( key in wp.Uploader.errorMap ) {
				if ( pluploadError.code === plupload[ key ] ) {
					message = wp.Uploader.errorMap[ key ];

					if ( _.isFunction( message ) ) {
						message = message( pluploadError.file, pluploadError );
					}

					break;
				}
			}

			error( message, pluploadError, pluploadError.file );
			$( self ).trigger( 'wp-user-media-upload-error', uploader, pluploadError );

			uploader.refresh();
		} );
	};

	$.extend( wpUserMedia.Uploader.prototype, {
		init    : function() {},
		success : function() {},
		added   : function() {},
		progress: function() {},
		complete: function() {},
		error   : function() {},

		refresh: function() {
			wp.Uploader.prototype.refresh.apply( this, arguments );
		},

		param: function( key, value ) {
			wp.Uploader.prototype.param.apply( this, arguments );
		}
	} );

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

	wpUserMedia.Views.uploaderProgress = wpUserMedia.View.extend( {
		tagName: 'div',
		className: 'wp-user-media-status',
		template: wpUserMedia.template( 'wp-user-media-progress' ),

		initialize: function() {
			this.model.on( 'change:percent', this.progress, this );
		},

		progress: function( model ) {
			if ( ! _.isUndefined( model.get( 'percent' ) ) ) {
				$( '#' + model.get( 'id' ) + ' .wp-user-media-progress .wp-user-media-bar' ).css( 'width', model.get('percent') + '%' );
			}
		}
	} );

	wpUserMedia.Views.Uploader = wpUserMedia.View.extend( {
		id: wpUserMediaParams.container,
		template: wpUserMedia.template( 'wp-user-media-uploader' ),

		initialize: function() {
			this.model = new Backbone.Model( _.pick( wpUserMediaParams, 'container', 'browser', 'dropzone' ) );
			this.on( 'ready', this.initUploader );
		},

		initUploader: function() {
			var pluploadOptions = _.mapObject( this.model.attributes, function( v, k ) {
				return '#' + v;
			} );

			_.extend( pluploadOptions, this.options || {} );
			this.uploader = new wpUserMedia.Uploader( pluploadOptions );

			this.uploader.filesError.on( 'add', this.uploadError, this );
			this.uploader.filesQueue.on( 'add', this.addProgressView, this );
		},

		uploadError: function( error ) {
			console.log( error );
		},

		addProgressView: function( file ) {
			this.views.add( '#wp-user-media-upload-status', new wpUserMedia.Views.uploaderProgress( { model: file } ) );
		}
	} );

	wpUserMedia.App = {
		init: function( restUrl ) {
			this.views     = new Backbone.Collection();
			this.users     = new wp.api.collections.Users();
			//this.userMedia = new wp.api.collections.User_media();

			this.overrides = {
				url: restUrl,
				'file_data_name': 'wp_user_media_upload',
				headers: {
					'X-WP-Nonce' : wpApiSettings.nonce
				}
			};

			this.attachUploader();
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

		attachUploader: function() {
			var uploader = new wpUserMedia.Views.Uploader( { overrides: this.overrides, params: {} } );

			// Add it to views
			this.views.add( { id: 'uploader_view', view: uploader } );

			// Display it
			uploader.inject( '#wp-user-media-uploader' );
		}
	};

	wp.api.loadPromise.done( function( api ) {
		var restUrl;

		if ( api.get( 'apiRoot' ) && api.get( 'versionString' ) ) {
			restUrl = api.get( 'apiRoot' ) + api.get( 'versionString' ) + 'user_media';
		}

		wpUserMedia.App.init( restUrl );
	} );

} )( jQuery );
