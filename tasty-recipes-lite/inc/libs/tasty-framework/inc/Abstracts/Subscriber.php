<?php
/**
 * Hooks subscriber abstract class.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Abstracts;

/**
 * Hooks subscriber abstract class.
 */
abstract class Subscriber {

	/**
	 * Subscriber instance.
	 *
	 * @since 1.0.4
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @since 1.0.4
	 *
	 * @return self
	 */
	public static function get_instance() {
		$called_class = get_called_class();
		if ( null !== self::$instance && get_class( self::$instance ) === $called_class ) {
			return self::$instance;
		}

		// @phpstan-ignore-next-line
		self::$instance = new static();
		self::$instance->add_hooks();
		return self::$instance;
	}

	/**
	 * Subscribe hooks.
	 *
	 * @since 1.0.4
	 *
	 * @return void
	 */
	abstract protected function add_hooks();
}
