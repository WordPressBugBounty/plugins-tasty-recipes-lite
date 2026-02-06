<?php
/**
 * JSMessageBox Abstract class, to handle showing success, info, failure, ...etc. messages in admin area.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Admin;

use Tasty\Framework\Abstracts\MessageBox;
use Tasty\Framework\Traits\Singleton;

/**
 * JSMessageBox Abstract class, to handle showing success, info, failure, ...etc. messages in admin area.
 */
class JSMessageBox extends MessageBox {

	use Singleton;

	/**
	 * MessageBox Unique Identifier, used in transient name.
	 *
	 * @return string
	 */
	protected function identifier() {
		return 'js';
	}
}
