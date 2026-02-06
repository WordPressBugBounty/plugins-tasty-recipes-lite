<?php
/**
 * Utility class deals with urls.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Utils;

use Tasty\Framework\Admin\Plugins\Factory;
use Tasty\Framework\Abstracts\PluginInstaller;

/**
 * Utility class deals with urls.
 */
class Url {

	/**
	 * Get the URL to the plugin landing page.
	 *
	 * @since 1.0.9
	 *
	 * @param PluginInstaller|string $plugin     Plugin name.
	 * @param array                  $utm_params UTM query strings.
	 *
	 * @return string
	 */
	public static function get_upgrade_url( $plugin, $utm_params = array() ) {
		if ( is_string( $plugin ) ) {
			$plugin = Factory::create( $plugin );
		}

		$utm_params = wp_parse_args(
			$utm_params,
			array(
				'utm_medium'   => $plugin->get_menu_slug(),
				'utm_campaign' => 'upgrade',
				'utm_content'  => 'settings',
			)
		);
		return self::add_utm_params(
			$plugin->is_lite() ? 'lite-upgrade' : $plugin->get_product_pricing_page_url(),
			$utm_params
		);
	}

	/**
	 * Return url with passed utm query strings added.
	 *
	 * @param string $url        Url to have utm query strings.
	 * @param array  $utm_params UTM query strings.
	 *
	 * @return string
	 */
	public static function add_utm_params( $url, $utm_params = array() ) {
		$default_utm_params = array(
			'utm_source'   => 'WordPress',
			'utm_medium'   => 'dashboard',
			'utm_campaign' => 'plugin',
			'utm_content'  => 'menu',
		);

		$utm_params = wp_parse_args( $utm_params, $default_utm_params );

		if ( false === strpos( $url, 'wptasty.com' ) ) {
			$url = 'https://www.wptasty.com/' . $url;
		}

		return add_query_arg( $utm_params, $url );
	}

	/**
	 * Add missing utm query strings to url without overwriting what might already be set.
	 * This is used for CTA links from the Sales API, where the sales team might have already
	 * set the utm query strings.
	 *
	 * @param string $url        Url to have utm query strings.
	 * @param array  $utm_params UTM query strings.
	 *
	 * @return string
	 */
	public static function add_missing_utm_params( $url, $utm_params ) {
		$query_args = array();

		if ( false === strpos( $url, 'utm_source' ) ) {
			$query_args['utm_source'] = 'WordPress';
		}

		if ( false === strpos( $url, 'utm_campaign' ) ) {
			$query_args['utm_campaign'] = $utm_params['utm_campaign'] ?? 'plugin';
		}

		if ( false === strpos( $url, 'utm_medium' ) && isset( $utm_params['utm_medium'] ) ) {
			$query_args['utm_medium'] = $utm_params['utm_medium'];
		}

		if ( false === strpos( $url, 'utm_content' ) && isset( $utm_params['utm_content'] ) ) {
			$query_args['utm_content'] = $utm_params['utm_content'];
		}

		return $query_args ? add_query_arg( $query_args, $url ) : $url;
	}

	/**
	 * Get main admin menu link.
	 *
	 * @return string
	 */
	public static function get_main_admin_url() {
		return menu_page_url( 'tasty', false );
	}

	/**
	 * Check if current page is one of our pages.
	 *
	 * @return bool
	 */
	public static function is_wpt_page() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}
		$screen = get_current_screen();
		return (bool) apply_filters( 'tasty_framework_admin_page_ours', $screen && ( 'toplevel_page_tasty' === $screen->base || stristr( $screen->base, 'wp-tasty_page_' ) ) );
	}

	/**
	 * Redirect to a URL even if the headers are already sent.
	 *
	 * @param string $url Url to redirect to.
	 *
	 * @return void
	 */
	public static function redirect( $url ) {
		if ( headers_sent() ) {
			echo wp_get_inline_script_tag(
				'window.location="' . esc_url( $url ) . '";'
			);
			return;
		}

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Check if the current page is the dashboard/licensing page.
	 *
	 * @return bool
	 */
	public static function is_dashboard_page() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}
		return 'toplevel_page_tasty' === get_current_screen()->id;
	}
}
