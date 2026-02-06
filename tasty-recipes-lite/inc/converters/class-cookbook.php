<?php
/**
 * Converter class for Cookbook Plugin.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Converters;

use Tasty_Recipes\Distribution_Metadata;
use Tasty_Recipes\Objects\Recipe;
use Tasty_Recipes\Utils;

/**
 * Converter class for Cookbook Plugin.
 */
class Cookbook extends Converter {

	/**
	 * Matching string for existing recipes.
	 *
	 * @var array|string
	 */
	protected static $match_string = array(
		'<!--Cookbook Recipe',
		'[cookbook_recipe',
	);

	/**
	 * Get recipe content to convert.
	 *
	 * @param string $content Existing content that may have a recipe.
	 *
	 * @return false|string
	 */
	public static function get_existing_to_convert( $content ) {
		if ( preg_match( '/<!--Cookbook Recipe (\d+)-->.+?<!--End Cookbook Recipe-->/ms', $content, $matches ) ) {
			return $matches[0];
		}
		return Utils::get_existing_shortcode( $content, 'cookbook_recipe' );
	}

	/**
	 * Convert recipe content to Tasty Recipes format.
	 *
	 * @param string $existing Existing content that may have a recipe.
	 * @param int    $post_id  ID for the post with the recipe.
	 *
	 * @return false|Recipe
	 */
	public static function create_recipe_from_existing( $existing, $post_id ) { // phpcs:ignore SlevomatCodingStandard
		preg_match( '/<!--Cookbook Recipe (\d+)-->/', $existing, $matches );
		if ( isset( $matches[1] ) ) {
			$id = $matches[1];
		} elseif ( false !== stripos( $existing, '[cookbook_recipe' ) ) {
			$existing = substr( $existing, 1, -1 ); // Remove '[' and ']' from shortcode.
			$parsed   = shortcode_parse_atts( $existing );
			if ( empty( $parsed['id'] ) ) {
				return false;
			}
			$id = $parsed['id'];
		} else {
			return false;
		}

		$post_id       = (int) $post_id; // Avoid UnusedVariable.
		$existing_post = get_post( $id );
		if ( ! $existing_post ) {
			return false;
		}

		$recipe         = Recipe::create();
		$converted_data = array();

		if ( ! $recipe ) {
			return false;
		}

		$mapping = array(
			// Cookbook -> Tasty Recipes.
			'post_title'            => 'title',
			'cookbook_author'       => 'author_name',
			'_thumbnail_id'         => 'image_id',
			'post_content'          => 'description',
			'cookbook_ingredients'  => 'ingredients',
			'cookbook_instructions' => 'instructions',
			'cookbook_notes'        => 'notes',
			'cookbook_course'       => 'category',
			'cookbook_cuisine'      => 'cuisine',
			'cookbook_servings'     => 'yield',
			'cookbook_prep_time'    => 'prep_time',
			'cookbook_cook_time'    => 'cook_time',
			'cookbook_total_time'   => 'total_time',
			'cookbook_nutrition'    => 'nutrition',
		);
		foreach ( $mapping as $cb => $tr ) {
			$value = 'post_' === substr( $cb, 0, 5 ) ? $existing_post->{$cb} : get_post_meta( $existing_post->ID, $cb, true );

			// Additional processing for these fields.
			switch ( $cb ) {
				case 'cookbook_ingredients':
				case 'cookbook_instructions':
					$value = ! empty( $value['raw'] ) ? $value['raw'] : '';
					break;
				case 'cookbook_servings':
					$serving_size = get_post_meta( $existing_post->ID, 'cookbook_servings_unit', true );
					if ( $serving_size ) {
						$value .= ' ' . $serving_size;
					}
					break;
				case 'cookbook_prep_time':
				case 'cookbook_cook_time':
				case 'cookbook_total_time':
					$nv = '';
					foreach ( array( 'hours', 'minutes', 'seconds' ) as $period ) {
						if ( isset( $value[ $period ] ) && ! empty( $value[ $period ] ) ) {
							$nv .= $value[ $period ] . ' ' . $period . ' ';
						}
					}
					$value = trim( $nv );
					break;
				case 'cookbook_nutrition':
					$nv = array(
						'serving_size',
						'calories',
						'sugar',
						'sodium',
						'carbohydrates',
						'fiber',
						'protein',
						'fat',
						'saturated_fat',
						'unsaturated_fat',
						'trans_fat',
						'cholesterol',
					);
					foreach ( $nv as $n ) {
						if ( isset( $value[ $n ] ) ) {
							$rawv = $value[ $n ];
							// Cookbook stores '0' but doesn't display it,
							// so we should discard it in the migration process.
							if ( 0 === (int) $rawv ) {
								continue;
							}
							if ( is_numeric( $rawv ) ) {
								if ( in_array( $n, array( 'sugar', 'carbohydrates', 'fiber', 'protein', 'fat', 'saturated_fat', 'unsaturated_fat', 'trans_fat' ), true ) ) {
									$rawv .= ' g';
								} elseif ( in_array( $n, array( 'sodium', 'cholesterol' ), true ) ) {
									$rawv .= ' mg';
								}
							}
							$converted_data[ $n ] = $rawv;
						}
					}
					$value = ''; // Don't set 'cookbook_nutrition'.
					break;
			}

			if ( '' !== $value ) {
				$converted_data[ $tr ] = $value;
			}
		}

		return self::save_converted_data_to_recipe( $converted_data, $recipe );
	}
}
