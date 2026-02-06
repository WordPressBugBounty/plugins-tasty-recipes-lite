<?php
/**
 * Manages Snap template.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Designs;

use Tasty\Framework\Traits\Singleton;

/**
 * Manages Snap template class.
 */
class Snap extends Abstract_Template {
	use Singleton;

	/**
	 * Sets the variables.
	 *
	 * @return void
	 */
	public function set_vars() {
		parent::set_vars();
		$this->customized['image_size']   = 'medium_large';
		$this->customized['rating_color'] = '';
		$this->customized['radius']       = '3px';
		$this->customized['design_opts']  = array( 'primary', 'secondary', 'icon' );
	}
}
