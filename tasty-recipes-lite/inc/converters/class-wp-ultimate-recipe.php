<?php
/**
 * Converter class for WP Ultimate Recipe.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Converters;

use Tasty_Recipes\Distribution_Metadata;
use Tasty_Recipes\Utils;
use Tasty_Recipes\Objects\Recipe;

/**
 * Converter class for WP Ultimate Recipe.
 */
class WP_Ultimate_Recipe extends Converter {

	/**
	 * Matching string for existing recipes.
	 *
	 * @var array|string
	 */
	protected static $match_string = array(
		'[ultimate-recipe',
		'<!-- wp:wp-ultimate-recipe/recipe',
	);

	/**
	 * Name of the block.
	 *
	 * @var string
	 */
	protected static $block_name = 'wp-ultimate-recipe/recipe';

	/**
	 * Name of the shortcode tag.
	 *
	 * @var string
	 */
	protected static $shortcode_tag = 'ultimate-recipe';

	/**
	 * Convert recipe content to Tasty Recipes format.
	 *
	 * @param string $existing Existing content that might contain a recipe.
	 * @param int    $post_id  ID for the post with the recipe.
	 *
	 * @return false|Recipe
	 */
	public static function create_recipe_from_existing( $existing, $post_id ) { // phpcs:ignore SlevomatCodingStandard
		$data = self::get_existing_block_or_shortcode( $existing, 'data' );
		if ( empty( $data['id'] ) ) {
			return false;
		}
		$recipe_id     = (int) $data['id'];
		$existing_post = get_post( $recipe_id );
		if ( ! $existing_post ) {
			return false;
		}

		$post_id = (int) $post_id; // Avoid UnusedVariable.

		$recipe         = Recipe::create();
		$converted_data = array();

		if ( ! $recipe ) {
			return false;
		}

		$mapping = array(
			// WPUR -> Tasty Recipes.
			'recipe_title'           => 'title',
			'recipe_author'          => 'author_name',
			'recipe_alternate_image' => 'image_id',
			'recipe_description'     => 'description',
			'recipe_ingredients'     => 'ingredients',
			'recipe_instructions'    => 'instructions',
			'recipe_notes'           => 'notes',
			'recipe_servings'        => 'yield',
			'recipe_prep_time'       => 'prep_time',
			'recipe_cook_time'       => 'cook_time',
		);
		foreach ( $mapping as $wpur => $tr ) {
			$value = get_post_meta( $existing_post->ID, $wpur, true );

			// Additional processing for these fields.
			switch ( $wpur ) {
				case 'recipe_title':
					// Defaults to the post title when no recipe title is provided.
					if ( ! $value ) {
						$value = $existing_post->post_title;
					}
					break;
				case 'recipe_author':
					// Defaults to the post author when no recipe author is provided.
					$user = get_user_by( 'id', $existing_post->post_author );
					if ( ! $value && $existing_post->post_author && $user ) {
						$value = $user->display_name;
					}
					break;
				case 'recipe_ingredients':
				case 'recipe_instructions':
					$parsed = array();
					if ( is_array( $value ) ) {
						foreach ( $value as $item ) {
							$group = isset( $item['group'] ) ? $item['group'] : '';
							if ( ! isset( $parsed[ $group ] ) ) {
								$parsed[ $group ] = array();
							}
							$bits = array();
							foreach ( array( 'amount', 'unit', 'description', 'ingredient', 'notes' ) as $k ) {
								if ( isset( $item[ $k ] ) && '' !== $item[ $k ] ) {
									// 'notes' gets wrapped in parens.
									$bits[] = 'notes' === $k ? '(' . $item[ $k ] . ')' : $item[ $k ];
								}
							}
							$parsed[ $group ][] = implode( ' ', $bits );
						}
					}
					$el    = 'recipe_ingredients' === $wpur ? 'ul' : 'ol';
					$value = '';
					foreach ( $parsed as $heading => $items ) {
						if ( ! empty( $heading ) ) {
							$value .= '<h4>' . $heading . '</h4>' . PHP_EOL;
						}
						$value .= '<' . $el . '>' . PHP_EOL;
						$value .= '<li>' . implode( '</li>' . PHP_EOL . '<li>', $items ) . '</li>' . PHP_EOL;
						$value .= '</' . $el . '>' . PHP_EOL;
					}
					$value = trim( $value, PHP_EOL );
					break;
				case 'recipe_alternate_image':
					// Defaults to post thumbnail when no alternate image is provided.
					if ( ! $value ) {
						$value = get_post_meta( $existing_post->ID, '_thumbnail_id', true );
					}
					break;
				case 'recipe_servings':
					$servings_type = get_post_meta( $existing_post->ID, 'recipe_servings_type', true );
					if ( $servings_type ) {
						$value .= ' ' . $servings_type;
					}
					break;
				case 'recipe_prep_time':
				case 'recipe_cook_time':
					$time_unit = get_post_meta( $existing_post->ID, "{$wpur}_text", true );
					if ( $time_unit ) {
						$value .= ' ' . $time_unit;
					}
					break;
			}

			if ( $value ) {
				$converted_data[ $tr ] = $value;
			}
		}

		// Back up registered taxonomies so we can restore them after we've
		// fetched the data.
		$backup_taxonomies        = $GLOBALS['wp_taxonomies'];
		$GLOBALS['wp_taxonomies'] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$tax_fields               = array(
			'course'  => 'category',
			'cuisine' => 'cuisine',
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
