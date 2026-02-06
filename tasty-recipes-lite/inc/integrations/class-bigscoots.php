<?php
/**
 * Handles the Tasty Recipes integration with Bigscoots.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Integrations;

use Tasty\Framework\Traits\Singleton;
use BigScoots_Cache;

/**
 * Integration with Bigscoots.
 */
class Bigscoots {
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
		return class_exists( 'BigScoots_Cache' );
	}

	/**
	 * Register WP hooks.
	 *
	 * @return void
	 */
	private function add_hooks() {
		add_action( 'tasty_recipes_after_saving_rating', array( $this, 'clear_post_cache_with_rating' ) );
	}

	/**
	 * Clear post cache once one-click rating is added.
	 *
	 * @param int $post_id Current Post ID.
	 *
	 * @return void
	 */
	public function clear_post_cache_with_rating( $post_id ) {
		// @phpstan-ignore-next-line
		if ( ! method_exists( 'BigScoots_Cache', 'clear_cache' ) ) {
			return;
		}

		BigScoots_Cache::clear_cache( $post_id );
	}
}
