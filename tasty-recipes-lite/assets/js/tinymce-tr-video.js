/*global tinymce:true, tastyRecipesEditor:true */

tinymce.PluginManager.add('tr_video', function(editor) {
	editor.addButton('tr_video', {
		image: tastyRecipesEditor.pluginURL + '/assets/images/video-icon.svg',
		tooltip: 'Video',
		onClick() {
			var frame = wp.media.embed.edit( '', true );

			frame.state( 'embed' ).props.on( 'change:url', function( model, url ) {
				if ( url && model.get( 'url' ) ) {
					frame.state( 'embed' ).metadata = model.toJSON();
				}
			} );

			frame.state( 'embed' ).on( 'select', function() {
				var data = frame.state( 'embed' ).metadata;

				editor.execCommand('mceInsertContent', false, data.url);
			} );

			frame.on( 'close', function() {
				frame.detach();
			} );

			frame.open();
		}
	});
});
