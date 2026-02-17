<?php
/**
 * Inbox API Client class.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Admin\Inbox;

use Tasty\Framework\Admin\Inbox\APIClient;
use Tasty\Framework\Utils\Vars;
use Tasty\Framework\Utils\HumanTimeDiff;

/**
 * Inbox API Client class.
 */
class Template {

	/**
	 * Render the inbox template.
	 *
	 * @return void
	 */
	public static function render() {
		$is_dismissed_tab = self::is_dismissed_tab();
		$messages         = APIClient::instance()->get_messages( $is_dismissed_tab );

		if ( is_wp_error( $messages ) ) {
			$messages = array();
		}

		tasty_get_admin_template()->render(
			'inbox',
			array(
				'is_dismissed_tab' => $is_dismissed_tab,
				'messages'         => $messages,
				'unread_count'     => self::get_unread_count( $messages ),
			),
			true
		);
	}

	/**
	 * Get the unread message count.
	 *
	 * @param array $messages The messages for the current page.
	 *
	 * @return int
	 */
	private static function get_unread_count( $messages ) {
		if ( ! self::is_dismissed_tab() ) {
			return count( $messages );
		}

		$unread_messages = APIClient::instance()->get_messages( false );
		return is_wp_error( $unread_messages ) ? 0 : count( $unread_messages );
	}

	/**
	 * Render the inbox messages.
	 *
	 * @param array $messages The messages to render.
	 *
	 * @return void
	 */
	public static function render_messages( $messages ) {
		$template_renderer = tasty_get_admin_template();
		$is_dismissed_tab  = self::is_dismissed_tab();
		foreach ( $messages as $message ) {
			$template_renderer->render(
				'inbox-message',
				array(
					'subject'          => $message['subject'],
					'message'          => $message['message'],
					'cta'              => $message['cta'],
					'ago'              => HumanTimeDiff::get( $message['starts'] ),
					'is_dismissed_tab' => $is_dismissed_tab,
					'dismiss_url'      => self::build_dismiss_url( $message['key'] ),
				),
				true
			);
		}
	}

	/**
	 * Render the inbox banner if there is one.
	 *
	 * @return void
	 */
	public static function render_banner() {
		$banner_message = APIClient::instance()->get_banner_message();
		if ( ! $banner_message ) {
			return;
		}

		tasty_get_admin_template()->render(
			'inbox-banner',
			array(
				'subject'     => $banner_message['subject'],
				'message'     => $banner_message['banner'],
				'cta'         => $banner_message['cta'],
				'dismiss_url' => self::build_dismiss_url( $banner_message['key'] ),
			),
			true
		);
	}

	/**
	 * Build the dismiss URL for a message.
	 *
	 * @param string $key The message key.
	 *
	 * @return string
	 */
	private static function build_dismiss_url( $key ) {
		$page_param = Vars::get_param( 'page' );

		// We check for the page value because the Vars::get_param will not always return the default value.
		$page = $page_param ? $page_param : 'tasty';

		$url = \add_query_arg(
			array(
				'page'   => $page,
				'action' => 'dismiss-inbox-message',
				'key'    => $key,
				'nonce'  => wp_create_nonce( 'tasty-dismiss-inbox-message' ),
			),
			admin_url( 'admin.php' )
		);
		$tab = Vars::get_param( 'tab' );
		if ( $tab ) {
			$url = add_query_arg( 'tab', $tab, $url );
		}
		return $url;
	}

	/**
	 * Check if the current tab is the dismissed tab.
	 *
	 * @return bool
	 */
	private static function is_dismissed_tab() {
		return Vars::get_param( 'tab' ) === 'inboxdismissed';
	}
}
