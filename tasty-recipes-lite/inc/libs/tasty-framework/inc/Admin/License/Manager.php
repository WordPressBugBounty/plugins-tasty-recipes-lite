<?php
/**
 * License manager class.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Admin\License;

use Tasty\Framework\Abstracts\PluginInstaller;
use Tasty\Framework\Admin\JSMessageBox;
use Tasty\Framework\Admin\Plugins\Factory;
use Tasty\Framework\Traits\Singleton;
use Tasty\Framework\Utils\Template;
use Tasty\Framework\Utils\Url;
use Tasty\Framework\Utils\Vars;
use WP_Error;

/**
 * License manager class.
 */
class Manager {

	use Singleton;

	/**
	 * License APIClient instance.
	 *
	 * @var APIClient
	 */
	private $api_client;

	/**
	 * Template engine instance.
	 *
	 * @var Template
	 */
	private $template;

	/**
	 * JS Message Box Instance.
	 *
	 * @var JSMessageBox
	 */
	private $js_messagebox;

	/**
	 * Admin notices.
	 *
	 * @var array
	 */
	private $admin_notices = array();

	/**
	 * Initialize class.
	 *
	 * @param APIClient    $api_client    License APIClient instance.
	 * @param Template     $template      Template engine instance.
	 * @param JSMessageBox $js_messagebox JS Message Box Instance.
	 *
	 * @return $this
	 */
	public function init( $api_client, $template, $js_messagebox ) {
		$this->api_client    = $api_client;
		$this->template      = $template;
		$this->js_messagebox = $js_messagebox;

		add_action( 'init', array( __CLASS__, 'load_updater' ) );

		// Ajax Handlers.
		add_action( 'wp_ajax_tasty_framework_license_key_plugins', array( $this, 'ajax_get_license_key_plugins' ) );
		add_action( 'wp_ajax_tasty_framework_activate_license', array( $this, 'ajax_activate_plugin_license' ) );
		add_action( 'wp_ajax_tasty_framework_download_plugin', array( $this, 'ajax_download_plugin' ) );
		add_action( 'wp_ajax_tasty_framework_activate_plugin', array( $this, 'ajax_activate_plugin' ) );
		add_action( 'wp_ajax_tasty_framework_deactivate_plugin', array( $this, 'ajax_deactivate_plugin' ) );
		add_action( 'wp_ajax_tasty_framework_remove_license', array( $this, 'ajax_remove_license' ) );
		add_filter( 'tasty_framework_admin_localize_script_data', array( $this, 'insert_nonces_into_js' ) );

		add_filter( 'tasty_framework_notification_icon', array( $this, 'maybe_show_menu_icon' ) );
		add_action( 'current_screen', array( $this, 'remove_all_admin_notices_from_dashboard_page' ) );

		return $this;
	}

	/**
	 * Load updater.
	 *
	 * @since 1.1
	 *
	 * @return void
	 */
	public static function load_updater() {
		foreach ( Factory::create_active_plugins() as $plugin ) {
			$plugin->load_updater();
		}
	}

	/**
	 * Ajax handler: Get license key attached plugins.
	 *
	 * @return void
	 */
	public function ajax_get_license_key_plugins() {
		check_ajax_referer( 'tasty_framework_license_key_plugins' );
		$this->validate_capability();

		$license_key = Vars::post_param( 'license_key' );
		$this->validate_license_key( $license_key );

		$plugins = $this->api_client->get_key_plugins( $license_key );
		$this->validate_api_response( $plugins );

		$final_plugins_list = array();
		if ( count( $plugins ) > 1 ) {
			$final_plugins_list[] = __( 'All Plugins', 'tasty-recipes-lite' );
		}
		foreach ( $plugins as $plugin ) {
			$final_plugins_list[ $plugin['id'] ] = $plugin['name'];
		}

		wp_send_json_success( $final_plugins_list );
	}

