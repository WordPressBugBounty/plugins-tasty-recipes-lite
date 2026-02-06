<?php
/**
 * Manages Fresh template.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Designs;

use Tasty\Framework\Traits\Singleton;

/**
 * Manages Fresh template.
 */
class Fresh extends Abstract_Template {
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
