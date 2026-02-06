/*global jQuery:true, inlineEditPost:true */
(function($) {
	$(document).ready(function(){
		$('table.wp-list-table').on('click', '.editinline', function() {
			var postId = inlineEditPost.getId(this);
			var recipeId = $('tr#post-'+postId+' .tasty-recipes-edit-button').data('recipe-id');
			if (recipeId) {
				$('tr#edit-'+postId+' .tasty-recipes-edit-button').data('recipe-id', recipeId);
				$('tr#edit-'+postId+' .tasty-recipes-edit-button').show();
			} else {
				$('tr#edit-'+postId+' .tasty-recipes-edit-button').hide();
			}
		});
		$('table.wp-list-table').on('click', '.tasty-recipes-edit-button', function(e){
			e.preventDefault();
			var postId = $(this).data('recipe-id');
			if (typeof window.tastyRecipesManagePosts.recipeDataStore[postId] === 'undefined') {
				/* eslint-disable-next-line no-alert */
				window.alert('Unable to find the initial recipe data.');
			}
			window.tastyRecipesEditorModal.open(
				window.tastyRecipesManagePosts.recipeDataStore[postId],
				function( response ) {
					window.tastyRecipesManagePosts.recipeDataStore[postId] = response.recipe;
				}
			);
		});
	});
}(jQuery));