	/**
	 * Ajax handler: Activate plugin license key.
	 *
	 * @return void
	 */
	public function ajax_activate_plugin_license() {
		check_ajax_referer( 'tasty_framework_activate_license' );
		$this->validate_capability();

		$license_key = Vars::post_param( 'license_key' );
		$this->validate_license_key( $license_key );

		$license_plugin = Vars::post_param( 'license_plugin' );
		$this->validate_plugin( $license_plugin );

		$license_plugin_id = Vars::post_param( 'license_plugin_id' );
		if ( empty( $license_plugin_id ) ) {
			// Handle all plugins case.
			$activated = $this->activate_all_plugins( $license_key );
		} else {
			$activated = $this->activate_one_plugin( $license_key, $license_plugin );
		}

		if ( is_wp_error( $activated ) ) {
			wp_send_json_error(
				array(
					'message' => $activated->get_error_message(),
				)
			);
		}

		wp_send_json_success(
			array(
				'plugin_status' => $activated,
			)
		);
	}

	/**
	 * Ajax handler: Download, install and activate plugin.
	 *
	 * @return void
	 */
	public function ajax_download_plugin() {
		check_ajax_referer( 'tasty_framework_download_plugin' );
		$this->validate_capability();

		$license_plugin = Vars::request_param( 'license_plugin' );
		$this->validate_plugin( $license_plugin );

		$plugin = Factory::create( $license_plugin );
		$this->validate_plugin( $plugin );

		$download_url = $plugin->get_plugin_download_url();
		if ( empty( $download_url ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Can\'t download plugin, please reach out to our support.', 'tasty-recipes-lite' ),
				)
			);
		}

		$plugin->maybe_install_and_activate( $download_url );

		$this->js_messagebox->add( 'success', esc_html__( 'Downloaded and activated successfully.', 'tasty-recipes-lite' ) );

		wp_send_json_success();
	}

	/**
	 * Ajax handler to activate a plugin.
	 *
	 * @return void
	 */
	public function ajax_activate_plugin() {
		check_ajax_referer( 'tasty_framework_activate_plugin' );
		$this->validate_capability();

		$plugin_name = Vars::get_param( 'plugin' );
		$this->validate_plugin( $plugin_name );

		$plugin_obj = Factory::create( $plugin_name );
		$this->validate_plugin( $plugin_obj );

		$plugin_obj->activate_plugin();

		$this->js_messagebox->add( 'success', esc_html__( 'Activated successfully.', 'tasty-recipes-lite' ) );

		wp_send_json_success();
	}

	/**
	 * Ajax handler to deactivate a plugin.
	 *
	 * @return void
	 */
	public function ajax_deactivate_plugin() {
		check_ajax_referer( 'tasty_framework_deactivate_plugin' );
		$this->validate_capability();

		$plugin_name = Vars::get_param( 'plugin' );
		$this->validate_plugin( $plugin_name );

		$plugin_obj = Factory::create( $plugin_name );
		$this->validate_plugin( $plugin_obj );

		$plugin_obj->deactivate_plugin();

		$this->js_messagebox->add( 'success', esc_html__( 'Deactivated successfully.', 'tasty-recipes-lite' ) );

		$data   = null;
		$active = Factory::create_active_plugins();
		if ( empty( $active ) ) {
			// Redirect if no active plugins are left.
			$data = array( 'redirect' => admin_url( 'plugins.php' ) );
		}

		wp_send_json_success( $data );
	}

	/**
	 * Ajax handler: Deactivate and remove license.
	 *
	 * @return void
	 */
	public function ajax_remove_license() {
		check_ajax_referer( 'tasty_framework_remove_license' );
		$this->validate_capability();

		$plugin_name = Vars::get_param( 'plugin' );
		$this->validate_plugin( $plugin_name );

		$plugin_obj = Factory::create( $plugin_name );
		$this->validate_plugin( $plugin_obj );

		$plugin_obj->remove_license();

		$this->js_messagebox->add( 'success', esc_html__( 'License was removed successfully.', 'tasty-recipes-lite' ) );

		wp_send_json_success();
	}

	/**
	 * If there are admin notices, shoe the alert icon in the admin menu.
	 *
	 * @since 1.0.9
	 *
	 * @param bool $show_icon Show icon or not.
	 *
	 * @return bool
	 */
	public function maybe_show_menu_icon( $show_icon ) {
		$this->set_admin_notices();
		if ( empty( $this->admin_notices ) ) {
			return $show_icon;
		}

		add_action( 'tasty_admin_notices', array( $this, 'show_license_notices' ) );
		add_filter( 'tasty_framework_dashboard_plugin_details', array( $this, 'maybe_show_dashboard_plugin_message' ), 10, 3 );
		return true;
	}

	/**
	 * Set admin notices to check for menu icon or showing the notices.
	 *
	 * @since 1.0.9
	 *
	 * @return void
	 */
	private function set_admin_notices() {
		foreach ( Factory::create_active_plugins() as $active_plugin ) {
			if ( ! $active_plugin->has_framework() || $active_plugin->is_lite() ) {
				continue;
			}

			$plugin_slug = $active_plugin->get_plugin_slug();
			if ( ! $active_plugin->is_licensed() ) {
				$this->admin_notices[ $plugin_slug ] = array(
					'type'        => 'Notices/license-empty',
					'render_atts' => array(
						'plugin_name'        => $active_plugin->get_plugin_name(),
						'plugin_pricing_url' => $active_plugin->get_product_pricing_page_url(),
						'license_url'        => Url::get_main_admin_url(),
					),
				);
			} elseif ( ! $active_plugin->is_valid_license() ) {
				$this->admin_notices[ $plugin_slug ] = array(
					'type'        => 'Notices/license-invalid',
					'render_atts' => array(
						'plugin_name' => $active_plugin->get_plugin_name(),
					),
				);
			}
		}
	}

	/**
	 * Show license admin notices.
	 *
	 * @return void
	 */
	public function show_license_notices() {
		$screen = get_current_screen();
		if ( $screen && 'post' === $screen->base ) {
			return;
		}

		foreach ( Factory::create_active_plugins() as $active_plugin ) {
			$plugin_slug = $active_plugin->get_plugin_slug();
			if ( ! isset( $this->admin_notices[ $plugin_slug ] ) ) {
				continue;
			}

			$show_notice = 'wp-tasty_page_' . $plugin_slug === get_current_screen()->id;
			if ( ! apply_filters( 'tasty_framework_show_license_notice', $show_notice, $active_plugin ) ) {
				continue;
			}

			$notice = $this->admin_notices[ $plugin_slug ];
			$this->template->render( $notice['type'], $notice['render_atts'], true );
		}
	}

	/**
	 * Maybe show dashboard message to enter license.
	 *
	 * @param array           $details     Plugin details.
	 * @param string          $plugin_name Plugin name.
	 * @param PluginInstaller $plugin      Plugin instance.
	 *
	 * @return array
	 */
	public function maybe_show_dashboard_plugin_message( $details, $plugin_name, $plugin ) {
		$plugin_slug = $plugin->get_plugin_slug();
		if ( isset( $this->admin_notices[ $plugin_slug ] ) ) {
			$details['message'] = sprintf(
				// translators: %s Plugin name.
				__( '%s is almost ready. Click the "Enter License" button above to continue.', 'tasty-recipes-lite' ),
				$details['name']
			);
		}

		return $details;
	}

	/**
	 * Activate all plugins at once.
	 *
	 * @param string $license_key License key to activate.
	 *
	 * @return string|WP_Error
	 */
	private function activate_all_plugins( $license_key ) {
		$plugins = $this->api_client->get_key_plugins( $license_key );
		if ( is_wp_error( $plugins ) ) {
			return $plugins;
		}

		$at_least_one_activated = false;
		foreach ( $plugins as $plugin ) {
			$activated               = $this->activate_one_plugin( $license_key, $plugin['name'] );
			$at_least_one_activated |= ! is_wp_error( $activated );
		}

		if ( ! $at_least_one_activated ) {
			return new WP_Error( 500, __( 'The license was not saved.', 'tasty-recipes-lite' ) );
		}

		return 'active';// To simulate the active state for the plugin to show the success message.
	}

	/**
	 * Activate one plugin and get its status.
	 *
	 * @param string $license_key License key.
	 * @param string $plugin_name Plugin Name.
	 *
	 * @return string|WP_Error
	 */
	private function activate_one_plugin( $license_key, $plugin_name ) {
		$plugin = Factory::create( $plugin_name );
		if ( is_null( $plugin ) ) {
			return new WP_Error( 500, esc_html__( 'That\'s not a valid plugin.', 'tasty-recipes-lite' ) );
		}

		$response = $plugin->activate_license( $license_key );
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				500,
				esc_html__( 'Can\'t connect to the WP Tasty server. Please try again later.', 'tasty-recipes-lite' ) .
				sprintf(
					// translators: %1$s Error message.
					esc_html__( 'Error: %1$s', 'tasty-recipes-lite' ),
					$response->get_error_message()
				)
			);
		}

		if ( 'valid' !== $response['license'] ) {
			return new WP_Error( 500, esc_html__( 'That\'s not a valid license.', 'tasty-recipes-lite' ) );
		}

		// Save license details.
		$plugin->save_license_key( $license_key );

		do_action( 'tasty_framework_license_activated', $plugin_name, $license_key, $plugin );

		return $plugin->get_plugin_status();
	}

	/**
	 * Insert nonces into the js localize data to be passed with the ajax request.
	 *
	 * @param array $data Array of data to be passed.
	 *
	 * @return array
	 */
	public function insert_nonces_into_js( $data ) {
		$data['nonces'] = array(
			'license_key_plugins'     => wp_create_nonce( 'tasty_framework_license_key_plugins' ),
			'activate_plugin_license' => wp_create_nonce( 'tasty_framework_activate_license' ),
			'download_plugin'         => wp_create_nonce( 'tasty_framework_download_plugin' ),
		);
		return $data;
	}

	/**
	 * Validates the license key and exit with json error.
	 *
	 * @param string $license_key License key to be validated.
	 *
	 * @return void
	 */
	private function validate_license_key( $license_key ) {
		if ( empty( $license_key ) || 32 !== strlen( (string) $license_key ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'That\'s not a valid license.', 'tasty-recipes-lite' ),
				)
			);
		}
	}

	/**
	 * Validates the API response.
	 *
	 * @param array|WP_Error $response Response to be validated.
	 *
	 * @return void
	 */
	private function validate_api_response( $response ) {
		if ( empty( $response ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Can\'t connect to the WP Tasty server. Please try again later.', 'tasty-recipes-lite' ),
				)
			);
		}

		if ( is_wp_error( $response ) ) {
			wp_send_json_error(
				array(
					'message' => $response->get_error_message(),
				)
			);
		}
	}

	/**
	 * Validates plugin.
	 *
	 * @param mixed $plugin Plugin to be validated.
	 *
	 * @return void
	 */
	private function validate_plugin( $plugin ) {
		if ( empty( $plugin ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'That\'s not a valid plugin.', 'tasty-recipes-lite' ),
				)
			);
		}
	}

	/**
	 * Validate current user capability.
	 *
	 * @param string $cap Capability to check, default is manage_options.
	 *
	 * @return void
	 */
	private function validate_capability( $cap = 'manage_options' ) {
		if ( ! current_user_can( $cap ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Not authorized!', 'tasty-recipes-lite' ),
				)
			);
		}
	}

	/**
	 * Remove all admin notices from our dashboard page only.
	 *
	 * @return void
	 */
	public function remove_all_admin_notices_from_dashboard_page() {
		if ( ! Url::is_wpt_page() ) {
			return;
		}

		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'network_admin_notices' );
		remove_all_actions( 'user_admin_notices' );
		remove_all_actions( 'all_admin_notices' );

		do_action( 'tasty_after_remove_admin_notices' );
		add_action( 'admin_notices', array( $this, 'register_tasty_admin_notices' ) );
	}

	/**
	 * Register tasty admin notices.
	 *
	 * @return void
	 */
	public function register_tasty_admin_notices() {
		do_action( 'tasty_admin_notices' );
	}
}
