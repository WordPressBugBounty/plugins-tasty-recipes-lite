<?php
/**
 * Generates distribution metadata.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

use Tasty_Recipes;
use Tasty_Recipes\Content_Model;
use Tasty_Recipes\Utils;
use Tasty_Recipes\Objects\Recipe;
use Tasty_Recipes\Integrations;

/**
 * Generates distribution metadata.
 */
class Distribution_Metadata {

	/**
	 * Regex used to match list item component names.
	 *
	 * @var string
	 */
	const COMPONENT_NAME_REGEX = '#^\s*(?<li>(<li[^>]*>))?[\s\n]*(?<p>(<p[^>]*>))?[\s\n]*(<strong>|<b>)(?<name>[^>]+)?(</strong>|</b>):*(?<remaining>.+)#m';

	/**
	 * Regex used to match list items.
	 *
	 * @var string
	 */
	const LI_MATCH_REGEX = '#<li[^\>]*>(.*)<\/li>#Us';

	/**
	 * Regex used to match an unstructured list.
	 *
	 * @var string
	 */
	const UNSTRUCTURED_LIST_MATCH_REGEX = '#^(<p[^>]*>)?[\d]+\.#m';

	/**
	 * Image sizes used in JSON+LD.
	 *
	 * @var array
	 */
	private static $json_ld_image_sizes = array(
		'1x1'  => array( 225, 225 ),
		'4x3'  => array( 260, 195 ),
		'16x9' => array( 320, 180 ),
	);

	/**
	 * Looks wp_head hooks so markup is added later.
	 *
	 * @return void
	 */
	public static function action_wp_head() {
		add_action( 'wp_head', array( __CLASS__, 'action_wp_head_noindex' ) );
	}

	/**
	 * Load the Tasty schema if Yoast is not present.
	 *
	 * @since 3.13
	 *
	 * @return void
	 */
	private static function maybe_load_google_schema() {
		if ( ! is_singular() ) {
			return;
		}

		if ( ! self::use_yoast_schema() ) {
			/**
			 * Bailout without printing the json markup if we decided to use Yoast SEO using the filter.
			 */
			self::action_wp_head_google_schema();
		}
	}

	/**
	 * Renders JSON+LD markup on singular views with recipes.
	 *
	 * @return void
	 */
	public static function action_wp_head_google_schema() {

		$recipes = Tasty_Recipes::get_recipes_for_post(
			0,
			array(
				'disable-json-ld' => false,
			)
		);
		if ( empty( $recipes ) ) {
			return;
		}

		$schemas = array();
		foreach ( $recipes as $recipe ) {
			$schemas[] = self::get_enriched_google_schema_for_recipe( $recipe, get_queried_object() );
		}

		foreach ( $schemas as $schema ) {
			echo wp_get_inline_script_tag(
				wp_json_encode( $schema, JSON_PRETTY_PRINT ),
				array(
					'type' => 'application/ld+json',
				)
			);
		}
	}

	/**
	 * Adds noindex to print template rendering.
	 *
	 * @return void
	 */
	public static function action_wp_head_noindex() {
		self::maybe_load_google_schema();
		if ( ! tasty_recipes_is_print() ) {
			return;
		}

		Integrations\Hubbub::init(); // Only load this integration inside print page.

		if ( ! did_action( 'wpseo_head' ) ) {
			echo '<meta name="robots" content="noindex,follow">' . PHP_EOL;
		}
	}

	/**
	 * Filter Yoast <meta name="robots"> output on the print template to noindex.
	 *
	 * @param string $robots Existing robots value.
	 *
	 * @return string
	 */
	public static function action_wpseo_robots( $robots ) {
		if ( tasty_recipes_is_print() ) {
			$robots = 'noindex,follow';
		}
		return $robots;
	}

	/**
	 * Check if older version of Yoast is active.
	 *
	 * @return bool
	 */
	private static function is_old_yoast() {
		return interface_exists( '\WPSEO_Graph_Piece' );
	}

	/**
	 * Check if newer version of Yoast is active.
	 *
	 * @return bool
	 */
	private static function is_new_yoast() {
		return class_exists( '\Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece' );
	}

	/**
	 * Use Yoast Schema or not.
	 *
	 * @return bool
	 */
	private static function use_yoast_schema() {
		return (bool) apply_filters( 'tasty_recipes_use_yoast_schema', self::is_new_yoast() || self::is_old_yoast() );
	}

	/**
	 * Incorporate Tasty Recipes into Yoast SEO when present.
	 *
	 * @param array  $pieces  Existing Yoast SEO graph pieces.
	 * @param object $context Yoast SEO context instance.
	 *
	 * @return array
	 */
	public static function filter_wpseo_schema_graph_pieces( $pieces, $context ) {
		if ( ! self::use_yoast_schema() ) {
			return $pieces;
		}

		if ( self::is_new_yoast() ) {
			$pieces[] = new \Tasty_Recipes\Integrations\Recipe_Graph_Piece_Yoast( $context );
			return $pieces;
		}

		if ( self::is_old_yoast() ) {
			// @phpstan-ignore-next-line
			$pieces[] = new \Tasty_Recipes\Integrations\Recipe_Graph_Piece( $context );
			return $pieces;
		}

		return $pieces;
	}

	/**
	 * Gets the enriched form of a Google Schema for a recipe.
	 *
	 * @param Recipe   $recipe Recipe object.
	 * @param \WP_Post $post   Post object.
	 *
	 * @return array
	 */
	public static function get_enriched_google_schema_for_recipe( Recipe $recipe, $post ) {
		$schema = self::get_google_schema_for_recipe( $recipe );
		$schema = self::enrich_google_schema_with_post_permalink( $schema, $post );
		$schema = self::enrich_google_schema_with_post_author( $schema, $recipe, $post );
		$schema = self::enrich_google_schema_with_post_video( $schema, $recipe, $post );
		$schema = self::enrich_google_schema_with_comments( $schema, $recipe, self::get_wp_query_comments() );

		/**
		 * Permit modification of the Google Schema data generated for a recipe.
		 *
		 * @since 1.0
		 */
		$schema = apply_filters( 'tasty_recipes_enriched_google_schema', $schema, compact( 'recipe', 'post' ) );

		if ( '0000-00-00 00:00:00' !== $post->post_date ) {
			$schema['datePublished'] = gmdate( 'Y-m-d', strtotime( $post->post_date ) );
		}
		return $schema;
	}

	/**
	 * Check if a string is empty using regex.
	 * \xc2\xa0 is the encoded version of \u00a0 and &nbsp;.
	 *
	 * @param string $content Content to test.
	 *
	 * @return bool
	 */
	public static function is_empty( $content ) {
		return 1 === preg_match( '#^\s*(?:&nbsp;|\xc2\xa0)*\s*$#i', $content );
	}

