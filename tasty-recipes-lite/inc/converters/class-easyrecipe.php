<?php
/**
 * Converter class for Easy Recipe.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Converters;

use Tasty_Recipes\Objects\Recipe;
use Tasty_Recipes\Ratings;
use Tasty_Recipes\Shortcodes;

/**
 * Converter class for Easy Recipe.
 */
class EasyRecipe extends Converter {

	/**
	 * Matching string for existing recipes.
	 *
	 * @var array|string
	 */
	protected static $match_string = array(
		'class="easyrecipe ',
		'class="easyrecipe"',
	);

	/**
	 * Get recipe content to convert.
	 *
	 * @param string $content Existing content that may contain a recipe.
	 *
	 * @return string
	 */
	public static function get_existing_to_convert( $content ) {
		// Cheap check first, then more expensive regex check.
		if ( false === stripos( $content, 'class="easyrecipe ' )
			&& false === stripos( $content, 'class="easyrecipe"' ) ) {
			return '';
		}
		preg_match( '#<div[^>]+class=[\'"][^\'"]*easyrecipe[^\'"]*[\'"][^>]*>(.+)<div class="endeasyrecipe"[^>]+>[\d\.]+<\/div>.{0,2}<\/div>#Us', $content, $matches );
		if ( ! empty( $matches[0] ) ) {
			return $matches[0];
		}
		$bits = explode( PHP_EOL, $content );
		// Uh oh, this content doesn't have any line endings.
		if ( count( $bits ) <= 1 ) {
			return '';
		}
		$matching = array();
		$open     = 0;
		$started  = false;
		// xpath doesn't return a string we can use for str_replace().
		foreach ( $bits as $bit ) {
			if ( false !== stripos( $bit, '<div class="easyrecipe' ) ) {
				$started = true;
			}
			if ( $started ) {
				$matching[] = $bit;
				$open      += substr_count( $bit, '<div' );
				$open      -= substr_count( $bit, '</div>' );
			}
			if ( 0 === $open ) {
				$started = false;
			}
		}
		return implode( PHP_EOL, $matching );
	}

