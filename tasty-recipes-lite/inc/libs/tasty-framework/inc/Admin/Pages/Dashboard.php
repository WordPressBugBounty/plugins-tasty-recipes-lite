<?php
/**
 * Dashboard page handler class.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Admin\Pages;

use Tasty\Framework\Abstracts\PluginInstaller;
use Tasty\Framework\Admin\Plugins\Factory;
use Tasty\Framework\Traits\Singleton;
use Tasty\Framework\Utils\Template;
use Tasty\Framework\Utils\Url;
use Tasty\Framework\Admin\Sales\APIClient as SalesAPIClient;

/**
 * Dashboard page handler class.
 */
class Dashboard {

	use Singleton;

	/**
	 * Template engine instance.
	 *
	 * @var Template
	 */
	private $template;

	/**
	 * Initialize instance.
	 *
	 * @param Template $template Template engine instance.
	 *
	 * @return $this
	 */
	public function init( $template ) {
		$this->template = $template;

		return $this;
	}

	/**
	 * Show page contents.
	 *
	 * @return void
	 */
	public function show() {
		$plugins           = array();
		$available_plugins = Factory::create_all_plugins();
		$show_add_license  = false;
		$is_lite_only      = true;

		foreach ( $available_plugins as $plugin ) {
			if ( ! $plugin ) {
				continue;
			}

			$plugins[] = $this->get_plugin_details( $plugin );

			if ( ! $plugin->is_valid_license() ) {
				$show_add_license = true;
			}

			if ( $plugin->is_active() ) {
				$is_lite_only &= $plugin->is_lite();
			}
		}

		$data = array(
			'plugins'          => $plugins,
			'show_add_license' => ! $is_lite_only && $show_add_license,
			'show_banner'      => $show_add_license,
		);
		$data = array_merge( $data, $this->get_all_access_vars( $is_lite_only ) );
		$this->template->render( 'dashboard', $data, true );
	}

	/**
	 * Get variables for All Access CTA.
	 *
	 * @param bool $is_lite_only Whether the site is only using Lite plugins.
	 *
	 * @return array
	 */
	private function get_all_access_vars( $is_lite_only ) {
		$sale_client  = SalesAPIClient::instance();
		$amount_saved = $sale_client->get_best_sale_value( 'all_access_amount_saved' ) ?? 50;
		$body         = $sale_client->get_best_sale_value( 'all_access_body' ) ?? __( 'Get all our plugins for 25 websites for one low price ($926 value)', 'tasty-recipes-lite' );

		$utm_params = array(
			'utm_campaign' => 'banner',
			'utm_content'  => 'allaccess',
		);
		if ( $is_lite_only ) {
			$utm_params['utm_campaign'] = 'lite_upgrade';
		}

		$cta_url = $sale_client->get_best_sale_value( 'all_access_cta_link' );
		$cta_url = $cta_url ? Url::add_missing_utm_params( $cta_url, $utm_params ) : Url::add_utm_params( $is_lite_only ? 'lite-upgrade' : 'pricing', $utm_params );

		$cta_text = $sale_client->get_best_sale_value( 'all_access_cta_text' ) ?? __( 'Get This Deal', 'tasty-recipes-lite' );

		return array(
			'all_access_amount_saved' => $amount_saved,
			'all_access_body'         => $body,
			'all_access_cta_url'      => $cta_url,
			'all_access_cta_text'     => $cta_text,
		);
	}

	/**
	 * Get plugin full details.
	 *
	 * @param PluginInstaller $plugin Plugin instance.
	 *
	 * @return array
	 */
	private function get_plugin_details( $plugin ) {
		$status = $this->get_plugin_status( $plugin );

		$details = array(
			'name'        => $plugin->get_plugin_name(),
			'status'      => $status,
			'status_text' => $this->get_plugin_status_text( $status ),
			'actions'     => $this->get_plugin_actions_by_status( $status, $plugin ),
			'buttons'     => $this->get_plugin_buttons( $plugin ),
			'message'     => '',
			'type_text'   => $plugin->is_lite() ? 'Lite' : '',
		);

		return (array) apply_filters( 'tasty_framework_dashboard_plugin_details', $details, $plugin->get_plugin_name(), $plugin, $status );
	}

	/**
	 * Get plugin status.
	 *
	 * @param PluginInstaller $plugin Plugin instance.
	 *
	 * @return string
	 */
	private function get_plugin_status( $plugin ) {
		$installed = $plugin->is_installed();
		$active    = $plugin->is_active();

		if ( ! $installed ) {
			return 'not-installed';
		}

		if ( ! $active ) {
			return 'installed';
		}

		if ( $plugin->is_lite() ) {
			return 'active';
		}

		$licensed = $plugin->is_licensed();

		if ( ! $licensed ) {
			return 'no-license';
		}

		if ( $plugin->is_error() ) {
			return 'error';
		}

		$expired = $plugin->is_expired();
		if ( $expired ) {
			return 'expired';
		}

		if ( ! $plugin->is_valid_license() ) {
			return 'no-license';
		}

		return 'active';
	}