	/**
	 * Generates the Google Schema for a recipe object.
	 *
	 * @param Recipe $recipe Recipe object.
	 *
	 * @return array
	 */
	public static function get_google_schema_for_recipe( Recipe $recipe ) {
		$recipe_json = $recipe->to_json();
		$schema      = array(
			'@context'    => 'https://schema.org/',
			'@type'       => 'Recipe',
			'name'        => $recipe_json['title'],
			'description' => wp_strip_all_tags( $recipe_json['description'] ),
		);

		if ( ! empty( $recipe_json['author_name'] ) ) {
			$author = array(
				'@type' => 'Person',
				'name'  => $recipe_json['author_name'],
			);
			$url    = get_option( Tasty_Recipes::DEFAULT_AUTHOR_LINK_OPTION, '' );
			if ( $url ) {
				$author['url'] = $url;
			}
			$schema['author'] = $author;
		}

		if ( ! empty( $recipe_json['keywords'] ) ) {
			$schema['keywords'] = $recipe_json['keywords'];
		}

		$image_urls = array();
		$image_id   = $recipe->get_image_id();
		if ( $image_id ) {
			foreach ( self::get_json_ld_image_sizes( $image_id ) as $image_size ) {
				$image_url = $recipe->get_image_size( $image_size );
				if ( $image_url ) {
					$image_urls[] = $image_url['url'];
				}
			}
		}
		$full_size = self::get_recipe_image_size_with_fallback( $recipe, 'full' );
		if ( ! empty( $image_urls ) ) {
			if ( ! empty( $full_size ) ) {
				$image_urls[] = $full_size['url'];
			}
			$schema['image'] = array_values( array_unique( $image_urls ) );
		} elseif ( ! empty( $full_size ) ) {
			$schema['image'] = array(
				'@type'  => 'ImageObject',
				'url'    => $full_size['url'],
				'height' => $full_size['height'],
				'width'  => $full_size['width'],
			);
		}

		$object = get_queried_object();
		if ( $object && is_a( $object, 'WP_Post' ) ) {
			$schema['url'] = get_permalink( $object->ID );
		}

		$schema['recipeIngredient'] = array();
		foreach ( self::parse_recipe_component_list( $recipe_json['ingredients'] ) as $ingredient ) {
			if ( 'heading' === $ingredient['type'] || self::is_empty( $ingredient['value'] ) ) {
				continue;
			}
			$schema['recipeIngredient'][] = $ingredient['value'];
		}

		self::add_instruction_schema( $recipe_json, $schema );
		self::add_cooking_schema( $recipe_json, $schema );
		self::add_rating_schema( $recipe, $schema );
		self::add_nutrition_schema( $recipe, $recipe_json, $schema );
		self::add_video_schema( $recipe, $schema );

		/**
		 * Permit modification of the Google Schema data generated for a recipe.
		 *
		 * @param array  $schema Existing Google Schema data.
		 * @param object $recipe Tasty Recipes recipe object.
		 */
		return apply_filters( 'tasty_recipes_google_schema', $schema, $recipe );
	}

	/**
	 * Add recipe instructions to a Google Schema.
	 *
	 * @since 3.12.3
	 *
	 * @param array $recipe_json Recipe JSON data.
	 * @param array $schema      Existing Google Schema data.
	 *
	 * @return void
	 */
	private static function add_instruction_schema( $recipe_json, &$schema ) {
		$schema['recipeInstructions'] = array();
		$heading_position             = null;
		foreach ( self::parse_recipe_instruction_list( $recipe_json['instructions'] ) as $instruction ) {
			if ( self::is_empty( $instruction['value'] ) ) {
				continue;
			}

			if ( 'heading' === $instruction['type'] ) {
				$heading_position               = count( $schema['recipeInstructions'] );
				$schema['recipeInstructions'][] = array(
					'@type'           => 'HowToSection',
					'name'            => $instruction['value'],
					'itemListElement' => array(),
				);
				continue;
			}
			$item = array(
				'@type' => 'HowToStep',
				'text'  => $instruction['value'],
			);
			if ( isset( $instruction['name'] ) ) {
				$item['name'] = $instruction['name'];
			}
			if ( isset( $instruction['url'] ) ) {
				$item['url'] = $instruction['url'];
			}
			if ( isset( $instruction['image_src'] ) ) {
				if ( isset( $instruction['image_width'] ) && isset( $instruction['image_height'] ) ) {
					$item['image'] = array(
						'@type'  => 'ImageObject',
						'url'    => $instruction['image_src'],
						'height' => $instruction['image_height'],
						'width'  => $instruction['image_width'],
					);
				} else {
					$item['image'] = $instruction['image_src'];
				}
			}
			$object = get_queried_object();
			if ( ! empty( $instruction['video_url'] )
				&& $object && is_a( $object, 'WP_Post' ) ) {
				$video_schema = self::get_video_schema_from_url( $instruction['video_url'], $object );
				if ( $video_schema ) {
					$item['video'] = $video_schema;
				}
			}
			if ( ! is_null( $heading_position ) ) {
				$schema['recipeInstructions'][ $heading_position ]['itemListElement'][] = $item;
			} else {
				$schema['recipeInstructions'][] = $item;
			}
		}
	}

	/**
	 * Add cooking attributes to a Google Schema.
	 *
	 * @since 3.12.3
	 *
	 * @param array $recipe_json Recipe JSON data.
	 * @param array $schema      Existing Google Schema data.
	 *
	 * @return void
	 */
	private static function add_cooking_schema( $recipe_json, &$schema ) {
		foreach ( Recipe::get_cooking_attributes() as $name => $meta ) {
			if ( empty( $meta['property'] ) || empty( $recipe_json[ $name ] ) ) {
				continue;
			}

			$value = $recipe_json[ $name ];
			if ( false !== stripos( $name, '_time' ) ) {
				if ( 'prep_time' === $name
					&& ! empty( $recipe_json['additional_time_value'] ) ) {
					$time  = time();
					$value = self::strtotime( $value, $time ) - $time
							+ self::strtotime( $recipe_json['additional_time_value'], $time ) - $time;
					$value = "{$value} seconds";
				}
				$value = self::get_duration_for_time( $value );
			}
			if ( $value && in_array( $name, array( 'cuisine', 'category', 'method' ), true ) ) {
				$bits  = explode( ',', $value );
				$value = trim( $bits[0] );
			}
			if ( $value && 'diet' === $name ) {
				// Turns 'Low Fat' into 'LowFatDiet'.
				$value = str_replace( ' ', '', $value ) . 'Diet';
			}

			// If '2 cups' or similar, needs to be broken into an array
			// where the number is the first entry.
			if ( 'yield' === $name && ! is_numeric( $value ) ) {
				if ( preg_match( '#[\d]+#', $value, $matches ) ) {
					$value = array(
						$matches[0],
						$value,
					);
				}
			}
			$schema[ $meta['property'] ] = $value;
		}
	}

	/**
	 * Add aggregate rating to a Google Schema.
	 *
	 * @since 3.12.3
	 *
	 * @param Recipe $recipe Recipe object.
	 * @param array  $schema Existing Google Schema data.
	 *
	 * @return void
	 */
	private static function add_rating_schema( $recipe, &$schema ) {
		$total_reviews = $recipe->get_total_reviews();
		if ( $total_reviews ) {
			$schema['aggregateRating'] = array(
				'@type'       => 'AggregateRating',
				'reviewCount' => (string) $total_reviews,
				'ratingValue' => (string) $recipe->get_average_rating(),
			);
		}
	}

