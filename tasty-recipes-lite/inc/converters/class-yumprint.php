<?php
/**
 * Converter class for YumPrint.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Converters;

use Tasty_Recipes\Distribution_Metadata;
use Tasty_Recipes\Utils;
use Tasty_Recipes\Objects\Recipe;

/**
 * Converter class for YumPrint.
 */
class YumPrint extends Converter {

	/**
	 * Matching string for existing recipes.
	 *
	 * @var array|string
	 */
	protected static $match_string = '[yumprint-recipe';

	/**
	 * Get recipe content to convert.
	 *
	 * @param string $content Existing content that may include a recipe.
	 *
	 * @return false|string
	 */
	public static function get_existing_to_convert( $content ) {
		return Utils::get_existing_shortcode( $content, 'yumprint-recipe' );
	}

	/**
	 * Convert recipe content to Tasty Recipes format.
	 *
	 * @param string $existing Existing content that may include a recipe.
	 * @param int    $post_id  ID for the post containing the recipe.
	 *
	 * @return false|Recipe
	 */
	public static function create_recipe_from_existing( $existing, $post_id ) { // phpcs:ignore SlevomatCodingStandard
		global $wpdb;

		$existing = substr( $existing, 1, -1 ); // Remove '[' and ']' from shortcode.
		$parsed   = shortcode_parse_atts( $existing );
		if ( empty( $parsed['id'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}yumprint_recipe_recipe WHERE id=%d", $parsed['id'] ) );
		if ( ! $existing ) {
			return false;
		}

		$post_id = (int) $post_id; // Avoid UnusedVariable.

		$recipe         = Recipe::create();
		$converted_data = array();
		if ( ! $recipe ) {
			return false;
		}

		$mapping_fields = array(
			// YumPrint      -> Tasty Recipes.
			'title'       => 'title',
			'author'      => 'author_name',
			'image'       => 'image_id',
			'summary'     => 'description',
			'notes'       => 'notes',
			'prepTime'    => 'prep_time',
			'cookTime'    => 'cook_time',
			'totalTime'   => 'total_time',
			'yields'      => 'yield',
			'servings'    => 'serving_size',
			'ingredients' => 'ingredients',
			'directions'  => 'instructions',
		);

		$data = json_decode( $existing->recipe );
		if ( ! is_object( $data ) ) {
			return false;
		}

		foreach ( $mapping_fields as $mpp => $tr ) {

			$value = isset( $data->$mpp ) ? $data->$mpp : false;

			if ( 'image_id' === $tr ) {
				$value = self::get_image_id_from_file( $value );
			}

			if ( false !== $value && in_array( $tr, array( 'ingredients', 'instructions', 'notes' ), true ) ) {
				$new_value    = '';
				$list_style   = 'instructions' === $tr ? 'ol' : 'ul';
				$list_opening = '<' . $list_style . '>';
				$list_closing = '</' . $list_style . '>';
				$is_open      = false;
				foreach ( $value as $bits ) {
					if ( $is_open ) {
						$new_value .= $list_closing . PHP_EOL;
						$is_open    = false;
					}
					if ( ! empty( $bits->title ) ) {
						$new_value .= PHP_EOL . '<h4>' . $bits->title . '</h4>' . PHP_EOL;
					}

					$is_open    = true;
					$new_value .= $list_opening . PHP_EOL;
					$new_value .= '<li>' . implode( '</li>' . PHP_EOL . '<li>', $bits->lines ) . '</li>' . PHP_EOL;
				}
				if ( $is_open ) {
					$new_value .= $list_closing . PHP_EOL;
				}
				$value = trim( $new_value );
			}
			$converted_data[ $tr ] = $value;
		}

		$row       = $wpdb->get_row( "SELECT theme FROM {$wpdb->prefix}yumprint_recipe_theme WHERE name='Current'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$nutrition = false;
		if ( ! empty( $row->theme ) ) {
			$theme = json_decode( $row->theme, true );
			if ( ! empty( $theme['layout']['nutrition'] ) ) {
				$nutrition = true;
			}
		}
		if ( $nutrition && isset( $data->servings ) && is_numeric( $data->servings ) ) {
			$servings       = (int) $data->servings;
			$mapping_fields = array(
				// YumPrint      -> Tasty Recipes.
				'calories'           => 'calories',
				'totalFat'           => 'fat',
				'totalCarbohydrates' => 'carbohydrates',
				'protein'            => 'protein',
				'transFat'           => 'trans_fat',
				'saturatedFat'       => 'saturated_fat',
				'unSaturatedFat'     => 'unsaturated_fat',
				'sodium'             => 'sodium',
				'sugars'             => 'sugar',
				'cholesterol'        => 'cholesterol',
			);

			$data = json_decode( $existing->nutrition );
			if ( ! is_object( $data ) ) {
				return self::save_converted_data_to_recipe( $converted_data, $recipe );
			}

			foreach ( $mapping_fields as $mpp => $tr ) {

				if ( 'unSaturatedFat' === $mpp ) {
					$value = $data->{'polyunsaturatedFat'} + $data->{'monounsaturatedFat'};
				} else {
					$value = $data->$mpp;
				}

				$value                 = $value / $servings;
				$value                 = round( $value );
				$converted_data[ $tr ] = $value;
			}
		}
		return self::save_converted_data_to_recipe( $converted_data, $recipe );
	}
}
