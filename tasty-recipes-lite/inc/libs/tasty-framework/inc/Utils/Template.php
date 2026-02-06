<?php
/**
 * Template engine class.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Utils;

use Tasty\Framework\Traits\Singleton;
use WP_Filesystem_Direct;

/**
 * Template engine class.
 */
class Template {

	use Singleton;

	/**
	 * Main Templates/Views directory path.
	 *
	 * @var string
	 */
	private $template_dir;

	/**
	 * Filesystem instance.
	 *
	 * @var WP_Filesystem_Direct
	 */
	private $filesystem;

	/**
	 * Render constructor.
	 *
	 * @param string               $templates_dir Views directory path.
	 * @param WP_Filesystem_Direct $filesystem    Filesystem instance.
	 *
	 * @return self
	 */
	public function init( string $templates_dir, $filesystem ) {
		$this->template_dir = $templates_dir;
		$this->filesystem   = $filesystem;

		return $this;
	}

	/**
	 * Render template to get its contents based on the passed data.
	 *
	 * @param string $template    Template name to be rendered.
	 * @param array  $data        Array of data to be passed to template.
	 * @param bool   $should_echo True to echo the contents of the template file, default is false.
	 *
	 * @return string|void Contents of this template to be echoed wherever you want if $should_echo is false,
	 *                     otherwise echo the contents without return.
	 */
	public function render( string $template, array $data = array(), bool $should_echo = false ) {
		$template_parts     = explode( '/', $template );
		$template_parts     = array_map( 'sanitize_file_name', $template_parts );
		$template           = implode( '/', $template_parts );
		$template_full_path = $this->template_dir . DIRECTORY_SEPARATOR . $template . '.php';

		if ( ! $this->filesystem->is_readable( $template_full_path ) ) {
			return '';
		}

		if ( $should_echo ) {
			// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
			include $template_full_path;
			return;
		}

		ob_start();
		// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		include $template_full_path;
		$output = trim( ob_get_clean() );

		return $output;
	}
}
