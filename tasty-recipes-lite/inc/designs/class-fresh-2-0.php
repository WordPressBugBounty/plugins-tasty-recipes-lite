<?php
/**
 * Manages Fresh 2.0 template.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Designs;

use Tasty\Framework\Traits\Singleton;

/**
 * Manages Fresh 2.0 template.
 */
class Fresh_2_0 extends Abstract_Template {
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
		$this->template_name = 'Fresh 2.0';
		$this->id            = 'fresh-2-0';
		$this->is_pro        = true;
	}
}
