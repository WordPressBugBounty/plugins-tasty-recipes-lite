/*global jQuery:true, inlineEditPost:true */
(function($) {
	$(document).ready(function(){
		$('table.wp-list-table').on('click', '.editinline', function() {
			var postId = inlineEditPost.getId(this);
			var recipeId = $('tr#post-'+postId+' .tasty-recipes-edit-button').data('recipe-id');
			if (recipeId) {
				$('tr#edit-'+postId+' .tasty-recipes-edit-button').attr('data-recipe-id', recipeId);
				$('tr#edit-'+postId+' .tasty-recipes-edit-button').show();
			} else {
				$('tr#edit-'+postId+' .tasty-recipes-edit-button').hide();
			}
		});
	});
}(jQuery));

const openRecipeFromList = async ( event ) => {
	const button = event.target.closest( '.tasty-recipes-edit-button' );
	if ( ! button || ! button.closest( 'table.wp-list-table' ) ) {
		return;
	}

	event.preventDefault();

	const postId = button.dataset.recipeId;
	if ( ! postId ) {
		return;
	}

	const store = window.tastyRecipesManagePosts.recipeDataStore;
	const openRecipe = ( recipe ) => {
		window.tastyRecipesEditorModal.open( recipe, ( response ) => {
			store[ postId ] = response.recipe;
		} );
	};

	if ( store[ postId ] !== undefined ) {
		openRecipe( store[ postId ] );
		return;
	}

	const formData = new FormData();
	formData.append( 'action', 'tasty_recipes_get_recipe_json' );
	formData.append( 'recipe_id', postId );
	formData.append( 'nonce', window.tastyRecipesEditorModalData.modifyNonce );

	try {
		const response = await fetch( window.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} );
		const data = await response.json();

		if ( ! data || ! data.success || ! data.data || ! data.data.recipe ) {
			window.alert( 'Unable to find the initial recipe data.' );
			return;
		}

		store[ postId ] = data.data.recipe;
		openRecipe( store[ postId ] );
	} catch {
		window.alert( 'Unable to find the initial recipe data.' );
	}
};

if ( 'loading' === document.readyState ) {
	document.addEventListener( 'DOMContentLoaded', () => {
		document.addEventListener( 'click', openRecipeFromList );
	} );
} else {
	document.addEventListener( 'click', openRecipeFromList );
}
