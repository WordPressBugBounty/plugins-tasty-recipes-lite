<?php
/**
 * Integrates Tasty Recipes with Jetpack.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Integrations;

/**
 * Integrates Tasty Recipes with Jetpack.
 */
class Jetpack {

	/**
	 * Adds our CPT to the list of ignored CPTs.
	 *
	 * @param array $post_types Existing post types.
	 *
	 * @return array
	 */
	public static function filter_jetpack_content_options_featured_image_exclude_cpt( $post_types ) {
		$post_types[] = 'tasty_recipe';
		return $post_types;
	}
}
