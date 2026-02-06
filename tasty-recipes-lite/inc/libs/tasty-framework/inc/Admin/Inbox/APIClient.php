<?php
/**
 * Inbox API Client class.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Admin\Inbox;

use Tasty\Framework\Abstracts\APIClient as AbstractAPIClient;
use Tasty\Framework\Traits\Singleton;
use Tasty\Framework\Traits\Who;
use Tasty\Framework\Utils\Vars;
use WP_Error;

/**
 * Inbox API Client class.
 */
class APIClient extends AbstractAPIClient {

	use Singleton;
	use Who;

	/**
	 * Dismissed messages are stored for re-use to prevent multiple queries.
	 *
	 * @var array|null
	 */
	private static $dismissed_messages;

	/**
	 * Get messages (Raw request).
	 *
	 * @param bool $dismissed If true, only dismissed messages are returned. If false, only non-dismissed messages are returned.
	 *
	 * @return array|WP_Error
	 */
	public function get_messages( $dismissed = false ) {
		$transient_name      = 'tasty_inbox_messages';
		$this->response_body = get_transient( $transient_name );

		if ( false === $this->response_body ) {
			$success = $this->handle_get( 'wp-json/inbox/v1/message/' );
			if ( ! $success ) {
				return new WP_Error( 500, $this->error_message );
			}
			set_transient( $transient_name, $this->response_body, 3 * HOUR_IN_SECONDS );
		}

		$this->maybe_dismiss_message();

		$messages = array_filter(
			$this->response_body,
			function ( $message ) use ( $dismissed ) {
				return $this->should_include_message_in_current_context( $message, $dismissed );
			}
		);
		if ( ! empty( $messages ) ) {
			return $messages;
		}

		$this->error_message = __( 'No messages found.', 'tasty-recipes-lite' );

		return new WP_Error( 500, $this->error_message );
	}

	/**
	 * Get message to show in banner if one is applicable.
	 *
	 * @return array|false
	 */
	public function get_banner_message() {
		$messages = $this->get_messages();
		if ( is_wp_error( $messages ) || ! $messages ) {
			return false;
		}
		foreach ( $messages as $message ) {
			if ( ! empty( $message['banner'] ) ) {
				return $message;
			}
		}
		return false;
	}

	/**
	 * Check if a message should be included in the current context.
	 *
	 * @param array $message   The message to check.
	 * @param bool  $dismissed If true, only dismissed messages are returned. If false, only non-dismissed messages are returned.
	 *
	 * @return bool
	 */
	private function should_include_message_in_current_context( $message, $dismissed ) {
		if ( empty( $message['key'] ) ) {
			return false;
		}

		if ( $dismissed !== $this->message_is_dismissed( $message['key'] ) ) {
			return false;
		}

		if ( ! $this->is_in_correct_timeframe( $message ) ) {
			return false;
		}

		return empty( $message['who'] ) || $this->matches_who( $message['who'] );
	}

	/**
	 * Check if a message is in the correct timeframe.
	 *
	 * @param array $message The message to check.
	 *
	 * @return bool
	 */
	private function is_in_correct_timeframe( $message ) {
		if ( ! empty( $message['expires'] ) && $message['expires'] + DAY_IN_SECONDS < time() ) {
			return false;
		}

		return empty( $message['starts'] ) || $message['starts'] < time();
	}

	/**
	 * Maybe dismiss a message.
	 *
	 * @return void
	 */
	private function maybe_dismiss_message() {
		if ( 'dismiss-inbox-message' !== Vars::get_param( 'action' ) ) {
			return;
		}

		if ( ! wp_verify_nonce( Vars::get_param( 'nonce' ), 'tasty-dismiss-inbox-message' ) ) {
			return;
		}

		$message_to_dismiss = Vars::get_param( 'key' );
		if ( $message_to_dismiss ) {
			$this->dismiss_message( $message_to_dismiss );
		}
	}

	/**
	 * Dismiss a message.
	 *
	 * @param string $key Message key to dismiss.
	 *
	 * @return void
	 */
	private function dismiss_message( $key ) {
		$current_user_id    = get_current_user_id();
		$dismissed_messages = get_user_option( 'tasty_dismissed_messages', $current_user_id );

		if ( ! is_array( $dismissed_messages ) ) {
			$dismissed_messages = array();
		}

		if ( in_array( $key, $dismissed_messages, true ) ) {
			return;
		}

		$dismissed_messages[] = $key;
		update_user_option( $current_user_id, 'tasty_dismissed_messages', $dismissed_messages );
	}

	/**
	 * Check if a message is dismissed.
	 *
	 * @param string $key Message key.
	 *
	 * @return bool
	 */
	private function message_is_dismissed( $key ) {
		if ( isset( self::$dismissed_messages ) ) {
			return in_array( $key, self::$dismissed_messages, true );
		}

		$dismissed_messages = get_user_option( 'tasty_dismissed_messages', get_current_user_id() );

		if ( ! is_array( $dismissed_messages ) ) {
			$dismissed_messages = array();
		}

		self::$dismissed_messages = $dismissed_messages;

		return in_array( $key, $dismissed_messages, true );
	}
}