	/**
	 * Add nutrition information to a Google Schema.
	 *
	 * @since 3.12.3
	 *
	 * @param Recipe $recipe      Recipe object.
	 * @param array  $recipe_json Recipe JSON data.
	 * @param array  $schema      Existing Google Schema data.
	 *
	 * @return void
	 */
	private static function add_nutrition_schema( $recipe, $recipe_json, &$schema ) {
		$nutrition = array();
		if ( $recipe->get_nutrifox_response() ) {
			foreach ( Recipe::get_nutrition_attributes() as $name => $meta ) {
				$value = $recipe->get_formatted_nutrifox_value( $name );
				if ( false === $value ) {
					continue;
				}
				$nutrition[ $meta['property'] ] = $value;
			}
		}

		/**
		 * Allow third-parties to modify the nutrition schema.
		 *
		 * @since 1.0.3
		 *
		 * @param array   $nutrition Nutrition schema.
		 * @param Recipe  $recipe    Recipe object.
		 * @param array   $recipe_json Recipe JSON data.
		 */
		$nutrition = apply_filters( 'tasty_recipes_nutrition_schema', $nutrition, $recipe, $recipe_json );

		if ( empty( $nutrition ) ) {
			return;
		}

		if ( isset( $nutrition['calories'] )
			&& is_numeric( $nutrition['calories'] ) ) {
			$nutrition['calories'] .= ' calories';
		}

		$nutrition['@type']  = 'nutritionInformation';
		$schema['nutrition'] = $nutrition;
	}

	/**
	 * Add video info to the Google Schema.
	 *
	 * @since 3.12.3
	 *
	 * @param Recipe $recipe Recipe object.
	 * @param array  $schema Existing Google Schema data.
	 *
	 * @return void
	 */
	private static function add_video_schema( $recipe, &$schema ) {
		$video_url   = $recipe->get_video_url();
		$oembed_data = $recipe->get_video_url_response();
		if ( $video_url && $oembed_data ) {
			$schema['video'] = self::get_google_schema_video_from_oembed_data( $oembed_data );
		}
	}

	/**
	 * Gets Google Schema video format from oEmbed structured data.
	 *
	 * @param object $oembed_data oEmbed structured data.
	 *
	 * @return array
	 */
	public static function get_google_schema_video_from_oembed_data( $oembed_data ) {
		$video_schema = array(
			'@context' => 'http://schema.org',
			'@type'    => 'VideoObject',
		);
		if ( isset( $oembed_data->title ) ) {
			$video_schema['name'] = $oembed_data->title;
		}
		if ( isset( $oembed_data->description ) ) {
			$video_schema['description'] = $oembed_data->description;
		}
		if ( ! empty( $oembed_data->duration ) ) {
			$duration_seconds = $oembed_data->duration;
			$duration         = self::get_duration_for_time( "{$duration_seconds} seconds" );

			$video_schema['duration'] = $duration;
		}
		if ( isset( $oembed_data->embed_url ) ) {
			$video_schema['embedUrl'] = $oembed_data->embed_url;
		} elseif ( isset( $oembed_data->embedUrl ) ) {
			$video_schema['embedUrl'] = $oembed_data->embedUrl;
		} elseif ( isset( $oembed_data->html ) ) {
			if ( preg_match( '#src="([^"]+)"#', $oembed_data->html, $matches ) ) {
				$video_schema['embedUrl'] = $matches[1];
			}
		}

		if ( isset( $oembed_data->content_url ) ) {
			$video_schema['contentUrl'] = $oembed_data->content_url;
		} elseif ( isset( $oembed_data->contentUrl ) ) {
			$video_schema['contentUrl'] = $oembed_data->contentUrl;
		} elseif ( isset( $video_schema['embedUrl'] ) ) {
			if ( preg_match(
				'#https?://www\.youtube\.com/embed/([^?]+)#',
				$video_schema['embedUrl'],
				$video_matches
			) ) {
				$video_schema['contentUrl'] = 'https://www.youtube.com/watch?v=' . $video_matches[1];
			} elseif ( preg_match(
				'#https?://player\.vimeo\.com/video/([^?]+)#',
				$video_schema['embedUrl'],
				$video_matches
			) ) {
				$video_schema['contentUrl'] = 'https://vimeo.com/' . $video_matches[1];
			}
		}
		if ( isset( $oembed_data->thumbnail_url ) ) {
			$video_schema['thumbnailUrl'] = array( $oembed_data->thumbnail_url );
		}

		if ( isset( $oembed_data->upload_date ) ) {
			$video_schema['uploadDate'] = gmdate( 'c', strtotime( $oembed_data->upload_date ) );
		} elseif ( isset( $oembed_data->uploadDate ) ) {
			$video_schema['uploadDate'] = gmdate( 'c', strtotime( $oembed_data->uploadDate ) );
		}

		/**
		 * Allow third-parties to modify the video schema array.
		 *
		 * @since 1.1.2
		 *
		 * @param array   $video_schema Video schema.
		 * @param object  $oembed_data oEmbed structured data.
		 */
		return apply_filters( 'tasty_recipes_google_schema_video_from_oembed_data', $video_schema, $oembed_data );
	}

	/**
	 * Enriches a Google Schema with the post permalink as necessary.
	 *
	 * @param array  $schema Existing Google Schema data.
	 * @param object $post   Post object.
	 *
	 * @return array
	 */
	public static function enrich_google_schema_with_post_permalink( $schema, $post ) {
		$permalink = get_permalink( $post->ID );
		if ( ! empty( $schema['recipeInstructions'] ) ) {
			foreach ( $schema['recipeInstructions'] as $i => $instruction ) {
				if ( 'HowToSection' === $instruction['@type'] && ! empty( $instruction['itemListElement'] ) ) {
					foreach ( $instruction['itemListElement'] as $j => $element ) {
						if ( empty( $element['url'] ) ) {
							continue;
						}
						$schema['recipeInstructions'][ $i ]['itemListElement'][ $j ]['url'] = $permalink . $schema['recipeInstructions'][ $i ]['itemListElement'][ $j ]['url'];
					}
				}
				if ( 'HowToStep' === $instruction['@type'] && ! empty( $instruction['url'] ) ) {
					$schema['recipeInstructions'][ $i ]['url'] = $permalink . $schema['recipeInstructions'][ $i ]['url'];
				}
			}
		}
		return $schema;
	}

	/**
	 * Enriches a Google Schema with post author if the author doesn't exist.
	 *
	 * @param array  $schema Existing Google Schema data.
	 * @param Recipe $recipe Recipe object.
	 * @param object $post   Post object.
	 *
	 * @return array
	 */
	public static function enrich_google_schema_with_post_author( $schema, $recipe, $post ) {

		if ( ! isset( $schema['author'] ) ) {
			$user = get_user_by( 'id', $post->post_author );
			if ( $user ) {
				$schema['author'] = array(
					'@type' => 'Person',
					'name'  => $user->display_name,
				);
			}
		}

		if ( isset( $schema['author'] ) ) {
			$shortcodes = Tasty_Recipes::get_recipe_ids_from_content( $post->post_content, array( 'full-result' => true ) );
			$shortcodes = wp_filter_object_list( $shortcodes, array( 'id' => (string) $recipe->get_id() ) );
			if ( ! empty( $shortcodes[0]['author_link'] ) ) {
				$schema['author']['url'] = $shortcodes[0]['author_link'];
			}
		}

		return $schema;
	}

