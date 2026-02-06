<?php
/**
 * Integrates Tasty Recipes with wpDiscuz.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Integrations;

/**
 * Integrates Tasty Recipes with wpDiscuz.
 */
class WpDiscuz {

	/**
	 * Recalculates recipe ratings after a new wpDiscuz comment is added.
	 *
	 * @param object $comment Comment object.
	 *
	 * @return void
	 */
	public static function action_wpdiscuz_after_comment_post( $comment ) {
		// Only process when there is one embedded recipe in a post.
		$recipes = \Tasty_Recipes::get_recipes_for_post( $comment->comment_post_ID );
		if ( 1 !== count( $recipes ) ) {
			return;
		}

		$recipe = reset( $recipes );
		\Tasty_Recipes\Ratings::update_recipe_rating( $recipe, $comment->comment_post_ID );
	}
}
