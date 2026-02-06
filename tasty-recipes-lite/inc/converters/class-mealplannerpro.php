<?php
/**
 * Converter class for Meal Planner Pro.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Converters;

use Tasty_Recipes\Distribution_Metadata;
use Tasty_Recipes\Objects\Recipe;
use Tasty_Recipes\Ratings;
use Tasty_Recipes\Shortcodes;

/**
 * Converter class for Meal Planner Pro.
 */
class MealPlannerPro extends Converter {

	/**
	 * Matching string for existing recipes.
	 *
	 * @var array|string
	 */
	protected static $match_string = '[mpprecipe-recipe';

	/**
	 * Get recipe content to convert.
	 *
	 * @param string $content Existing content that could include a recipe.
	 *
	 * @return object|string
	 */
	public static function get_existing_to_convert( $content ) {
		preg_match( '#\[mpprecipe-recipe:[\d]+\]#s', $content, $matches );
		return ! empty( $matches[0] ) ? $matches[0] : '';
	}

	/**
	 * Convert recipe content to Tasty Recipes format.
	 *
	 * @param string $existing Existing content that could include a recipe.
	 * @param int    $post_id  ID for the post with the recipe.
	 *
	 * @return false|Recipe
	 */
	public static function create_recipe_from_existing( $existing, $post_id ) {
		global $wpdb;

		preg_match( '#\[mpprecipe-recipe:([\d]+)\]#s', $existing, $matches );
		if ( empty( $matches[1] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mpprecipe_recipes WHERE recipe_id=%d", $matches[1] ) );
		if ( ! $existing ) {
			return false;
		}
		$existing = (object) $existing;

		$mapping_fields = array(
			// MPP            -> Tasty Recipes.
			'recipe_title' => 'title',
			'author'       => 'author_name',
			'cuisine'      => 'cuisine',
			'type'         => 'category',
			'recipe_image' => 'image_id',
			'summary'      => 'description',
			'notes'        => 'notes',
			'prep_time'    => 'prep_time',
			'cook_time'    => 'cook_time',
			'total_time'   => 'total_time',
			'yield'        => 'yield',
			'serving_size' => 'serving_size',
			'ingredients'  => 'ingredients',
			'instructions' => 'instructions',
		);

		$recipe         = Recipe::create();
		$converted_data = array();

		if ( ! $recipe ) {
			return false;
		}

		foreach ( $mapping_fields as $mpp => $tr ) {

			$value = $existing->$mpp;

			if ( is_null( $value ) ) {
				continue;
			}

			if ( in_array( $tr, array( 'prep_time', 'cook_time', 'total_time' ), true ) ) {
				$value = Distribution_Metadata::get_time_for_duration( $value );
			}

			if ( 'image_id' === $tr ) {
				$value        = static::get_image_id_from_file( $value );
				$thumbnail_id = get_post_thumbnail_id( $post_id );
				if ( ! $value && $thumbnail_id ) {
					$value = $thumbnail_id;
				}
			}

			if ( in_array( $tr, array( 'description', 'ingredients', 'instructions', 'notes' ), true ) ) {
				$value = str_replace( "\r\n", PHP_EOL, $value );
			}

			if ( in_array( $tr, array( 'ingredients', 'instructions' ), true ) ) {
				$list_style = 'instructions' === $tr ? 'ol' : 'ul';
				$value      = self::process_lines_into_lists_and_headings( $value, $list_style );
			}

			if ( in_array( $tr, array( 'description', 'notes' ), true ) ) {
				$value = self::process_markdownish_into_html( $value );
			}
			$converted_data[ $tr ] = $value;
		}
		return self::save_converted_data_to_recipe( $converted_data, $recipe );
	}

	/**
	 * Also handle [b] and [i] tags for Meal Planner Pro.
	 *
	 * @param string $item String with markdown to be converted.
	 *
	 * @return string
	 */
	protected static function process_markdownish_into_html( $item ) {
		$output         = parent::process_markdownish_into_html( $item );
		$search_replace = array(
			'[b]'  => '<strong>',
			'[/b]' => '</strong>',
			'[i]'  => '<em>',
			'[/i]' => '</em>',
		);
		$output         = str_replace( array_keys( $search_replace ), array_values( $search_replace ), $output );
		return $output;
	}
}
