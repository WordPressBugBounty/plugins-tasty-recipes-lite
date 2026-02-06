<?php
/**
 * Converter class for WP Recipe Maker.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Converters;

use Tasty_Recipes\Distribution_Metadata;
use Tasty_Recipes\Objects\Recipe;
use Tasty_Recipes\Utils;

/**
 * Converter class for WP Recipe Maker.
 */
class WP_Recipe_Maker extends Converter {

	/**
	 * Matching string for existing recipes.
	 *
	 * @var array|string
	 */
	protected static $match_string = array(
		'<!--WPRM Recipe',
		'<!-- wp:wp-recipe-maker/recipe',
	);

	/**
	 * Name of the block.
	 *
	 * @var string
	 */
	protected static $block_name = 'wp-recipe-maker/recipe';

	/**
	 * Get the total number of posts with recipes in their content.
	 *
	 * @return int
	 */
	public static function get_count() {
		$post_ids = self::get_post_ids( PHP_INT_MAX );
		return count( $post_ids );
	}

	/**
	 * Get post ids for posts with recipes in their content.
	 *
	 * @param int $per_page Number of posts to fetch. Defaults to 10.
	 *
	 * @return array
	 */
	public static function get_post_ids( $per_page = 10 ) {
		$results = self::get_select_query(
			'ID, post_content',
			array(
				'type'  => 'get_results',
				'limit' => '0, ' . (int) $per_page,
			)
		);

		$post_ids = array();
		foreach ( $results as $result ) {
			if ( self::get_existing_to_convert( $result->post_content ) ) {
				$post_ids[] = (int) $result->ID;
			}
		}
		return $post_ids;
	}

	/**
	 * Get recipe content to convert.
	 *
	 * @param string $content Existing content to convert.
	 *
	 * @return object|string
	 */
	public static function get_existing_to_convert( $content ) {
		$match = self::get_existing_block_or_shortcode( $content );
		if ( $match && false !== stripos( $match, '"id"' ) ) {
			return $match;
		}
		preg_match( '#<!--WPRM Recipe (\d+)-->.+?<!--End WPRM Recipe-->#ms', $content, $matches );
		if ( ! empty( $matches[0] ) ) {
			return $matches[0];
		}
		return '';
	}

