<?php
/**
 * Registers shortcodes used by the plugin.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

use Tasty_Recipes;
use Tasty_Recipes\Objects\Recipe;
use Tasty_Recipes\Designs\Template;
use Tasty\Framework\Utils\Url;
use Tasty_Recipes\Assets;

/**
 * Registers shortcodes used by the plugin.
 */
class Shortcodes {

	/**
	 * Recipe shortcode name.
	 *
	 * @var string
	 */
	const RECIPE_SHORTCODE = 'tasty-recipe';

	/**
	 * Template markups.
	 *
	 * @var string[]
	 */
	private static $template_markups = array(
		'inside_ingredients' => '<!--INSIDE_INGREDIENTS-->',
	);

	/**
	 * Selected included template name.
	 *
	 * @var array
	 */
	private static $template = [
		'css' => '',
		'php' => '',
	];

	/**
	 * Custom template name if not an included template.
	 *
	 * @var string
	 */
	private static $custom_template = '';

	/**
	 * Only load inline JS once.
	 *
	 * @since 1.0
	 *
	 * @var bool
	 */
	private static $js_loaded = false;

	/**
	 * Load hooks.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function load_hooks() {
		add_action( 'init', array( __CLASS__, 'action_init_register_shortcode' ) );

		add_filter( 'tasty_recipes_recipe_card_output', array( __CLASS__, 'filter_tasty_recipes_recipe_card_output' ), 10, 2 );

		add_filter( 'tasty_recipes_the_content', array( __CLASS__, 'add_inside_ingredients' ), 10, 2 );
		add_filter( 'tasty_recipes_recipe_card_output', array( __CLASS__, 'inside_ingredients_handler' ), 10, 3 );
		add_action( 'tasty_recipes_card_after_ingredients', array( __CLASS__, 'run_after_ingredients' ) );
		add_action( 'tasty_recipes_card_after_title', array( __CLASS__, 'print_ratings' ) );
	}

	/**
	 * Registers shortcodes with their callbacks.
	 *
	 * @return void
	 */
	public static function action_init_register_shortcode() {
		add_shortcode( self::RECIPE_SHORTCODE, array( __CLASS__, 'render_tasty_recipe_shortcode' ) );

		add_action( 'wp_head', array( Assets::class, 'action_wp_head' ), 7 );
	}

	/**
	 * Filters the recipe card output to enhance with any styling customizations.
	 *
	 * @param string $output  Existing card output.
	 * @param string $context Where the shortcode is being rendered.
	 *
	 * @return string
	 */
	public static function filter_tasty_recipes_recipe_card_output( $output, $context = 'frontend' ) {
		if ( false === stripos( $output, 'data-tasty-recipes-customization' ) ) {
			return $output;
		}
		$settings = Tasty_Recipes::get_filtered_customization_settings();
		$output   = preg_replace_callback(
			'#<[a-z]+[^>]+data-tasty-recipes-customization=[\'"](?<options>[^\'"]+)[\'"][^>]*>#U',
			function ( $matches ) use ( $settings ) {
				$element = $matches[0];
				$styles  = '';

				$important = tasty_recipes_is_print() ? '' : ' !important';
				foreach ( explode( ' ', $matches['options'] ) as $option ) {
					if ( false !== stripos( $option, '.' ) ) {
						list( $key, $css_property ) = explode( '.', $option );
						if ( in_array( $css_property, array( 'innerHTML', 'innerText' ), true ) ) {
							continue;
						}
						$key = str_replace( '-', '_', $key );
						if ( ! empty( $settings[ $key ] ) ) {
							$styles .= $css_property . ': ' . $settings[ $key ] . $important . '; ';
						}
					}
				}
				if ( empty( $styles ) ) {
					return $element;
				}

				$styles = trim( $styles );
				if ( preg_match( '#style=[\'"](?<existing>[^\'"]+)[\'"]#', $element, $inline_style ) ) {
					$element = str_replace(
						$inline_style[0],
						str_replace(
							$inline_style['existing'],
							rtrim( $inline_style['existing'], '; ' ) . '; ' . $styles,
							$inline_style[0]
						),
						$element
					);
				} else {
					$element = str_replace(
						' data-tasty-recipes-customization',
						' style="' . esc_attr( $styles ) . '" data-tasty-recipes-customization',
						$element
					);
				}
				return $element;
			},
			$output
		);

		if ( 'frontend' === $context ) {
			foreach ( array( 'footer-heading', 'footer-description' ) as $field ) {
				$output = preg_replace(
					'#<[a-z1-9]+[^>]+data-tasty-recipes-customization=[\'"][^\'"]*' . $field . '\.(innerHTML|innerText)[^\'"]*[\'"][^>]*><\/[a-z1-9]+>#Us',
					'',
					$output
				);
			}
			// Clean up containers as necessary, from inside to outside.
			foreach ( array( 'tasty-recipes-footer-copy', 'tasty-recipes-footer-content', 'tasty-recipes-entry-footer' ) as $container ) {
				$output = preg_replace(
					'#<(div|footer)[^>]+class=[\'"][^\'"]*' . $container . '[^\'"]*[\'"][^>]*>[\s\t]+</(div|footer)>#Us',
					'',
					$output
				);
			}
		}

		return $output;
	}

