<?php
/**
 * Manages Default template.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Designs;

use Tasty\Framework\Traits\Singleton;

/**
 * Default template class.
 */
class Default_Template extends Abstract_Template {
	use Singleton;

	/**
	 * Sets the variables.
	 *
	 * @return void
	 */
	public function set_vars() {
		$this->id            = '';
		$this->template_name = 'Default';
	}

	/**
	 * Get current template folder.
	 *
	 * @return string
	 */
	protected function get_template_folder() {
		return '';
	}

	/**
	 * Get current template path.
	 *
	 * @param false|int $variation Template variation.
	 *
	 * @return string
	 */
	public function get_template_path( $variation = false ) {
		return $this->get_base_templates_path( $variation ) . 'templates/recipe/tasty-recipes.php';
	}

	/**
	 * Get current style path.
	 *
	 * @param false|int $variation Template variation.
	 *
	 * @return string
	 */
	public function get_style_path( $variation = false ) {
		unset( $variation );
		return '';
	}
}
