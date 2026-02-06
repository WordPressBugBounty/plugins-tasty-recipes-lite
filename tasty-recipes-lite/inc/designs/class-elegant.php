<?php
/**
 * Manages Elegant template.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Designs;

use Tasty\Framework\Traits\Singleton;

/**
 * Manages Elegant template.
 */
class Elegant extends Abstract_Template {
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