	/**
	 * Enrich a Google Schema with video from post content if video doesn't exist.
	 *
	 * @param array    $schema Existing Google Schema data.
	 * @param object   $recipe Recipe object.
	 * @param \WP_Post $post   Post object.
	 *
	 * @return array
	 */
	public static function enrich_google_schema_with_post_video( $schema, $recipe, $post ) {

		if ( isset( $schema['video'] ) ) {
			return $schema;
		}

		/**
		 * Allow themes to override the post video with a value stored in post meta (or elsewhere).
		 *
		 * @param mixed   $video_url Override video URL.
		 * @param \WP_Post $post      Post object.
		 */
		$video_url = apply_filters( 'tasty_recipes_enrich_google_schema_post_video_url', false, $post );

		if ( ! $video_url ) {
			$video_url = self::get_video_url_from_content( $post->post_content );
		}
		$video_schema = self::get_video_schema_from_url( $video_url, $post );
		if ( $video_schema ) {
			$schema['video'] = $video_schema;
		}

		return $schema;
	}

	/**
	 * Gets the video schema out of a string of content.
	 *
	 * @param string $content Existing content to search.
	 *
	 * @return false|string
	 */
	public static function get_video_url_from_content( $content ) {

		// Transform any AdThrive shortcodes into a special format
		// that only Tasty Recipes will process.
		$content = str_replace( '[adthrive-in-post-video-player ', '[tr-adthrive-in-post-video-player ', $content );

		// Process any AdThrive, YouTube, or Vimeo videos within the post content.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$content = apply_filters( 'the_content', $content );

		if ( false === stripos( $content, '<iframe ' )
			&& false === stripos( $content, '<script ' )
			&& false === stripos( $content, 'adthrive-in-post-video-player' )
			&& false === stripos( $content, 'mv-video-target' ) ) {
			return false;
		}

		$shortcode = 'tr-adthrive-in-post-video-player';
		if ( preg_match_all(
			'#(<iframe[^>]*src=["\']([^"\']+)["\'][^>]*>[^<]*</iframe>|<script[^>]*src=["\']([^"\']+)["\'][^>]*>[^<]*</script>|\[' . $shortcode .
			'\s([^\]]+)\])|<div([^>]+class=["\'][^"\']*mv-video-target[^"\']*["\'][^>]*)>#',
			$content,
			$matches,
			PREG_OFFSET_CAPTURE
		) ) {
			$diff = 0;
			foreach ( $matches[1] as $index => $match ) {
				$match[1] = $match[1] + $diff;
				$before   = substr( $content, 0, $match[1] );
				// Ignore content inbetween
				// <!-- tasty-recipes-ignore -->These apples<!-- /tasty-recipes-ignore -->.
				preg_match_all( '#<!--\s?\/?tasty-recipes-ignore\s?-->#i', $before, $before_matches );
				if ( ! empty( $before_matches[0] ) ) {
					$last_before = array_pop( $before_matches[0] );
					// If the last marker wasn't a closing marker, then we're
					// inside the ignore part.
					if ( false === stripos( $last_before, '/tasty-recipes-ignore' ) ) {
						continue;
					}
				}
				if ( false !== stripos( $match[0], 'tr-adthrive-in-post-video-player' ) ) {
					$shortcode_inner = $matches[4][ $index ][0];
					$shortcode_inner = str_replace( array( '&#8221;', '&#8220;', '&#8243;' ), '"', $shortcode_inner );
					// WP 4.0 doesn't correctly handle dashes in shortcode attributes.
					$shortcode_inner = str_replace(
						array(
							'video-id',
							'upload-date',
						),
						array(
							'video_id',
							'upload_date',
						),
						$shortcode_inner
					);
					return '[tr-adthrive-in-post-video-player ' . $shortcode_inner . ']';
				}

				$video_url = self::get_mediavine_video_url( $matches, $index );
				if ( $video_url ) {
					// Found a URL we can use, so break and fall through.
					return $video_url;
				}
			}
		}
		return false;
	}

	/**
	 * Gets the video URL from a Mediavine video.
	 *
	 * @param array $matches Array of matches from the content.
	 * @param int   $index   Index of the match to process.
	 *
	 * @return false|string
	 */
	private static function get_mediavine_video_url( $matches, $index ) {
		$video_url = false;
		if ( ! empty( $matches[5][ $index ][0] ) ) {
			$attrs = shortcode_parse_atts( $matches[5][ $index ][0] );
			if ( ! empty( $attrs['data-video-id'] ) ) {
				return sprintf( 'https://dashboard.mediavine.com/videos/%s/edit', $attrs['data-video-id'] );
			}
		} else {
			$src = ! empty( $matches[3][ $index ][0] ) ? $matches[3][ $index ][0] : $matches[2][ $index ][0];
			// We can only reverse the 'src' for Mediavine, YouTube and Vimeo.
			if ( preg_match( '#//video\.mediavine\.com/videos/([^\.]+).js#', $src, $video_matches ) ) {
				$video_url = 'https://dashboard.mediavine.com/videos/' . $video_matches[1] . '/edit/';
			} elseif ( preg_match( '#https?://www\.youtube\.com/embed/([^?]+)#', $src, $video_matches ) ) {
				$video_url = 'https://www.youtube.com/watch?v=' . $video_matches[1];
			} elseif ( preg_match( '#https?://player\.vimeo\.com/video/([^?]+)#', $src, $video_matches ) ) {
				$video_url = 'https://vimeo.com/' . $video_matches[1];
			}
		}
		return $video_url;
	}

