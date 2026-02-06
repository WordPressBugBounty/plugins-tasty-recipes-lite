<?php
/**
 * Manages block editor registration and configuration.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

use Tasty_Recipes\Objects\Recipe;
use WP_Post;

/**
 * Manages block editor registration and configuration.
 */
class Block_Editor {

	/**
	 * Block type name.
	 *
	 * @var string
	 */
	const RECIPE_BLOCK_TYPE = 'wp-tasty/tasty-recipe';

	/**
	 * Register the scripts and block type.
	 *
	 * @return void
	 */
	public static function action_init_register() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		Assets::register_modal_editor_script();
		$time = filemtime( dirname( __DIR__ ) . '/assets/js/nutrifox-resize.js' );
		wp_register_script(
			'tasty-recipes-nutrifox-resize',
			plugins_url( 'assets/js/nutrifox-resize.js', TASTY_RECIPES_LITE_FILE ),
			array(),
			$time,
			true
		);
		
		$asset_meta = tasty_get_asset_meta( dirname( TASTY_RECIPES_LITE_FILE ) . '/assets/dist/recipes-block.build.asset.php' );
		wp_register_script(
			'tasty-recipes-block-editor',
			plugins_url( 'assets/dist/recipes-block.build.js', TASTY_RECIPES_LITE_FILE ),
			$asset_meta['dependencies'],
			$asset_meta['version'],
			true
		);
		wp_register_style(
			'tasty-recipes-block-editor',
			plugins_url( 'assets/dist/recipes-block.css', TASTY_RECIPES_LITE_FILE ),
			array(),
			$asset_meta['version']
		);
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'tasty-recipes-block-editor', 'tasty-recipes-lite' );
		}

		/**
		 * Filter the block attributes.
		 *
		 * @since 3.8
		 *
		 * @param array $attributes Block attributes.
		 */
		$attributes = apply_filters(
			'tasty_recipes_lite_block_attributes',
			array(
				'attributes'      => array(
					'className'            => array(
						'type' => 'string',
					),
					'id'                   => array(
						'type' => 'number',
					),
					'lastUpdated'          => array(
						'type' => 'number',
					),
					'author_link'          => array(
						'type' => 'string',
					),
					'override_author_link' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
				'editor_script'   => 'tasty-recipes-block-editor',
				'editor_style'    => array( 'tasty-framework-global-css', 'tasty-recipes-main', 'tasty-recipes-block-editor' ),
				'render_callback' => array( 'Tasty_Recipes\Shortcodes', 'render_tasty_recipe_shortcode' ),
			)
		);

		register_block_type(
			self::RECIPE_BLOCK_TYPE,
			$attributes
		);
	}

	/**
	 * Get recipes without parents.
	 * 
	 * @since 1.1
	 *
	 * @return array
	 */
	public static function get_formatted_recipes_without_parents() {
		global $wpdb;

		// phpcs:ignore
		$recipes = $wpdb->get_results(
			"SELECT post.ID, post.post_title 
			FROM $wpdb->posts post 
			LEFT JOIN $wpdb->postmeta postmeta ON post.ID = postmeta.post_id AND postmeta.meta_key = '_tasty_recipe_parents'
			WHERE post.post_type = 'tasty_recipe' 
			AND post.post_status = 'publish'
			AND postmeta.meta_id IS NULL"
		);

		if ( empty( $recipes ) ) {
			return array();
		}

		$returnable = array();
		foreach ( $recipes as $recipe ) {
			$returnable[] = array(
				'value' => $recipe->ID,
				'label' => ! empty( $recipe->post_title ) ? $recipe->post_title : __( '(no title)', 'tasty-recipes-lite' ),
			);
		}

		return $returnable;
	}

	/**
	 * Get the block for a given recipe.
	 *
	 * @param Recipe $recipe Recipe instance.
	 *
	 * @return string
	 */
	public static function get_block_for_recipe( Recipe $recipe ) {
		return '<!-- wp:wp-tasty/tasty-recipe {"id":' . $recipe->get_id() . ',"lastUpdated":' . time() . '} /-->';
	}

	/**
	 * Check if post has recipes.
	 *
	 * @since 3.8
	 *
	 * @param WP_Post $post Post object to check.
	 *
	 * @return bool
	 */
	public static function post_has_recipes( $post ) {
		return function_exists( 'has_block' ) && has_block( self::RECIPE_BLOCK_TYPE, $post );
	}
}