	/**
	 * Get plugin status text.
	 *
	 * @param string $status Status code.
	 *
	 * @return string
	 */
	private function get_plugin_status_text( $status ) {
		$statuses = array(
			'active'        => __( 'Active', 'tasty-recipes-lite' ),
			'installed'     => __( 'Installed', 'tasty-recipes-lite' ),
			'not-installed' => __( 'Not installed', 'tasty-recipes-lite' ),
			'no-license'    => __( 'No License', 'tasty-recipes-lite' ),
			'expired'       => __( 'Expired', 'tasty-recipes-lite' ),
			'error'         => __( 'Error', 'tasty-recipes-lite' ),
		);

		if ( ! isset( $statuses[ $status ] ) ) {
			return '';
		}

		return $statuses[ $status ];
	}

	/**
	 * Get plugin actions by status.
	 *
	 * @param string          $status Status code.
	 * @param PluginInstaller $plugin Plugin instance.
	 *
	 * @return array
	 */
	private function get_plugin_actions_by_status( $status, $plugin ) {
		$actions = array();

		switch ( $status ) {
			case 'no-license':
			case 'not-installed':
				$actions[]       = array(
					'type' => 'external',
					'name' => __( 'Learn more', 'tasty-recipes-lite' ),
					'link' => Url::add_utm_params(
						$plugin->is_lite() ? 'lite-upgrade' : $plugin->get_product_details_page_url(),
						array(
							'utm_campaign' => 'actions',
							'utm_content'  => 'learn_more',
						)
					),
				);
				$get_license_url = Url::get_upgrade_url(
					$plugin,
					array(
						'utm_campaign' => 'actions',
						'utm_content'  => 'get_license',
						'utm_medium'   => 'dashboard',
					)
				);

				$actions[] = array(
					'type'  => 'external',
					'name'  => __( 'Get a license', 'tasty-recipes-lite' ),
					'link'  => $get_license_url,
					'class' => 'tasty-highlight',
				);
				break;

			case 'active':
			case 'expired':
				$this->active_or_expired_actions( $plugin, $actions );
				$this->upgrade_actions( $plugin, $actions );
				break;

			case 'installed':
				$this->installed_plugin_actions( $plugin, $actions );
				break;
		}

		$this->active_plugin_actions( $plugin, $actions );

		return $actions;
	}

	/**
	 * Add actions for active or expired plugins.
	 *
	 * @since 1.0.9
	 *
	 * @param PluginInstaller $plugin   Plugin instance.
	 * @param array           &$actions Actions array.
	 *
	 * @return void
	 */
	private function active_or_expired_actions( $plugin, &$actions ) {
		if ( ! $plugin->is_lite() ) {
			$actions[] = array(
				'type' => 'ajax',
				'name' => __( 'Remove license', 'tasty-recipes-lite' ),
				'link' => add_query_arg(
					array(
						'action'      => 'tasty_framework_remove_license',
						'_ajax_nonce' => wp_create_nonce( 'tasty_framework_remove_license' ),
						'plugin'      => $plugin->get_plugin_name(),
					),
					admin_url( 'admin-ajax.php' )
				),
			);
		}

		$actions['settings'] = array(
			'type' => 'internal',
			'name' => __( 'Settings', 'tasty-recipes-lite' ),
			'link' => menu_page_url( $plugin->get_menu_slug(), false ),
		);
	}

	/**
	 * Add upgrade actions for plugins.
	 *
	 * @since 1.0.9
	 *
	 * @param PluginInstaller $plugin   Plugin instance.
	 * @param array           &$actions Actions array.
	 *
	 * @return void
	 */
	private function upgrade_actions( $plugin, &$actions ) {
		if ( ! $plugin->can_upgrade() ) {
			return;
		}

		$upgrade_url = Url::get_upgrade_url(
			$plugin,
			array(
				'utm_medium'  => 'dashboard',
				'utm_content' => 'actions',
			)
		);
		$actions[]   = array(
			'type'  => 'external',
			'name'  => __( 'Upgrade plan', 'tasty-recipes-lite' ),
			'link'  => $upgrade_url,
			'class' => 'tasty-highlight',
		);
	}

	/**
	 * Add actions for installed plugins.
	 *
	 * @since 1.0.9
	 *
	 * @param PluginInstaller $plugin   Plugin instance.
	 * @param array           &$actions Actions array.
	 *
	 * @return void
	 */
	private function installed_plugin_actions( $plugin, &$actions ) {
		$learn_more_url = Url::add_utm_params(
			$plugin->is_lite() ? 'lite-upgrade' : $plugin->get_product_details_page_url(),
			array(
				'utm_campaign' => 'buttons',
				'utm_content'  => $plugin->get_plugin_slug(),
			)
		);
		$actions[]      = array(
			'type' => 'external',
			'name' => __( 'Learn more', 'tasty-recipes-lite' ),
			'link' => $learn_more_url,
		);
		$actions[]      = array(
			'type' => 'ajax',
			'name' => __( 'Activate plugin', 'tasty-recipes-lite' ),
			'link' => add_query_arg(
				array(
					'action'      => 'tasty_framework_activate_plugin',
					'_ajax_nonce' => wp_create_nonce( 'tasty_framework_activate_plugin' ),
					'plugin'      => $plugin->get_plugin_name(),
				),
				admin_url( 'admin-ajax.php' )
			),
		);
	}