	/**
	 * Gets the video schema for a given video URL.
	 *
	 * @param string $video_url Video URL to fetch the video schema for.
	 * @param object $post      Post context this video exists in.
	 *
	 * @return array|false
	 */
	public static function get_video_schema_from_url( $video_url, $post ) {
		if ( ! $video_url ) {
			return false;
		}
		// If this was an AdThrive shortcode, also process that.
		if ( false !== stripos( $video_url, '[tr-adthrive-in-post-video-player' ) ) {
			$shortcode_inner = substr( str_replace( '[tr-adthrive-in-post-video-player', '', $video_url ), 0, -1 );
			$atts            = shortcode_parse_atts( $shortcode_inner );
			if ( ! empty( $atts['video_id'] ) ) {
				$oembed_data = Tasty_Recipes::get_template_part(
					'video/adthrive-oembed-response',
					array(
						'video_id'    => $atts['video_id'],
						'title'       => isset( $atts['name'] ) ? html_entity_decode( $atts['name'], ENT_COMPAT, 'UTF-8' ) : '',
						'description' => isset( $atts['description'] ) ? html_entity_decode( $atts['description'], ENT_COMPAT, 'UTF-8' ) : '',
						'upload_date' => isset( $atts['upload_date'] ) ? $atts['upload_date'] : '',
					)
				);
				if ( $oembed_data ) {
					return self::get_google_schema_video_from_oembed_data( json_decode( $oembed_data ) );
				}
			}
			return false;
		}
		// See if there's an oEmbed cache for this video.
		$cache_key = '_tr_oembed_' . md5( $video_url );
		$ttl_key   = $cache_key . '_ttl';
		$cache     = get_post_meta( $post->ID, $cache_key, true );
		$ttl       = get_post_meta( $post->ID, $ttl_key, true );
		if ( ! $cache || $ttl < time() ) {
			if ( ! function_exists( '_wp_oembed_get_object' ) ) {
				require_once ABSPATH . WPINC . '/class-oembed.php';
			}
			$wp_oembed = _wp_oembed_get_object();
			$provider  = $wp_oembed->get_provider( $video_url );
			if ( ! $provider ) {
				update_post_meta( $post->ID, $cache_key, '{{unknown}}' );
				update_post_meta( $post->ID, $ttl_key, time() + ( 3 * DAY_IN_SECONDS ) );
				return false;
			}
			$response_data = $wp_oembed->fetch( $provider, $video_url );

			/**
			 * Add extra data to the video schema response.
			 *
			 * @since 1.0
			 */
			$response_data = apply_filters(
				'tasty_recipes_video_schema_response',
				$response_data,
				compact( 'video_url', 'post' )
			);

			if ( false === $response_data ) {
				update_post_meta( $post->ID, $cache_key, '{{unknown}}' );
				update_post_meta( $post->ID, $ttl_key, time() + ( 1 * DAY_IN_SECONDS ) );
				return false;
			}
			update_post_meta( $post->ID, $cache_key, $response_data );
			$cache = $response_data;
			update_post_meta( $post->ID, $ttl_key, time() + ( 7 * DAY_IN_SECONDS ) );
		}
		return self::get_google_schema_video_from_oembed_data( $cache );
	}

	/**
	 * Enrich a Google Schema with reviews from post content if reviews don't exist.
	 *
	 * @param array  $schema   Existing Google Schema data.
	 * @param object $recipe   Recipe object.
	 * @param array  $comments Any comments to enrich from.
	 *
	 * @return array
	 */
	public static function enrich_google_schema_with_comments( $schema, $recipe, $comments ) {

		if ( isset( $schema['review'] ) || empty( $comments ) ) {
			return $schema;
		}
		/**
		 * Optionally set a limit on the number of reviews included in the schema markup.
		 *
		 * @param int $number Can be any number
		 */
		$limit = apply_filters( 'tasty_recipes_limit_schema_reviews', null );

		$reviews = array();
		foreach ( $comments as $comment ) {
			if ( empty( $comment->comment_content ) ) {
				continue;
			}

			$rating = get_comment_meta( $comment->comment_ID, Ratings::COMMENT_META_KEY, true );
			if ( ! $rating || (int) $rating < 1 ) {
				continue;
			}
			$reviews[] = array(
				'@type'         => 'Review',
				'reviewRating'  => array(
					'@type'       => 'Rating',
					'ratingValue' => (string) $rating,
				),
				'author'        => array(
					'@type' => 'Person',
					'name'  => $comment->comment_author,
				),
				'datePublished' => gmdate( 'Y-m-d', strtotime( $comment->comment_date ) ),
				'reviewBody'    => $comment->comment_content,
			);
			if ( ! is_null( $limit ) && count( $reviews ) >= $limit ) {
				break;
			}
		}

		if ( ! empty( $reviews ) ) {
			$schema['review'] = $reviews;
		}

		return $schema;
	}

