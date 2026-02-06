<?php
/**
 * Integrates Tasty Recipes with Yoast SEO's Open Graph.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Integrations;

use Yoast\WP\SEO\Config\Schema_IDs;
use Yoast\WP\SEO\Context\Meta_Tags_Context;
use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;

/**
 * Integrates Tasty Recipes with Yoast SEO's Open Graph.
 */
class Recipe_Graph_Piece_Yoast extends Abstract_Schema_Piece {

	/**
	 * Recipe associated with this instance.
	 *
	 * @var \Tasty_Recipes\Objects\Recipe
	 */
	private $recipe;

	/**
	 * Whether or not an article is present on this page too.
	 *
	 * @var bool
	 */
	private $using_article;

	/**
	 * Recipe_Graph_Piece constructor.
	 *
	 * @param Meta_Tags_Context $context A value object with context variables.
	 */
	public function __construct( Meta_Tags_Context $context ) {
		$this->context = $context;

		$this->using_article = false;
		add_filter( 'wpseo_schema_article', array( $this, 'filter_wpseo_schema_article' ) );
	}

	/**
	 * Keeps track of whether the Yoast SEO article schema is used.
	 *
	 * @param array $data Existing article schema data.
	 *
	 * @return array
	 */
	public function filter_wpseo_schema_article( $data ) {
		$this->using_article = true;
		if ( $this->is_needed() ) {
			// Use the recipe as the main entity of the page.
			unset( $data['mainEntityOfPage'] );
		}
		return $data;
	}

	/**
	 * Determines whether or not a piece should be added to the graph.
	 *
	 * @return bool
	 */
	public function is_needed() {
		if ( ! is_singular() ) {
			return false;
		}
		$recipes = \Tasty_Recipes::get_recipes_for_post(
			$this->context->id,
			array(
				'disable-json-ld' => false,
			)
		);
		if ( empty( $recipes ) ) {
			return false;
		}
		$this->recipe = array_shift( $recipes );
		return true;
	}

	/**
	 * Returns Recipe Schema data.
	 *
	 * @return array|bool Recipe data on success, false on failure.
	 */
	public function generate() {
		$schema                     = \Tasty_Recipes\Distribution_Metadata::get_enriched_google_schema_for_recipe( $this->recipe, get_post( $this->context->id ) );
		$schema['@id']              = $this->context->canonical . '#recipe';
		$schema['isPartOf']         = array(
			'@id' => $this->using_article ? $this->context->canonical . Schema_IDs::ARTICLE_HASH : $this->context->main_schema_id,
		);
		$schema['mainEntityOfPage'] = $this->context->main_schema_id;
		return $schema;
	}
}
