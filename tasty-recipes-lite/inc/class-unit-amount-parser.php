<?php
/**
 * Parses units and amounts in ingredient strings.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

/**
 * Parses units and amounts in ingredient strings.
 */
class Unit_Amount_Parser {

	/**
	 * Annotates a string with its units and amounts.
	 *
	 * @deprecated 1.0
	 *
	 * @param string $ingr Existing ingredient string.
	 *
	 * @return string
	 */
	public static function annotate_string_with_spans( $ingr ) {
		_deprecated_function( __METHOD__, '1.0' );
		return $ingr;
	}

	/**
	 * Whether or not a string has a non-numeric amount.
	 *
	 * @deprecated 1.0
	 *
	 * @return bool
	 */
	public static function string_has_non_numeric_amount() {
		_deprecated_function( __METHOD__, '1.0' );
		return false;
	}
}
