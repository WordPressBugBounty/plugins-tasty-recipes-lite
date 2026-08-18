<?php
/**
 * Registry of WordPress option names used by Tasty Recipes Lite.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

/**
 * WordPress option name constants.
 *
 * @since 1.2.7
 */
final class Options {

	/**
	 * Customization settings option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const CUSTOMIZATION = 'tasty_recipes_customization';

	/**
	 * Default author link option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const DEFAULT_AUTHOR_LINK = 'tasty_recipes_default_author_link';

	/**
	 * Instacart enable option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const INSTACART = 'tasty_recipes_instacart';

	/**
	 * Instagram handle option.
	 *
	 * @since 1.2.7
	 * @deprecated 1.0
	 *
	 * @var string
	 */
	public const INSTAGRAM_HANDLE = '';

	/**
	 * Instagram hashtag option.
	 *
	 * @since 1.2.7
	 * @deprecated 1.0
	 *
	 * @var string
	 */
	public const INSTAGRAM_HASHTAG = '';

	/**
	 * License key option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const LICENSE_KEY = 'tasty_recipes_license_key';

	/**
	 * ShareASale affiliate ID option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const SHAREASALE = 'tasty_recipes_shareasale';

	/**
	 * Enable taxonomy links option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const ENABLE_TAXONOMY_LINKS = 'tasty_recipes_enable_taxonomy_links';

	/**
	 * Stored plugin version option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const PLUGIN_VERSION = 'tasty_recipes_plugin_version';

	/**
	 * Powered-by link option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const POWEREDBY = 'tasty_recipes_poweredby';

	/**
	 * Template option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const TEMPLATE = 'tasty_recipes_template';

	/**
	 * Quick links option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const QUICK_LINKS = 'tasty_recipes_quick_links';

	/**
	 * Quick links style option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const QUICK_LINKS_STYLE = 'tasty_recipes_quick_links_style';

	/**
	 * Card buttons option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const CARD_BUTTONS = 'tasty_recipes_card_buttons';

	/**
	 * Unit conversion option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const UNIT_CONVERSION = 'tasty_recipes_unit_conversion';

	/**
	 * Automatic unit conversion option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const AUTOMATIC_UNIT_CONVERSION = 'tasty_recipes_automatic_unit_conversion';

	/**
	 * Ingredient checkboxes option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const INGREDIENT_CHECKBOXES = 'tasty_recipes_ingredient_checkboxes';

	/**
	 * Cook mode option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const COOK_MODE = 'tasty_recipes_cook_mode';

	/**
	 * Disable scaling option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const DISABLE_SCALING = 'tasty_recipes_disable_scaling';

	/**
	 * Copy to clipboard option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const COPY_TO_CLIPBOARD = 'tasty_recipes_copy_to_clipboard';

	/**
	 * Template variation option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const TEMPLATE_VARIATION = 'tasty_recipes_template_variation';

	/**
	 * AI scraper prevention option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const AI_SCRAPER_PREVENTION = 'tasty_recipes_ai_scraper_prevention';

	/**
	 * Improved keys notice dismissal option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const IMPROVED_KEYS_NOTICE_DISMISSED = 'tasty_recipes_improved_keys_notice_dismissed';

	/**
	 * Onboarding welcome redirect option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const WELCOME_REDIRECT = 'tasty_recipes_welcome_redirect';

	/**
	 * Onboarding skipped option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const ONBOARDING_SKIPPED = 'tasty_recipes_onboarding_skipped';

	/**
	 * Onboarding usage data consent option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const ONBOARDING_USAGE_DATA = 'tasty_recipes_onboarding_usage_data';

	/**
	 * Database version option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const DB_VERSION = 'tasty_recipes_db_version';

	/**
	 * Ignored conversion types option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const IGNORE_CONVERT_TYPES = 'tasty_recipes_ignore_convert_types';

	/**
	 * Default taxonomy terms inserted flag option.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	public const DEFAULT_TERMS_INSERTED = 'tasty_recipes_default_terms_inserted_v2';

	/**
	 * Option names keyed for settings JavaScript consumption.
	 *
	 * @since 1.2.7
	 *
	 * @return array<string, string>
	 */
	public static function as_js_map(): array {
		return self::map_class_constants_to_js( self::class );
	}

	/**
	 * Map a class's non-empty string constants to a camelCase JS map.
	 *
	 * Shared helper so Pro (and other registries) can reuse the same conversion.
	 *
	 * @since 1.2.7
	 *
	 * @param string $class_name Fully-qualified class name.
	 *
	 * @return array<string, string>
	 */
	public static function map_class_constants_to_js( string $class_name ): array {
		$map = array();

		foreach ( ( new \ReflectionClass( $class_name ) )->getConstants() as $name => $value ) {
			if ( ! is_string( $value ) || '' === $value ) {
				continue;
			}

			$map[ self::const_name_to_camel_case( $name ) ] = $value;
		}

		return $map;
	}

	/**
	 * Convert a SCREAMING_SNAKE constant name to camelCase.
	 *
	 * @since 1.2.7
	 *
	 * @param string $name Constant name.
	 *
	 * @return string
	 */
	private static function const_name_to_camel_case( string $name ): string {
		$parts = explode( '_', strtolower( $name ) );
		$key   = array_shift( $parts );

		foreach ( $parts as $part ) {
			$key .= ucfirst( $part );
		}

		return $key;
	}
}
