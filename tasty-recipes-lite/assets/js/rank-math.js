(function(){

	var getAdditionalContent = function() {
		if ( typeof window.tastyRecipesBlockEditor === 'undefined' ) {
			return '';
		}
		var additionalContent = '',
			store = window.tastyRecipesBlockEditor.recipeDataStore;
		Object.keys(window.tastyRecipesBlockEditor.recipeDataStore).forEach(function(key) {
			additionalContent += store[key].description + "\n\n";
			additionalContent += store[key].ingredients + "\n\n";
			additionalContent += store[key].instructions + "\n\n";
			additionalContent += store[key].notes;
		});
		return additionalContent;
	};

	wp.hooks.addFilter(
		'rank_math_content',
		'tasty-recipes-rank-math',
		function( content ) {
			content += "\n\n" + getAdditionalContent();
			return content;
		},
		11
	);

	wp.hooks.addAction(
		'tasty_recipes_updated_recipe',
		'tasty-recipes-rank-math',
		function() {
			window.rankMathEditor.refresh('content');
		}
	);

}());