	/**
	 * Convert recipe content to Tasty Recipes format.
	 *
	 * @param string $existing Existing content that may contain a recipe.
	 * @param int    $post_id  ID for the post with the recipe.
	 *
	 * @return false|Recipe
	 */
	public static function create_recipe_from_existing( $existing, $post_id ) { // phpcs:ignore SlevomatCodingStandard

		$recipe = Recipe::create();
		if ( ! $recipe ) {
			return false;
		}

		$nodes = array(
			'title'           => array( '(div|span)', 'class', 'ERS?Name' ),
			'author_name'     => array( '(div|span)', 'class', '(ERSAuthor|author)' ),
			'category'        => array( '(div|span)', 'class', '(ERSCategory|type)' ),
			'cuisine'         => array( '(div|span)', 'class', '(ERSCuisine|cuisine)' ),
			'prep_time'       => array( '(span|time)', '(itemprop|class)', '(prepTime|preptime)' ),
			'cook_time'       => array( '(span|time)', '(itemprop|class)', '(cookTime|cooktime)' ),
			'total_time'      => array( '(span|time)', '(itemprop|class)', '(totalTime|totaltime)' ),
			'yield'           => array( '(div|span)', 'class', '(ERSServes|yield)' ),
			'description'     => array( 'div', 'class', 'ERS?Summary' ),
			'ingredients'     => array( '(div|ul)', 'class', '(ERSIngredients|ingredients)' ),
			'instructions'    => array( '(div)', 'class', '(ERSInstructions|instructions)' ),
			'notes'           => array( 'div', 'class', 'ERS?Notes' ),
			'serving_size'    => array( 'span', 'class', 'servingSize' ),
			'calories'        => array( 'span', 'class', 'calories' ),
			'sugar'           => array( 'span', 'class', 'sugar' ),
			'sodium'          => array( 'span', 'class', 'sodium' ),
			'fat'             => array( 'span', 'class', 'fat' ),
			'saturated_fat'   => array( 'span', 'class', 'saturatedFat' ),
			'unsaturated_fat' => array( 'span', 'class', 'unsaturatedFat' ),
			'trans_fat'       => array( 'span', 'class', 'transFat' ),
			'carbohydrates'   => array( 'span', 'class', 'carbohydrates' ),
			'fiber'           => array( 'span', 'class', 'fiber' ),
			'protein'         => array( 'span', 'class', 'protein' ),
			'cholesterol'     => array( 'span', 'class', 'cholesterol' ),
		);

		$image_id = 0;
		if ( preg_match( '#<link[^>]+itemprop=[\'"]image[\'"][^>]+href=[\'"]([^\'"]+)[\'"][^>]*>#', $existing, $matches ) ) {
			$image_id = static::get_image_id_from_file( $matches[1] );
		}

		// Might be a really old EasyRecipe with a plain <img> tag.
		if ( ! $image_id && str_contains( $existing, '<div class="ERSTopRight">' ) ) {
			if ( preg_match( '#<img[^>]+src=[\'"]([^\'"]+)[\'"]#', $existing, $matches ) ) {
				$image_id = static::get_image_id_from_file( $matches[1] );
			}
		}

		/**
		 * Permit modification of the $image_id when converting.
		 *
		 * @param int    $image_id Image ID to be used.
		 * @param object $recipe   Tasty Recipe object.
		 * @param int    $post_id  Original post ID.
		 */
		$image_id = apply_filters( 'tasty_recipes_convert_easyrecipe_image_id', $image_id, $recipe, $post_id );
		if ( $image_id ) {
			$recipe->set_image_id( $image_id );
		}

		// Prepare instruction headings for old versions of EasyRecipe data
		// Otherwise, our matching regex will break down.
		$existing = preg_replace( '#<div class="ERSeparator">([^<]+)<\/div>#', '<h4>$1</h4>', $existing );

		// Remove generic headings which break our regex.
		$existing = str_replace(
			array(
				'<div class="ERSIngredientsHeader ERSHeading">Ingredients</div>',
				'<div class="ERSInstructionsHeader ERSHeading">Instructions</div>',
				'<div class="ERSClear"></div>',
			),
			'',
			$existing
		);

		$attributes     = Recipe::get_attributes();
		$converted_data = array();
		foreach ( $nodes as $key => $query ) {
			$pattern = '#<' . $query[0] . '[^>]+' . $query[1] . '=[\'"][^\'"]*' . $query[2] . '([\'"]|\s[^\'"]*[\'"])[^>]*>(.+)<\/' . $query[0] . '#Us';
			if ( ! preg_match_all( $pattern, $existing, $matches ) ) {
				continue;
			}
			// 'prep_time' matches multiple.
			// 'title' matches (div|span).
			// 'author_name', 'category', 'cuisine', 'yield' match multiple.
			if ( in_array( $key, array( 'prep_time', 'cook_time', 'total_time' ), true ) ) {
				$offset = 5;
			} elseif ( in_array(
				$key,
				array(
					'author_name',
					'category',
					'cuisine',
					'yield',
					'instructions',
					'ingredients',
				),
				true
			) ) {
				$offset = 4;
			} elseif ( 'title' === $key ) {
				$offset = 3;
			} else {
				$offset = 2;
			}
			$value = trim( $matches[ $offset ][0] );

			$markdown_transform = array(
				'title',
				'author_name',
				'description',
				'ingredients',
				'instructions',
				'notes',
			);
			if ( in_array( $key, $markdown_transform, true ) ) {
				$value = self::transform_markup_to_html( $value );
			}

			// Remove 'Author: ', etc. from the values.
			if ( in_array( $key, array( 'author_name', 'category', 'cuisine', 'yield' ), true )
				&& str_starts_with( $matches[2][0], 'ERS' ) ) {
				$bits = explode( ': ', $value );
				array_shift( $bits );
				$value = implode( ': ', $bits );
			}

			if ( 'ingredients' === $key ) {
				$value = str_replace( ' class="ingredient"', '', $value );
				$value = preg_replace( '#<li class="ERSeparator">(.+)<\/li>#', '</ul>' . PHP_EOL . '<h4>$1</h4>' . PHP_EOL . '<ul>', $value );
				// If matched 'ul', then we need to restart with 'ul'.
				// Otherwise, we can use the existing starter.
				$start = 'ul' === $matches[1][0] ? '<ul>' : '';
				// Separator right at the beginning.
				if ( '</ul>' === substr( $value, 0, 5 ) ) {
					$value = substr( $value, 5 );
					$start = '';
				}
				$value = $start . PHP_EOL . ' 	' . $value . PHP_EOL . '</ul>';
				$value = trim( $value );
			}
			if ( 'instructions' === $key ) {
				$value = str_replace( ' class="instruction"', '', $value );
			}
			$sanitize_callback = 'sanitize_text_field';
			$meta              = $attributes[ $key ];
			if ( ! empty( $meta['sanitize_callback'] ) ) {
				$sanitize_callback = $meta['sanitize_callback'];
			}
			$value = $sanitize_callback( $value );
			if ( 'title' === $key ) {
				$value = wp_unslash( $value );
				$value = str_replace( '<span class="fn">', '', $value );
			}
			$value                  = html_entity_decode( $value, ENT_COMPAT, 'UTF-8' );
			$converted_data[ $key ] = $value;
		}
		return self::save_converted_data_to_recipe( $converted_data, $recipe );
	}

	/**
	 * Get the image id for a given media file.
	 *
	 * If the image doesn't exist in the media library, it's imported.
	 *
	 * @param string $file File path.
	 *
	 * @return int
	 */
	protected static function get_image_id_from_file( $file ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$image_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='attachment' and guid=%s", $file ) );
		if ( $image_id ) {
			return $image_id;
		}
		// It may be a cropped version.
		$large_file = preg_replace( '#-[\d]{2,4}x[\d]{2,4}\.#', '.', $file );
		if ( $large_file !== $file ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$image_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='attachment' and guid=%s", $large_file ) );
			if ( $image_id ) {
				return $image_id;
			}
		}
		return static::media_sideload_image( $file );
	}

	/**
	 * Transform EasyRecipe markup to HTML.
	 *
	 * @param string $content Existing markup to transform.
	 *
	 * @return string
	 */
	public static function transform_markup_to_html( $content ) {

		// Standard markup.
		$search_replace = array(
			'[b]'  => '<strong>',
			'[/b]' => '</strong>',
			'[i]'  => '<em>',
			'[/i]' => '</em>',
			'[u]'  => '<u>',
			'[/u]' => '</u>',
			'[br]' => '<br>',
		);
		$content        = str_replace( array_keys( $search_replace ), array_values( $search_replace ), $content );

		// Replace links.
		$content = preg_replace( '#\[url([^\]]+)?\]([^\[]+)\[\/url\]#', '<a$1>$2</a>', $content );

		// Replace images.
		$content = preg_replace( '#\[img([^\]]+)?\]#', '<img$1>', $content );

		return $content;
	}
}
