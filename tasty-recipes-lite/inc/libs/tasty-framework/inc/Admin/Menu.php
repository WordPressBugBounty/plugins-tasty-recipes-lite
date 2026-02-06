<?php
/**
 * Admin menu class.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Admin;

use Tasty\Framework\Admin\Pages\Dashboard;
use Tasty\Framework\Admin\Plugins\Factory;
use Tasty\Framework\Main;
use Tasty\Framework\Traits\Singleton;
use Tasty\Framework\Utils\Template;
use Tasty\Framework\Utils\Url;
use Tasty\Framework\Admin\Sales\APIClient as SalesAPIClient;
use Tasty\Framework\Admin\License\APIClient as LicenseAPIClient;

/**
 * Admin menu class.
 */
class Menu {

	use Singleton;

	/**
	 * Menu items.
	 *
	 * @var array
	 */
	private $items = array();

	/**
	 * Menu items hooks.
	 *
	 * @var array
	 */
	private $menu_hooks = array(); // @phpstan-ignore-line

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
	 * Initialize class.
	 *
	 * @param Template     $template      Template engine instance.
	 * @param JSMessageBox $js_messagebox JS Message Box Instance.
	 *
	 * @return $this
	 */
	public function init( $template, $js_messagebox ) {
		$this->template      = $template;
		$this->js_messagebox = $js_messagebox;

		add_action( 'admin_menu', array( $this, 'add_default_admin_menu_items' ) );
		add_filter( 'allowed_redirect_hosts', array( $this, 'add_wptasty_site' ) );

		add_action( 'in_admin_header', array( $this, 'show_admin_header' ), 100 );
		add_action( 'in_admin_footer', array( $this, 'show_admin_footer' ), 100 );
		add_filter( 'admin_body_class', array( $this, 'add_admin_body_classes' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_menu_classes' ) );

		return $this;
	}

	/**
	 * Add default menu items.
	 *
	 * @return void
	 */
	public function add_default_admin_menu_items() {
		$base_slug = 'tasty';
		$cap       = 'manage_options';

		add_menu_page(
			'WP Tasty',
			'WP Tasty',
			$cap,
			$base_slug,
			array( $this, 'show_dashboard' ),
			Main::plugin_url( 'assets/images/icon-menu.svg' ),
			58
		);

		$base_menu_hook = add_submenu_page(
			$base_slug,
			__( 'WP Tasty Dashboard', 'tasty-recipes-lite' ),
			__( 'Dashboard', 'tasty-recipes-lite' ),
			$cap,
			$base_slug
		);

		$this->menu_hooks[ $base_menu_hook ] = __( 'Dashboard', 'tasty-recipes-lite' );

		foreach ( $this->get_menu_items() as $menu_key => $menu_item ) {
			if ( ! empty( $menu_item['redirect_url'] ) ) {
				$menu_key = $menu_item['redirect_url'];
			}

			$subpage_hook = add_submenu_page( $base_slug, $menu_item['page_title'], $menu_item['title'], $menu_item['capability'], $menu_key, $menu_item['callback'] );

			if ( ! empty( $menu_item['load_callback'] ) && is_callable( $menu_item['load_callback'] ) ) {
				add_action( 'load-' . $subpage_hook, $menu_item['load_callback'] );
			}

			$this->menu_hooks[ $subpage_hook ] = $menu_item['title'];
		}

		$this->maybe_add_upsell_submenu_item();
	}

	/**
	 * Add the Black Friday submenu item if the current date is within the sale range.
	 *
	 * @since x.x
	 *
	 * @return void
	 */
	private function maybe_add_upsell_submenu_item() {
		if ( ! current_user_can( 'manage_options' ) || $this->is_all_access() ) {
			return;
		}

		$sales_client  = SalesAPIClient::instance();
		$submenu_label = $sales_client->get_best_sale_value( 'menu_cta_text' ) ?? __( 'Upgrade', 'tasty-recipes-lite' );
		$submenu_label = '<span class="wpt-upgrade-submenu">' . esc_html( $submenu_label ) . '</span>';

		$utm_params = array(
			'utm_medium'  => 'menu',
			'utm_content' => 'submenu',
		);
		$menu_url   = $sales_client->get_best_sale_value( 'menu_cta_link' );
		$menu_url   = $menu_url ? Url::add_missing_utm_params( $menu_url, $utm_params ) : Url::add_utm_params( $this->is_lite_only() ? 'lite-upgrade' : 'pricing', $utm_params );

		global $submenu;
		//phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$submenu['tasty'][] = array( $submenu_label, 'manage_options', $menu_url );
	}

	/**
	 * Check if all active plugins are lite plugins.
	 *
	 * @return bool
	 */
	private function is_lite_only() {
		foreach ( Factory::create_active_plugins() as $active_plugin ) {
			if ( $active_plugin->has_framework() && ! $active_plugin->is_lite() && $active_plugin->is_licensed() ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Check if the user has all access license set.
	 *
	 * @return bool
	 */
	private function is_all_access() {
		$api_client = LicenseAPIClient::instance();

		foreach ( Factory::create_active_plugins() as $active_plugin ) {
			if ( $active_plugin->has_framework() && ! $active_plugin->is_lite() && $active_plugin->is_licensed() ) {
				$license      = $active_plugin->get_license_key();
				$plugins      = $api_client->get_key_plugins( $license );
				$first_plugin = reset( $plugins );
				if ( is_array( $first_plugin ) && ! empty( $first_plugin['type'] ) && $first_plugin['type'] === 'complete-bundle' ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get default menu items.
	 *
	 * @return array
	 */
	private function get_menu_items() {
		$items = array();

		$all_plugins = Factory::create_all_plugins();

		foreach ( $all_plugins as $plugin ) {
			if ( $plugin->is_installed() && ! $plugin->is_active() ) {
				continue;
			}

			$redirect_url = $plugin->is_active() ? '' : Url::add_utm_params(
				$plugin->is_lite() ? 'lite-upgrade' : $plugin->get_product_details_page_url()
			);
			$item_key     = ! empty( $redirect_url ) ? $redirect_url : $plugin->get_menu_slug();
			if ( ! $plugin->has_framework() && $plugin->is_active() ) {
				// Use the old settings link in the new nav if not updated.
				$item_key = menu_page_url( $item_key, false );
			}

			$items[ $item_key ] = array(
				'title'        => $plugin->get_plugin_name(),
				'page_title'   => $plugin->get_plugin_name(),
				'callback'     => '',
				'active'       => $plugin->is_active(),
				'redirect_url' => $redirect_url,
				'capability'   => $plugin->get_required_capability(),
			);
		}

		$items = (array) apply_filters( 'tasty_framework_admin_menu_items', $items );

		uasort( $items, array( $this, 'sort_menu_items' ) );

		$this->items = $items;

		return $items;
	}

	/**
	 * Sort menu items based on active state.
	 *
	 * @param array $previous Previous menu item.
	 * @param array $current  Current menu item.
	 *
	 * @return int
	 */
	public function sort_menu_items( $previous, $current ) {
		return $current['active'] && ! $previous['active'] ? 1 : -1;
	}

	/**
	 * Show dashboard page.
	 *
	 * @return void
	 */
	public function show_dashboard() {
		Dashboard::instance()->init( $this->template )->show();
	}

	/**
	 * Add wptasty website to the allowed list of sites to be safely redirected to.
	 *
	 * @param array $allowed List of allowed sites.
	 *
	 * @return mixed
	 */
	public function add_wptasty_site( $allowed ) {
		$allowed[] = 'www.wptasty.com';
		return $allowed;
	}

	/**
	 * Show header in our menu pages.
	 *
	 * @return void
	 */
	public function show_admin_header() {
		if ( ! Url::is_wpt_page() ) {
			return;
		}

		do_action( 'tasty_framework_admin_header_before' );

		$title = apply_filters( 'tasty_framework_admin_header_title', get_admin_page_title() );

		if ( 'WP Tasty Dashboard' === $title ) {
			$title = __( 'Dashboard', 'tasty-recipes-lite' );
		}

		$this->template->render(
			'header',
			array(
				'title' => $title,
				'logo'  => TASTY_FRAMEWORK_URL_ASSETS_IMAGES . '/header-logo.svg',
			),
			true
		);

		do_action( 'tasty_framework_admin_header_after' );
	}

	/**
	 * Show footer in our menu pages.
	 *
	 * @return void
	 */
	public function show_admin_footer() {
		if ( ! Url::is_wpt_page() ) {
			return;
		}

		do_action( 'tasty_framework_admin_footer_before' );

		switch ( get_current_screen()->id ) {
			case 'toplevel_page_tasty':
				$this->template->render( 'dashboard-modal', array(), true );
				break;
		}

		$this->template->render( 'alerts', array(), true );

		do_action( 'tasty_framework_admin_footer_after' );
	}

	/**
	 * Add admin body classes.
	 *
	 * @param string $classes Admin body class names.
	 *
	 * @return string
	 */
	public function add_admin_body_classes( $classes ) {
		if ( apply_filters( 'tasty_framework_notification_icon', false ) ) {
			$classes .= ' tasty-has-notification ';
		}

		if ( ! Url::is_wpt_page() ) {
			return $classes;
		}

		$classes .= ' tasty-framework ';
		return $classes;
	}

	/**
	 * Enqueue admin assets only in our pages.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets() {
		wp_register_style( 'tasty-framework-global-css', TASTY_FRAMEWORK_URL_ASSETS_STYLES . '/global.css', array(), TASTY_FRAMEWORK_VERSION );
		wp_enqueue_style( 'tasty-framework-admin-menu-css', TASTY_FRAMEWORK_URL_ASSETS_STYLES . '/admin-menu.css', array(), TASTY_FRAMEWORK_VERSION );

		wp_enqueue_script( 'tasty-framework-global', TASTY_FRAMEWORK_URL_ASSETS_SCRIPTS . '/global.js', array(), TASTY_FRAMEWORK_VERSION, true );

		if ( ! Url::is_wpt_page() ) {
			return;
		}

		do_action( 'tasty_framework_admin_enqueue_assets_before' );

		wp_enqueue_script( 'tasty-framework-backend', TASTY_FRAMEWORK_URL_ASSETS_SCRIPTS . '/backend.js', array(), TASTY_FRAMEWORK_VERSION, true );

		$js_backend_data = (array) apply_filters(
			'tasty_framework_admin_localize_script_data',
			array(
				'js_messagebox' => $this->get_all_box_messages(),
			)
		);

		wp_localize_script(
			'tasty-framework-backend',
			'tasty_backend',
			$js_backend_data
		);

		switch ( get_current_screen()->id ) {
			case 'toplevel_page_tasty':
				wp_enqueue_style( 'tasty-framework-dashboard', TASTY_FRAMEWORK_URL_ASSETS_STYLES . '/dashboard.css', array(), TASTY_FRAMEWORK_VERSION );
				break;
			default:
				wp_enqueue_style( 'tasty-framework-settings', TASTY_FRAMEWORK_URL_ASSETS_STYLES . '/settings.css', array(), TASTY_FRAMEWORK_VERSION );
		}

		do_action( 'tasty_framework_admin_enqueue_assets_after' );
	}

	/**
	 * Play with our submenu classes to differentiate between active and not active plugins.
	 *
	 * @return void
	 */
	public function handle_menu_classes() {
		if ( empty( $this->items ) ) {
			return;
		}

		global $submenu;

		if ( ! isset( $submenu['tasty'] ) ) {
			return;
		}

		foreach ( $submenu['tasty'] as $sub_key => $sub_item ) {
			if (
				empty( $this->items[ $sub_item[2] ] )
				||
				(
					! empty( $this->items[ $sub_item[2] ] )
					&&
					! empty( $this->items[ $sub_item[2] ]['active'] )
				)
			) {
				$class = 'tasty-is-active';
			} else {
				$class = 'tasty-not-active';
			}

			// The key 4 is responsible for the class attribute.
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$submenu['tasty'][ $sub_key ][4] = $class;
		}
	}

	/**
	 * Get all box messages for the js.
	 *
	 * @return array
	 */
	private function get_all_box_messages() {
		$messages          = $this->js_messagebox->get_and_clear();
		$settings_messages = get_settings_errors( 'general' );
		if ( empty( $settings_messages ) ) {
			return $messages;
		}

		foreach ( $settings_messages as $msg ) {
			$messages[] = array(
				'type'    => $msg['type'],
				'message' => $msg['message'],
			);
		}
		return $messages;
	}
}
