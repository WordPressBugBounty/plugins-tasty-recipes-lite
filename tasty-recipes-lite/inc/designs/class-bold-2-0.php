<?php
/**
 * Manages Bold 2.0 template.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Designs;

use Tasty\Framework\Traits\Singleton;

/**
 * Manages Bold 2.0 template.
 */
class Bold_2_0 extends Abstract_Template {
	use Singleton;

	/**
	 * Variation_count.
	 *
	 * @var int
	 */
	protected $variation_count = 3;

	/**
	 * What mechanisms this template supports.
	 * 
	 * @var array
	 */
	protected $supports = array( 'customization', 'variations' );

	/**
	 * Sets the variables.
	 *
	 * @return void
	 */
	public function set_vars() {
		$this->template_name = 'Bold 2.0';
		$this->id            = 'bold-2-0';
		$this->is_pro        = true;
	}
}
