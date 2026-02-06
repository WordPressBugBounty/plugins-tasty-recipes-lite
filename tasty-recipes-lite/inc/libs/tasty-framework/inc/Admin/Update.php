<?php
/**
 * Update plugin handler.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Admin;

use Tasty\Framework\Traits\Singleton;

/**
 * Update plugin handler.
 *
 * Handles first time installations or updating hooks.
 */
class Update {

	use Singleton;

	/**
	 * Framework option name.
	 *
	 * @var string
	 */
	private $version_option_name = 'tasty_framework_version';

	/**
	 * Initialize class and add hooks.
	 *
	 * @return $this
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'maybe_fire_update_routine' ) );

		return $this;
	}

	/**
	 * Maybe fire update routine.
	 *
	 * @return void
	 */
	public function maybe_fire_update_routine() {
		$db_version = $this->get_db_version();

		if ( TASTY_FRAMEWORK_VERSION === $db_version ) {
			return;
		}

		if ( empty( $db_version ) ) {
			do_action( 'tasty_framework_first_install', TASTY_FRAMEWORK_VERSION );

			if ( false === get_option( 'tasty_first_activation' ) ) {
				update_option( 'tasty_first_activation', time(), false );
			}
		}

		if ( version_compare( TASTY_FRAMEWORK_VERSION, $db_version, '>' ) ) {
			do_action( 'tasty_framework_update', TASTY_FRAMEWORK_VERSION, $db_version );
		} else {
			do_action( 'tasty_framework_downgrade', TASTY_FRAMEWORK_VERSION, $db_version );
		}

		$this->set_db_version( TASTY_FRAMEWORK_VERSION );
	}

	/**
	 * Get current framework version from DB.
	 *
	 * @return string
	 */
	private function get_db_version() {
		return get_option( $this->version_option_name, '' );
	}

	/**
	 * Set current framework version into DB.
	 *
	 * @param string $version Version number.
	 *
	 * @return void
	 */
	private function set_db_version( $version ) {
		update_option( $this->version_option_name, $version, true );
	}
}
