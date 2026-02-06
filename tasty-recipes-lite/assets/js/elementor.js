/*global elementor:true */
(function(){

	var tastyRecipeControl = elementor.modules.controls.BaseData.extend({
		onReady() {
			var self = this;

			this.$el.find('button[data-event="tasty-recipes:edit-recipe"]').on('click', function() {
				var initialRecipe;
				var recipeId = self.ui.input.val();
				if (recipeId && typeof window.tastyRecipesElementorControl.recipeDataStore[recipeId] !== 'undefined') {
					initialRecipe = window.tastyRecipesElementorControl.recipeDataStore[recipeId];
				} else {
					initialRecipe = {
						author_name: window.tastyRecipesEditor.defaultAuthorName
					};
				}
				window.tastyRecipesEditorModal.open(initialRecipe, function(response) {
					self.setValue(response.recipe.id);
					window.tastyRecipesElementorControl.recipeDataStore[response.recipe.id] = response.recipe;
				});
			});
		},
	});

	elementor.addControlView('tasty-recipe-control', tastyRecipeControl);
}());
