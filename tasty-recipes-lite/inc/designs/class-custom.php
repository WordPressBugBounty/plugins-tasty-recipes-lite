<?php
/**
 * Manages custom template.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Designs;

use Tasty\Framework\Traits\Singleton;

/**
 * Manages Bold template.
 */
class Custom extends Abstract_Template {
	use Singleton;

	/**
	 * Get current template plugin file.
	 *
	 * @return string
	 */
	protected function get_template_plugin() {
		return get_stylesheet_directory();
	}

	/**
	 * Get templates base path with trailing slash.
	 *
	 * @param bool $hard_variation If true, use the hard variation.
	 *
	 * @return string
	 */
	protected function get_base_templates_path( $hard_variation = false ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return trailingslashit( $this->get_template_plugin() );
	}

	/**
	 * Get current template folder.
	 *
	 * @return string
	 */
	protected function get_template_folder() {
		return '';
	}
}
