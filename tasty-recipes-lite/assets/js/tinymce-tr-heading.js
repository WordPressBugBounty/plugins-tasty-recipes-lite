/*global tinymce:true, tastyRecipesEditor:true */

tinymce.PluginManager.add('tr_heading', function(editor) {
	var name = 'h4';
	editor.addButton('tr_heading', {
		image: tastyRecipesEditor.pluginURL + '/assets/images/header-icon.svg',
		tooltip: 'Heading',
		onClick() {
			editor.execCommand( 'mceToggleFormat', false, 'h4' );
		},
		onPostRender() {
			var self = this, setup = function() {
			editor.formatter.formatChanged(name, function(state) {
				self.active(state);
				});
			};
			if (editor.formatter) {
				setup();
			} else {
				editor.on('init', setup);
			}
		}
	});
});