	/**
	 * Renders the Tasty Recipes shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public static function render_tasty_recipe_shortcode( $atts ) { // phpcs:ignore SlevomatCodingStandard
		if ( is_home() || empty( $atts['id'] ) ) {
			return '';
		}

		$recipe = Recipe::get_by_id( (int) $atts['id'] );
		if ( ! $recipe ) {
			return '';
		}

		// Ensures the shortcode preview has access to the full recipe data.
		$recipe_json = $recipe->to_json();

		Tasty_Recipes::get_instance()->recipe_json = $recipe_json;

		// There are some dependencies on the parent post.
		$current_post = get_post();

		self::set_template_info();
		if ( empty( self::$template['php'] ) ) {
			return '';
		}

		$wrapper_classes = array(
			'tasty-recipes',
			'tasty-recipes-' . $recipe->get_id(),
		);
		if ( tasty_recipes_is_print() ) {
			$wrapper_classes[] = 'tasty-recipes-print';
		} else {
			$wrapper_classes[] = 'tasty-recipes-display';
		}

		$template_obj = Template::get_object_by_name( 'current' );
		$image_size   = $template_obj->get_image_size();

		/**
		 * Image size used for the recipe card.
		 *
		 * @var string $image_size Image size defaults to 'thumbnail'.
		 */
		$image_size = apply_filters( 'tasty_recipes_card_image_size', $image_size );

		if ( ! empty( $recipe_json['image_sizes'][ $image_size ] ) ) {
			$wrapper_classes[] = 'tasty-recipes-has-image';
		} else {
			$wrapper_classes[] = 'tasty-recipes-no-image';
		}

		// Added by block editor 'Additional Classes' feature.
		if ( ! empty( $atts['className'] ) ) {
			$wrapper_classes = array_merge( $wrapper_classes, explode( ' ', $atts['className'] ) );
		}

		$shareasale = get_option( Tasty_Recipes::SHAREASALE_OPTION );
		$show_plug  = $shareasale || get_option( Tasty_Recipes::POWEREDBY_OPTION );
		if ( $show_plug ) {
			$wrapper_classes[] = 'tasty-recipes-has-plug';
		}

		$wrapper_classes = apply_filters(
			'tasty_recipes_card_wrapper_classes',
			$wrapper_classes,
			[
				'recipe'   => $recipe,
				'template' => self::$template['php'],
			]
		);

		$before_recipe = '';
		$error         = get_post_meta( $recipe->get_id(), 'nutrifox_error', true );
		if ( $error && self::is_error_message_showable() ) {
			$before_recipe .= '<div style="border:4px solid #dc3232;padding:10px 12px;margin-top:10px;margin-bottom:10px;">' .
				'<p>Nutrifox API integration failed.</p>';
			$before_recipe .= '<pre>' . wp_kses_post( $error->get_error_message() ) . '</pre>';
			$before_recipe .= '<p>Try saving the recipe again. Contact Tasty Recipes support if the error persists.</p>';
			$before_recipe .= '</div>' . PHP_EOL;
		}
		if ( $current_post ) {
			$before_recipe .= '<a class="button tasty-recipes-print-button tasty-recipes-no-print tasty-recipes-print-above-card"';
			if ( tasty_recipes_is_print() ) {
				$before_recipe .= ' onclick="window.print();event.preventDefault();"';
			}
			$before_recipe .= ' href="' . esc_url( tasty_recipes_get_print_url( $current_post->ID, $recipe->get_id() ) ) . '">' .
				esc_html__( 'Print', 'tasty-recipes-lite' ) .
				'</a>';
		}

		/**
		 * Modify output rendered before the recipe.
		 *
		 * @param string $before_recipe Prepared output to display.
		 * @param Recipe $recipe
		 */
		$before_recipe = apply_filters( 'tasty_recipes_before_recipe', $before_recipe, $recipe );

		// Begin the recipe output.
		$ret            = $before_recipe;
		$ret           .= '<span class="tasty-recipes-jump-target" ' .
			'id="tasty-recipes-' . esc_attr( (string) $recipe->get_id() ) . '-jump-target" ' .
			'style="display:block;padding-top:2px;margin-top:-2px;"></span>';
		$ret           .= '<div id="tasty-recipes-' . $recipe->get_id() . '" data-tr-id="' . $recipe->get_id() . '" class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '"';
		$customizations = self::get_card_container_customizations();
		if ( $customizations ) {
			$ret .= ' data-tasty-recipes-customization="' . esc_attr( $customizations ) . '"';
		}
		$ret .= '>' . PHP_EOL;

		$template_vars = array(
			'recipe'             => $recipe,
			'recipe_styles'      => '',
			'recipe_scripts'     => '',
			'recipe_convertable' => false,
			'recipe_ingredients' => $recipe_json['ingredients'],
			'recipe_scalable'    => false,
			'copy_ingredients'   => false,
			'footer_heading'     => '',
			'footer_description' => '',
			'template'           => self::$custom_template,
			'raw_atts'           => $atts,
		);
		/**
		 * The template vars before they are prepared.
		 *
		 * @since 1.0
		 */
		$template_vars = apply_filters( 'tasty_recipes_pre_template_vars', $template_vars, compact( 'atts' ) );

		$ingredients = $template_vars['recipe_ingredients'];

		// Strip out this <span> so Tasty Links can apply to ingredient names.
		if ( false !== stripos( $ingredients, '<span class="nutrifox-name">' ) ) {
			$ingredients = preg_replace( '#<span class="nutrifox-name">(.+)</span>#U', '$1', $ingredients );
		}

		$template_vars['recipe_ingredients'] = apply_filters(
			'tasty_recipes_the_content',
			self::do_caption_shortcode( $template_vars['recipe_ingredients'] ),
			'ingredients'
		);

