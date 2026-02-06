<?php
/**
 * Tasty Pins class.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Admin\Plugins;

use Tasty\Framework\Abstracts\PluginInstaller;
use Tasty\Framework\Traits\Singleton;

/**
 * Tasty Pins class.
 */
class Pins extends PluginInstaller {

	use Singleton;

	/**
	 * Get current plugin file (dir/file.php).
	 *
	 * @return string
	 */
	protected function get_plugin_file() {
		return 'tasty-pins/tasty-pins.php';
	}

	/**
	 * Get current plugin name.
	 *
	 * @return string
	 */
	public function get_plugin_name() {
		return 'Tasty Pins';
	}

	/**
	 * Get current plugin license key option key.
	 *
	 * @return string
	 */
	protected function get_license_key_option_name() {
		return 'tasty_pins_license_key';
	}

	/**
	 * Get current plugin license check transient name.
	 *
	 * @return string
	 */
	protected function get_license_check_cache_key() {
		return 'tasty-pins-license-check';
	}

	/**
	 * Get current plugin details url.
	 *
	 * @return string
	 */
	public function get_product_details_page_url() {
		return $this->get_store_url() . '/tasty-pins';
	}

	/**
	 * Get current plugin pricing url.
	 *
	 * @return string
	 */
	public function get_product_pricing_page_url() {
		return $this->get_store_url() . '/pricing-pins';
	}

	/**
	 * Can add the plugin item to admin menu?
	 *
	 * @return bool
	 */
	public function has_framework() {
		$version = $this->plugin_version();
		return ! $version || version_compare( $version, '2.0', '>' );
	}

	/**
	 * Get required capability.
	 *
	 * @return string
	 */
	public function get_required_capability() {
		return 'edit_others_posts';
	}
}
