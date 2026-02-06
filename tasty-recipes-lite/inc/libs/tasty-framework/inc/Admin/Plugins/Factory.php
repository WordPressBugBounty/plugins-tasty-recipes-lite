<?php
/**
 * Factory class to generate instance for our plugins.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Admin\Plugins;

use Tasty\Framework\Abstracts\PluginInstaller;
use Tasty\Framework\Admin\License\APIClient;

/**
 * Factory class to generate instance for our plugins.
 */
class Factory {

	/**
	 * List of our plugins' names.
	 *
	 * @var string[]
	 */
	private static $plugins = array(
		'Tasty Recipes',
		'Tasty Links',
		'Tasty Pins',
		'Tasty Roundups',
	);

	/**
	 * Create a plugin instance based on plugin name.
	 *
	 * @param string $plugin_name Plugin name.
	 *
	 * @return PluginInstaller|null
	 */
	public static function create( $plugin_name ) {
		$filesystem = tasty_get_filesystem();
		$api_client = APIClient::instance();

		switch ( $plugin_name ) {
			case 'Tasty Recipes':
			case 'Tasty Recipes Lite':
				return Recipes::instance()->init( $filesystem, $api_client );
			case 'Tasty Links':
				return Links::instance()->init( $filesystem, $api_client );
			case 'Tasty Pins':
				return Pins::instance()->init( $filesystem, $api_client );
			case 'Tasty Roundups':
				return Roundups::instance()->init( $filesystem, $api_client );
		}
		return null;
	}

	/**
	 * Create active plugins instances.
	 *
	 * @return array
	 */
	public static function create_active_plugins() {
		$active_plugins = array();

		foreach ( self::$plugins as $plugin_name ) {
			$plugin = self::create( $plugin_name );
			if ( ! $plugin->is_active() ) {
				continue;
			}
			$active_plugins[] = $plugin;
		}

		return (array) apply_filters( 'tasty_framework_active_plugins', $active_plugins );
	}

	/**
	 * Create all plugins instances.
	 *
	 * @return array
	 */
	public static function create_all_plugins() {
		$plugins = array();
		foreach ( self::$plugins as $plugin_name ) {
			$plugins[] = self::create( $plugin_name );
		}
		return (array) apply_filters( 'tasty_framework_all_plugins', $plugins );
	}
}