		$recipe_author_name = '';
		if ( ! empty( $recipe_json['author_name'] ) ) {
			$link = ! empty( $atts['author_link'] ) ? $atts['author_link'] : get_option( Tasty_Recipes::DEFAULT_AUTHOR_LINK_OPTION, '' );
			if ( $link ) {
				$recipe_author_name = '<a data-tasty-recipes-customization="detail-value-color.color" class="tasty-recipes-author-name" href="' . esc_url( $link ) . '">' .
					$recipe_json['author_name'] . '</a>';
			} else {
				$recipe_author_name = '<span data-tasty-recipes-customization="detail-value-color.color" class="tasty-recipes-author-name">' .
					$recipe_json['author_name'] . '</span>';
			}
		}

		$add_template_vars = array(
			'recipe_json'                   => $recipe_json,
			'recipe_title'                  => $recipe_json['title_rendered'],
			'recipe_image'                  => ! empty( $recipe_json['image_sizes'][ $image_size ] ) ? $recipe_json['image_sizes'][ $image_size ]['html'] : '',
			'recipe_rating_icons'           => '',
			'recipe_rating_label'           => '',
			'recipe_author_name'            => $recipe_author_name,
			'recipe_details'                => array(),
			'recipe_description'            => self::do_caption_shortcode( $recipe_json['description_rendered'] ),
			'recipe_instructions_has_video' => false,
			'recipe_instructions'           => Distribution_Metadata::apply_instruction_step_numbers(
				apply_filters( 'tasty_recipes_the_content', self::do_caption_shortcode( $recipe_json['instructions'] ), 'instructions' )
			),
			'recipe_keywords'               => '',
			'recipe_notes'                  => $recipe_json['notes_rendered'],
			'recipe_nutrifox_id'            => $recipe_json['nutrifox_id'],
			'recipe_nutrifox_embed'         => '',
			'recipe_video_embed'            => '',
			'recipe_nutrition'              => array(),
			'recipe_hidden_nutrition'       => array(),
			'first_button'                  => self::get_card_button( $recipe, 'first' ),
			'second_button'                 => self::get_card_button( $recipe, 'second' ),
		);

		$template_vars = array_merge( $add_template_vars, $template_vars );

		/**
		 * Enable responsive iframes by default, but permit disabling on a site.
		 *
		 * @param bool $responsive_iframes Whether or not to enable responsive iframes.
		 */
		$responsive_iframes = apply_filters( 'tasty_recipes_enable_responsive_iframes', true );

		$embed_data = $recipe->get_video_url_response();
		if ( ! empty( $embed_data->html ) ) {
			add_filter( 'tasty_recipes_allowed_html', array( __CLASS__, 'allow_html_iframe' ) );

			$template_vars['recipe_video_embed'] = $responsive_iframes ? self::make_iframes_responsive(
				$embed_data->html
			) : $embed_data->html;
		} elseif ( ! empty( $embed_data->provider_url )
			&& 'www.adthrive.com' === wp_parse_url( $embed_data->provider_url, PHP_URL_HOST ) ) {
			// If the AdThrive plugin is active, assume the <div> will be
			// handled correctly on the frontend.
			if ( shortcode_exists( 'adthrive-in-post-video-player' ) ) {
				// Show the thumbnail as the preview in the backend.
				if ( is_admin() ) {
					$template_vars['recipe_video_embed'] = sprintf( '<img src="%s">', esc_url( $embed_data->thumbnail_url ) );
				} else {
					$template_vars['recipe_video_embed'] = sprintf( '<div class="adthrive-video-player in-post" data-video-id="%s"></div>', $embed_data->video_id );
				}
				// Fallback is to display the shortcode.
			} else {
				$template_vars['recipe_video_embed'] = $recipe->get_video_url();
			}
		}

		$oembed_fields = array(
			'recipe_description',
			'recipe_ingredients',
			'recipe_instructions',
			'recipe_notes',
		);

		/*
		 * Remove <iframe>-based videos in print.
		 */
		if ( tasty_recipes_is_print() ) {
			foreach ( $oembed_fields as $oembed_field ) {
				$template_vars[ $oembed_field ] = preg_replace(
					'#(<br[^>]*>|\n)*?<iframe[^>]*>[^<]*</iframe>(<br[^>]*>|\n)*?#',
					'',
					$template_vars[ $oembed_field ]
				);
			}
		}
		if ( ! tasty_recipes_is_print() ) {
			$template_vars['recipe_instructions_has_video'] = preg_match_all(
				'#<iframe[^>]*>[^<]*</iframe>#',
				$template_vars['recipe_instructions'],
				$matches
			);

			/*
			 * Applies video settings to each oEmbed field, as necessary.
			 */
			foreach ( $oembed_fields as $oembed_field ) {
				$field      = str_replace( 'recipe_', '', $oembed_field );
				$method     = "get_{$field}_video_settings";
				$o_settings = $recipe->{$method}();
				if ( empty( $o_settings ) ) {
					continue;
				}
				$o_settings                     = explode( ' ', $o_settings );
				$template_vars[ $oembed_field ] = preg_replace_callback(
					'#<iframe[^>]*src=["\']([^"\']+)["\'][^>]*>[^<]*</iframe>#',
					function ( $matches ) use ( $o_settings ) {
						$host = wp_parse_url( $matches[1], PHP_URL_HOST );
						$url  = $matches[1];
						switch ( $host ) {
							case 'player.vimeo.com':
								$args = array(
									'autoplay'      => array(
										'autoplay'  => 1,
										'muted'     => 1,
										'autopause' => 0,
									),
									'mute'          => array( 'muted' => 1 ),
									'loop'          => array( 'loop' => 1 ),
									'hide-controls' => array( 'controls' => 0 ),
								);
								foreach ( $o_settings as $setting ) {
									if ( isset( $args[ $setting ] ) ) {
										$url = add_query_arg( $args[ $setting ], $url );
									}
								}
								$matches[0] = str_replace(
									$matches[1],
									$url,
									$matches[0]
								);
								break;
							case 'www.youtube.com':
								$args = array(
									'autoplay'      => array( 'autoplay' => 1 ),
									'mute'          => array( 'muted' => 1 ),
									'loop'          => array( 'loop' => 1 ),
									'hide-controls' => array( 'controls' => 0 ),
								);
								foreach ( $o_settings as $setting ) {
									if ( isset( $args[ $setting ] ) ) {
										$url = add_query_arg( $args[ $setting ], $url );
									}
								}
								$matches[0] = str_replace(
									$matches[1],
									$url,
									$matches[0]
								);
								break;
						}
						return $matches[0];
					},
					$template_vars[ $oembed_field ]
				);
			}
		}

