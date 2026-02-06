/*global tastyRecipesSettings:true, jQuery:true */
(function( wp, $ ){

	var converter = {

		type: false,
		getAction: 'tasty_recipes_get_count',
		convertAction: 'tasty_recipes_convert',
		template: 'tasty-recipes-convert',
		data: {
			label: '',
			loading: false,
			converting: false,
			count: 0,
			converted: 0,
			errorMessage: '',
		},

		initialize( wrapper ) {
			wrapper.show();
			this.wrapper = wrapper;
			this.type = wrapper.data('type');
			this.data.label = wrapper.data('label');
			this.data.loading = true;
			this.renderTemplate();
			this.bindEvents();
			this.fetchCount();
		},

		renderTemplate() {
			if ( this.type !== this.wrapper.data('type') ) {
				return;
			}
			var template = wp.template( this.template );
			$(this.wrapper).html( template( this.data ) );
		},

		bindEvents() {
			$(this.wrapper).on('click', 'button.start-conversion', $.proxy( function(){
				this.data.converting = true;
				this.renderTemplate();
				this.triggerConvert();
			}, this ));
		},

		fetchCount() {
			wp.ajax.send( this.getAction, {
				data: {
					nonce: tastyRecipesSettings.nonce,
					type: this.type,
				},
				type: 'GET'
			}).done( $.proxy( function( response ){
				this.data.loading = false;
				this.data.count = response.count;
				setTimeout($.proxy(function(){
					this.renderTemplate();
				}, this), 1000 );
			}, this) ).fail( $.proxy( function( response ){
				this.data.loading = false;
				this.data.errorMessage = response.message;
				setTimeout($.proxy(function(){
					this.renderTemplate();
				}, this), 1000 );
			}, this) );
		},

		triggerConvert() {
			this.data.converting = true;
			wp.ajax.send( this.convertAction, {
				data: {
					nonce: tastyRecipesSettings.nonce,
					type: this.type,
				},
				type: 'GET'
			}).done( $.proxy( function( response ){
				this.data.converted += response.converted;
				this.renderTemplate();
				if ( response.count ) {
					this.triggerConvert();
				} else {
					this.data.converting = false;
				}
			}, this) ).fail( $.proxy( function( response ){
				this.data.errorMessage = response.message;
				this.data.converting = false;
				this.renderTemplate();
			}, this) );
		}

	};

	$(document).ready(function(){
		var typeConverter = false;

		$('.tasty-recipes-converter-options button').each(function(){
			var button = $(this);
			wp.ajax.send( converter.getAction, {
				data: {
					nonce: tastyRecipesSettings.nonce,
					type: button.data('type'),
				},
				type: 'GET'
			}).done(function(response ){
				if ( response.count ) {
					button.removeAttr('disabled');
				}
			});
		});

		var wrapper = $('.tasty-recipes-converter-control');
		$('.tasty-recipes-converter-options button').on('click',function(e){
			e.preventDefault();
			var el = $(this);
			$('.tasty-recipes-converter-options button').removeClass('active button-primary');
			$('.tasty-recipes-converter-welcome').hide();
			el.addClass('active button-primary');
			typeConverter = jQuery.extend( true, {}, converter );
			wrapper.data( 'type', el.data('type') );
			wrapper.data( 'label', el.data('label') );
			typeConverter.initialize( wrapper );
		});

		wrapper.on('click', '.tasty-recipes-converter-close', $.proxy( function(){
			typeConverter = false;
			wrapper.html('');
			wrapper.data('type','');
			$('.tasty-recipes-converter-options button').removeClass('active button-primary');
			$('.tasty-recipes-converter-welcome').show();
		}, this) );
	});

}(window.wp, jQuery));
