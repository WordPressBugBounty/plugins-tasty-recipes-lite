/*global tastyRecipesEditor:true */
(function(wp, $, editorModalData){
	var updateCallback = null,
		isOpen = false,
		container = null,
		editorInstance = null;
	$(document).ready(function(){
		container = $('.tasty-recipes-modal-container');
		window.tastyRecipesEditorModal.initEvents();
	});

	var editorModal = {

		/**
		 * Initializes events within the editor modal.
		 */
		initEvents() {
			container = $('.tasty-recipes-modal-container');
			$('.tasty-recipes-button-insert', container).on('click', $.proxy( function(){
				this.close( true );
			}, this ));
			$('.tasty-recipes-button-update', container).on('click', $.proxy( function(){
				this.close( true );
			}, this ));
			$('.tasty-recipes-modal-close', container).on('click', $.proxy( function(){
				this.close( false );
			}, this ));
		},

		/**
		 * Opens the editor modal.
		 *
		 * @param {Object}   data     The recipe data to open the modal with.
		 * @param {Function} callback The callback to run when the modal is closed.
		 */
		open( data = null, callback = null ) {
			if (isOpen) {
				return;
			}
			isOpen = true;
			updateCallback = callback;
			editorInstance = document.createElement('div');
			document.querySelector('.tasty-recipes-modal-container .tasty-recipes-frame-content').appendChild(editorInstance);
			window.tastyRecipesMountRecipeEditor(
				editorInstance,
				data
			);
			if ( typeof data === 'object' && typeof data.id !== 'undefined' ) {
				container.addClass('tasty-recipes-state-editing');
			} else {
				container.addClass('tasty-recipes-state-creating');
			}
			$('body').addClass('tasty-recipes-modal-open');
			$(document).on('keydown.tasty-recipes-escape', $.proxy( function( event ){
				if ( 27 === event.keyCode ) {
					this.close( false );
					event.stopImmediatePropagation();
				}
			}, this ));
			$('form', container).on('submit', $.proxy(function( event ){
				event.preventDefault();
				this.close( true );
			}, this ));
			/* Allows Tasty Links to set up its data. */
			$(document).trigger('tasty-recipes-pre-modal-show', data );
			container.show();
			$('.tasty-recipes-frame-content', container ).focus();
			if ( typeof tinyMCE !== 'undefined' ) {
				var vis = $('.mce-toolbar-grp.mce-inline-toolbar-grp.mce-container.mce-panel:visible');
				if ( vis.length ) {
					vis.addClass('tr-temp-hide');
				}
			}
		},

		/**
		 * Closes the editor modal.
		 *
		 * @param {boolean} save Whether or not to save the recipe.
		 */
		close( save ) {
			isOpen = false;
			$('body').removeClass('tasty-recipes-modal-open');
			$(document).unbind('keydown.tasty-recipes-escape');
			$('.tasty-recipes-toolbar-primary button', container).attr('disabled', 'disabled');
			var hideContainer = function(){
				var hid = $('.mce-toolbar-grp.mce-inline-toolbar-grp.mce-container.mce-panel.tr-temp-hide');
				if ( hid.length ) {
					hid.removeClass('tr-temp-hide');
				}
				container.hide();
				container.removeClass('tasty-recipes-state-creating tasty-recipes-state-editing');
				$('.tasty-recipes-toolbar-primary button', container).removeAttr('disabled');
				window.tastyRecipesDestroyRecipeEditor(
					editorInstance
				);
			};
			if ( save ) {
				if ( typeof tinyMCE !== 'undefined' ) {
					tinyMCE.triggerSave();
				}
				var paramObj = {};
				$.each( $('form', container ).serializeArray(), function(_, kv) {
					if (paramObj.hasOwnProperty(kv.name)) {
						paramObj[kv.name] = $.makeArray(paramObj[kv.name]);
						paramObj[kv.name].push(kv.value);
					} else {
						paramObj[kv.name] = kv.value;
					}
				});
				wp.ajax.post( 'tasty_recipes_modify_recipe', {
					post_id: editorModalData.currentPostId,
					nonce: tastyRecipesEditor.modifyNonce,
					recipe: paramObj
				} ).done( function( response ) {
					hideContainer();
					if ( updateCallback ) {
						updateCallback( response );
					}
				} )
				.fail( function( response ) {
					hideContainer();
					if ( updateCallback ) {
						updateCallback( response );
					}
				} );
			} else {
				hideContainer();
			}
		},
	};

	window.tastyRecipesEditorModal = editorModal;
}(window.wp, window.jQuery, window.tastyRecipesEditorModalData));
