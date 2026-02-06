<?php
/**
 * Converter class for Mediavine Create.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Converters;

use Tasty_Recipes\Distribution_Metadata;
use Tasty_Recipes\Utils;
use Tasty_Recipes\Objects\Recipe;

/**
 * Converter class for Mediavine Create.
 */
class Mediavine_Create extends Converter {

	/**
	 * Matching string for existing recipes.
	 *
	 * @var array|string
	 */
	protected static $match_string = array(
		'wp:mv/recipe',
		'[mv_create',
	);

	/**
	 * Name of the block.
	 *
	 * @var string
	 */
	protected static $block_name = 'mv/recipe';

	/**
	 * Name of the shortcode tag.
	 *
	 * @var string
	 */
	protected static $shortcode_tag = 'mv_create';

	/**
	 * Convert recipe content to Tasty Recipes format.
	 *
	 * @param string $existing Existing content that might contain a recipe.
	 * @param int    $post_id  ID for the post with the recipe.
	 *
	 * @return false|Recipe
	 */
	public static function create_recipe_from_existing( $existing, $post_id ) { // phpcs:ignore SlevomatCodingStandard
		global $wpdb;

		$post_id = (int) $post_id; // Avoid UnusedVariable.

		$data = self::get_existing_block_or_shortcode( $existing, 'data' );
		// Only converting recipe cards.
		if ( empty( $data['type'] ) || 'recipe' !== $data['type'] ) {
			return false;
		}
		// Block uses 'id' while shortcode uses 'key'.
		if ( ! empty( $data['id'] ) ) {
			$recipe_id = (int) $data['id'];
		} elseif ( ! empty( $data['key'] ) ) {
			$recipe_id = (int) $data['key'];
		} else {
			return false;
		}

		$recipe         = Recipe::create();
		$converted_data = array();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mv_creations WHERE id=%d", $recipe_id ) );
		if ( ! $existing ) {
			return false;
		}
		$existing      = (object) $existing;
		$existing_post = get_post( $existing->object_id );

		$mapping_fields = array(
			// MV Create -> Tasty Recipes.
			'title'             => 'title',
			'author'            => 'author_name',
			'thumbnail_id'      => 'image_id',
			'yield'             => 'yield',
			'suitable_for_diet' => 'diet',
			'description'       => 'description',
			'instructions'      => 'instructions',
			'notes'             => 'notes',
			'keywords'          => 'keywords',
			'prep_time'         => 'prep_time',
			'active_time'       => 'cook_time',
			'additional_time'   => 'additional_time_value',
			'total_time'        => 'total_time',
			'external_video'    => 'video_url',
		);

		$recipe         = Recipe::create();
		$converted_data = array();

		if ( ! $recipe ) {
			return false;
		}

		foreach ( $mapping_fields as $mc => $tr ) {

			$value = $existing->$mc;

			if ( is_null( $value ) ) {
				continue;
			}

			if ( 'instructions' === $mc ) {
				// Handle '[mv_schema_meta name=&quot;Pour the milk&quot;]' schema details.
				$value = str_replace( '&quot;', '"', $value );
				$value = preg_replace_callback(
					'#\[mv_schema_meta([^\]]+)\]#',
					function ( $matches ) {
						$atts = shortcode_parse_atts( $matches[1] );
						if ( empty( $atts['name'] ) ) {
							return $matches[0];
						}
						return '<strong>' . $atts['name'] . '</strong>';
					},
					$value
				);
			}

			if ( in_array( $mc, array( 'prep_time', 'active_time', 'total_time' ), true ) ) {
				$value = Distribution_Metadata::format_time_for_human( $value );
			}

			if ( 'additional_time' === $mc ) {
				$converted_data['additional_time_label'] = $existing->additional_time_label ? $existing->additional_time_label : 'Additional Time';

				$value = Distribution_Metadata::format_time_for_human( $value );
			}

			if ( 'external_video' === $mc ) {
				$data = json_decode( $value, true );
				if ( ! empty( $data['contentUrl'] ) ) {
					$value = $data['contentUrl'];
				}
			}

			$converted_data[ $tr ] = $value;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing_supplies = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mv_supplies WHERE creation=%d ORDER BY position ASC", $recipe_id ) );
		$ingredients       = array();
		foreach ( $existing_supplies as $ingredient ) {
			$ingredient = (array) $ingredient;
			if ( ! isset( $ingredients[ $ingredient['group'] ] ) ) {
				$ingredients[ $ingredient['group'] ] = array();
			}
			$output = '';
			if ( ! empty( $ingredient['link'] ) ) {
				preg_match( '/([^[]*?)\[(.*)\](.*)/', $ingredient['original_text'], $matches );
				if ( empty( $matches ) ) {
					$before    = '';
					$after     = '';
					$link_text = $ingredient['original_text'];
				} else {
					$before    = $matches[1];
					$link_text = $matches[2];
					$after     = $matches[3];
				}

				$output .= wp_filter_post_kses( $before );
				$output .= '<a href="' . esc_url( $ingredient['link'] ) . '"';
				if ( $ingredient['nofollow'] ) {
					$output .= ' rel="nofollow"';
				}
				// Check for internal links.
				if ( ! str_starts_with( $ingredient['link'], get_site_url() ) ) {
					$output .= ' target="_blank"';
				}
				$output .= '>';
				$output .= wp_filter_post_kses( $link_text );
				$output .= '</a>';
				$output .= wp_filter_post_kses( $after );
			} else {
				$output .= wp_filter_post_kses( $ingredient['original_text'] );
			}
			$ingredients[ $ingredient['group'] ][] = $output;
		}
		$output = '';
		foreach ( $ingredients as $group => $lines ) {
			if ( ! in_array( $group, array( 'mv-has-no-group', '_empty_' ), true ) ) {
				$output .= '<h3>' . wp_filter_post_kses( (string) $group ) . '</h3>' . PHP_EOL;
			}
			$output .= '<ul>' . PHP_EOL;
			foreach ( $lines as $line ) {
				$output .= '<li>' . $line . '</li>' . PHP_EOL;
			}
			$output .= '</ul>' . PHP_EOL;
		}
		$converted_data['ingredients'] = trim( $output );

		$mapping_fields = array(
			// MV Create -> Tasty Recipes.
			'serving_size'    => 'serving_size',
			'calories'        => 'calories',
			'total_fat'       => 'fat',
			'saturated_fat'   => 'saturated_fat',
			'unsaturated_fat' => 'unsaturated_fat',
			'trans_fat'       => 'trans_fat',
			'cholesterol'     => 'cholesterol',
			'carbohydrates'   => 'carbohydrates',
			'sodium'          => 'sodium',
			'fiber'           => 'fiber',
			'sugar'           => 'sugar',
			'protein'         => 'protein',
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing_nutrition = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mv_nutrition WHERE creation=%d", $recipe_id ) );
		if ( is_object( $existing_nutrition ) ) {
			foreach ( $mapping_fields as $mc => $tr ) {

				$value = $existing_nutrition->$mc;

				if ( is_null( $value ) ) {
					continue;
				}

				$converted_data[ $tr ] = $value;
			}
		}

		if ( ! empty( $existing_post ) ) {
			// Back up registered taxonomies so we can restore them after we've
			// fetched the data.
			$backup_taxonomies        = $GLOBALS['wp_taxonomies'];
			$GLOBALS['wp_taxonomies'] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$tax_fields               = array(
				'category'   => 'category',
				'mv_cuisine' => 'cuisine',
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
		}

		if ( ! empty( $existing->rating ) ) {
			update_post_meta(
				$recipe->get_id(),
				'create_ratings',
				array(
					'rating'       => $existing->rating,
					'rating_count' => $existing->rating_count,
				)
			);
		}

		return self::save_converted_data_to_recipe( $converted_data, $recipe );
	}
}