		if ( $template_vars['recipe_instructions_has_video'] ) {
			add_filter( 'tasty_recipes_allowed_html', array( __CLASS__, 'allow_html_iframe' ) );
		}

		/*
		 * Make all <iframes> responsive if feature is enabled.
		 */
		if ( $responsive_iframes ) {
			foreach ( $oembed_fields as $oembed_field ) {
				if ( false === stripos( $template_vars[ $oembed_field ], '<iframe' ) ) {
					continue;
				}
				$template_vars[ $oembed_field ] = self::make_iframes_responsive(
					$template_vars[ $oembed_field ]
				);
			}
		}

		$total_reviews = $recipe->get_total_reviews();

		$template_vars['recipe_rating_label'] = '<span data-tasty-recipes-customization="detail-label-color.color" class="rating-label">';

		if ( $total_reviews ) {
			$average_rating                        = $recipe->get_average_rating();
			$template_vars['recipe_rating_label'] .= Ratings::get_rating_label( $total_reviews, $average_rating );
		} else {
			$template_vars['recipe_rating_label'] .= Ratings::is_enabled() ? __( 'No reviews', 'tasty-recipes-lite' ) : '';
			$average_rating                        = 0;
		}

		$template_vars['recipe_rating_icons']  = Ratings::get_rating_stars_html( true, $average_rating );
		$template_vars['recipe_rating_label'] .= '</span>';

		self::add_author_info( $template_vars );
		self::add_cooking_attrs( $template_vars );

		$settings = Tasty_Recipes::get_filtered_customization_settings();

		/**
		 * Filters the Nutrifox display style: 'card' or 'label'.
		 *
		 * @param string $display_style Existing display style.
		 */
		$nutrifox_display_style = apply_filters(
			'tasty_recipes_nutrifox_display_style',
			$settings['nutrifox_display_style']
		);

		self::add_nutrition_attrs( $recipe, $nutrifox_display_style, $template_vars );
		self::add_nutrifox_label( $nutrifox_display_style, $template_vars );

		$template_vars['atts'] = $atts;

		/**
		 * Allow third-parties to modify the template variables prior to rendering.
		 *
		 * @param array  $template_vars Template variables to be used.
		 * @param object $recipe        Recipe object.
		 */
		$template_vars = apply_filters( 'tasty_recipes_recipe_template_vars', $template_vars, $recipe, compact( 'atts' ) );

		self::prepare_assets( $template_vars );
		self::$js_loaded = true;

		$ret .= Tasty_Recipes::get_template_part( self::$template['php'], $template_vars );
		$ret .= '</div>';

		self::add_powered_by( $shareasale, $show_plug, $ret );
		Assets::maybe_add_tinymce_style( $ret );