	/**
	 * Get the comments generated by WP_Query.
	 *
	 * Duplicates some of the comments_template() function.
	 *
	 * @return array
	 */
	public static function get_wp_query_comments() {
		global $post, $user_ID;

		/*
		 * Comment author information fetched from the comment cookies.
		 */
		$commenter = wp_get_current_commenter();

		/*
		 * The email address of the current comment author escaped for use in attributes.
		 * Escaped by sanitize_comment_cookies().
		 */
		$comment_author_email = $commenter['comment_author_email'];

		$comment_args = array(
			'orderby'                   => 'comment_date_gmt',
			'order'                     => 'ASC',
			'status'                    => 'approve',
			'post_id'                   => $post->ID,
			'no_found_rows'             => false,
			'update_comment_meta_cache' => false, // We lazy-load comment meta for performance.
		);

		if ( get_option( 'thread_comments' ) ) {
			$comment_args['hierarchical'] = 'threaded';
		} else {
			$comment_args['hierarchical'] = false;
		}

		if ( $user_ID ) {
			$comment_args['include_unapproved'] = array( $user_ID );
		} elseif ( ! empty( $comment_author_email ) ) {
			$comment_args['include_unapproved'] = array( $comment_author_email );
		}

		$per_page = 0;
		if ( get_option( 'page_comments' ) ) {
			$per_page = (int) get_query_var( 'comments_per_page' );
			if ( 0 === $per_page ) {
				$per_page = (int) get_option( 'comments_per_page' );
			}

			$comment_args['number'] = $per_page;
			$page                   = (int) get_query_var( 'cpage' );

			if ( $page ) {
				$comment_args['offset'] = ( $page - 1 ) * $per_page;
			} elseif ( 'oldest' === get_option( 'default_comments_page' ) ) {
				$comment_args['offset'] = 0;
			} else {
				// If fetching the first page of 'newest', we need a top-level comment count.
				$top_level_query = new \WP_Comment_Query();
				$top_level_args  = array(
					'count'   => true,
					'orderby' => false,
					'post_id' => $post->ID,
					'status'  => 'approve',
				);

				if ( $comment_args['hierarchical'] ) {
					$top_level_args['parent'] = 0;
				}

				if ( isset( $comment_args['include_unapproved'] ) ) {
					$top_level_args['include_unapproved'] = $comment_args['include_unapproved'];
				}

				$top_level_count = $top_level_query->query( $top_level_args );

				$comment_args['offset'] = ( (int) ceil( $top_level_count / $per_page ) - 1 ) * $per_page;
			}
		}

		/**
		 * Filters the arguments used to query comments in comments_template().
		 *
		 * @see WP_Comment_Query::__construct()
		 *
		 * @param array $comment_args {
		 *     Array of WP_Comment_Query arguments.
		 *
		 *     @type array|string $orderby                   Field(s) to order by.
		 *     @type string       $order                     Order of results. Accepts 'ASC' or 'DESC'.
		 *     @type string       $status                    Comment status.
		 *     @type array        $include_unapproved        Array of IDs or email addresses whose unapproved comments
		 *                                                   will be included in results.
		 *     @type int          $post_id                   ID of the post.
		 *     @type bool         $no_found_rows             Whether to refrain from querying for found rows.
		 *     @type bool         $update_comment_meta_cache Whether to prime cache for comment meta.
		 *     @type bool|string  $hierarchical              Whether to query for comments hierarchically.
		 *     @type int          $offset                    Comment offset.
		 *     @type int          $number                    Number of comments to fetch.
		 * }
		 */
		$comment_args  = apply_filters( 'comments_template_query_args', $comment_args ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$comment_query = new \WP_Comment_Query( $comment_args );
		$_comments     = $comment_query->comments;

		// Trees must be flattened before they're passed to the walker.
		if ( $comment_args['hierarchical'] ) {
			$comments_flat = array();
			foreach ( $_comments as $_comment ) {
				$comments_flat[]  = $_comment;
				$comment_children = $_comment->get_children(
					array(
						'format'  => 'flat',
						'status'  => $comment_args['status'],
						'orderby' => $comment_args['orderby'],
					)
				);

				foreach ( $comment_children as $comment_child ) {
					$comments_flat[] = $comment_child;
				}
			}
		} else {
			$comments_flat = $_comments;
		}

		/**
		 * Filters the comments array.
		 *
		 * @param array $comments Array of comments supplied to the comments template.
		 * @param int   $post_ID  Post ID.
		 */
		return apply_filters( 'comments_array', $comments_flat, $post->ID ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Gets the list of instructions from a string.
	 *
	 * @param string $component Existing text to parse.
	 *
	 * @return array
	 */
	public static function parse_recipe_instruction_list( $component ) {
		return self::parse_recipe_component_list(
			self::apply_instruction_step_numbers(
				shortcode_unautop( wpautop( $component ) )
			),
			true
		);
	}

	/**
	 * Applies #instruction-step-[\d] to each step.
	 *
	 * @param string $component Existing instruction content.
	 *
	 * @return string
	 */
	public static function apply_instruction_step_numbers( $component ) {
		$step           = 0;
		$step_set_regex = '#^(\s*)(<[a-z]+)([^>]*>)#';
		if ( preg_match_all( self::LI_MATCH_REGEX, $component, $matches ) ) {
			$component = preg_replace_callback(
				self::LI_MATCH_REGEX,
				function ( $matches ) use ( &$step, $step_set_regex ) {
					$step++;
					$matches[0] = preg_replace(
						$step_set_regex,
						'$1$2 id="instruction-step-' . $step . '"$3',
						$matches[0]
					);
					return $matches[0];
				},
				$component
			);
		} elseif ( preg_match_all( self::UNSTRUCTURED_LIST_MATCH_REGEX, $component, $matches, PREG_OFFSET_CAPTURE ) > 1 ) {
			// Intro text before the first number, so set that as the first step.
			if ( $matches[0][0][1] > 0 ) {
				++$step;
				$component = preg_replace(
					$step_set_regex,
					'$1$2 id="instruction-step-' . $step . '"$3',
					$component
				);
			}
			$component = preg_replace_callback(
				self::UNSTRUCTURED_LIST_MATCH_REGEX,
				function ( $matches ) use ( &$step, $step_set_regex ) {
					$step++;
					$matches[0] = preg_replace(
						$step_set_regex,
						'$1$2 id="instruction-step-' . $step . '"$3',
						$matches[0]
					);
					return $matches[0];
				},
				$component
			);
		} else {
			$bits = explode( PHP_EOL, $component );
			foreach ( $bits as $i => $bit ) {
				if ( self::is_empty( wp_strip_all_tags( $bit ) ) ) {
					continue;
				}
				$heading = self::get_heading( $bit );
				if ( $heading ) {
					continue;
				}
				++$step;
				$bits[ $i ] = preg_replace(
					$step_set_regex,
					'$1$2 id="instruction-step-' . $step . '"$3',
					$bit
				);
			}
			$component = implode( PHP_EOL, $bits );
		}
		return $component;
	}

	/**
	 * Get a list from a string of ingredients or instructions.
	 *
	 * @param string $component   Existing text to parse.
	 * @param bool   $parse_names Parse the names if they exist.
	 *
	 * @return array
	 */
	public static function parse_recipe_component_list( $component, $parse_names = false ) { // phpcs:ignore SlevomatCodingStandard
		$component_list = array();
		if ( preg_match_all( self::LI_MATCH_REGEX, $component, $matches, PREG_OFFSET_CAPTURE ) ) {
			$prev_offset = 0;
			foreach ( $matches[1] as $i => $match ) {
				$maybe_heading = mb_substr( $component, $prev_offset, $match[1] - $prev_offset, 'UTF-8' );
				$maybe_heading = str_replace( array( '<ul>', '</ul>', '<ol>', '</ol>', '<li>', '</li>' ), '', $maybe_heading );
				$heading_bits  = explode( PHP_EOL, $maybe_heading );
				foreach ( $heading_bits as $bit ) {
					$heading = self::get_heading( $bit );
					if ( $heading ) {
						$component_list[] = array(
							'type'  => 'heading',
							'value' => $heading,
						);
					}
				}
				$bit  = $matches[0][ $i ][0];
				$item = array(
					'type' => 'item',
				);
				if ( $parse_names && preg_match( self::COMPONENT_NAME_REGEX, $bit, $name_matches ) ) {
					$item['name'] = rtrim( $name_matches['name'], '.: ' );
					$bit          = $name_matches['remaining'];

					if ( ! empty( $name_matches['p'] ) ) {
						$bit = $name_matches['p'] . $bit;
					}

					if ( ! empty( $name_matches['li'] ) ) {
						$bit = $name_matches['li'] . $bit;
					}
				}
				$item['value']    = $bit;
				$component_list[] = $item;
				$prev_offset      = $match[1] + mb_strlen( $match[0], 'UTF-8' );
			}
		} elseif ( preg_match_all( self::UNSTRUCTURED_LIST_MATCH_REGEX, $component, $matches, PREG_OFFSET_CAPTURE ) > 1 ) {
			// Intro text before the first number, so save that as the first list item.
			if ( $matches[0][0][1] > 0 ) {
				$component_list[] = array(
					'type'  => 'item',
					'value' => mb_substr( $component, 0, $matches[0][0][1] - 1, 'UTF-8' ),
				);
			}
			foreach ( $matches[0] as $i => $match ) {
				$start = $match[1] - 1;
				if ( $start < 0 ) {
					$start = 0;
				}
				// Component value is the distance between this offset and the next.
				$length = isset( $matches[0][ $i + 1 ] ) ? $matches[0][ $i + 1 ][1] - $match[1] - 1 : null;
				$bit    = mb_substr( $component, $start, $length, 'UTF-8' );
				$item   = array(
					'type' => 'item',
				);
				// Remove '1. ' from '<p id="instruction-step-2">1. First, rinse and drain the rice.'.
				$bit = preg_replace(
					'#^\s*(<[a-z]+[^>]*>)\s*[\d]+\.#',
					'$1',
					$bit
				);
				if ( $parse_names && preg_match( self::COMPONENT_NAME_REGEX, $bit, $name_matches ) ) {
					$item['name'] = rtrim( $name_matches['name'], '.: ' );
					$bit          = $name_matches['remaining'];

					if ( ! empty( $name_matches['p'] ) ) {
						$bit = $name_matches['p'] . $bit;
					}

					if ( ! empty( $name_matches['li'] ) ) {
						$bit = $name_matches['li'] . $bit;
					}
				}
				$item['value']    = $bit;
				$component_list[] = $item;
			}
		} else {
			// No list items detected, so fall back to stripping out things that look like headings.
			$bits = explode( PHP_EOL, $component );
			foreach ( $bits as $bit ) {

				// phpcs:ignore WordPressVIPMinimum.Functions.StripTags.StripTagsTwoParameters
				$test_bit = trim( strip_tags( $bit, '<img>' ) );
				if ( empty( $test_bit ) ) {
					continue;
				}
				$heading = self::get_heading( $bit );
				if ( $heading ) {
					$component_list[] = array(
						'type'  => 'heading',
						'value' => $heading,
					);
					continue;
				}
				$item = array(
					'type' => 'item',
				);
				if ( $parse_names && preg_match( self::COMPONENT_NAME_REGEX, $bit, $name_matches ) ) {
					$item['name'] = rtrim( $name_matches['name'], '.: ' );
					$bit          = $name_matches['remaining'];

					if ( ! empty( $name_matches['p'] ) ) {
						$bit = $name_matches['p'] . $bit;
					}

					if ( ! empty( $name_matches['li'] ) ) {
						$bit = $name_matches['li'] . $bit;
					}
				}
				$item['value']    = $bit;
				$component_list[] = $item;
			}
		}
		$media_on_top             = false;
		$enrich_key               = null;
		$bottom_enrich_key        = null;
		$media_component          = null;
		$bottom_media_component   = null;
		$processed_component_list = array();
		foreach ( $component_list as $component_item ) {
			$processed_component = array(
				'type' => $component_item['type'],
			);
			// If we've found media on top, always inspect the last line for the media.
			if ( $media_on_top ) {
				$bits       = explode( PHP_EOL, trim( $component_item['value'] ) );
				$last       = end( $bits );
				$rich_media = self::get_rich_media_for_component( apply_filters( 'tasty_recipes_the_content', $last, 'component' ) );
				if ( ! empty( $rich_media ) ) {
					// Discard the last item.
					array_pop( $bits );
					$bottom_media_component = $rich_media;
					$bottom_enrich_key      = count( $processed_component_list ) + 1;
				}
				$component_item['value'] = implode( PHP_EOL, $bits );
			}

			if ( 'item' === $component_item['type'] ) {
				if ( preg_match( '#^\s*<[a-z]+[^>]*id=[\'"](?<href>[^\'"]+)[\'"][^>]*>#', $component_item['value'], $name_matches ) ) {
					$processed_component['url'] = '#' . $name_matches['href'];
				}
				$component_item['value'] = apply_filters( 'tasty_recipes_the_content', $component_item['value'], 'component' );
				$processed_component     = array_merge(
					$processed_component,
					self::get_rich_media_for_component( $component_item['value'] )
				);
				if ( isset( $component_item['name'] ) ) {
					$processed_component['name'] = $component_item['name'];
				}
			}
			$component_value = trim( wp_strip_all_tags( strip_shortcodes( $component_item['value'] ) ) );
			// Don't add yet if it's an image or a video.
			if ( empty( $component_value ) ) {
				if ( ! isset( $processed_component['video_url'] )
					&& ! isset( $processed_component['image_src'] ) ) {
					continue;
				}
				$media_component = $processed_component;
				if ( ! count( $processed_component_list ) ) {
					$media_on_top = true;
				}
				$enrich_key = count( $processed_component_list ) - 1;
				if ( $media_on_top ) {
					++$enrich_key;
				}
				continue;
			}
			$processed_component['value'] = $component_value;
			$processed_component_list[]   = $processed_component;
			// If there's an existing media component to be added,
			// merge over the data.
			if ( is_array( $media_component )
				&& isset( $processed_component_list[ $enrich_key ] ) ) {
				if ( isset( $media_component['image_src'] )
					&& ! isset( $processed_component_list[ $enrich_key ]['image_src'] ) ) {
					$processed_component_list[ $enrich_key ]['image_src'] = $media_component['image_src'];
					if ( isset( $media_component['image_width'] )
						&& isset( $media_component['image_height'] ) ) {
						$processed_component_list[ $enrich_key ]['image_width']  = (int) $media_component['image_width'];
						$processed_component_list[ $enrich_key ]['image_height'] = (int) $media_component['image_height'];
					}
				}
				if ( isset( $media_component['video_url'] )
					&& ! isset( $processed_component_list[ $enrich_key ]['video_url'] ) ) {
					$processed_component_list[ $enrich_key ]['video_url'] = $media_component['video_url'];
				}
				$media_component = null;
			}
			if ( $bottom_media_component ) {
				$media_component        = $bottom_media_component;
				$enrich_key             = $bottom_enrich_key;
				$bottom_media_component = null;
				$bottom_enrich_key      = null;
			}
		}
		return $processed_component_list;
	}

	/**
	 * Format times so strtotime() can use them properly.
	 *
	 * @param string $time Existing time string.
	 * @param mixed  $now  What now should be considered as.
	 *
	 * @return int
	 */
	public static function strtotime( $time, $now = null ) {
		if ( null === $now ) {
			$now = time();
		}
		// Parse string to remove any info in parentheses.
		$time = preg_replace( '/\([^\)]+\)/', '', $time );

		// Parse string to handle 00:10 time format.
		$time = preg_replace( '/(\d+):(\d+)/', '$1 hours $2 minutes', $time );

		// Convert words in a time to string to English.
		$time = self::convert_time_to_english( $time );

		return strtotime( $time, $now );
	}

	/**
	 * Get a list of translated time pieces, and replace them with English.
	 *
	 * @since 3.11
	 *
	 * @param string $time Time to convert.
	 *
	 * @return string
	 */
	private static function convert_time_to_english( $time ) {
		$strings = self::empty_time_strings();

		foreach ( $strings as $string => $translation ) {
			if ( $string === $translation || ! str_contains( $time, $translation ) ) {
				continue;
			}

			// Replace the word if it's at the beginning or end of the string.
			$time = preg_replace( '/^' . $translation . ' /', $string . ' ', $time );
			$time = preg_replace( '/ ' . $translation . '$/', ' ' . $string, $time );

			// Middle of the string.
			$time = preg_replace( '/ ' . $translation . ' /', ' ' . $string . ' ', $time );
		}

		return $time;
	}

	/**
	 * Calculate the number of hours and minutes from a unix timestmp.
	 *
	 * @param int $time Time to compare.
	 *
	 * @return array
	 */
	public static function convert_seconds_to_minutes_hours( $time ) {
		$hours   = (int) floor( $time / HOUR_IN_SECONDS );
		$minutes = (int) floor( ( $time - ( $hours * HOUR_IN_SECONDS ) ) / MINUTE_IN_SECONDS );
		$seconds = $time - ( $hours * HOUR_IN_SECONDS ) - ( $minutes * MINUTE_IN_SECONDS );
		return array(
			'hours'   => $hours,
			'minutes' => $minutes,
			'seconds' => $seconds,
		);
	}


	/**
	 * Get an ISO 8601 duration based on a human-readable time.
	 *
	 * @param string $time Time to get the duration of.
	 *
	 * @return string
	 */
	public static function get_duration_for_time( $time ) {
		// Assume a number is minutes.
		if ( is_numeric( $time ) ) {
			$time = "{$time} minutes";
		}
		$now              = time();
		$time             = self::strtotime( $time, $now );
		$time             = $time - $now;
		$calculated_times = self::convert_seconds_to_minutes_hours( $time );
		$duration         = 'PT';
		if ( ! empty( $calculated_times['hours'] ) ) {
			$duration .= $calculated_times['hours'] . 'H';
		}
		if ( ! empty( $calculated_times['minutes'] ) ) {
			$duration .= $calculated_times['minutes'] . 'M';
		}
		if ( ! empty( $calculated_times['seconds'] ) ) {
			$duration .= $calculated_times['seconds'] . 'S';
		}
		return 'PT' !== $duration ? $duration : '';
	}

	/**
	 * Get a human-readable time based on an ISO 8601 duration.
	 *
	 * @param string $duration Duration to process.
	 *
	 * @return string
	 */
	public static function get_time_for_duration( $duration ) {
		try {
			$date = new \DateInterval( $duration );
		} catch ( \Exception $e ) {
			return '';
		}
		$total = ( $date->h * HOUR_IN_SECONDS ) + ( $date->i * MINUTE_IN_SECONDS );
		return self::format_time_for_human( $total );
	}

	/**
	 * Format a timestamp for a human.
	 *
	 * @param int $timestamp Timestamp to format.
	 *
	 * @return string
	 */
	public static function format_time_for_human( $timestamp ) {
		$bits    = self::convert_seconds_to_minutes_hours( $timestamp );
		$strings = self::time_strings();

		if ( 0 === $timestamp % HOUR_IN_SECONDS ) {
			if ( HOUR_IN_SECONDS === $timestamp ) {
				$formatted = sprintf( $strings['individual_hour'], $bits['hours'] );
			} else {
				$formatted = sprintf( $strings['multiple_hours'], $bits['hours'] );
			}
		} elseif ( $timestamp >= HOUR_IN_SECONDS * 2 ) {
			$formatted = sprintf( $strings['multiple_hours_with_minutes'], $bits['hours'], $bits['minutes'] );
		} elseif ( $timestamp >= HOUR_IN_SECONDS && ( $timestamp < HOUR_IN_SECONDS * 2 ) ) {
			$formatted = sprintf( $strings['individual_hour_with_minutes'], $bits['hours'], $bits['minutes'] );
		} elseif ( $timestamp <= MINUTE_IN_SECONDS ) {
			$formatted = sprintf( $strings['individual_minute'], $bits['minutes'] );
		} elseif ( $timestamp < HOUR_IN_SECONDS ) {
			$formatted = sprintf( $strings['multiple_minutes'], $bits['minutes'] );
		}
		return $formatted;
	}

	/**
	 * Get the possible translated time strings.
	 *
	 * @since 3.11
	 *
	 * @return array
	 */
	public static function time_strings() {
		return apply_filters(
			'tasty_recipes_human_time_formats',
			array(
				// translators: Individual hour.
				'individual_hour'              => __( '%s hour', 'tasty-recipes-lite' ),
				// translators: Multiple hours.
				'multiple_hours'               => __( '%s hours', 'tasty-recipes-lite' ),
				// translators: Multiple hours and minutes.
				'multiple_hours_with_minutes'  => __( '%1$s hours %2$s minutes', 'tasty-recipes-lite' ),
				// translators: Individual hour but multiple minutes.
				'individual_hour_with_minutes' => __( '%1$s hour %2$s minutes', 'tasty-recipes-lite' ),
				// translators: Individual minute.
				'individual_minute'            => __( '%s minute', 'tasty-recipes-lite' ),
				// translators: Multiple minutes.
				'multiple_minutes'             => __( '%s minutes', 'tasty-recipes-lite' ),
			)
		);
	}

	/**
	 * Strip %s from each time string for a direct list of time string replacements.
	 * ie: 'minute' => 'minuto'.
	 *
	 * @since 3.11
	 *
	 * @return array
	 */
	public static function empty_time_strings() {
		$strings       = self::time_strings();
		$empty_strings = array();
		foreach ( $strings as $key => $string ) {
			// Use the direct English equivalent.
			$basic_key = str_replace( array( 'individual_', 'multiple_' ), '', $key );

			$empty_strings[ $basic_key ] = trim( str_replace( '%s', '', $string ) );
		}

		return $empty_strings;
	}

	/**
	 * Check whether a given string could be a heading.
	 *
	 * @param string $text String to process.
	 *
	 * @return bool|string
	 */
	public static function get_heading( $text ) {
		$text = trim( preg_replace( '#<p[^>]*>(.+)</p>#', '$1', trim( $text ) ) );
		// For The Red Beans:.
		if ( ':' === substr( $text, -1, 1 ) ) {
			return substr( $text, 0, -1 );
		}
		// <strong>For The Red Beans</strong>
		if ( '<strong>' === substr( $text, 0, 8 ) && '</strong>' === substr( $text, -9, 9 ) ) {
			return substr( $text, 8, -9 );
		}
		// <h3>For The Red Beans</h3>
		if ( preg_match( '#^<h[1-6]>(.+)<\/h[1-6]>$#', $text, $matches ) ) {
			return $matches[1];
		}
		return false;
	}

	/**
	 * Processes a component for rich media.
	 *
	 * @param string $value Existing component value.
	 *
	 * @return array
	 */
	public static function get_rich_media_for_component( $value ) {
		$processed_component = array();
		if ( preg_match( '#<img([^>]+)>#', $value, $matches ) ) {
			$attrs = shortcode_parse_atts( $matches[1] );
			if ( isset( $attrs['src'] ) ) {
				$processed_component['image_src'] = $attrs['src'];
				if ( isset( $attrs['width'] ) && isset( $attrs['height'] ) ) {
					$processed_component['image_width']  = $attrs['width'];
					$processed_component['image_height'] = $attrs['height'];
				}
			}
		}
		$video_url = self::get_video_url_from_content( $value );
		if ( $video_url ) {
			$processed_component['video_url'] = $video_url;
		}
		return $processed_component;
	}

	/**
	 * Get an image size for the recipe, falling back to the post if needed.
	 *
	 * @param Recipe $recipe Recipe object to inspect.
	 * @param string $size   Specific image size to get.
	 *
	 * @return array|false
	 */
	public static function get_recipe_image_size_with_fallback( $recipe, $size ) {
		$image_size = $recipe->get_image_size( $size );
		if ( $image_size ) {
			return $image_size;
		}
		$current_post = get_post();
		if ( ! $current_post ) {
			return false;
		}
		$thumbnail_id = get_post_thumbnail_id( $current_post->ID );
		if ( ! $thumbnail_id ) {
			preg_match( '#wp-image-([\d]+)#', $current_post->post_content, $matches );
			if ( ! empty( $matches[1] ) ) {
				$thumbnail_id = $matches[1];
			}
		}
		if ( ! $thumbnail_id ) {
			return false;
		}
		$image_src = wp_get_attachment_image_src( $thumbnail_id, $size );
		if ( $image_src ) {
			return array(
				'url'    => $image_src[0],
				'width'  => $image_src[1],
				'height' => $image_src[2],
			);
		}
		return false;
	}

	/**
	 * Gets the JSON+LD image sizes to use for the recipe image.
	 *
	 * @param int $thumbnail_id Attachment ID.
	 *
	 * @return array
	 */
	public static function get_json_ld_image_sizes( $thumbnail_id ) {
		/**
		 * Allow the JSON+LD image sizes to be filtered.
		 *
		 * @param array $json_ld_image_sizes Existing image sizes.
		 */
		return apply_filters(
			'tasty_recipes_json_ld_image_sizes',
			self::$json_ld_image_sizes,
			$thumbnail_id
		);
	}
}
