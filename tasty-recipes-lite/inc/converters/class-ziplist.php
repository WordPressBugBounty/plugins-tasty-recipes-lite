<?php
/**
 * Converter class for ZipList.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Converters;

use Tasty_Recipes\Distribution_Metadata;
use Tasty_Recipes\Objects\Recipe;

/**
 * Converter class for ZipList.
 */
class ZipList extends Converter {

	/**
	 * Matching string for existing recipes.
	 *
	 * @var array|string
	 */
	protected static $match_string = array(
		'[amd-zlrecipe-recipe',
		'<!-- wp:zip-recipes/recipe-block',
	);

	/**
	 * Matching regex pattern for existing recipes.
	 *
	 * @var string
	 */
	private static $regex_pattern = '#(\[amd-zlrecipe-recipe:([\d]+)\]|<!-- wp:zip-recipes/recipe-block(.+)/-->)#Us';

	/**
	 * Get recipe content to convert.
	 *
	 * @param string $content Existing content that could have a recipe.
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
	 * @param string $existing Existing content that could have a recipe.
	 * @param int    $post_id  ID for the post with the recipe.
	 *
	 * @return false|Recipe
	 */
	public static function create_recipe_from_existing( $existing, $post_id ) { // phpcs:ignore VariableAnalysis
		global $wpdb;

		// Shortcode: [amd-zlrecipe-recipe:1].
		// Block: <!-- wp:zip-recipes/recipe-block {"id":"1"} /-->.
		preg_match( self::$regex_pattern, $existing, $matches );
		if ( empty( $matches[2] ) && empty( $matches[3] ) ) {
			return false;
		}
		$recipe_id = 0;
		// Shortcode.
		if ( ! empty( $matches[2] ) ) {
			$recipe_id = (int) $matches[2];
			// Block.
		} elseif ( ! empty( $matches[3] ) ) {
			$block_attr = json_decode( trim( $matches[3] ), true );
			if ( empty( $block_attr['id'] ) ) {
				return false;
			}
			$recipe_id = (int) $block_attr['id'];
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}amd_zlrecipe_recipes WHERE recipe_id=%d", $recipe_id ) );
		if ( ! $existing ) {
			return false;
		}
		$existing = (object) $existing;

		$mapping_fields = array(
			// ZipList        -> Tasty Recipes.
			'recipe_title'  => 'title',
			'recipe_image'  => 'image_id',
			'summary'       => 'description',
			'notes'         => 'notes',
			'prep_time'     => 'prep_time',
			'cook_time'     => 'cook_time',
			'total_time'    => 'total_time',
			'yield'         => 'yield',
			'serving_size'  => 'serving_size',
			'calories'      => 'calories',
			'fat'           => 'fat',
			'ingredients'   => 'ingredients',
			'instructions'  => 'instructions',
			'carbs'         => 'carbohydrates',
			'protein'       => 'protein',
			'fiber'         => 'fiber',
			'sugar'         => 'sugar',
			'saturated_fat' => 'saturated_fat',
			'sodium'        => 'sodium',
			'category'      => 'category',
			'cuisine'       => 'cuisine',
			'trans_fat'     => 'trans_fat',
			'cholesterol'   => 'cholesterol',
		);

		$recipe         = Recipe::create();
		$converted_data = array();
		if ( ! $recipe ) {
			return false;
		}

		foreach ( $mapping_fields as $zl => $tr ) {

			if ( ! isset( $existing->$zl ) ) {
				continue;
			}

			$value = $existing->$zl;

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

		// Persist ratings data if it exists.
		$ratings_table = "{$wpdb->prefix}zrdn_visitor_ratings";
		$results       = $wpdb->get_results( $wpdb->prepare( 'SHOW TABLES LIKE %s', $ratings_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( ! empty( $results ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$ratings = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}zrdn_visitor_ratings WHERE recipe_id=%d", $recipe_id ) );
			update_post_meta( $recipe->get_id(), 'zrp_ratings', $ratings );
		}

		return self::save_converted_data_to_recipe( $converted_data, $recipe );
	}
}
