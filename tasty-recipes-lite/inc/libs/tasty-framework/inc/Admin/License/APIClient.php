<?php
/**
 * License API Client class.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Admin\License;

use Tasty\Framework\Abstracts\APIClient as AbstractAPIClient;
use Tasty\Framework\Traits\Singleton;
use WP_Error;

/**
 * License API Client class.
 */
class APIClient extends AbstractAPIClient {

	use Singleton;

	/**
	 * Get the server's public IP address for license error reporting.
	 *
	 * @since x.x
	 *
	 * @return string Public IP address or 'unknown'.
	 */
	protected function get_ip_address() {
		$cached = get_transient( 'tasty_framework_server_public_ip' );
		if ( false !== $cached ) {
			return $cached;
		}

		$response = wp_remote_request(
			'https://api64.ipify.org',
			array(
				'method'  => 'GET',
				'timeout' => 3,
			)
		);

		if ( ! is_wp_error( $response ) ) {
			$ip = $this->cache_valid_ip( trim( wp_remote_retrieve_body( $response ) ) );

			if ( $ip ) {
				return $ip;
			}
		}

		$ip = $this->cache_valid_ip( gethostbyname( gethostname() ) );

		return $ip ? $ip : 'unknown';
	}

	/**
	 * Validate and cache a public IP address.
	 *
	 * @since x.x
	 *
	 * @param string $ip IP address to validate.
	 *
	 * @return false|string Valid public IP or false.
	 */
	private function cache_valid_ip( $ip ) {
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			return false;
		}

		set_transient( 'tasty_framework_server_public_ip', $ip, HOUR_IN_SECONDS );

		return $ip;
	}

	/**
	 * Get plugins attached to license key (Raw request).
	 *
	 * @param string $license_key License key to be checked.
	 *
	 * @return array|WP_Error
	 */
	private function get_key_plugins_raw( $license_key ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$success = $this->handle_get( 'wp-json/s11edd/v1/updates/?l=' . base64_encode( $license_key ) );
		if ( $success ) {
			$plugins = array_filter(
				$this->response_body,
				function ( $plugin ) {
					return ! empty( $plugin['package'] );
				}
			);
			if ( ! empty( $plugins ) ) {
				return $plugins;
			}

			$this->error_message = __( 'No plugins are associated with that license or it does not exist.', 'tasty-recipes-lite' );
		}

		return new WP_Error( 500, $this->error_message );
	}

	/**
	 * Get plugins attached to license key (Cached).
	 *
	 * @param string $license_key License key to be checked.
	 *
	 * @return array|WP_Error
	 */
	public function get_key_plugins( $license_key ) {
		$cache_key    = 'tasty_framework_key_plugins_' . $license_key;
		$cached_value = get_transient( $cache_key );
		if ( false !== $cached_value ) {
			return $cached_value;
		}
		$key_plugins = $this->get_key_plugins_raw( $license_key );
		if ( ! is_wp_error( $key_plugins ) ) {
			set_transient( $cache_key, $key_plugins, HOUR_IN_SECONDS );
		}
		return $key_plugins;
	}

	/**
	 * Send activate license request.
	 *
	 * @param string $license_key License key.
	 * @param string $plugin_name Plugin name.
	 *
	 * @return array|WP_Error
	 */
	public function activate_plugin_license( $license_key, $plugin_name ) {
		$api_params = array(
			'timeout' => 15,
			'body'    => array(
				'edd_action' => 'activate_license',
				'license'    => $license_key,
				'item_name'  => $plugin_name, // the name of our product in EDD.
				'url'        => home_url(),
			),
		);

		return $this->send_edd_request( $api_params );
	}

	/**
	 * Send deactivate license request.
	 *
	 * @param string $license_key License key.
	 * @param string $plugin_name Plugin name.
	 *
	 * @return array|WP_Error
	 */
	public function deactivate_plugin_license( $license_key, $plugin_name ) {
		$api_params = array(
			'timeout' => 15,
			'body'    => array(
				'edd_action' => 'deactivate_license',
				'license'    => $license_key,
				'item_name'  => $plugin_name, // the name of our product in EDD.
				'url'        => home_url(),
			),
		);

		return $this->send_edd_request( $api_params );
	}

	/**
	 * Send check license request (Raw request).
	 *
	 * @param string $license_key License key.
	 * @param string $plugin_name Plugin name.
	 *
	 * @return array|WP_Error
	 */
	public function check_plugin_license_raw( $license_key, $plugin_name ) {
		$api_params = array(
			'timeout' => 15,
			'body'    => array(
				'edd_action' => 'check_license',
				'license'    => $license_key,
				'item_name'  => $plugin_name, // the name of our product in EDD.
				'url'        => home_url(),
			),
		);

		return $this->send_edd_request( $api_params );
	}

	/**
	 * Send EDD request and prepare the response.
	 *
	 * @param array|WP_Error $api_params API sent params.
	 *
	 * @return array|WP_Error
	 */
	private function send_edd_request( $api_params ) {
		return $this->handle_edd_response(
			$this->handle_post( '', $api_params )
		);
	}

	/**
	 * Handle and prepare EDD response.
	 *
	 * @param bool $response_status WP response status.
	 *
	 * @return array|WP_Error
	 */
	private function handle_edd_response( $response_status ) {
		if ( $response_status ) {
			if ( $this->response_body && array_key_exists( 'success', $this->response_body ) && $this->response_body['success'] ) {
				return $this->response_body;
			}

			$error_code          = $this->response_body['error'] ?? '';
			$this->error_message = $this->get_edd_error_message( $error_code );

			return new WP_Error( 'edd_error', $this->error_message );
		}

		return new WP_Error( 500, $this->error_message );
	}

	/**
	 * Map an EDD error code to a human-readable message.
	 *
	 * @since x.x
	 *
	 * @param string $error_code EDD error code from the API response.
	 *
	 * @return string Translated error message.
	 */
	private function get_edd_error_message( $error_code ) {
		$messages = array(
			'expired'             => __( 'This license key has expired.', 'tasty-recipes-lite' ),
			'disabled'            => __( 'This license key has been disabled.', 'tasty-recipes-lite' ),
			'revoked'             => __( 'This license key has been revoked.', 'tasty-recipes-lite' ),
			'no_activations_left' => __( 'This license has reached its activation limit. Deactivate it on another site first.', 'tasty-recipes-lite' ),
			'item_name_mismatch'  => __( 'This license key is not valid for this plugin.', 'tasty-recipes-lite' ),
			'invalid_item_id'     => __( 'This license key is not valid for this plugin.', 'tasty-recipes-lite' ),
			'key_mismatch'        => __( 'This license key is invalid.', 'tasty-recipes-lite' ),
			'missing'             => __( 'This license key does not exist.', 'tasty-recipes-lite' ),
		);

		return $messages[ $error_code ] ?? __( 'Unknown error', 'tasty-recipes-lite' );
	}
}