	/**
	 * Add actions for installed plugins.
	 *
	 * @since 1.0.9
	 *
	 * @param PluginInstaller $plugin   Plugin instance.
	 * @param array           &$actions Actions array.
	 *
	 * @return void
	 */
	private function active_plugin_actions( $plugin, &$actions ) {
		if ( ! $plugin->is_active() ) {
			return;
		}

		$actions[] = array(
			'type' => 'ajax',
			'name' => __( 'Deactivate', 'tasty-recipes-lite' ),
			'link' => add_query_arg(
				array(
					'action'      => 'tasty_framework_deactivate_plugin',
					'_ajax_nonce' => wp_create_nonce( 'tasty_framework_deactivate_plugin' ),
					'plugin'      => $plugin->get_plugin_name(),
				),
				admin_url( 'admin-ajax.php' )
			),
		);
	}

	/**
	 * Get plugin buttons by status.
	 *
	 * @param PluginInstaller $plugin Plugin instance.
	 *
	 * @return array
	 */
	private function get_plugin_buttons( $plugin ) {
		$buttons   = array();
		$installed = $plugin->is_installed();
		$active    = $plugin->is_active();

		if ( ! $installed ) {
			if ( ! $plugin->is_valid_license() ) {
				return array();
			}

			$buttons[] = array(
				'type' => 'ajax',
				'name' => __( 'Download', 'tasty-recipes-lite' ),
				'link' => add_query_arg(
					array(
						'action'         => 'tasty_framework_download_plugin',
						'_ajax_nonce'    => wp_create_nonce( 'tasty_framework_download_plugin' ),
						'license_plugin' => $plugin->get_plugin_name(),
					),
					admin_url( 'admin-ajax.php' )
				),
			);
			return $buttons;
		}

		if ( $active ) {
			$this->add_renew_btn( $plugin, $buttons );
			$this->add_lite_upgrade_btn( $plugin, $buttons );
		} else {
			$buttons[] = array(
				'type' => 'ajax',
				'name' => __( 'Activate', 'tasty-recipes-lite' ),
				'link' => add_query_arg(
					array(
						'action'      => 'tasty_framework_activate_plugin',
						'_ajax_nonce' => wp_create_nonce( 'tasty_framework_activate_plugin' ),
						'plugin'      => $plugin->get_plugin_name(),
					),
					admin_url( 'admin-ajax.php' )
				),
			);
		}

		return $buttons;
	}

	/**
	 * Add renew button for expired plugins.
	 *
	 * @param PluginInstaller $plugin   Plugin instance.
	 * @param array           &$buttons List of buttons.
	 *
	 * @return void
	 */
	private function add_renew_btn( $plugin, &$buttons ) {
		if ( $plugin->is_lite() || ! $plugin->is_licensed() || $plugin->is_error() || ! $plugin->is_expired() ) {
			return;
		}

		$buttons[] = array(
			'type' => 'external',
			'name' => __( 'Renew', 'tasty-recipes-lite' ),
			'link' => Url::add_utm_params(
				'account/downloads',
				array(
					'utm_medium'  => 'dashboard',
					'utm_content' => 'expired',
				)
			),
		);
	}

	/**
	 * Add upgrade button for lite plugins.
	 *
	 * @param PluginInstaller $plugin  Plugin instance.
	 * @param array           $buttons List of buttons.
	 *
	 * @return void
	 */
	private function add_lite_upgrade_btn( $plugin, &$buttons ) {
		if ( ! $plugin->is_lite() ) {
			return;
		}

		$sale_client = SalesAPIClient::instance();

		$utm_params      = array(
			'utm_campaign' => 'lite_upgrade',
			'utm_content'  => 'buttons',
			'utm_medium'   => 'dashboard',
		);
		$get_license_url = $sale_client->get_best_sale_value( 'lite_plugin_upgrade_button_link' );
		$get_license_url = $get_license_url ? Url::add_missing_utm_params( $get_license_url, $utm_params ) : Url::get_upgrade_url( $plugin, $utm_params );

		$buttons[] = array(
			'type'  => 'external',
			'name'  => $sale_client->get_best_sale_value( 'lite_plugin_upgrade_button_text' ) ?? __( 'Upgrade', 'tasty-recipes-lite' ),
			'link'  => $get_license_url,
			'class' => 'tasty-highlight',
		);
	}
}
