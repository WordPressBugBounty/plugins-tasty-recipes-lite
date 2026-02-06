<?php
/**
 * Old updater class file.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Admin\License;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Used to allow plugins to use their own update API.
 * This is no longer used, but may still be called.
 * It can removed after Updater() is removed and released in all plugins.
 */
class Updater {

	/**
	 * Old EDD updater constructor.
	 *
	 * @deprecated 1.1
	 *
	 * @return void
	 */
	public function __construct() {
		// Deprecated.
	}
}
