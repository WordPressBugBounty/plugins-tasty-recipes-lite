<?php
/**
 * MessageBox Abstract class, Abstract for user targeted messages box, can be used to show messages in admin area.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Abstracts;

/**
 * MessageBox Abstract class, Abstract for user targeted messages box, can be used to show messages in admin area.
 */
abstract class MessageBox {

	/**
	 * Current User ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * MessageBox Unique Identifier, used in transient name.
	 *
	 * @return string
	 */
	abstract protected function identifier();

	/**
	 * Initialize instance.
	 *
	 * @param int $user_id Current user ID.
	 *
	 * @return $this
	 */
	public function init( $user_id ) {
		$this->user_id = $user_id;

		return $this;
	}

	/**
	 * Prepare the transient name.
	 *
	 * @return string
	 */
	private function get_transient_name() {
		return 'tasty_messagebox_' . $this->user_id . '_' . $this->identifier();
	}

	/**
	 * Get all messages from the message box.
	 *
	 * @return array
	 */
	public function all() {
		$items = get_transient( $this->get_transient_name() );
		return false !== $items ? $items : array();
	}

	/**
	 * Add new message to the message box.
	 *
	 * @param string $type    Type of the message.
	 * @param string $message Message text.
	 *
	 * @return bool
	 */
	public function add( $type, $message ) {
		$items   = $this->all();
		$items[] = array(
			'type'    => $type,
			'message' => $message,
		);
		return set_transient( $this->get_transient_name(), $items, 10 * MINUTE_IN_SECONDS );
	}

	/**
	 * Clear current message box.
	 *
	 * @return void
	 */
	public function clear() {
		delete_transient( $this->get_transient_name() );
	}

	/**
	 * Get messages in messagebox then clear them.
	 *
	 * @return array
	 */
	public function get_and_clear() {
		$items = $this->all();
		$this->clear();
		return $items;
	}
}
