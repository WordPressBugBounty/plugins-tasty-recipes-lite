<?php
/**
 * Converter class for WordPress.com Recipe shortcode.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Converters;

use Tasty_Recipes\Utils;
use Tasty_Recipes\Objects\Recipe;

/**
 * Converter class for WordPress.com Recipe shortcode.
 */
class WPCom extends Converter {

	/**
	 * Matching string for existing recipes.
	 *
	 * @var array|string
	 */
	protected static $match_string = '[recipe';

	/**
	 * Get recipe content to convert.
	 *
	 * @param string $content Existing content that might have a recipe.
	 *
	 * @return false|string
	 */
	public static function get_existing_to_convert( $content ) {
		return Utils::get_existing_shortcode( $content, 'recipe' );
	}

	/**
	 * Convert recipe content to Tasty Recipes format.
	 *
	 * @param string $existing Existing content that might have a recipe.
	 * @param int    $post_id  ID for the post with the recipe.
	 *
	 * @return false|Recipe
	 */
	public static function create_recipe_from_existing( $existing, $post_id ) { // phpcs:ignore VariableAnalysis

		$base_recipe = self::get_all_shortcodes_to_convert( $existing, 'recipe' );
		if ( count( $base_recipe ) !== 1 ) {
			return false;
		}
		$converted_data = array();
		$base_recipe    = array_pop( $base_recipe );
		$base_atts      = shortcode_parse_atts( $base_recipe[3] );
		foreach ( array(
			'title'       => 'title',
			'servings'    => 'yield',
			'description' => 'description',
			'time'        => 'total_time',
		) as $wpcom => $tr ) {
			if ( isset( $base_atts[ $wpcom ] ) ) {
				$converted_data[ $tr ] = $base_atts[ $wpcom ];
			}
		}
		if ( isset( $base_atts['image'] ) ) {
			$image = self::get_image_id_from_file( $base_atts['image'] );
			if ( $image ) {
				$converted_data['image_id'] = $image;
			}
		}

		$notes = self::get_all_shortcodes_to_convert( $base_recipe[5], 'recipe-notes' );
		if ( ! empty( $notes ) ) {
			$converted_data['notes'] = trim( $notes[0][5] );
		}

		foreach ( array(
			'ingredients' => 'ingredients',
			'directions'  => 'instructions',
		) as $wpcom => $tr ) {
			$processed_data = self::get_all_shortcodes_to_convert( $base_recipe[5], 'recipe-' . $wpcom );
			if ( empty( $processed_data ) ) {
				continue;
			}
			$transformed_data = '';
			foreach ( $processed_data as $matched ) {
				$matched_atts = shortcode_parse_atts( $matched[3] );
				if ( ! empty( $matched_atts['title'] ) ) {
					$transformed_data .= '<h4>' . $matched_atts['title'] . '</h4>' . PHP_EOL;
				}
				$bits = explode( PHP_EOL, trim( $matched[5] ) );
				foreach ( $bits as $i => $bit ) {
					$bit        = preg_replace( '#^(-|[\d]+\.)\s#', '', $bit );
					$bits[ $i ] = '<li>' . $bit . '</li>';
				}
				$cleaned           = implode( PHP_EOL, $bits );
				$transformed_data .= 'ingredients' === $tr ? '<ul>' : '<ol>';
				$transformed_data .= PHP_EOL . $cleaned . PHP_EOL;
				$transformed_data .= 'ingredients' === $tr ? '</ul>' : '</ol>';
				$transformed_data .= PHP_EOL;
			}
			$converted_data[ $tr ] = trim( $transformed_data );
		}

		$recipe = Recipe::create();
		if ( ! $recipe ) {
			return false;
		}

		return self::save_converted_data_to_recipe( $converted_data, $recipe );
	}

	/**
	 * Get all shortcode data to convert.
	 *
	 * @param string $content       Content to parse for a shortcode.
	 * @param string $shortcode_tag Shortcode tag to look for.
	 *
	 * @return array|false
	 */
	protected static function get_all_shortcodes_to_convert( $content, $shortcode_tag ) {
		$backup_tags = $GLOBALS['shortcode_tags'];
		remove_all_shortcodes();
		add_shortcode( $shortcode_tag, '__return_false' ); // @phpstan-ignore argument.type
		preg_match_all( '/' . get_shortcode_regex() . '/', $content, $matches, PREG_SET_ORDER );
		if ( empty( $matches ) ) {
			$GLOBALS['shortcode_tags'] = $backup_tags; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			return false;
		}

		$existing = array();
		foreach ( $matches as $shortcode ) {
			if ( $shortcode_tag === $shortcode[2] ) {
				$existing[] = $shortcode;
			}
		}
		$GLOBALS['shortcode_tags'] = $backup_tags; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		return $existing;
	}
}
