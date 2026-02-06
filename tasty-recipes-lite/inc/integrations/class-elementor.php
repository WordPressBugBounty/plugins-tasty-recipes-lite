<?php
/**
 * Integrates Tasty Recipes with Elementor.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Integrations;

use WP_Post;

/**
 * Integrates Tasty Recipes with Elementor.
 */
class Elementor {

	/**
	 * Register our elementor hooks.
	 * This is the first step to migrate registering hooks from the main plugin file to its original place.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter(
			'tasty_recipes_add_media_button',
			array(
				__CLASS__,
				'filter_tasty_recipes_add_media_button',
			),
			10,
			2
		);
		add_action(
			'elementor/controls/controls_registered',
			array(
				__CLASS__,
				'action_controls_registered',
			)
		);
		add_action(
			'elementor/widgets/widgets_registered',
			array(
				__CLASS__,
				'action_widgets_registered',
			)
		);
		add_action(
			'elementor/editor/before_enqueue_scripts',
			array(
				__CLASS__,
				'action_before_enqueue_scripts',
			)
		);
		add_action(
			'elementor/editor/footer',
			array(
				__CLASS__,
				'action_editor_footer',
			)
		);
	}

	/**
	 * Registers the controls.
	 *
	 * @return void
	 */
	public static function action_controls_registered() {
		\Elementor\Plugin::instance()->controls_manager->register(
			new \Tasty_Recipes\Integrations\Elementor\Recipe_Control()
		);
	}

	/**
	 * Registers the widgets.
	 *
	 * @return void
	 */
	public static function action_widgets_registered() {
		\Elementor\Plugin::instance()->widgets_manager->register(
			new \Tasty_Recipes\Integrations\Elementor\Recipe_Widget()
		);
	}

	/**
	 * Runs before scripts are enqueued.
	 *
	 * @return void
	 */
	public static function action_before_enqueue_scripts() {
		// Registers our TinyMCE settings before Elementor noops TinyMCE.
		add_action(
			'print_default_editor_scripts',
			function () {
				ob_start();
				wp_editor(
					'',
					'tasty-recipes-editor',
					array(
						'teeny' => true,
					)
				);
				ob_get_clean();
			}
		);
	}

	/**
	 * Renders the editor modal template.
	 *
	 * @return void
	 */
	public static function action_editor_footer() {
		\Tasty_Recipes\Assets::action_admin_footer_render_template();
	}

	/**
	 * Disable the 'Add Recipe' button in Elementor's RTE.
	 *
	 * @param bool   $retval    Existing return value.
	 * @param string $editor_id ID of the editor.
	 *
	 * @return bool
	 */
	public static function filter_tasty_recipes_add_media_button( $retval, $editor_id ) {
		if ( 'elementorwpeditor' === $editor_id ) {
			return false;
		}
		return $retval;
	}
}
