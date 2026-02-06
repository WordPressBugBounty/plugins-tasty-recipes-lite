<?php
/**
 * Manages Classic editor metabox.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

use Tasty_Recipes;

/**
 * Manages Classic editor metabox.
 */
class MetaBox {
	/**
	 * Singleton instance.
	 *
	 * @since 3.8
	 *
	 * @var MetaBox
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @since 3.8
	 *
	 * @return MetaBox
	 */
	public static function get_instance() {
		if ( null !== self::$instance ) {
			return self::$instance;
		}

		self::$instance = new self();
		self::$instance->add_hooks();
		return self::$instance;
	}

	/**
	 * Subscribe hooks.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	private function add_hooks() {
		add_action( 'load-post.php', array( $this, 'meta_box_init' ) );
	}

	/**
	 * Gets current post type.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	private function get_current_post_type() {
		global $post;

		if ( $post && $post->post_type ) {
			return $post->post_type;
		}

		global $typenow;

		if ( $typenow ) {
			return $typenow;
		}

		global $current_screen;

		if ( $current_screen && $current_screen->post_type ) {
			return $current_screen->post_type;
		}

		return Utils::request_param( 'post_type', 'sanitize_key', '' );
	}

	/**
	 * Initialize metabox and css in classic editor.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public function meta_box_init() {
		$post_type = $this->get_current_post_type();
		if ( empty( $post_type ) ) {
			return;
		}

		if ( ! is_post_type_viewable( $post_type ) ) {
			return;
		}

		if ( Utils::is_block_editor() ) {
			return;
		}

		add_editor_style( plugins_url( '/assets/dist/recipe.css', __DIR__ ) );

		if ( empty( Editor::get_dismissed_converters() ) ) {
			return;
		}

		// For Classic editor.
		add_action( 'add_meta_boxes', array( $this, 'create_meta_box' ) );
	}

	/**
	 * Add the metabox.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public function create_meta_box() {
		add_meta_box(
			'tasty-recipes-metabox',
			'Tasty Recipes',
			array( $this, 'print_metabox_html' ),
			null,
			'side',
			'low'
		);
	}

	/**
	 * Print metabox HTML.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public function print_metabox_html() {
		Tasty_Recipes::echo_template_part(
			'parts/metabox',
			array(
				'dismissed_converters' => Editor::get_dismissed_converters(),
			)
		);
	}
}
