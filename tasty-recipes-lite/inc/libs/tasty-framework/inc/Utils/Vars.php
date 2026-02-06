<?php
/**
 * Utility class deals with request variables and sanitizing them.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Utils;

/**
 * Utility class deals with request variables and sanitizing them.
 */
class Vars {

	/**
	 * Check if a POST value exists and sanitize it.
	 *
	 * @param string $key           The POST key to check.
	 * @param string $sanitize      The type of sanitization to apply.
	 * @param mixed  $default_value The default value to return if the key is not set.
	 *
	 * @return mixed
	 */
	public static function post_param( $key, $sanitize = 'sanitize_text_field', $default_value = '' ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing
		return isset( $_POST[ $key ] ) ? self::sanitize_item( wp_unslash( $_POST[ $key ] ), $sanitize ) : $default_value;
	}

	/**
	 * Check if a GET value exists and sanitize it.
	 *
	 * @param string $key           The GET key to check.
	 * @param string $sanitize      The type of sanitization to apply.
	 * @param mixed  $default_value The default value to return if the key is not set.
	 *
	 * @return mixed
	 */
	public static function get_param( $key, $sanitize = 'sanitize_text_field', $default_value = '' ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Recommended
		return isset( $_GET[ $key ] ) ? self::sanitize_item( wp_unslash( $_GET[ $key ] ), $sanitize ) : $default_value;
	}

	/**
	 * Check if a SERVER value exists and sanitize it.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key           The SERVER key to check.
	 * @param string $sanitize      The type of sanitization to apply.
	 * @param mixed  $default_value The default value to return if the key is not set.
	 *
	 * @return mixed
	 */
	public static function server_param( $key, $sanitize = 'sanitize_text_field', $default_value = '' ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return isset( $_SERVER[ $key ] ) ? self::sanitize_item( wp_unslash( $_SERVER[ $key ] ), $sanitize ) : $default_value;
	}

	/**
	 * Check if a REQUEST value exists and sanitize it.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key           The SERVER key to check.
	 * @param string $sanitize      The type of sanitization to apply.
	 * @param mixed  $default_value The default value to return if the key is not set.
	 *
	 * @return mixed
	 */
	public static function request_param( $key, $sanitize = 'sanitize_text_field', $default_value = '' ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
		return isset( $_REQUEST[ $key ] ) ? self::sanitize_item( wp_unslash( $_REQUEST[ $key ] ), $sanitize ) : $default_value;
	}

	/**
	 * Sanitize item value.
	 *
	 * @param mixed  $item     Item to be sanitized.
	 * @param string $sanitize Sanitize callback function.
	 *
	 * @return mixed
	 */
	public static function sanitize_item( $item, $sanitize = 'sanitize_text_field' ) {
		if ( empty( $item ) || empty( $sanitize ) || ! function_exists( $sanitize ) ) {
			return $item;
		}

		if ( is_array( $item ) ) {
			foreach ( $item as $k => $v ) {
				$item[ $k ] = self::sanitize_item( $v, $sanitize );
			}
		} else {
			$item = call_user_func( $sanitize, $item );
		}

		return $item;
	}
}