		return apply_filters( 'tasty_recipes_recipe_card_output', $ret, 'frontend', compact( 'recipe' ) );
	}

	/**
	 * Get details about the selected template.
	 *
	 * @since 3.13
	 *
	 * @param bool|string $custom_design Custom design.
	 * @param false|int   $variation     Template variation.
	 *
	 * @return void
	 */
	private static function set_template_info( $custom_design = false, $variation = false ) {
		self::$template['php'] = '';
		self::$custom_template = '';

		/**
		 * Modify template used in rendering recipe.
		 *
		 * @param string $template
		 */
		$template = apply_filters( 'tasty_recipes_recipe_template', 'tasty-recipes' );
		if ( ! in_array( $template, array( 'tasty-recipes', 'easyrecipe' ), true ) ) {
			return;
		}

		$custom_design = $custom_design ? $custom_design : 'current';
		$template_obj  = Template::get_object_by_name( $custom_design );

		self::$custom_template = $custom_design;
		if ( $template_obj ) {
			self::$custom_template = $template_obj->get_id();
			self::$template['php'] = $template_obj->get_template_path( $variation );
			self::$template['css'] = $template_obj->get_style_path( $variation );
		}
	}

	/**
	 * Add author name to the recipe template.
	 *
	 * @param array $template_vars Template variables.
	 *
	 * @return void
	 */
	private static function add_author_info( &$template_vars ) {
		if ( ! empty( $template_vars['recipe_author_name'] ) ) {
			$template_vars['recipe_details']['author'] = array(
				'label' => __( 'Author', 'tasty-recipes-lite' ),
				'value' => $template_vars['recipe_author_name'],
				'class' => 'author',
			);
		}
	}

	/**
	 * Add cooking attributes to the recipe template like yield, diet, etc.
	 *
	 * @param array $template_vars Template variables.
	 *
	 * @return void
	 */
	private static function add_cooking_attrs( &$template_vars ) {
		$recipe_json = $template_vars['recipe_json'];
		foreach ( Recipe::get_cooking_attributes() as $attribute => $meta ) {

			if ( empty( $recipe_json[ $attribute ] ) ) {
				continue;
			}
			$value = $recipe_json[ $attribute ];

			// Use the display/localized version of the Diet.
			if ( 'diet' === $attribute ) {
				if ( isset( $meta['options'][ $value ] ) ) {
					$value = $meta['options'][ $value ];
				}
			}

			if ( 'additional_time_label' === $attribute ) {
				$label = $value;
				$value = '<span data-tasty-recipes-customization="detail-value-color.color" ' .
					'class="' . esc_attr( 'tasty-recipes-additional-time' ) . '">' .
					$recipe_json['additional_time_value'] .
					'</span>';
				$template_vars['recipe_details']['additional_time'] = array(
					'label' => $label,
					'value' => $value,
					'class' => 'additional-time',
				);
				continue;
			}

			// Handled above.
			if ( 'additional_time_value' === $attribute ) {
				continue;
			}

			/**
			 * Filter the HTML for the cooking attribute.
			 *
			 * @since 1.0
			 */
			$value = apply_filters( 'tasty_recipes_cooking_html', $value, $attribute, $template_vars );
			$value = '<span data-tasty-recipes-customization="detail-value-color.color" ' .
				'class="' . esc_attr( 'tasty-recipes-' . str_replace( '_', '-', $attribute ) ) . '">'
				. $value .
				'</span>';
			$template_vars['recipe_details'][ $attribute ] = array(
				'label' => $meta['label'],
				'value' => $value,
				'class' => str_replace( '_', '-', $attribute ),
			);
		}
	}

	/**
	 * Add nutrition attributes to the recipe template.
	 *
	 * @param Recipe $recipe                 Recipe object.
	 * @param string $nutrifox_display_style Nutrifox display style.
	 * @param array  $template_vars          Template variables.
	 *
	 * @return void
	 */
	private static function add_nutrition_attrs( $recipe, $nutrifox_display_style, &$template_vars ) {
		$nutrifox = $recipe->get_nutrifox_response();
		if ( ! $nutrifox ) {
			return;
		}

		foreach ( Recipe::get_nutrition_attributes() as $attribute => $meta ) {
			// See if the data exists in Nutrifox now.
			$exists = isset( $meta['nutrifox_key'] ) &&
				(
					! isset( $nutrifox['nutrients'][ $meta['nutrifox_key'] ]['visible'] )
					|| true === $nutrifox['nutrients'][ $meta['nutrifox_key'] ]['visible']
				);

			if ( ! $exists ) {
				continue;
			}

			$class_name     = 'tasty-recipes-' . str_replace( '_', '-', $attribute );
			$nutrifox_value = $recipe->get_formatted_nutrifox_value( $attribute );
			$template_vars['recipe_hidden_nutrition'][ $attribute ] = array(
				'value' => '<span data-tasty-recipes-customization="detail-value-color.color" ' .
					'class="' . esc_attr( $class_name ) . '">' .
					esc_html( $nutrifox_value ) . '</span>',
			);

			if ( 'card' !== $nutrifox_display_style ) {
				continue;
			}

			// Show the plain text nutrition values.
			$template_vars['recipe_nutrition'][ $attribute ] = array(
				'label' => $meta['label'],
				'value' => '<span data-tasty-recipes-customization="body-color.color" ' .
					'class="' . esc_attr( $class_name ) . '">' .
					esc_html( $nutrifox_value ) . '</span>',
			);
		}
	}

	/**
	 * Add the script for the Nutrifox label.
	 *
	 * @since 3.12.3
	 *
	 * @param string $nutrifox_display_style Nutrifox display style.
	 * @param array  $template_vars          Template variables.
	 *
	 * @return void
	 */
	private static function add_nutrifox_label( $nutrifox_display_style, &$template_vars ) {
		$recipe_json = $template_vars['recipe_json'];
		if ( empty( $recipe_json['nutrifox_id'] ) || 'label' !== $nutrifox_display_style ) {
			return;
		}

		$nutrifox_id = (int) $recipe_json['nutrifox_id'];

		$template_vars['recipe_nutrifox_embed'] = self::nutrifox_iframe( $nutrifox_id );
	}

	/**
	 * Get the Nutrifox iframe.
	 *
	 * @since 1.0
	 *
	 * @param int $nutrifox_id Nutrifox ID.
	 *
	 * @return string
	 */
	public static function nutrifox_iframe( $nutrifox_id ) {
		if ( ! self::$js_loaded ) {
			wp_enqueue_script( 'tasty-recipes-nutrifox-resize', plugins_url( 'assets/js/nutrifox-resize.js', __DIR__ ), array(), TASTY_RECIPES_LITE_VERSION, true );
			add_filter( 'wp_inline_script_attributes', array( __CLASS__, 'set_nutrifox_js_attributes' ), 10, 2 );
		}

		$nutrifox_iframe_url = sprintf(
			'%s/embed/label/%d',
			Tasty_Recipes::nutrifox_url(),
			$nutrifox_id
		);

		add_filter( 'tasty_recipes_allowed_html', array( __CLASS__, 'allow_html_iframe' ) );

		$embed = '<iframe ' .
			'title="nutritional information" ' .
			'id="nutrifox-label-' . absint( $nutrifox_id ) . '" ' .
			'src="' . esc_url( $nutrifox_iframe_url ) . '" ' .
			'style="width:100%;border-width:0;"' .
			'></iframe>';

		return $embed;
	}

	/**
	 * Allow iframe in sanitized HTML.
	 *
	 * @since 1.0
	 *
	 * @param array $allowed_html Allowed HTML.
	 *
	 * @return array
	 */
	public static function allow_html_iframe( $allowed_html ) {
		if ( isset( $allowed_html['iframe'] ) ) {
			return $allowed_html;
		}

		$allowed_html['iframe'] = array(
			'allow'                 => true,
			'allowfullscreen'       => true,
			'id'                    => true,
			'class'                 => true,
			'frameborder'           => true,
			'referrerpolicy'        => true,
			'src'                   => true,
			'style'                 => true,
			'title'                 => true,
			'webkitallowfullscreen' => true,
			'mozallowfullscreen'    => true,
		);

		return $allowed_html;
	}

	/**
	 * Set the Nutrifox javascript attributes.
	 *
	 * @since 1.0
	 *
	 * @param array  $attributes Existing attributes.
	 * @param string $data       Javascript to show.
	 *
	 * @return array
	 */
	public static function set_nutrifox_js_attributes( $attributes, $data ) {
		if ( strpos( $data, '/nutrifox.' ) ) {
			$attributes['data-cfasync'] = 'false';
		}

		return $attributes;
	}

	/**
	 * Add the "Powered by Tasty Recipes" link to the recipe card.
	 *
	 * @param string $shareasale ShareASale ID.
	 * @param bool   $show_plug  Whether to show the powered by link.
	 * @param string $ret        Recipe card HTML.
	 *
	 * @return void
	 */
	private static function add_powered_by( $shareasale, $show_plug, &$ret ) {
		if ( ! $show_plug ) {
			return;
		}

		$url = Url::add_utm_params( 'tasty-recipes', array( 'utm_content' => 'poweredby' ) );
		if ( $shareasale ) {
			$url = sprintf( 'https://shareasale.com/r.cfm?b=973044&u=%s&m=69860&urllink=&afftrack=trattr', $shareasale );
		}

		$ret .= '<div class="tasty-recipes-plug">';
		$ret .= esc_html__( 'Recipe Card powered by', 'tasty-recipes-lite' );
		$ret .= '<a href="' . esc_url( $url ) . '" target="_blank" rel="nofollow">';
		$ret .= '<img data-pin-nopin="true" alt="Tasty Recipes" src="' . esc_url( plugins_url( 'assets/images/tasty-recipes-neutral.svg', __DIR__ ) ) . '" height="20">';
		$ret .= '</a>';
		$ret .= '</div>';
	}

	/**
	 * Register the js and css for inline rendering.
	 *
	 * @since 3.9
	 *
	 * @param array $template_vars Template variables.
	 *
	 * @return void
	 */
	private static function prepare_assets( &$template_vars ) {
		if ( self::$js_loaded ) {
			return;
		}

		self::load_registered_styles( $template_vars['recipe_styles'] );
		$template_vars['recipe_styles'] = '';

		self::get_common_recipe_scripts();
		$scripts = Utils::minify_js( $template_vars['recipe_scripts'] );
		if ( empty( $scripts ) ) {
			return;
		}

		wp_add_inline_script( 'tasty-recipes', $scripts );

		// Clear so templates that echo won't add duplication.
		$template_vars['recipe_scripts'] = '';
	}

	/**
	 * Load late registered styles.
	 *
	 * @since 1.0
	 *
	 * @param string $css CSS to load.
	 *
	 * @return void
	 */
	private static function load_registered_styles( $css ) {
		if ( empty( $css ) ) {
			return;
		}

		wp_register_style( 'tasty-recipes-footer', false, array(), TASTY_RECIPES_LITE_VERSION );
		wp_enqueue_style( 'tasty-recipes-footer' );
		wp_add_inline_style( 'tasty-recipes-footer', $css );
	}

	/**
	 * Print ratings html.
	 * This is run on the tasty_recipes_card_after_title hook
	 * which won't exist in older templates. This will prevent
	 * the ratings from showing multiple times.
	 *
	 * @since 1.0
	 *
	 * @param array $template_vars Template variables.
	 *
	 * @return void
	 */
	public static function print_ratings( $template_vars ) {
		if ( empty( $template_vars['recipe_rating_icons'] ) && empty( $template_vars['recipe_rating_label'] ) ) {
			return;
		}

		$template_vars['fallback'] = 'parts/card-ratings';
		Tasty_Recipes::echo_template_part(
			str_replace( 'tasty-recipes.php', 'card-ratings.php', self::$template['php'] ),
			$template_vars
		);
	}

	/**
	 * Get the shortcode for a given recipe.
	 *
	 * @param Recipe $recipe Recipe instance.
	 *
	 * @return string
	 */
	public static function get_shortcode_for_recipe( Recipe $recipe ) {
		$shortcode = '[' . self::RECIPE_SHORTCODE . ' id="' . $recipe->get_id() . '"]';
		/**
		 * Filters the shortcode for a given recipe.
		 *
		 * @since 1.2.2
		 *
		 * @param string $shortcode Shortcode string.
		 * @param Recipe $recipe    Recipe instance.
		 */
		return apply_filters( 'tasty_recipes_recipe_shortcode', $shortcode, $recipe );
	}

	/**
	 * Gets any wrapper customizations for the card.
	 *
	 * @param string $custom_design Design being rendered.
	 *
	 * @return string
	 */
	public static function get_card_container_customizations( $custom_design = '' ) {
		return self::get_template_customization( 'container', $custom_design );
	}

	/**
	 * Gets any customizations for the recipe template.
	 *
	 * @param string $name          Name of the customization.
	 * @param string $custom_design Design being rendered.
	 *
	 * @return string
	 */
	public static function get_template_customization( $name, $custom_design = '' ) {
		return Template::get_template_customization( $name, $custom_design ? $custom_design : self::$custom_template );
	}

	/**
	 * Gets a given card button.
	 *
	 * @param object $recipe   Recipe object.
	 * @param string $position Button to generate.
	 * @param string $template Name of the template.
	 *
	 * @return string
	 */
	public static function get_card_button( $recipe, $position, $template = null ) {
		$settings = Tasty_Recipes::get_card_button_settings( $template );
		if ( empty( $settings[ $position ] ) ) {
			return '';
		}

		if ( null === $template ) {
			$template = 'current';
		}
		$template_obj = Template::get_object_by_name( $template );

		$atts = array(
			'recipe'        => $recipe,
			'customization' => $template_obj->get_customized( 'admin_button' ),
		);
		return Tasty_Recipes::get_template_part(
			'buttons/' . $settings[ $position ],
			$atts
		);
	}

	/**
	 * Gets the styles to inject into the recipe card.
	 *
	 * @param string    $override_design Design being used if not the saved option.
	 * @param false|int $variation       Template variation.
	 *
	 * @return string
	 */
	public static function get_styles_as_string( $override_design = '', $variation = false ) {
		self::set_template_info( $override_design, $variation );

		// Add stylesheet so we can add inline CSS to it.
		wp_register_style( 'tasty-recipes-before', false, array(), TASTY_RECIPES_LITE_VERSION );
		wp_enqueue_style( 'tasty-recipes-before' );
		wp_enqueue_style( 'tasty-recipes-main' );

		$styles       = '';
		$css_files    = [];
		$template_obj = Template::get_object_by_name( self::$custom_template );
		
		if ( $template_obj->supports( 'variations' ) ) {
			$css_files[] = $template_obj->get_shared_style_path();
		}

		if ( self::$custom_template && self::$template['css'] ) {
			$css_files[] = self::$template['css'];
			add_filter( 'tasty_recipes_css_vars', array( __CLASS__, 'add_template_css_vars' ) );
		}
		

		/**
		 * Allow third-parties to more easily inject their own styles.
		 */
		$custom_styles_path = apply_filters( 'tasty_recipes_custom_css', '' );
		if ( file_exists( $custom_styles_path ) ) {
			$css_files[] = $custom_styles_path;
		}

		/**
		 * Filters the styles to be injected into the recipe card.
		 *
		 * @since 1.0
		 *
		 * @param array $css_files Existing styles.
		 */
		$css_files = (array) apply_filters( 'tasty_recipes_styles', $css_files );
		$css_files = array_unique( $css_files );
		foreach ( $css_files as $css_file ) {
			if ( file_exists( $css_file ) ) {
				$styles .= Utils::get_contents( $css_file );
			}
		}

		/**
		 * Filters the styles to be injected into the recipe card.
		 *
		 * @since 1.0
		 *
		 * @param string $styles Existing styles.
		 */
		return apply_filters( 'tasty_recipes_styles_string', $styles );
	}

	/**
	 * Add the template customization to the CSS vars.
	 *
	 * @since 1.0
	 *
	 * @param array $vars Existing CSS vars.
	 *
	 * @return array
	 */
	public static function add_template_css_vars( $vars ) {
		$vars['--tr-radius'] = self::get_template_customization( 'radius', self::$custom_template );
		return $vars;
	}

	/**
	 * Do the caption shortcode when applying content filters.
	 *
	 * @param string $content Content to render.
	 *
	 * @return string
	 */
	private static function do_caption_shortcode( $content ) {
		global $shortcode_tags;
		if ( ! has_shortcode( $content, 'caption' )
			&& ! isset( $shortcode_tags['caption'] ) ) {
			return $content;
		}
		$backup_tags = $shortcode_tags;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$shortcode_tags = array(
			'caption' => $backup_tags['caption'],
		);
		$content        = do_shortcode( $content );
		$shortcode_tags = $backup_tags; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		return $content;
	}

	/**
	 * Makes all iframes within the content responsive using modern CSS aspect-ratio.
	 *
	 * @param string $content Content to process.
	 *
	 * @return string
	 */
	private static function make_iframes_responsive( $content ) {
		// Responsive iframes using modern CSS aspect-ratio.
		if ( preg_match_all(
			'#<iframe([^>]+)>.*</iframe>#',
			$content,
			$matches
		) ) {
			foreach ( $matches[0] as $i => $original_iframe ) {
				$iframe = $original_iframe;
				$attrs  = shortcode_parse_atts( $matches[1][ $i ] );
				if ( empty( $attrs['height'] )
					|| empty( $attrs['width'] )
					|| empty( $attrs['src'] ) ) {
					continue;
				}
				// Remove the existing width and height attributes.
				$iframe = preg_replace( '#(width|height)=[\'"][^\'"]+[\'"]#', '', $iframe );
				// First try to inject the 'fitvidsignore' class into the existing classes.
				$iframe = preg_replace( '#(<iframe[^>]+class=")([^"]+)#', '$1fitvidsignore $2', $iframe, -1, $count );
				// If no replacements were found, then the <iframe> needs a class.
				if ( ! $count ) {
					$iframe = str_replace( '<iframe ', '<iframe class="fitvidsignore" ', $iframe );
				}

				// Check if this is a YouTube Shorts video.
				// Shorts can be detected by /shorts/ in URL, or by vertical aspect ratio on YouTube embeds.
				$is_youtube        = preg_match( '#youtube\.com/(embed|shorts)/|youtu\.be/#i', $attrs['src'] );
				$is_vertical       = (float) $attrs['height'] > (float) $attrs['width'];
				$has_shorts_path   = preg_match( '#youtube\.com/shorts/#i', $attrs['src'] );
				$is_youtube_shorts = $has_shorts_path || ( $is_youtube && $is_vertical );
				$shorts_class      = $is_youtube_shorts ? ' tasty-recipe-youtube-shorts' : '';

				// Use simplified aspect ratios: 9/16 for vertical (Shorts), 16/9 for horizontal.
				$aspect_ratio = $is_youtube_shorts ? '9 / 16' : '16 / 9';
				$inline_style = ' style="aspect-ratio: ' . esc_attr( $aspect_ratio ) . ';"';
				$iframe       = '<div class="' . esc_attr( 'tasty-recipe-responsive-iframe-container' . $shorts_class ) . '"' . $inline_style . '>' . $iframe . '</div>';
				$content      = str_replace(
					$original_iframe,
					$iframe,
					$content
				);
			}
		}
		return $content;
	}

	/**
	 * Whether or not an error message should be shown.
	 *
	 * @return bool
	 */
	private static function is_error_message_showable() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Add inside ingredients template markup.
	 *
	 * @since 3.8
	 *
	 * @param string $content Ingredients content.
	 * @param string $type    Type returned from the filter.
	 *
	 * @return string
	 */
	public static function add_inside_ingredients( $content, $type = null ) {
		if ( 'ingredients' === $type ) {
			$content .= self::$template_markups['inside_ingredients'];
		}

		return $content;
	}

	/**
	 * The hook is present, so we don't need the fallback.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public static function run_after_ingredients() {
		remove_filter( 'tasty_recipes_recipe_card_output', array( 'Tasty_Recipes\Shortcodes', 'inside_ingredients_handler' ) );
	}

	/**
	 * Force the tasty_recipes_card_after_ingredients hook to run if
	 * it's missing from the template.
	 *
	 * @since 3.8
	 *
	 * @param string $content Filter content.
	 * @param string $context Where the shortcode is being rendered.
	 * @param array  $atts    Includes the recipe object.
	 *
	 * @return string
	 */
	public static function inside_ingredients_handler( $content, $context, $atts = array() ) {
		if ( ! stripos( $content, self::$template_markups['inside_ingredients'] ) ) {
			return $content;
		}

		$content = str_ireplace(
			self::$template_markups['inside_ingredients'],
			self::add_after_ingredients_hook( $atts ),
			$content
		);
		return $content;
	}

	/**
	 * Force the hook missing from the template.
	 *
	 * @since 3.8
	 *
	 * @param array $atts Includes the recipe object.
	 *
	 * @return string
	 */
	private static function add_after_ingredients_hook( $atts ) {
		// This hook should only run when it comes from the template.
		remove_action( 'tasty_recipes_card_after_ingredients', array( 'Tasty_Recipes\Shortcodes', 'run_after_ingredients' ) );

		$recipe = empty( $atts['recipe'] ) ? false : $atts['recipe'];
		ob_start();
		do_action( 'tasty_recipes_card_after_ingredients', $recipe );
		return ob_get_clean();
	}

	/**
	 * Get common recipe scripts, like JS vars needed for other scripts.
	 *
	 * @return void
	 */
	private static function get_common_recipe_scripts() {
		if ( ! self::$js_loaded ) {
			Assets::get_common_recipe_scripts();
		}
	}

	/**
	 * Renders the Quick Links shortcode.
	 *
	 * @deprecated 1.0
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public static function render_quick_links_shortcode( $atts ) {
		_deprecated_function( __METHOD__, '1.0', 'Quick_Links::render_quick_links_shortcode' );
		return Quick_Links::render_quick_links_shortcode( $atts );
	}

	/**
	 * Get the classes for the quick links.
	 *
	 * @deprecated 1.0
	 *
	 * @param string $classes Existing classes.
	 *
	 * @return string
	 */
	public static function quick_links_classes( $classes ) {
		_deprecated_function( __METHOD__, '1.0', 'Quick_Links::quick_links_classes' );
		return Quick_Links::quick_links_classes( $classes );
	}

	/**
	 * Moved to Assets to get Shortcodes smaller.
	 *
	 * @since 3.13
	 *
	 * @return void
	 */
	public static function action_wp_head() {
		_deprecated_function( __METHOD__, '1.0', 'Assets::maybe_load_head' );
	}

	/**
	 * Print cook mode html.
	 *
	 * @since 3.8
	 * @deprecated 1.0
	 *
	 * @return void
	 */
	public static function print_cook_mode() {
		_deprecated_function( __METHOD__, '1.0' );
	}

	/**
	 * Process ingredients to annotate <li> with structured data.
	 *
	 * @deprecated 1.0
	 *
	 * @param string $ingredients Existing ingredients string.
	 *
	 * @return string
	 */
	public static function process_ingredients_annotate_with_spans( $ingredients ) {
		_deprecated_function( __METHOD__, '1.0' );
		return $ingredients;
	}

	/**
	 * Process ingredients to annotate <li> with checkboxes.
	 *
	 * @deprecated 1.0
	 *
	 * @param string $ingredients Existing ingredients string.
	 *
	 * @return string
	 */
	public static function process_ingredients_for_checkboxes( $ingredients ) {
		_deprecated_function( __METHOD__, '1.0' );
		return $ingredients;
	}
}
