<?php
/**
 * Manages Simple template.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Designs;

use Tasty\Framework\Traits\Singleton;

/**
 * Manages Simple template.
 */
class Simple extends Abstract_Template {
	use Singleton;

	/**
	 * Sets the variables.
	 *
	 * @return void
	 */
	public function set_vars() {
		parent::set_vars();
		$this->customized['container']   = 'primary-color.background-color secondary-color.border-color';
		$this->customized['design_opts'] = array( 'primary', 'secondary' );
	}
}
