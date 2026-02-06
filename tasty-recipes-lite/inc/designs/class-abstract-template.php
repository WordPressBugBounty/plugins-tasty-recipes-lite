<?php
/**
 * Abstract template class.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Designs;

/**
 * Design template abstract class.
 *
 * @method string get_id() Get template unique ID.
 * @method string get_template_name() Get template name.
 * @method string get_image_size() Get the image size for the template.
 * @method string get_customized(string $key = '') Get customized options.
 */
abstract class Abstract_Template {

	/**
	 * Template ID.
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Template name.
	 *
	 * @var string
	 */
	protected $template_name = '';

	/**
	 * If this is a pro template.
	 *
	 * @var bool
	 */
	protected $is_pro = false;

	/**
	 * Customize options.
	 *
	 * @var array
	 */
	protected $customized = [
		'container'    => '',
		'admin_button' => 'button-color.border-color button-color.background button-text-color.color',
		'image_size'   => 'thumbnail',
		'card_buttons' => [
			'first'  => 'print',
			'second' => '',
		],
		'rating_color' => 'body-color.color',
		'radius'       => '2px',
		'design_opts'  => array(),
	];

	/**
	 * Sets the variables.
	 *
	 * @return void
	 */
	public function set_vars() {
		$this->template_name = str_replace( '_', ' ', substr( strrchr( get_class( $this ), '\\' ), 1 ) );
		$this->id            = sanitize_title_with_dashes( $this->template_name );
	}

	/**
	 * Magic method to intercept calls to get_ methods.
	 *
	 * @param string $name      Method name.
	 * @param array  $arguments Method arguments.
	 *
	 * @return string|null
	 */
	public function __call( $name, $arguments ) {
		// Intercept only methods starting with 'get_'.
		if ( strncmp( $name, 'get_', 4 ) !== 0 || ! method_exists( $this, $name ) ) {
			return null;
		}

		// Check if method is protected.
		$reflection = new \ReflectionMethod( $this, $name );
		if ( $reflection->isProtected() ) {
			$this->set_vars();
			return call_user_func_array( [ $this, $name ], $arguments );
		}

		return null;
	}

	/**
	 * If current template is pro or lite.
	 *
	 * @return bool
	 */
	public function is_pro() {
		$this->set_vars();
		return $this->is_pro;
	}

	/**
	 * Get template unique ID.
	 *
	 * @return string
	 */
	protected function get_id() {
		return $this->id;
	}

	/**
	 * Get template name.
	 *
	 * @return string
	 */
	protected function get_template_name() {
		return $this->template_name;
	}

	/**
	 * Get the image size for the template.
	 *
	 * @return string
	 */
	protected function get_image_size() {
		return $this->get_customized( 'image_size' );
	}

	/**
	 * Get a list of extra options that show in the customization settings.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	protected function get_template_design_opts() {
		return (array) $this->get_customized( 'design_opts' );
	}

	/**
	 * Get customize options.
	 *
	 * @param string $key Setting to get.
	 *
	 * @return array|string
	 */
	protected function get_customized( $key = '' ) {
		return $this->customized[ $key ];
	}

	/**
	 * Get current template plugin file.
	 *
	 * @return string
	 */
	protected function get_template_plugin() {
		return TASTY_RECIPES_LITE_FILE;
	}

	/**
	 * Get templates base path with trailing slash.
	 *
	 * @return string
	 */
	protected function get_base_templates_path() {
		return trailingslashit(
			dirname( $this->get_template_plugin() ) .
			'/' . $this->get_template_folder()
		);
	}

	/**
	 * Get current template folder.
	 *
	 * @return string
	 */
	protected function get_template_folder() {
		return 'templates/designs/' . $this->id;
	}

	/**
	 * Get current template path.
	 *
	 * @return string
	 */
	public function get_template_path() {
		if ( $this->is_pro() ) {
			return dirname( $this->get_template_plugin() ) . '/templates/admin/locked-template.php';
		}

		$template = $this->get_file_path( 'tasty-recipes.php' );
		if ( file_exists( $template ) ) {
			return $template;
		}

		// Fallback to default template.
		return dirname( $this->get_template_plugin() ) . '/templates/recipe/tasty-recipes.php';
	}

	/**
	 * Get current template style's path.
	 *
	 * @return string
	 */
	public function get_style_path() {
		return $this->get_file_path( 'tasty-recipes.css' );
	}

	/**
	 * Get current template style or template file path.
	 *
	 * @param string $file File name.
	 *
	 * @return string
	 */
	protected function get_file_path( $file ) {
		if ( empty( $this->id ) ) {
			return '';
		}

		return $this->get_base_templates_path() . $file;
	}

	/**
	 * Get current template design url.
	 *
	 * @return string
	 */
	public function get_template_url() {
		if ( empty( $this->id ) ) {
			return '';
		}
		return trailingslashit(
			plugins_url( $this->get_template_folder(), $this->get_template_plugin() )
		);
	}
}
