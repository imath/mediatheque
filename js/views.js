/* global wp, _ */

// Make sure the wp object exists.
window.wp = window.wp || {};
window.mediaTheque = window.mediaTheque || _.extend( {}, _.pick( window.wp, 'Backbone', 'template' ) );

( function( $ ) {

	mediaTheque.Models      = mediaTheque.Models || {};
	mediaTheque.Collections = mediaTheque.Collections || {};
	mediaTheque.Views       = mediaTheque.Views || {};

	// Extend wp.Backbone.View with .prepare()
	mediaTheque.View = mediaTheque.Backbone.View.extend( {
		prepare: function() {
			if ( ! _.isUndefined( this.model ) && _.isFunction( this.model.toJSON ) ) {
				return this.model.toJSON();
			} else {
				return {};
			}
		}
	} );

	mediaTheque.Views.User = mediaTheque.View.extend( {
		tagName:    'li',
		className:  'user',
		template: mediaTheque.template( 'mediatheque-user' )
	} );

	mediaTheque.Views.Feedback = mediaTheque.Views.User.extend( {
		className: 'notice',
		template: mediaTheque.template( 'mediatheque-feedback' ),

		events: {
			'click .notice-dismiss' : 'removeSelf'
		},

		initialize: function() {
			_.extend( this.model.attributes, _.pick( mediaThequeSettings.common, 'dismissibleText' ) );

			if ( this.model.get( 'type' ) ) {
				this.el.className += ' ' + this.model.get( 'type' );
			}

			if ( this.model.get( 'dismissible' ) ) {
				this.el.className += ' is-dismissible';
			}
		},

		removeSelf: function() {
			this.remove();
		}
	} );

	mediaTheque.Views.Users = mediaTheque.View.extend( {
		tagName:   'ul',
		className: 'users',

		events: {
			'click a.user-link' : 'displayUserMedia'
		},

		initialize: function() {
			this.collection.on( 'add', this.prepareNewView, this );

			this.isRequestingMore = false;
			this.scrollingElement = $( document );

			if ( $( '.media-frame-content' ).length ) {
				this.scrollingElement = $( '.media-frame-content' );

			// We should use this for any case
			} else if ( this.options.scrollingElement ) {
				this.scrollingElement = $( this.options.scrollingElement );
			}

			this.scrollingElement.on( 'scroll', _.bind( this.scroll, this ) );
		},

		prepareNewView: function( model ) {
			var o = this.options || {};

			o.ghost.add( model );

			this.addItemView( model );
		},

		addItemView: function( user ) {
			this.views.add( new mediaTheque.Views.User( { model: user } ) );
		},

		displayUserMedia: function( event ) {
			event.preventDefault();

			var user_id = $( event.currentTarget ).data( 'id' );

			_.each( this.collection.models, function( model ) {
				var attributes = { current: false };
				if ( user_id === model.get( 'id' ) ) {
					attributes.current = true;
				}

				model.set( attributes );
			} );
		},

		/**
		 * Edit the more query by extending this method.
		 *
		 * @return {object} Additionnal Query Parameters for the more query if needed.
		 */
		getScrollData: function() {
			return {};
		},

		scroll: function() {
			var listOffset = this.$el.offset(), el = document.body,
			    scrollTop = $( document ).scrollTop(), sensibility = 20;

			if ( $( '#wpadminbar' ).length ) {
				sensibility += $( '#wpadminbar' ).height();
			}

			if ( ! this.$el.is( ':visible' ) || ! this.collection.hasMore() || this.isRequestingMore || this.$el.hasClass( 'loading' ) ) {
				return;
			}

			if ( scrollTop + el.clientHeight + sensibility > this.el.clientHeight + listOffset.top ) {
				var data = this.getScrollData();
				this.isRequestingMore = true;

				this.collection.more( {
					data    : data,
					success : _.bind( this.resetRequestingMore, this ),
					error   : _.bind( this.resetRequestingMore, this )
				} );
			}
		},

		resetRequestingMore: function() {
			this.isRequestingMore = false;
		}
	} );

	mediaTheque.Views.Droppable = mediaTheque.View.extend( {
		initialize: function() {
			this.$el.attr( 'data-id',   this.model.get( 'id' ) );
			this.$el.bind( 'dragover',  _.bind( this.dragOver, this  ) );
			this.$el.bind( 'dragenter', _.bind( this.dragOver, this  ) );
			this.$el.bind( 'dragleave', _.bind( this.dragLeave, this ) );
			this.$el.bind( 'drop',      _.bind( this.dropIn, this    ) );

			this.isDroppable = true;
		},

		dragOver: function( event ) {
			var e = event;

			e.preventDefault();

			if ( e.originalEvent ) {
				e = e.originalEvent;
			}

			if ( -1 === _.indexOf( e.dataTransfer.types, 'modelid' ) || ! this.isDroppable ) {
				return false;
			}

			if ( ! this.$el.hasClass( 'drag-over' ) ) {
				this.$el.addClass( 'drag-over' );
			}

			return false;
		},

		dragLeave: function( event ) {
			event.preventDefault();

			if ( ! this.isDroppable ) {
				return false;
			}

			if ( this.$el.hasClass( 'drag-over' ) ) {
				this.$el.removeClass( 'drag-over' );
			}

			return false;
		},

		dropIn: function( event ) {
			var e = event;

			if ( e.originalEvent ) {
				e = e.originalEvent;
			}

			if ( ! this.isDroppable ) {
				return false;
			}

			if ( this.$el.hasClass( 'drag-over' ) ) {
				this.$el.removeClass( 'drag-over' );
			}

			this.handleDrop( e.dataTransfer.getData( 'modelId' ) );
		},

		/**
		 * Update the User Media Parent dir
		 *
		 * @param  {integer} id The User Media ID.
		 */
		handleDrop: function( id ) {
			if ( _.isUndefined( id ) ) {
				return;
			}

			var model = this.options.ghost.get( id ), parent = this.model.get( 'id' );

			if ( _.isNaN( parent ) ) {
				parent = 0;
			}

			$( '#trail ul' ).first().addClass( 'loading' );
			$( '#media ul' ).first().addClass( 'loading' );

			// Let's make sure the PUT verb won't be blocked by the server.
			Backbone.emulateHTTP = true;

			model.save( {
				'post_parent': this.model.get( 'id' )
			}, {
				success: _.bind( this.userMediaMoved, this )
			} );
		},

		/**
		 * Wait untill the model is successfully saved before
		 * removing it from the ghost collection.
		 *
		 * @param  {Backbone.Model} model The User Media object.
		 */
		userMediaMoved: function( model ) {
			$( '#trail ul' ).first().removeClass( 'loading' );
			$( '#media ul' ).first().removeClass( 'loading' );

			this.options.ghost.remove( model );
		}
	} );

	mediaTheque.Views.editUserMedia = mediaTheque.View.extend( {
		className: 'media-edit',

		events: {
			'click button.reset'  : 'removeView',
			'click button.submit' : 'updateUserMedia'
		},

		initialize: function() {
			this.userMedia = new wp.api.models.UserMedia( { id: this.model.get( 'id' ) } );

			this.userMedia.fetch( {
				data: { 'user_media_edit': true },
				success : _.bind( this.setEditFields, this )
			} );
		},

		setEditFields: function( model ) {
			var fields = mediaThequeSettings.editFields;

			_.each( fields, function( field, id ) {
				var userMediaAttribute = model.get( id );

				if ( userMediaAttribute ) {
					if ( ! _.isUndefined( userMediaAttribute.rendered ) ) {
						field.value = userMediaAttribute.rendered;
					} else {
						field.value = userMediaAttribute;
					}

					field.id = id;
				}

				this.views.add( new mediaTheque.Views.Field(
					{ model: new Backbone.Model( field ) },
					{ at: field.position }
				) );
			}, this );
		},

		removeView: function( event ) {
			event.preventDefault();

			$( event.currentTarget ).closest( 'li.user-media' ).removeClass( 'editing' );
			$( '#mediatheque-backdrop' ).removeClass( 'editing' );
			this.remove();
		},

		updateUserMedia: function( event ) {
			event.preventDefault();

			var elements = $( event.currentTarget ).closest( 'form' ).find( '[data-setting]' ), edits = {};

			_.each( elements, function( e ) {
				var attribute = $( e ).data( 'setting' ), userMediaAttribute = this.model.get( attribute ),
					val;

				if ( 'DIV' === $( e ).prop( 'tagName') ) {
					val = $( e ).html();
				} else {
					val = $( e ).val();
				}

				if ( userMediaAttribute ) {
					if ( ! _.isUndefined( userMediaAttribute.rendered ) ) {
						userMediaAttribute = userMediaAttribute.rendered;
					}

					if ( userMediaAttribute !== val ) {
						edits[ attribute ] = val;
					}
				}
			}, this );

			if ( ! _.isEmpty( edits ) ) {
				this.userMedia.save( edits );
				this.model.set( { edited: edits } );
			}

			this.removeView( event );
		}
	} );

	mediaTheque.Views.UserMedia = mediaTheque.Views.Droppable.extend( {
		tagName:    'li',
		className:  'user-media',
		template: mediaTheque.template( 'mediatheque-media' ),

		events: {
			'click .delete' : 'deleteUserMedia',
			'click .edit'   : 'editUserMedia'
		},

		initialize: function() {
			var o = this.options || {};

			if ( this.model.get( 'uploading' ) ) {
				// Show Upload progress
				this.model.on( 'change:percent', this.progress, this );

				// Replace the uploaded file with the User Media model.
				this.model.on( 'change:guid', this.update, this );

				this.model.on( 'destroy', this.remove, this );

			// The dir background is specific.
			} else if ( 'dir' === this.model.get( 'media_type' ) ) {
				this.el.className += ' dir droppable';

				if ( 'wp-editor' !== o.uiType && 'display' !== o.uiType ) {
					mediaTheque.Views.Droppable.prototype.initialize.apply( this, arguments );
				} else {
					this.$el.attr( 'data-id', this.model.get( 'id' ) );
				}

				this.model.set( { uiType: o.uiType }, { silent: true } );

			// Set additionnal properties
			} else {
				this.setMediaProps();
			}

			this.model.on( 'change:edited', this.update, this );
		},

		setMediaProps: function() {
			var o = this.options || {};

			if ( 'wp-editor' !== o.uiType && 'display' !== o.uiType ) {
				this.$el.prop( 'draggable', true );
			} else {
				this.el.className += ' selectable';
			}

			this.$el.attr( 'data-id', this.model.get( 'id' ) );

			if ( 'image' === this.model.get( 'media_type' ) && this.model.get( 'guid' ) ) {
				var bgUrl = fullUrl = this.model.get( 'guid' ).rendered,
				    mediaDetails = this.model.get( 'media_details' ), fileName;

				if ( _.isObject( mediaDetails.sizes ) && mediaDetails.sizes.medium ) {
					fileName = mediaDetails.file.split( '/' );
					bgUrl = bgUrl.replace( fileName[ fileName.length - 1 ], mediaDetails.sizes.medium.file );
				}

				this.model.set( { background: bgUrl, fullUrl: fullUrl }, { silent: true } );
			}

			this.model.set( {
				download: this.model.get( 'link' ) + mediaThequeSettings.common.downloadSlug + '/',
				uiType : o.uiType
			}, { silent: true } );

			// Files need their root Url to be set as the Rest Endpoint.
			if ( true === this.model.previous( 'uploading' ) ) {
				_.extend( this.model, { urlRoot: mediaTheque.App.overrides.url } );
			}
		},

		progress: function( file ) {
			if ( ! _.isUndefined( file.get( 'percent' ) ) ) {
				$( '#' + file.get( 'id' ) + ' .mediatheque-progress .mediatheque-bar' ).css( 'width', file.get('percent') + '%' );
			}
		},

		update: function( file, changed ) {
			if ( ! _.isUndefined( file.filename ) ) {
				_.each( ['date', 'filename', 'uploading', 'subtype' ], function( attribute ) {
					file.unset( attribute, { silent: true } );
				} );
			} else if ( ! _.isUndefined( changed.title ) && changed.title && changed.title !== this.model.get( 'title' ).rendered ) {
				this.model.set( { title: { rendered: changed.title }, edited: {} }, { silent: true } );
			}

			this.setMediaProps();
			this.render();
		},

		deleteUserMedia: function( event ) {
			event.preventDefault();

			// Let's make sure the DELETE verb won't be blocked by the server.
			Backbone.emulateHTTP = true;

			$( '#trail ul' ).first().addClass( 'loading' );
			$( '#media ul' ).first().addClass( 'loading' );

			// Destroy the model.
			this.model.destroy( {
				wait: true,
				success: _.bind( this.userMediaDeleted, this )
			} );
		},

		userMediaDeleted: function( model ) {
			$( '#trail ul' ).first().removeClass( 'loading' );
			$( '#media ul' ).first().removeClass( 'loading' );

			// Remove the model.
			this.options.ghost.remove( model );
		},

		editUserMedia: function( event ) {
			event.preventDefault();

			this.views.set( '.user-media-edit-container', new mediaTheque.Views.editUserMedia( { model: this.model } ) );
			this.$el.addClass( 'editing' );
			$( '#mediatheque-backdrop' ).addClass( 'editing' );
		}
	} );

	mediaTheque.Views.UserMedias = mediaTheque.Views.Users.extend( {
		tagName:   'ul',
		className: 'user-media',

		events: {
			'click .dir .user-media-content'        : 'openDir',
			'click .selectable'                     : 'selectMedia',
			'dragstart [draggable=true]'            : 'setDragData'
		},

		initialize: function() {
			var o = this.options || {};

			mediaTheque.Views.Users.prototype.initialize.apply( this, arguments );

			// Init the view with default Query Vars.
			this.queryUserMedia();

			// Listen to Query Vars changes
			this.listenTo( o.queryVars, 'change:parent', this.getChildren );

			// Listen to User Media removed from the ghost Collection
			this.listenTo( o.ghost, 'remove', this.adjustMore );
			this.listenTo( o.ghost, 'change:selected', this.setSelection );
		},

		/**
		 * When a User Media is moved into a dir, we need to remove its view
		 * and make sure to exclude displayed User Media from the more Query.
		 *
		 * @param  {Backbone.Model} model The User Media Object
		 */
		adjustMore: function( model ) {
			var o = this.options || {}, modelId = model.get( 'id' );

			this.excludeDisplayed = []

			// Remove the moved User Media from the list.
			if ( ! _.isUndefined( this.views._views[''] ) && this.views._views[''].length && modelId ) {
				_.each( this.views._views[''], function( view ) {
					if ( modelId === view.model.get( 'id' ) ) {
						view.remove();
					} else {
						this.excludeDisplayed.push( view.model.get( 'id' ) );
					}
				}, this );
			}

			// Clean up the main collection if the Model is still in.
			if ( this.collection.get( modelId ) ) {
				this.collection.remove( modelId );
			}

			// Update the total available User Media for the query
			this.collection.state.totalObjects -= 1;

			// If All the User Media are displayed, no need to get more on scroll.
			if ( this.views._views[''].length >= this.collection.state.totalObjects ) {
				delete this.excludeDisplayed;
			}
		},

		/**
		 * Exclude Displayed User Media and reset page for the more Query if needed
		 *
		 * @return {object} Query Parameters.
		 */
		getScrollData: function() {
			var data = {};

			if ( _.isArray( this.excludeDisplayed ) ) {
				_.extend( data, {
					exclude: this.excludeDisplayed.join( ',' ),
					page   : 1
				} );

				delete this.excludeDisplayed;
			}

			return data;
		},

		queryUserMedia: function( options ) {
			var o = this.options || {};

			if ( _.isObject( options ) ) {
				o.queryVars.set( options );
			}

			// Clean subviews.
			if ( ! _.isUndefined( this.views._views[''] ) && this.views._views[''].length ) {
				_.each( this.views._views[''], function( view ) {
					view.remove();
				} );
			}

			// Eventually restrict the query to a media type
			if ( 'undefined' != typeof mediaThequeCustoms && mediaThequeCustoms.mediaType ) {

				// In this case, we need to get all user media for the given media type
				if ( 0 === o.queryVars.get( 'parent' ) ) {
					o.queryVars.unset( 'parent', { silent: true } );
				}

				o.queryVars.set( { 'media_type' : mediaThequeCustoms.mediaType }, { silent: true } );
			}

			this.collection.reset();
			this.collection.fetch( {
				data : o.queryVars.attributes,
				success : _.bind( this.doFeedback, this ),
				error   : _.bind( this.doFeedback, this )
			} );
		},

		removeFeedback: function() {
			if ( ! _.isUndefined( this.views._views[''] ) && this.views._views[''].length ) {
				_.each( this.views._views[''], function( view ) {
					if ( view.$el.hasClass( 'notice' ) ) {
						view.remove();
					}
				} );
			}
		},

		doFeedback: function( collection, request ) {
			var feedback = new Backbone.Model();

			if ( collection.length ) {
				return;
			}

			if ( _.isUndefined( request.responseJSON ) ) {
				feedback.set( {
					message: mediaThequeSettings.common.noUserMedia,
					type:    'notice-warning'
				} );
			} else if ( ! _.isUndefined( request.responseJSON.message ) ) {
				feedback.set( {
					message: request.responseJSON.message,
					type:    'notice-error'
				} );
			} else {
				return;
			}

			this.views.add( new mediaTheque.Views.Feedback( { model: feedback } ), { at: 0 } );
		},

		addItemView: function( userMedia ) {
			var o = this.options || {}, position = userMedia.get( 'at' ),
			    uiType = o.queryVars.get( 'user_media_context' );

			// Remove all feedbacks.
			this.removeFeedback();

			if ( _.isUndefined( position ) ) {
				this.views.add( new mediaTheque.Views.UserMedia( { model: userMedia, ghost: o.ghost, uiType: uiType } ) );
			} else {
				this.views.add( new mediaTheque.Views.UserMedia( { model: userMedia, ghost: o.ghost, uiType: uiType } ), { at: position } );
			}
		},

		openDir: function( event ) {
			event.preventDefault();

			var parent = $( event.currentTarget ).closest( '.dir' ).data( 'id' ),
			    o = this.options || {};

			if ( ! parent ) {
				return;
			}

			// Ask the listener to get dir's children
			o.queryVars.set( { parent: parent } );
		},

		selectMedia: function( event ) {
			var o = this.options;

			event.preventDefault();

			var media = event.currentTarget, id = $( media ).data( 'id' ),
			    self  = this, current;

			if ( id ) {
				_.each( o.ghost.models, function( model ) {
					var selected = true;

					if ( id !== model.get( 'id' ) ) {
						model.set( { selected: false } );
						self.$el.find( 'li.user-media' ).removeClass( 'selected' );
					} else {
						if ( selected === model.get( 'selected' ) ) {
							selected = false;
						}

						model.set( { selected: selected } );
					}
				} );

				current = o.ghost.get( id );

				if ( current.get( 'selected' ) ) {
					$( media ).addClass( 'selected' );
				} else {
					$( media ).removeClass( 'selected' );
				}
			}
		},

		setSelection: function( model, selected ) {
			var userMediaSelection = this.options.selection;

			if ( true !== selected && ! userMediaSelection.get( model ) ) {
				return false;
			}

			if ( userMediaSelection.length ) {
				userMediaSelection.reset();
			}

			if ( true === selected ) {
				userMediaSelection.add( model );
			}
		},

		getChildren: function( model, changed ) {
			if ( _.isUndefined( changed ) ) {
				return;
			}

			this.queryUserMedia();
		},

		setDragData: function( event ) {
			var e = event;

			if ( this.$el.closest( 'ul' ).hasClass( 'loading' ) ) {
				event.preventDefault();
				return false;
			}

			if ( e.originalEvent ) {
				e = e.originalEvent;
			}

			e.dataTransfer.setData( 'modelId', $( event.currentTarget ).data( 'id' ) );
		}
	} );

	mediaTheque.Views.displayUserMedias = mediaTheque.Views.UserMedias.extend( {
		events: {
			'click .user-media-content' : 'selectMedia',
		},

		initialize: function() {
			var o = this.options || {}, qv = { 'user_media_context': 'display' };

			mediaTheque.Views.Users.prototype.initialize.apply( this, arguments );

			if ( mediaThequeSettings.common.directory ) {
				qv.parent = parseInt( mediaThequeSettings.common.directory, 10 );
			}

			// Init the view with default Query Vars.
			this.queryUserMedia( qv );
		},

		selectMedia: function( event ) {
			var o = this.options;

			event.preventDefault();

			var media = $( event.currentTarget ).closest( 'li' ), id = $( media ).data( 'id' ), current;

			if ( id ) {
				current = o.ghost.get( id );

				if ( current.get( 'link' ) ) {
					document.location.href = current.get( 'link' );
				}
			}
		}
	} );

	mediaTheque.Views.uploaderProgress = mediaTheque.View.extend( {
		tagName: 'div',
		className: 'mediatheque-status',
		template: mediaTheque.template( 'mediatheque-progress' ),

		initialize: function() {
			this.model.on( 'change:percent', this.progress, this );
		},

		progress: function( model ) {
			if ( ! _.isUndefined( model.get( 'percent' ) ) ) {
				$( '#' + model.get( 'id' ) + ' .mediatheque-progress .mediatheque-bar' ).css( 'width', model.get('percent') + '%' );
			}
		}
	} );

	mediaTheque.Views.MkDir = mediaTheque.View.extend( {
		tagName: 'div',
		id: 'directory-form',
		className: 'postbox',
		template: mediaTheque.template( 'mediatheque-dirmaker' ),

		events: {
			'click button.close' : 'removeSelf',
			'click #create-dir'  : 'createDir'
		},

		initialize: function() {
			this.model.set( _.pick( mediaThequeSettings.common, 'closeBtn' ), { silent: true } );
		},

		removeSelf: function( event ) {
			if ( _.isObject( event ) && event.currentTarget ) {
				event.preventDefault();
			}

			mediaTheque.App.toolbarItems.get( this.options.toolbarItem ).set( { active: false } );
		},

		createDir: function( event ) {
			event.preventDefault();

			var form = $( event.currentTarget ).closest( 'form' ), dirData = {},
			    p = this.options.params || {};

			_.each( $( form ).serializeArray(), function( pair ) {
				pair.name = pair.name.replace( '[]', '' );
				dirData[ pair.name ] = pair.value;
			} );

			// Missing title!
			if ( ! dirData.title ) {
				return;
			}

			_.extend( dirData, p );

			var dir = new wp.api.models.UserMedia( dirData );
			dir.save( {
				action: 'mkdir_user_media'
			}, {
				success: _.bind( this.mkdirSuccess, this ),
				error: _.bind( this.mkdirError, this )
			} );
		},

		mkdirSuccess: function( model ) {
			model.set( { at: 0 }, { silent: true } );
			mediaTheque.App.userMedia.add( model );
			this.removeSelf();
		},

		mkdirError: function( error ) {
			console.log( error );
		}
	} );

	mediaTheque.Views.Uploader = mediaTheque.Views.MkDir.extend( {
		id: mediaThequeSettings.params.container,
		className: 'mediatheque-uploader-box',
		template: mediaTheque.template( 'mediatheque-uploader' ),

		initialize: function() {
			this.model = new Backbone.Model( mediaThequeSettings.params );
			this.on( 'ready', this.initUploader );

			mediaTheque.Views.MkDir.prototype.initialize.apply( this, arguments );
		},

		initUploader: function() {
			var pluploadOptions = _.mapObject( _.pick( this.model.attributes, 'container', 'browser', 'dropzone' ), function( v, k ) {
				return '#' + v;
			} );

			_.extend( pluploadOptions, this.options || {} );
			this.uploader = new mediaTheque.Uploader( pluploadOptions );

			this.uploader.filesError.on( 'add', this.uploadError, this );
			this.uploader.filesQueue.on( 'add', this.addProgressView, this );
			this.uploader.filesQueue.on( 'reset', this.removeSelf, this );
		},

		uploadError: function( error ) {
			var o = this.options || {}, file, errorData,
			    feedback = new Backbone.Model( {
			    	dismissible: true,
			    	type: 'notice-error'
			    } );

			file = error.get( 'file' );
			errorData = error.get( 'data' );

			if ( error.get( 'feedback' ) ) {
				feedback.set( { message: error.get( 'feedback' ) } );
			} else {
				feedback.set( { message: errorData.message } );
			}

			if ( _.isObject( o.mediaView ) ) {
				var model = o.mediaView.collection.get( file.id );
				o.mediaView.collection.remove( file.id );

				// The message should be preprended with the File name.
				o.mediaView.views.add( new mediaTheque.Views.Feedback( { model: feedback } ), { at: 0 } );
			} else {
				this.views.add( '#mediatheque-upload-status', new mediaTheque.Views.Feedback( { model: feedback } ) );
			}
		},

		addProgressView: function( file ) {
			var o = this.options || {};

			if ( ! _.isObject( o.mediaView ) ) {
				this.views.add( '#mediatheque-upload-status', new mediaTheque.Views.uploaderProgress( { model: file } ) );
			} else {
				o.mediaView.collection.add( file.set( { at: 0 }, { silent: true } ) );
			}
		}
	} );

	mediaTheque.Views.ToolbarItem = mediaTheque.View.extend( {
		tagName  : 'li',
		template: mediaTheque.template( 'mediatheque-toolbar-item' ),

		initialize: function() {
			if ( this.model.get( 'current' ) ) {
				this.el.className = 'current';
			}

			this.model.on( 'change:disable', this.refreshItem, this );
		},

		refreshItem: function( model, changed ) {
			var element = $( this.$el ).find( 'button' ).first();

			element.prop( 'disabled', changed );
		}
	} );

	mediaTheque.Views.Toolbar = mediaTheque.View.extend( {
		tagName  : 'ul',
		className: 'filter-links',

		events: {
			'click button' : 'activateView'
		},

		initialize: function() {
			var o = this.options || {}, position = 0, current = false;

			_.each( mediaThequeSettings.toolbarItems, function( name, id ) {
				position += 1;

				if ( o.users.length && 'users' === id ) {
					current = true;
				} else {
					current = false;
				}

				this.collection.add( {
					id: id,
					name: name,
					position: position,
					current: current,
					disable: 0 !== o.users.length && 'users' !== id,
				} );

				this.addItemBar( this.collection.get( id ) );
			}, this );

			this.collection.on( 'change:current', this.refreshToolbar, this );
		},

		addItemBar: function( toolbarItem ) {
			this.views.add( new mediaTheque.Views.ToolbarItem( { model: toolbarItem } ) );
		},

		refreshToolbar: function( model, changed ) {
			if ( false === changed || 'false' === changed ) {
				return;
			}

			_.each( this.$el.children(), function( e ) {
				$( e ).removeClass( 'current' );
			} );

			$( this.$el ).find( '[data-id="' + model.get( 'id' ) + '"]' ).parent( 'li' ).addClass( 'current' );
		},

		activateView: function( event ) {
			event.preventDefault();

			var current = $( event.currentTarget ), model, disable = false, subview = null;

			if ( current.prop( 'disabled' ) ) {
				return;
			}

			if ( 'upload' === current.data( 'id' ) || 'directory' === current.data( 'id' ) ) {
				subview = current.data( 'id' );
			} else if ( 'users' === current.data( 'id' ) ) {
				disable = true;
			}

			_.each( this.collection.models, function( model ) {
				var attributes = { disable: disable, current: false, active: false };

				if ( ! _.isNull( subview ) ) {
					if ( model.get( 'id' ) === subview ) {
						attributes.active  = true;
					} else {
						attributes.current = model.get( 'current' );
					}

				} else if ( model.get( 'id' ) === current.data( 'id' ) ) {
					attributes.current = true;

					if ( 'users' === model.get( 'id' ) ) {
						attributes.disable = false;
					}
				}

				model.set( attributes );
			}, this );
		}
	} );

	mediaTheque.Views.trailItem = mediaTheque.Views.Droppable.extend( {
		tagName  : 'li',
		template: mediaTheque.template( 'mediatheque-trail' ),

		initialize: function() {
			mediaTheque.Views.Droppable.prototype.initialize.apply( this, arguments );

			this.isDroppable = false;

			if ( this.model.get( 'showLink') ) {
				this.model.unset( 'showLink', { silent: true } );
			}

			// Update the trailItem link
			this.model.on( 'change:showLink', this.rerender, this );

			// Remove the trailItem view
			this.model.on( 'remove', this.remove, this );
		},

		rerender: function( model, changed ) {
			if ( true === changed ) {
				this.isDroppable = true;
			}

			this.render();
		}
	} );

	mediaTheque.Views.Trail = mediaTheque.View.extend( {
		tagName  : 'ul',
		className: 'trail-links',

		events: {
			'click a' : 'moveUp'
		},

		initialize: function() {
			this.collection.on( 'reset', this.resetTrail, this );
			this.collection.on( 'add', this.addTrailItem, this );
		},

		resetTrail: function() {
			// Clean subviews.
			if ( ! _.isUndefined( this.views._views[''] ) && this.views._views[''].length ) {
				_.each( this.views._views[''], function( view ) {
					view.remove();
				} );
			}
		},

		addTrailItem: function( trailItem ) {
			this.views.add( new mediaTheque.Views.trailItem( { model: trailItem, ghost: this.options.ghost } ) );
		},

		moveUp: function( event ) {
			var o = this.options || {}, action;

			event.preventDefault();

			action = $( event.currentTarget ).prop( 'class' );

			if ( 'root-dir' === action ) {
				o.queryVars.set(
					{ parent: 0 },
					{ 'move' : action }
				);
			} else if ( 'parent-dir' === action ) {
				o.queryVars.set(
					{ parent: $( event.currentTarget ).data( 'id' ) },
					{ 'move' : action }
				);
			}
		}
	} );

	mediaTheque.Views.Root = mediaTheque.View.extend( {

		initialize: function() {
			var o = this.options || {};

			this.ghost = new Backbone.Collection();

			this.listenTo( o.toolbarItems, 'change:active', this.displayForms );
			this.listenTo( o.toolbarItems, 'change:current', this.manageLists );
			this.listenTo( o.queryVars,    'change:parent',  this.updateTrail );

			// Using the App into the WordPress Editor
			if ( 'wp-editor' === o.uiType ) {
				o.users = [];
				this.setToolbar();

			// Using the App elsewhere.
			} else {
				this.views.set( '#users', new mediaTheque.Views.Users( { collection: o.users, ghost: this.ghost } ) );

				this.listenTo( o.users, 'reset', this.queryUsers );
				o.users.on( 'change:current', this.setToolbar, this );
				o.users.reset();
			}
		},

		/**
		 * Admins will be able to list users. Listing a user's files needs to select the user.
		 * Regular users won't be able to do that and their own files will be automatically loaded.
		 */
		queryUsers: function() {
			var o = this.options || {};

			o.users.fetch( {
				data: { 'has_disk_usage' : true },
				success : _.bind( this.displayToolbar, this ),
				error   : _.bind( this.setToolbar, this )
			} );
		},

		/**
		 * Display the Main Toolbar
		 */
		displayToolbar: function() {
			var o = this.options || {};

			if ( ! _.isUndefined( this.views._views['#toolbar'] ) ) {
				return;
			}

			this.views.add( '#toolbar', new mediaTheque.Views.Toolbar( {
				collection: o.toolbarItems,
				users: o.users
			} ) );
		},

		/**
		 * Adjust the Toolbar according to the current user's capabilities.
		 */
		setToolbar: function( model, changed ) {
			var o = this.options || {}, currentStatus, form;

			if ( ! _.isUndefined( changed ) && false === changed ) {
				return;
			}

			// The User is not an admin.
			if ( _.isUndefined( this.views._views['#toolbar'] ) ) {
				delete mediaThequeSettings.toolbarItems.users;

				this.displayToolbar();

				// Display the current status
				currentStatus = o.toolbarItems.findWhere( { current: true } );
				if ( ! _.isUndefined( currentStatus ) ) {
					this.manageLists( currentStatus, true );

				// Set the Public status as current one.
				} else {
					o.toolbarItems.get( 'publish' ).set( { current: true } );
				}

				// Make sure forms are not considered active on query changes
				form = o.toolbarItems.findWhere( { active: true } );
				if ( form ) {
					o.toolbarItems.get( form ).set( { active: false }, { silent: true } );
				}

			// The User is an admin and has selected a user.
			} else {
				_.each( o.toolbarItems.models, function( model ) {
					var attributes = { disable: false, current: false };
					if ( 'publish' === model.get( 'id' ) ) {
						attributes.current = true;
					}

					model.set( attributes );
				} );
			}
		},

		displayForms: function( model, active ) {
			var o = this.options || {}, params = { 'post_status': 'publish' },
			    s = null, empty = _.isUndefined( this.views._views['#forms'] ) || 0 === this.views._views['#forms'].length;

			if ( -1 === _.indexOf( ['upload', 'directory'], model.get( 'id' ) ) ) {
				return;
			}

			if ( false === active ) {
				if ( empty ) {
					return;
				} else {
					_.each( this.views._views['#forms'], function( view ) {
						if ( ( 'upload' === model.get( 'id' ) && view.uploader ) || ( 'directory' === model.get( 'id' ) && ! view.uploader ) ) {
							view.remove();
						}
					} );
				}
			} else {
				if ( ! empty ) {
					_.each( this.views._views['#forms'], function( view ) {
						view.remove();
					} );
				}

				s = o.toolbarItems.findWhere( { current: true } );

				if ( _.isObject( s ) ) {
					params.post_status = s.get( 'id' );
				}

				if ( o.queryVars.get( 'parent' ) ) {
					params.post_parent = o.queryVars.get( 'parent' );
				}

				if ( o.queryVars.get( 'user_id' ) ) {
					params.user_id = o.queryVars.get( 'user_id' );
				}

				if ( 'upload' === model.get( 'id' ) ) {
					this.views.add( '#forms', new mediaTheque.Views.Uploader( {
						overrides: o.overrides,
						params: params,
						toolbarItem: 'upload',
						mediaView: _.first( this.views._views['#media'] )
					} ) );
				} else {
					this.views.add( '#forms', new mediaTheque.Views.MkDir( {
						params: params,
						toolbarItem: 'directory',
						model: new Backbone.Model( mediaThequeSettings.dirmaker )
					} ) );
				}
			}
		},

		manageLists: function( model, changed ) {
			var o = this.options || {};

			if ( 'users' === model.get( 'id' ) ) {
				// First remove any media views
				if( ! _.isUndefined( this.views._views['#media'] ) && 0 !== this.views._views['#media'].length ) {
					_.first( this.views._views['#media'] ).remove();
				}

				// Remove the users view if it's not the current toolbar item
				if ( false === changed && ! _.isUndefined( this.views._views['#users'] ) && 0 !== this.views._views['#users'].length ) {
					_.first( this.views._views['#users'] ).remove();
				}

				if ( true === changed ) {
					this.views.set( '#users', new mediaTheque.Views.Users( { collection: o.users, ghost: this.ghost } ) );
					o.users.reset();

					// Remove the trail when switching users.
					if ( ! _.isUndefined( this.views._views['#trail'] ) && 0 !== this.views._views['#trail'].length ) {
						_.first( this.views._views['#trail'] ).remove();
					}
				}

			// In all other cases manage statuses
			} else if ( true === changed ) {

				// Add the trail view
				if ( _.isUndefined( this.views._views['#trail'] ) || 0 === this.views._views['#trail'].length ) {
					this.views.add( '#trail', new mediaTheque.Views.Trail( {
						collection: o.trailItems,
						ghost:     this.ghost,
						queryVars: o.queryVars
					} ) );
				}

				// Reset the trail collection
				o.trailItems.reset();

				// Reset the Query vars
				o.queryVars.clear();
				o.queryVars.set( {
					'user_media_context': ! _.isUndefined( o.uiType ) ? o.uiType : 'admin',
					'post_status': model.get( 'id' ),
					'parent'     : 0
				}, { silent: true } );

				// Set the User ID.
				if ( o.users.length ) {
					var author = o.users.findWhere( { current: true } );
					o.queryVars.set( { 'user_id': author.get( 'id' ) }, { silent: true } );

					// Add the User to trail
					o.trailItems.add( author );
				} else {
					o.queryVars.set( { 'user_id': 0 }, { silent: true } );
				}

				// Add the Status
				o.trailItems.add( model );

				// Add the media view
				this.views.set( '#media', new mediaTheque.Views.UserMedias( {
					collection: o.media,
					ghost:      this.ghost,
					queryVars:  o.queryVars,
					selection:  o.selection
				} ) );
			}
		},

		updateTrail: function( model, changed, options ) {
			var o = this.options || {}, dir, parent, status;

			if ( _.isUndefined( changed ) ) {
				return;
			}

			dir = this.ghost.get( changed );

			// Move down into the dirs tree
			if ( ! _.isUndefined( dir ) && ! options.move ) {

				if ( 0 === dir.get( 'parent' ) ) {
					status = o.toolbarItems.findWhere( { current: true } );
					parent = o.trailItems.get( status.get( 'id' ) );
					parent.set( { showLink: true } );

				} else {
					parent = o.trailItems.get( dir.get( 'parent' ) );
					parent.set( { showLink: true } );
				}

				// Add to trail the current displayed folder.
				o.trailItems.add( this.ghost.get( changed ) );

			// Move up into the dirs tree
			} else if ( options.move ) {
				if ( 'root-dir' === options.move ) {
					status = o.toolbarItems.findWhere( { current: true } );
					parent = o.trailItems.get( status.get( 'id' ) );
					parent.set( { showLink: false } );
				} else {
					parent = o.trailItems.get( changed );
					parent.set( { showLink: false } );
				}

				var remove = o.trailItems.where( { 'media_type': 'dir' } );

				// Remove from trail the children.
				_.each( remove, function( trailItem ) {
					if ( 'root-dir' === options.move || trailItem.get( 'id' ) > changed ) {
						o.trailItems.remove( trailItem );
					}
				} );
			}
		}
	} );

	mediaTheque.Views.Display = mediaTheque.View.extend( {

		initialize: function() {
			var o = this.options || {};

			this.ghost = new Backbone.Collection();

			// Add the media view
			this.views.set( '#media', new mediaTheque.Views.displayUserMedias( {
				collection: o.media,
				ghost:      this.ghost,
				queryVars:  o.queryVars,
				scrollingElement: '#mediatheque-container'
			} ) );
		}
	} );

	mediaTheque.Views.Field = mediaTheque.View.extend( {
		tagName  : 'label',
		template: mediaTheque.template( 'mediatheque-field-item' ),

		initialize: function() {
			var classes = this.model.get( 'classes' );

			if ( -1 !== _.indexOf( ['submit', 'reset' ], this.model.get( 'type' ) ) ) {
				this.el.className = this.model.get( 'type' );

			} else if ( _.isArray( classes ) ) {
				this.el.className = classes.join( ' ' );
			}
		}
	} );

} )( jQuery );
