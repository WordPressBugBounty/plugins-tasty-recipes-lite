<?php
/**
 * Converter class for Yummly.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Converters;

use Tasty_Recipes\Distribution_Metadata;
use Tasty_Recipes\Objects\Recipe;

/**
 * Converter class for Yummly.
 */
class Yummly extends Converter {

	/**
	 * Matching string for existing recipes.
	 *
	 * @var array|string
	 */
	protected static $match_string = '[amd-yrecipe-recipe';

	/**
	 * Matching regex pattern for existing recipes.
	 *
	 * @var string
	 */
	private static $regex_pattern = '#\[amd-yrecipe-recipe:([\d]+)\]#s';

	/**
	 * Get recipe content to convert.
	 *
	 * @param string $content Existing content that may have a recipe.
	 *
	 * @return object|string
	 */
	public static function get_existing_to_convert( $content ) {
		preg_match( self::$regex_pattern, $content, $matches );
		return ! empty( $matches[0] ) ? $matches[0] : '';
	}

	/**
	 * Convert recipe content to Tasty Recipes format.
	 *
	 * @param string $existing Existing content with a recipe.
	 * @param int    $post_id  ID for the post with the recipe.
	 *
	 * @return false|Recipe
	 */
	public static function create_recipe_from_existing( $existing, $post_id ) { // phpcs:ignore SlevomatCodingStandard
		global $wpdb;

		preg_match( self::$regex_pattern, $existing, $matches );
		if ( empty( $matches[1] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}amd_yrecipe_recipes WHERE recipe_id=%d", $matches[1] ) );
		if ( ! $existing ) {
			return false;
		}
		$existing = (object) $existing;

		$post_id = (int) $post_id; // Avoid UnusedVariable.

		$mapping_fields = array(
			// Yummly      -> Tasty Recipes.
			'recipe_title' => 'title',
			'recipe_image' => 'image_id',
			'summary'      => 'description',
			'notes'        => 'notes',
			'prep_time'    => 'prep_time',
			'cook_time'    => 'cook_time',
			'total_time'   => 'total_time',
			'yield'        => 'yield',
			'serving_size' => 'serving_size',
			'calories'     => 'calories',
			'fat'          => 'fat',
			'ingredients'  => 'ingredients',
			'instructions' => 'instructions',
		);

		$recipe         = Recipe::create();
		$converted_data = array();
		if ( ! $recipe ) {
			return false;
		}

		foreach ( $mapping_fields as $yl => $tr ) {

			$value = $existing->$yl;

			if ( is_null( $value ) ) {
				continue;
			}

			if ( in_array( $tr, array( 'prep_time', 'cook_time', 'total_time' ), true ) ) {
				$value = Distribution_Metadata::get_time_for_duration( $value );
			}

			if ( 'image_id' === $tr ) {
				$value = self::get_image_id_from_file( $value );
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
}
