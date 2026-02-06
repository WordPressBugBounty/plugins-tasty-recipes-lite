<?php
/**
 * Backward Compatibility class deals with the older versions of our plugins.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Admin;

use Tasty\Framework\Admin\Plugins\Factory;
use Tasty\Framework\Traits\Singleton;
use Tasty\Framework\Utils\Url;

/**
 * Backward Compatibility class deals with the older versions of our plugins.
 */
class BackwardCompatibility {

	use Singleton;

	/**
	 * Initialize the component.
	 *
	 * @return $this
	 */
	public function init() {
		add_action( 'current_screen', array( $this, 'hide_license_admin_notice_in_dashboard_page' ) );

		return $this;
	}

	/**
	 * Hide license admin notice in our dashboard page only for active plugins.
	 *
	 * @return void
	 */
	public function hide_license_admin_notice_in_dashboard_page() {
		if ( ! Url::is_dashboard_page() ) {
			return;
		}

		foreach ( Factory::create_active_plugins() as $active_plugin ) {
			if ( $active_plugin->has_framework() ) {
				continue;
			}
			$plugin_namespace = str_replace( ' ', '_', $active_plugin->get_plugin_name() );
			remove_action( 'admin_notices', array( $plugin_namespace . '\Admin', 'action_admin_notices_license_key' ) );
		}
	}
}
