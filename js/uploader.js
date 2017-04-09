/* global wp, _ */

// Make sure the wp object exists.
window.wp = window.wp || {};
window.mediaTheque = window.mediaTheque || _.extend( {}, _.pick( window.wp, 'Backbone', 'template' ) );

( function( $ ) {

	mediaTheque.Models      = mediaTheque.Models || {};
	mediaTheque.Collections = mediaTheque.Collections || {};
	mediaTheque.Views       = mediaTheque.Views || {};

	// {@extends wp.api.WPApiBaseModel}
	mediaTheque.Models.File = wp.api.WPApiBaseModel.extend( {
		file: {},

		save: function( attrs, options ) {
			return Backbone.Model.prototype.save.call( this, attrs, options );
		},

		destroy: function( options, attrs, model ) {
			if ( _.isUndefined( this.urlRoot ) ) {
				this.clear();
				this.trigger( 'destroy', this, this.collection, options );
				return false;
			}

			return Backbone.Model.prototype.destroy.call( this, options );
		}
	} );

	mediaTheque.Uploader = function( options ) {
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
					filename:  file.name
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
				file.userMedia = new mediaTheque.Models.File( attributes );
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
			$( self ).trigger( 'mediatheque-new-upload', uploader, files );
		} );

		/**
		 * Reset the filesQueue once the upload is complete
		 *
		 * @event BeforeUpload
		 * @param {plupload.Uploader} uploader Uploader instance.
		 * @param {Array}             files    Array of file objects that were added to queue by the user.
		 */
		this.uploader.bind( 'UploadComplete', function( uploader, files ) {
			$( self ).trigger( 'mediatheque-upload-complete', uploader, files );
			self.filesQueue.reset();
		} );

		this.uploader.bind( 'Error', function( uploader, pluploadError ) {
			var message = pluploadL10n.default_error,
				key;

			// Try to find the User Media errors
			if ( ! _.isUndefined( pluploadError.response ) ) {
				pluploadError.response = JSON.parse( pluploadError.response );

				if ( ! _.isUndefined( pluploadError.response.message ) ) {
					message = pluploadError.response.message;
				}

			// Check for plupload errors.
			} else {
				for ( key in wp.Uploader.errorMap ) {
					if ( pluploadError.code === plupload[ key ] ) {
						message = wp.Uploader.errorMap[ key ];

						if ( _.isFunction( message ) ) {
							message = message( pluploadError.file, pluploadError );
						}

						break;
					}
				}
			}

			error( message, pluploadError, pluploadError.file );
			$( self ).trigger( 'mediatheque-upload-error', uploader, pluploadError );

			uploader.refresh();
		} );
	};

	$.extend( mediaTheque.Uploader.prototype, {
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

} )( jQuery );
