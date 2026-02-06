<?php
/**
 * Converter class for Simple Recipe Pro.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Converters;

use Tasty_Recipes\Utils;
use Tasty_Recipes\Objects\Recipe;

/**
 * Converter class for Simple Recipe Pro.
 */
class Simple_Recipe_Pro extends Converter {

	/**
	 * Matching string for existing recipes.
	 *
	 * @var array|string
	 */
	protected static $match_string = '[simple-recipe';

	/**
	 * Shortcode tag for existing recipes.
	 *
	 * @var string
	 */
	protected static $shortcode_tag = 'simple-recipe';

	/**
	 * Convert recipe content to Tasty Recipes format.
	 *
	 * @param string $existing Existing content that may have a recipe.
	 * @param int    $post_id  ID for the post with the recipe.
	 *
	 * @return false|Recipe
	 */
	public static function create_recipe_from_existing( $existing, $post_id ) { // phpcs:ignore SlevomatCodingStandard

		$existing_post = get_post( $post_id );
		if ( ! $existing_post ) {
			return false;
		}

		preg_match( '#\[simple-recipe:([^\]]+)\]#s', $existing, $matches );
		if ( ! empty( $matches[0] ) ) {
			$mv = get_post_meta( $post_id, $matches[0], true );
			if ( ! empty( $mv ) ) {
				$one_meta_data = array();
				$bits          = explode( PHP_EOL . ';', $mv );
				$bits[0]       = ltrim( $bits[0], ';' );
				foreach ( $bits as $bit ) {
					$delimiter = strpos( $bit, ':' );
					$key       = str_replace( ' ', '_', strtolower( substr( $bit, 0, $delimiter ) ) );
					if ( 'name' === $key ) {
						$key = 'recipename';
					}
					if ( 'total_servings' === $key ) {
						$key = 'servings';
					}
					if ( 'by' === $key ) {
						$key = 'author';
					}
					if ( in_array(
						$key,
						array(
							'total_fat',
							'saturated_fat',
							'dietary_fiber',
						),
						true
					) ) {
						$key = str_replace( '_', '', $key );
					}
					$value                 = trim( substr( $bit, $delimiter + 1 ) );
					$one_meta_data[ $key ] = $value;
				}
			}
		}

		$recipe         = Recipe::create();
		$converted_data = array();
		$srp_option     = get_option( '_simple_recipe_pro', array() );

		if ( ! $recipe ) {
			return false;
		}

		$mapping = array(
			// Simple Recipe Pro -> Tasty Recipes.
			'recipename'     => 'title',
			'image'          => 'image_id',
			'author'         => 'author_name',
			'description'    => 'description',
			'ingredients'    => 'ingredients',
			'directions'     => 'instructions',
			'prep_time'      => 'prep_time',
			'cook_time'      => 'cook_time',
			'total_time'     => 'total_time',
			'servings'       => 'yield',
			'serving_size'   => 'serving_size',
			'recipe_type'    => 'category',
			'cuisine'        => 'cuisine',
			'notes'          => 'notes',
			'nutrition_data' => 'nutrition',
		);
		foreach ( $mapping as $srp => $tr ) {
			if ( ! empty( $one_meta_data ) ) {
				$value = isset( $one_meta_data[ $srp ] ) ? $one_meta_data[ $srp ] : '';
			} else {
				$value = get_post_meta( $existing_post->ID, "_simple_recipe_pro_{$srp}", true );
			}

			// Additional processing for these fields.
			switch ( $srp ) {
				case 'image':
					$value = self::get_image_id_from_file( $value );
					break;
				case 'author':
					$author_enabled = true;
					if ( ! empty( $srp_option['recipeauthor'] ) && 'off' === $srp_option['recipeauthor'] ) {
						$author_enabled = false;
					}
					if ( ! $value && $author_enabled ) {
						$user = get_user_by( 'id', $existing_post->post_author );
						if ( $user ) {
							$value = $user->display_name;
						}
					}
					break;
				case 'prep_time':
					$value = self::format_times( $value );
					break;
				case 'cook_time':
					$value = self::format_times( $value );
					break;
				case 'total_time':
					$value = self::format_times( $value );
					break;
				case 'ingredients':
					if ( is_string( $value ) && in_array( $value[0], array( '[', '{' ), true ) ) {
						$try_json = json_decode( $value, true );
						if ( false !== $try_json ) {
							$value = $try_json;
						}
					}
					if ( is_string( $value ) ) {
						break;
					}
					$new_value = '';
					foreach ( $value as $ingredient ) {
						if ( ':' === substr( $ingredient['ingredient'], -1 ) ) {
							$new_value .= '</ul><h4>' . $ingredient['ingredient'] . '</h4><ul>';
						} else {
							$new_value .= '<li>' . trim( $ingredient['portion'] . ' ' . $ingredient['ingredient'] ) . '</li>';
						}
					}
					if ( ! empty( $new_value ) ) {
						$new_value = '<ul>' . $new_value . '</ul>';
						if ( '<ul></ul>' === substr( $new_value, 0, 9 ) ) {
							$new_value = substr( $new_value, 9 );
						}
						if ( '<ul></ul>' === substr( $new_value, -9 ) ) {
							$new_value = substr( $new_value, 0, -9 );
						}
						$value = $new_value;
					}
					break;
				case 'nutrition_data':
					$nutrition_enabled = true;
					if ( ! empty( $srp_option['nutrition'] ) && 'off' === $srp_option['nutrition'] ) {
						$nutrition_enabled = false;
					}
					if ( get_post_meta( $existing_post->ID, '_simple_recipe_pro_hidenutrition', true ) ) {
						$nutrition_enabled = false;
					}
					if ( $nutrition_enabled ) {
						$saved_nutrition = array(
							'calories'     => 'calories',
							'totalfat'     => 'fat',
							'saturatedfat' => 'saturated_fat',
							'transfat'     => 'trans_fat',
							'cholesterol'  => 'cholesterol',
							'sodium'       => 'sodium',
							'carbohydrate' => 'carbohydrates',
							'dietaryfiber' => 'fiber',
							'sugars'       => 'sugar',
							'protein'      => 'protein',
						);
						if ( ! empty( $one_meta_data ) ) {
							$compare_value = $one_meta_data;
						} else {
							$compare_value = json_decode( $value, true );
						}
						foreach ( $saved_nutrition as $nk => $tk ) {
							if ( isset( $compare_value[ $nk ] ) && '' !== $compare_value[ $nk ] ) {
								$converted_data[ $tk ] = $compare_value[ $nk ];
							}
						}
					}
					// Don't set the 'nutrition' key.
					$value = '';
					break;
			}

			if ( '' !== $value ) {
				$converted_data[ $tr ] = $value;
			}
		}

		$ratings = get_post_meta( $existing_post->ID, '_ratings', true );
		if ( ! empty( $ratings ) ) {
			update_post_meta( $recipe->get_id(), 'srp_ratings', $ratings );
		}

		return self::save_converted_data_to_recipe( $converted_data, $recipe );
	}
}
