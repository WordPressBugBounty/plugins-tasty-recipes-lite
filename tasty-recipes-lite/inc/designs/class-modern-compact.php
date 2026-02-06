<?php
/**
 * Manages Modern Compact template.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Designs;

use Tasty\Framework\Traits\Singleton;

/**
 * Manages Modern Compact template.
 */
class Modern_Compact extends Abstract_Template {
	use Singleton;

	/**
	 * Sets the variables.
	 *
	 * @return void
	 */
	public function set_vars() {
		parent::set_vars();
		$this->is_pro = true;
	}
}
