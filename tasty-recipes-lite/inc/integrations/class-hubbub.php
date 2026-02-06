<?php
/**
 * Handles the integration with Hubbub.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Integrations;

use Tasty\Framework\Traits\Singleton;

/**
 * Integration with Hubbub (previously Mediavine Grow Social).
 */
class Hubbub {
	use Singleton;

	/**
	 * Initialize the integration code based on the plugin status if it's active or not.
	 *
	 * @return void
	 */
	public static function init() {
		$instance = self::instance();
		if ( ! $instance || ! $instance->is_active() ) {
			return;
		}

		$instance->add_hooks();
	}

	/**
	 * Check if Hubbub plugin is active or not.
	 *
	 * @return bool
	 */
	private function is_active() {
		return class_exists( 'Social_Pug' );
	}

	/**
	 * Register WP hooks.
	 *
	 * @return void
	 */
	private function add_hooks() {
		add_filter( 'dpsp_is_location_displayable', array( __CLASS__, 'hide_tools_in_recipe_print' ) );
	}

	/**
	 * Hide all Hubbub tools in our print page.
	 *
	 * @param bool $displayable Current display status.
	 *
	 * @return bool
	 */
	public static function hide_tools_in_recipe_print( $displayable ) {
		return $displayable && ! tasty_recipes_is_print();
	}
}
