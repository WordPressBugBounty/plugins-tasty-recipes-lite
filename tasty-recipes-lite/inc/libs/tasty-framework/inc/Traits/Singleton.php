<?php
/**
 * Singleton pattern trait.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Traits;

/**
 * Singleton pattern trait.
 *
 * To load one and only one instance per request.
 */
trait Singleton {

	/**
	 * Current instance.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Return current instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Stop cloning.
	 *
	 * @return void
	 */
	final public function __clone() {
		_doing_it_wrong( __METHOD__, esc_attr__( 'Don\'t try again!', 'tasty-recipes-lite' ), '1.0.0' );
	}

	/**
	 * Stop serialization.
	 *
	 * @return void
	 */
	final public function __wakeup() {
		_doing_it_wrong( __METHOD__, esc_attr__( 'Don\'t try again!', 'tasty-recipes-lite' ), '1.0.0' );
	}

	/**
	 * Stop getting direct instance.
	 */
	final private function __construct() {}
}