	/**
	 * Convert recipe content to Tasty Recipes format.
	 *
	 * @param string $existing Existing content to convert.
	 * @param int    $post_id  Post id for the post being converted.
	 *
	 * @return false|Recipe
	 */
	public static function create_recipe_from_existing( $existing, $post_id ) { // phpcs:ignore SlevomatCodingStandard
		$post_id = (int) $post_id; // Avoid UnusedVariable.
		if ( preg_match( '/<!--WPRM Recipe (\d+)-->/', $existing, $matches ) ) {
			$id = $matches[1];
		} elseif ( preg_match( '#<!-- wp:wp-recipe-maker/recipe(.+)/?-->#Us', $existing, $matches ) ) {
			$block_attr = json_decode( trim( $matches[1] ), true );
			if ( empty( $block_attr['id'] ) ) {
				return false;
			}
			$id = $block_attr['id'];
		} else {
			return false;
		}

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
			// WP Recipe Maker -> Tasty Recipes.
			'post_title'                   => 'title',
			'wprm_author_name'             => 'author_name',
			'_thumbnail_id'                => 'image_id',
			'post_content'                 => 'description',
			'wprm_ingredients'             => 'ingredients',
			'wprm_instructions'            => 'instructions',
			'wprm_notes'                   => 'notes',
			'wprm_servings'                => 'yield',
			'wprm_prep_time'               => 'prep_time',
			'wprm_cook_time'               => 'cook_time',
			'wprm_total_time'              => 'total_time',
			'wprm_video_embed'             => 'video_url',
			'wprm_nutrition'               => 'nutrition',
			'wprm_nutrition_calories'      => 'calories',
			'wprm_nutrition_carbohydrates' => 'carbohydrates',
			'wprm_nutrition_protein'       => 'protein',
			'wprm_nutrition_fat'           => 'fat',
			'wprm_nutrition_saturated_fat' => 'saturated_fat',
			'wprm_nutrition_cholesterol'   => 'cholesterol',
			'wprm_nutrition_sodium'        => 'sodium',
			'wprm_nutrition_fiber'         => 'fiber',
			'wprm_nutrition_sugar'         => 'sugar',
		);
		foreach ( $mapping as $wprm => $tr ) {
			$value = get_post_meta( $existing_post->ID, $wprm, true );

			// Additional processing for these fields.
			switch ( $wprm ) {
				case 'post_title':
				case 'post_content':
					$value = $existing_post->$wprm;
					break;
				case 'wprm_author_name':
					$display = get_post_meta( $existing_post->ID, 'wprm_author_display', true );
					if ( 'post_author' === $display ) {
						$pp_id       = get_post_meta( $existing_post->ID, 'wprm_parent_post_id', true );
						$parent_post = get_post( $pp_id );
						if ( $pp_id && $parent_post ) {
							$user = get_user_by( 'id', $parent_post->post_author );
							if ( $user ) {
								$value = $user->display_name;
							}
						}
					}
					break;
				case 'wprm_ingredients':
				case 'wprm_instructions':
					$parsed = array();
					if ( is_array( $value ) ) {
						foreach ( $value as $top_item ) {
							$group = isset( $top_item['name'] ) ? $top_item['name'] : '';
							if ( ! isset( $parsed[ $group ] ) ) {
								$parsed[ $group ] = array();
							}
							$h = 'wprm_ingredients' === $wprm ? 'ingredients' : 'instructions';
							foreach ( $top_item[ $h ] as $item ) {
								$bits = array();
								foreach ( array( 'amount', 'unit', 'name', 'notes', 'description', 'ingredient', 'text', 'image' ) as $k ) {
									if ( isset( $item[ $k ] ) && '' !== $item[ $k ] ) {
										$v = $item[ $k ];
										if ( 'image' === $k ) {
											if ( $v ) {
												$v = wp_get_attachment_image( $v, 'medium' );
											} else {
												continue;
											}
										}
										// 'notes' gets wrapped in parens.
										$bits[] = 'notes' === $k ? '(' . $v . ')' : $v;
									}
								}
								if ( ! empty( $bits ) ) {
									$joined = implode( ' ', $bits );
									// Replace <p> with <br>, because ours will end up in a list.
									$joined = str_replace( array( '<p>', '</p>' ), array( '', '<br>' ), $joined );
									if ( '<br>' === substr( $joined, -4 ) ) {
										$joined = substr( $joined, 0, -4 );
									}
									$parsed[ $group ][] = $joined;
								}
							}
						}
					}
					$el    = 'wprm_ingredients' === $wprm ? 'ul' : 'ol';
					$value = '';
					foreach ( $parsed as $heading => $items ) {
						if ( empty( $items ) ) {
							continue;
						}
						if ( ! empty( $heading ) ) {
							$value .= '<h4>' . $heading . '</h4>' . PHP_EOL;
						}
						$value .= '<' . $el . '>' . PHP_EOL;
						$value .= '<li>' . implode( '</li>' . PHP_EOL . '<li>', $items ) . '</li>' . PHP_EOL;
						$value .= '</' . $el . '>' . PHP_EOL;
					}
					$value = trim( $value, PHP_EOL );
					break;
				case 'wprm_servings':
					$servings_type = get_post_meta( $existing_post->ID, 'wprm_servings_unit', true );
					if ( $servings_type ) {
						$value .= ' ' . $servings_type;
					}
					break;
				case 'wprm_prep_time':
				case 'wprm_cook_time':
				case 'wprm_total_time':
					if ( '' !== $value && 0 !== (int) $value ) {
						// WPRM stores in minutes.
						$time  = (int) $value * 60;
						$value = Distribution_Metadata::format_time_for_human( $time );
					}
					break;
				case 'wprm_nutrition':
					$nutrition = $value;
					if ( ! empty( $nutrition ) && ! empty( $nutrition['calories'] ) ) {
						foreach ( $nutrition as $attr => $nutrient ) {
							if ( 'serving_unit' === $attr ) {
								continue;
							}
							if ( false !== $nutrient && '' !== $nutrient && method_exists( $recipe, "set_{$attr}" ) ) {
								$converted_data[ $attr ] = $nutrient;
							}
							unset( $attr, $nutrient );
						}
					}
					if ( isset( $converted_data['serving_size'] )
						&& isset( $nutrition['serving_unit'] ) ) {
						$converted_data['serving_size'] .= ' ' . $nutrition['serving_unit'];
						$converted_data['serving_size']  = trim( $converted_data['serving_size'] );
					}
					$tr    = 'calories';
					$value = isset( $nutrition['calories'] ) ? $nutrition['calories'] : '';
					break;
				case 'wprm_video_embed':
					if ( false !== stripos( $value, '<iframe' ) ) {
						$src = Utils::get_element_attribute( $value, 'iframe', 'src' );
						if ( $src ) {
							$youtube_id = Utils::get_youtube_id( $src );
							if ( $youtube_id ) {
								$value = sprintf(
									'https://www.youtube.com/watch?v=%s',
									$youtube_id
								);
							} else {
								$value = $src;
							}
						}
					}
					break;
			}

			if ( $value ) {
				$converted_data[ $tr ] = $value;
			}
		}

		$ratings = get_post_meta( $existing_post->ID, 'wprm_rating', true );
		if ( ! empty( $ratings ) ) {
			update_post_meta( $recipe->get_id(), 'wprm_ratings', $ratings );
		}

		// Back up registered taxonomies so we can restore them after we've
		// fetched the data.
		$backup_taxonomies        = $GLOBALS['wp_taxonomies'];
		$GLOBALS['wp_taxonomies'] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$tax_fields               = array(
			'wprm_course'  => 'category',
			'wprm_cuisine' => 'cuisine',
			'wprm_keyword' => 'keywords',
		);
		foreach ( $tax_fields as $tax => $tr ) {
			register_taxonomy( $tax, $existing_post->post_type );
			$terms = get_the_terms( $existing_post->ID, $tax );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$term_names            = wp_list_pluck( $terms, 'name' );
				$converted_data[ $tr ] = implode( ', ', $term_names );
			}
		}
		// Restore registered taxonomies.
		$GLOBALS['wp_taxonomies'] = $backup_taxonomies; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		return self::save_converted_data_to_recipe( $converted_data, $recipe );
	}
}
