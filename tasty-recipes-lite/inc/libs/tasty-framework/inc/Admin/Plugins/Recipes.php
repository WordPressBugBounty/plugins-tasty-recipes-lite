<?php
/**
 * Tasty Recipes class.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Admin\Plugins;

use Tasty\Framework\Abstracts\PluginInstaller;
use Tasty\Framework\Traits\Singleton;

/**
 * Tasty Recipes class.
 */
class Recipes extends PluginInstaller {

	use Singleton;

	/**
	 * Get current plugin file (dir/file.php).
	 *
	 * @return string
	 */
	protected function get_plugin_file() {
		return $this->is_lite() ? $this->get_lite_plugin_file() : $this->get_pro_plugin_file();
	}

	/**
	 * Get pro plugin file (dir/file.php).
	 *
	 * @since 1.0.9
	 *
	 * @return string
	 */
	protected function get_pro_plugin_file() {
		return $this->extract_plugin_file_from_full_path(
			$this->path_constant(),
			'tasty-recipes/tasty-recipes.php'
		);
	}

	/**
	 * Get lite plugin file (dir/file.php).
	 *
	 * @since 1.0.9
	 *
	 * @return string
	 */
	protected function get_lite_plugin_file() {
		return $this->extract_plugin_file_from_full_path(
			'TASTY_RECIPES_LITE_FILE',
			'tasty-recipes-lite/tasty-recipes-lite.php'
		);
	}

	/**
	 * Get current plugin name.
	 *
	 * @since 1.0.9
	 *
	 * @return string
	 */
	public function get_menu_slug() {
		return 'tasty-recipes';
	}

	/**
	 * Get current plugin name.
	 *
	 * @return string
	 */
	public function get_plugin_name() {
		return 'Tasty Recipes' . ( $this->is_lite() ? ' Lite' : '' );
	}

	/**
	 * Get current plugin license key option key.
	 *
	 * @return string
	 */
	protected function get_license_key_option_name() {
		return 'tasty_recipes_license_key';
	}

	/**
	 * Get current plugin license check transient name.
	 *
	 * @return string
	 */
	protected function get_license_check_cache_key() {
		return 'tasty-recipes-license-check';
	}

	/**
	 * Get current plugin details url.
	 *
	 * @return string
	 */
	public function get_product_details_page_url() {
		return $this->get_store_url() . '/tasty-recipes';
	}

	/**
	 * Get current plugin pricing url.
	 *
	 * @return string
	 */
	public function get_product_pricing_page_url() {
		return $this->get_store_url() . '/pricing-recipes';
	}

	/**
	 * Can add the plugin item to admin menu?
	 *
	 * @return bool
	 */
	public function has_framework() {
		return $this->is_lite_active() ||
			version_compare( $this->plugin_version(), '3.9.1', '>' );
	}

	/**
	 * Is either lite or pro active?
	 *
	 * @since 1.0.9
	 *
	 * @return bool
	 */
	public function is_active() {
		return is_plugin_active( $this->get_pro_plugin_file() ) || is_plugin_active( $this->get_lite_plugin_file() );
	}

	/**
	 * Is the lite plugin active?
	 *
	 * @since 1.0.9
	 *
	 * @return bool
	 */
	private function is_lite_active() {
		return defined( 'TASTY_RECIPES_LITE_VERSION' );
	}

	/**
	 * Is the pro plugin active?
	 *
	 * @since 1.0.9
	 *
	 * @return bool
	 */
	public function is_pro_active() {
		return defined( 'TASTY_RECIPES_PRO_PLUGIN_VERSION' );
	}

	/**
	 * Is the plugin lite only?
	 *
	 * @since 1.0.9
	 *
	 * @return bool
	 */
	public function is_lite() {
		return $this->is_lite_active() && ! $this->is_pro_active();
	}
}
