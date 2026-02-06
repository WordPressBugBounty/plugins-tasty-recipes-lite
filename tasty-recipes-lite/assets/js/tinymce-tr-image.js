/*global tinymce:true, tastyRecipesEditor:true */

tinymce.PluginManager.add('tr_image', function(editor) {
	editor.addButton('tr_image', {
		image: tastyRecipesEditor.pluginURL + '/assets/images/gallery-icon.svg',
		tooltip: 'Image',
		onClick() {
			if ( wp && wp.media && wp.media.editor ) {
				wp.media.editor.open( editor.id );
			}
		}
	});
});
