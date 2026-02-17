<?php
/**
 * Manages Frontend events.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

use Tasty_Recipes;
use Tasty_Recipes\Objects\Recipe;
use WP_Post;

/**
 * Frontend class.
 */
class Frontend {
	/**
	 * Singleton instance.
	 *
	 * @since 3.8
	 *
	 * @var Frontend
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @since 3.8
	 *
	 * @return Frontend
	 */
	public static function get_instance() {
		if ( null !== self::$instance ) {
			return self::$instance;
		}

		self::$instance = new self();
		self::$instance->add_hooks();
		return self::$instance;
	}

	/**
	 * Subscribe hooks.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	private function add_hooks() {
		add_filter( 'tasty_recipes_quick_links', array( $this, 'insert_rating_quick_link' ), 10, 4 );
	}

	/**
	 * Renders CSS vars in <head>.
	 *
	 * @deprecated 3.11.1
	 *
	 * @return void
	 */
	public static function action_wp_head() {
		_deprecated_function( __METHOD__, '3.11.1' );
	}

	/**
	 * Insert rating quick link into quick links array.
	 *
	 * @since 3.9
	 *
	 * @param array   $links                  Current list of available quick links.
	 * @param WP_Post $post                   Current post object.
	 * @param int     $recipe_id              Current recipe ID.
	 * @param array   $should_prepend_jump_to Array of selected quick links.
	 *
	 * @return array
	 *
	 * @TODO Update this method to extract the html outside it after merging PR#2351
	 */
	public function insert_rating_quick_link( $links, $post, $recipe_id, $should_prepend_jump_to ) {
		if ( ! in_array( 'rating', $should_prepend_jump_to, true ) || ! Ratings::is_enabled() ) {
			return $links;
		}

		$recipe = Recipe::get_by_id( $recipe_id );
		if ( ! $recipe ) {
			return $links;
		}

		$total_reviews     = $recipe->get_total_reviews();
		$rating_link_class = '';

		if ( $total_reviews ) {
			$average_rating       = $recipe->get_average_rating();
			$recipe_rating_label  = '<span data-tasty-recipes-customization="detail-label-color.color" class="rating-label">';
			$recipe_rating_label .= Ratings::get_rating_label( $total_reviews, $average_rating );
			$recipe_rating_label .= '</span>';
			$recipe_rating_icons  = Ratings::get_rendered_rating( $average_rating );
			$rating_link_class    = 'tasty-recipes-has-ratings';
		} else {
			$recipe_rating_label = Quick_Links::get_label_value( 'rating' );
			$recipe_rating_icons = '';
			$rating_link_class   = Quick_Links::quick_links_classes( $rating_link_class );
		}
		$links['rating'] = '<a class="tasty-recipes-rating-link tasty-recipes-scrollto ' . esc_attr( $rating_link_class ) . '" href="#respond">' .
			$recipe_rating_icons . $recipe_rating_label .
			'</a>';

		return $links;
	}

	/**
	 * Print instacart HTML.
	 *
	 * @since 3.8
	 * @deprecated 1.0
	 *
	 * @return void
	 */
	public function print_instacart_html() {
		_deprecated_function( __METHOD__, '1.0' );
	}

	/**
	 * Insert Instacart widget script.
	 *
	 * @since 3.8
	 * @deprecated 1.0
	 *
	 * @return void
	 */
	public function insert_instacart_script() {
		_deprecated_function( __METHOD__, '1.0' );
	}
}
