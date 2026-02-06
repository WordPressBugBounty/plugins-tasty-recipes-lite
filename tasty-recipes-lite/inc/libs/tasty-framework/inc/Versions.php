<?php
/**
 * Versioning class.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework;

/**
 * Versioning class.
 */
class Versions {

	/**
	 * Current instance.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Available versions.
	 *
	 * @var array
	 */
	private $versions = array();

	/**
	 * Get one instance of current class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register framework version.
	 *
	 * @param string   $version  Framework Version.
	 * @param callable $callback Version callback.
	 *
	 * @return void
	 */
	public function register( $version, $callback ) {
		if ( isset( $this->versions[ $version ] ) ) {
			return;
		}

		$this->versions[ $version ] = $callback;
	}

	/**
	 * Get all available versions.
	 *
	 * @return array
	 */
	public function get_versions() {
		return $this->versions;
	}

	/**
	 * Get latest version.
	 *
	 * @return string
	 */
	public function latest_version() {
		if ( empty( $this->versions ) ) {
			return '';
		}
		$versions = array_keys( $this->versions );
		uasort( $versions, 'version_compare' );
		return end( $versions );
	}

	/**
	 * Get latest version callback.
	 *
	 * @return string
	 */
	public function latest_version_callback() {
		$latest_version = $this->latest_version();
		if ( empty( $latest_version ) || ! isset( $this->versions[ $latest_version ] ) ) {
			return '__return_null';
		}
		return $this->versions[ $latest_version ];
	}

	/**
	 * Call latest version callback.
	 *
	 * @return void
	 */
	public static function initialize_latest_version() {
		$self = self::instance();
		call_user_func( $self->latest_version_callback() );
	}
}
