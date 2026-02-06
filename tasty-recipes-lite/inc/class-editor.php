<?php
/**
 * Customizations to the WordPress editor experience.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

use Tasty_Recipes;
use Tasty_Recipes\Objects\Recipe;
use WP_Post;

/**
 * Customizations to the WordPress editor experience.
 */
class Editor {

	/**
	 * Get message notice classes.
	 *
	 * @since 3.8
	 *
	 * @param array $message Message array.
	 *
	 * @return string[]
	 */
	private static function get_message_notice_classes( $message ) {
		$classes = array(
			'notice',
			'notice-' . $message['type'],
		);

		if ( ! empty( $message['dismissible'] ) ) {
			$classes[] = 'is-dismissible';
		}

		if ( ! empty( $message['class'] ) ) {
			$classes[] = $message['class'];
		}

		return $classes;
	}

	/**
	 * Renders a notice when a convertable recipe is detected.
	 *
	 * @return void
	 */
	public static function action_admin_notices() {
		$screen  = get_current_screen();
		$post_id = Utils::get_param( 'post', 'intval', 0 );
		if ( 'post' !== $screen->base || empty( $post_id ) ) {
			return;
		}

		foreach ( self::get_converter_messages( $post_id ) as $message ) {
			if ( 'success' === $message['type'] ) {
				echo '<div class="notice updated is-dismissible"><p>' .
					esc_html( $message['content'] ) .
					'</p></div>';
			} else {
				echo '<div id="' . esc_attr( $message['id'] ) . '" ' .
					'class="' . esc_attr( implode( ' ', self::get_message_notice_classes( $message ) ) ) . '" ' .
					'data-dismiss_action="' . ( ! empty( $message['dismiss_action'] ) ? esc_url( $message['dismiss_action'] ) : '' ) . '">';
				echo '<p>' . esc_html( $message['content'] ) . '</p>';
				echo '<p>';
				foreach ( $message['actions'] as $action ) {
					echo '<a ' .
						'class="button ' . ( ! empty( $action['class'] ) ? esc_attr( $action['class'] ) : '' ) . '" ' .
						'href="' . esc_url( $action['url'] ) . '" ' .
						'data-name="' . ( ! empty( $action['name'] ) ? esc_attr( $action['name'] ) : '' ) . '">' .
						esc_html( $action['label'] ) .
						'</a>&nbsp;';
				}
				echo '</p>';
				echo '</div>';

			}
		}
	}

	/**
	 * Get global ignored conversion types.
	 *
	 * @since 3.8
	 *
	 * @return array
	 */
	private static function get_ignored_conversion_types() {
		return (array) get_option( 'tasty_recipes_ignore_convert_types', array() );
	}

	/**
	 * Check if this post is ignored for this converter.
	 *
	 * @since 3.8
	 *
	 * @param int    $post_id        Current post ID.
	 * @param string $converter_type Converter key to check.
	 *
	 * @return bool
	 */
	private static function is_ignored_post_for_conversion_type( $post_id, $converter_type ) {
		$option = self::get_ignored_conversion_types();
		return (
				! empty( $option )
				&&
				isset( $option[ $converter_type ] )
			)
			||
			get_post_meta( $post_id, 'tasty_recipes_ignore_convert_' . $converter_type, true );
	}

	/**
	 * Get convert Url.
	 *
	 * @since 3.8
	 *
	 * @param WP_Post $post           Current post object.
	 * @param string  $converter_type Converter key.
	 *
	 * @return string
	 */
	private static function get_convert_url( $post, $converter_type ) {
		$query_args = array(
			'action'          => 'tasty_recipes_convert_recipe',
			'nonce'           => wp_create_nonce( 'tasty_recipes_convert_recipe' . $post->ID ),
			'post_id'         => $post->ID,
			'type'            => $converter_type,
			'is_block_editor' => Utils::is_block_editor( $post ), // Convert to a Tasty Recipes block when this post has blocks.
		);
		return add_query_arg( $query_args, admin_url( 'admin-ajax.php' ) );
	}

	/**
	 * Get dismiss convert Url for this post only.
	 *
	 * @since 3.8
	 *
	 * @param int    $post_id        Current post ID.
	 * @param string $converter_type Converter key.
	 *
	 * @return string
	 */
	private static function get_dismiss_convert_url_for_post( $post_id, $converter_type ) {
		return add_query_arg(
			array(
				'action'  => 'tasty_recipes_ignore_convert',
				'nonce'   => wp_create_nonce( 'tasty_recipes_convert_recipe' . $post_id ),
				'post_id' => $post_id,
				'type'    => $converter_type,
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 * Get dismiss convert Url globally for this converter.
	 *
	 * @since 3.8
	 *
	 * @param string $converter_type Converter key.
	 *
	 * @return string
	 */
	private static function get_dismiss_convert_url_for_type( $converter_type ) {
		return add_query_arg(
			array(
				'action'      => 'tasty_recipes_ignore_type_convert',
				'type'        => $converter_type,
				'_ajax_nonce' => wp_create_nonce( 'tasty_recipes_ignore_type_convert' ),
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 * Get revert dismissing convert Url globally for this converter or for this post only.
	 *
	 * @since 3.8
	 *
	 * @param string $converter_type Converter key.
	 * @param int    $post_id        Current post ID.
	 *
	 * @return string
	 */
	private static function get_revert_ignore_url( $converter_type, $post_id ) {
		return add_query_arg(
			array(
				'action'   => 'tasty_recipes_revert_ignore_type_convert',
				'_wpnonce' => wp_create_nonce( 'tasty_recipes_revert_ignore_type_convert' ),
				'type'     => $converter_type,
				'post_id'  => $post_id,
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 * Gets the converter messages for a given post.
	 *
	 * @param int $post_id ID for the post to inspect.
	 *
	 * @return array
	 */
	public static function get_converter_messages( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		$messages = array();
		$content  = $post->post_content;
		// Correct Windows-style line endings.
		$content = str_replace( "\r\n", "\n", $content );
		$content = str_replace( "\r", "\n", $content );
		foreach ( Tasty_Recipes::get_converters() as $key => $config ) {
			$class = $config['class'];
			if ( ! $class::get_existing_to_convert( $content ) ) {
				continue;
			}
			if ( self::is_ignored_post_for_conversion_type( $post_id, $key ) ) {
				continue;
			}

			$messages[] = array(
				'id'             => 'tasty_recipes_convert_' . $key,
				'class'          => 'tasty_recipes_convert_notice',
				'type'           => 'info',
				'content'        => sprintf(
					// translators: Converter label.
					esc_html__( '%s recipe detected. Would you like to convert it to Tasty Recipes?', 'tasty-recipes-lite' ),
					$config['label']
				),
				'actions'        => array(
					array(
						'url'   => self::get_convert_url( $post, $key ),
						'label' => esc_html__( 'Convert', 'tasty-recipes-lite' ),
						'class' => 'button-primary',
						'name'  => 'convert',
					),
					array(
						'url'   => self::get_dismiss_convert_url_for_type( $key ),
						'label' => sprintf(
							// translators: %s name of a plugin.
							esc_html__( 'Do not ask again for %s recipes', 'tasty-recipes-lite' ),
							$config['label']
						),
						'name'  => 'dismiss_converter',
					),
				),
				'dismissible'    => true,
				'dismiss_action' => self::get_dismiss_convert_url_for_post( $post_id, $key ),
			);
		}

		if ( ! empty( $messages ) && ! wp_revisions_enabled( $post ) ) {
			$messages[] = array(
				'id'      => 'tasty_recipes_convert_revisions_warning',
				'type'    => 'warning',
				'content' => esc_html__( 'Warning: Post revisions are not enabled, please back up post content before converting to Tasty Recipes.', 'tasty-recipes-lite' ),
				'actions' => array(
					array(
						'url'   => 'https://www.wptasty.com/convert-single',
						'label' => __( 'Learn more', 'tasty-recipes-lite' ),
						'name'  => 'learn_more',
					),
				),
			);
		}

		$get_tasty_recipes_message = Utils::get_param( 'tasty_recipes_message' );

		if ( ! empty( $get_tasty_recipes_message ) && 'converted_recipe_success' === $get_tasty_recipes_message ) {
			$messages[] = array(
				'type'        => 'success',
				'content'     => esc_html__( 'Successfully migrated the recipe to Tasty Recipes. Enjoy!', 'tasty-recipes-lite' ),
				'dismissible' => true,
			);
		}
		return $messages;
	}

	/**
	 * Whether or not the Tasty Recipes button should display.
	 *
	 * @return bool
	 */
	public static function is_tasty_recipes_editor_view() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return true;
		}
		$screen = get_current_screen();
		if ( empty( $screen ) ) {
			return false;
		}
		return ! in_array( $screen->base, array( 'widgets', 'customize' ), true );
	}

	/**
	 * Registers an 'Add Recipe' button to Media Buttons.
	 *
	 * @param string $editor_id Editor instance to be displayed.
	 *
	 * @return void
	 */
	public static function action_media_buttons( $editor_id ) {

		if ( ! self::is_tasty_recipes_editor_view() ) {
			return;
		}
		if ( ! apply_filters( 'tasty_recipes_add_media_button', true, $editor_id ) ) {
			return;
		}
		?>
		<button type="button" class="button tasty-recipes-add-recipe" data-editor="<?php echo esc_attr( $editor_id ); ?>">
			<span class="wp-media-buttons-icon dashicons dashicons-carrot"></span>
			<?php esc_html_e( 'Add Recipe', 'tasty-recipes-lite' ); ?>
		</button>
		<?php
	}

	/**
	 * Adds a 'Add rel="nofollow" to link' checkbox to the WordPress link editor.
	 *
	 * @deprecated 1.0
	 *
	 * @return void
	 */
	public static function action_after_wp_tiny_mce() {
		_deprecated_function( __METHOD__, '4.0', 'Tasty_Recipes_Pro\Editor::action_after_wp_tiny_mce' );
	}

	/**
	 * Validate the inputs before conversion and return the post to convert.
	 *
	 * @return WP_Post
	 */
	private static function get_current_post_for_conversion_ajax() {
		$nonce   = Utils::get_param( 'nonce' );
		$post_id = Utils::get_param( 'post_id', 'intval', 0 );
		if ( empty( $post_id ) || empty( $nonce ) ) {
			wp_die( esc_html__( "You don't have permission to do this.", 'tasty-recipes-lite' ) );
		}

		$post = get_post( $post_id );
		if ( empty( $post ) ) {
			wp_die( esc_html__( "You don't have permission to do this.", 'tasty-recipes-lite' ) );
		}

		$post_type_object = get_post_type_object( $post->post_type );
		if (
			! current_user_can( $post_type_object->cap->edit_post, $post->ID )
			||
			! wp_verify_nonce( $nonce, 'tasty_recipes_convert_recipe' . $post->ID )
		) {
			wp_die( esc_html__( "You don't have permission to do this.", 'tasty-recipes-lite' ) );
		}

		return $post;
	}

	/**
	 * Handles an AJAX request to convert a recipe.
	 *
	 * @return void
	 */
	public static function handle_wp_ajax_convert_recipe() {
		$post = self::get_current_post_for_conversion_ajax();

		$recipe     = false;
		$type       = ! empty( Utils::get_param( 'is_block_editor' ) ) ? 'block' : 'shortcode';
		$converters = Tasty_Recipes::get_converters();
		$get_type   = Utils::get_param( 'type' );
		if ( isset( $converters[ $get_type ] ) ) {
			$class  = $converters[ $get_type ]['class'];
			$recipe = $class::convert_post( $post->ID, $type );
		}

		if ( ! $recipe ) {
			wp_die( esc_html__( 'Unknown error converting recipe.', 'tasty-recipes-lite' ) );
		}

		$redirect_url = add_query_arg( 'tasty_recipes_message', 'converted_recipe_success', get_edit_post_link( $post->ID, 'raw' ) );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Handles an AJAX request to ignore a recipe conversion.
	 *
	 * @return void
	 */
	public static function handle_wp_ajax_ignore_convert() {
		$post = self::get_current_post_for_conversion_ajax();

		update_post_meta( $post->ID, 'tasty_recipes_ignore_convert_' . Utils::get_param( 'type', 'sanitize_key' ), true );
		status_header( 200 );
		echo 'Done';
		exit;
	}

	/**
	 * Validate the variable and exit with json error when it is empty.
	 *
	 * @since 3.8
	 *
	 * @param string $type Variable you need to check.
	 *
	 * @return void
	 */
	private static function validate_type_input_and_exit( $type = '' ) {
		if ( ! empty( $type ) ) {
			return;
		}

		Utils::send_json_error( __( 'Invalid request', 'tasty-recipes-lite' ) );
	}

	/**
	 * Handles the AJAX request to ignore conversion for a converter globally.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public static function handle_wp_ajax_ignore_type_convert() {
		check_ajax_referer( 'tasty_recipes_ignore_type_convert' );

		$converter_type = Utils::sanitize_get_key( 'type' );

		self::validate_type_input_and_exit( $converter_type );
		Admin::validate_ajax_capability();

		$option                    = self::get_ignored_conversion_types();
		$option[ $converter_type ] = true;
		update_option( 'tasty_recipes_ignore_convert_types', $option, false );
		wp_send_json_success();
	}

	/**
	 * Handles the AJAX request to revert ignoring conversion for a converter globally.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public static function handle_wp_ajax_revert_ignore_type_convert() {
		check_ajax_referer( 'tasty_recipes_revert_ignore_type_convert' );

		$converter_type = Utils::sanitize_get_key( 'type' );

		self::validate_type_input_and_exit( $converter_type );
		Admin::validate_ajax_capability();

		$option = self::get_ignored_conversion_types();

		unset( $option[ $converter_type ] );
		update_option( 'tasty_recipes_ignore_convert_types', $option, false );

		$post_id = Utils::get_param( 'post_id', 'intval' );

		if ( ! empty( $post_id ) ) {
			delete_post_meta( $post_id, 'tasty_recipes_ignore_convert_' . $converter_type );
		}

		wp_safe_redirect( wp_get_referer() );
		exit;
	}

	/**
	 * Handles an AJAX request to render a shortcode with its data.
	 *
	 * @return void
	 */
	public static function handle_wp_ajax_parse_shortcode() {
		$shortcode = Utils::post_param( 'shortcode' );
		$post_id   = Utils::post_param( 'post_id', 'intval', 0 );
		$nonce     = Utils::post_param( 'nonce' );

		if ( empty( $shortcode ) || empty( $post_id ) || empty( $nonce ) ) {
			wp_send_json_error();
		}

		$post = get_post( $post_id );
		if ( empty( $post ) ) {
			wp_send_json_error();
		}

		$post_type_object = get_post_type_object( $post->post_type );
		if (
			! current_user_can( $post_type_object->cap->edit_post, $post->ID )
			||
			! wp_verify_nonce( $nonce, 'tasty_recipes_parse_shortcode' )
		) {
			wp_send_json_error();
		}

		self::render_shortcode_response( $shortcode, $post );
	}

	/**
	 * Handles an AJAX request to modify a recipe.
	 *
	 * @return void
	 */
	public static function handle_wp_ajax_modify_recipe() {
		$post_recipe = Utils::post_param( 'recipe', '' );
		$post_id     = Utils::post_param( 'post_id', 'intval', 0 );
		$nonce       = Utils::post_param( 'nonce' );

		if (
			empty( $post_recipe ) ||
			empty( $nonce ) ||
			! wp_verify_nonce( $nonce, 'tasty_recipes_modify_recipe' ) ||
			! current_user_can( 'edit_posts' )
		) {
			wp_send_json_error();
		}

		$post = false;

		if ( ! empty( $post_id ) ) {
			$post = get_post( $post_id );

			if ( empty( $post ) ) {
				wp_send_json_error();
			}

			// Check if user can edit the parent post.
			$post_type_object = get_post_type_object( $post->post_type );
			if ( ! current_user_can( $post_type_object->cap->edit_post, $post->ID ) ) {
				wp_send_json_error();
			}
		}

		if ( ! empty( $post_recipe['id'] ) ) {
			$recipe_id = (int) $post_recipe['id'];

			// Verify the user can edit this specific recipe (checks ownership for non-Editors).
			if ( ! current_user_can( 'edit_post', $recipe_id ) ) {
				wp_send_json_error();
			}

			$recipe = Recipe::get_by_id( $recipe_id );
		} else {
			$recipe = Recipe::create();
		}

		if ( empty( $recipe ) ) {
			wp_send_json_error(
				array(
					'type'    => 'no-items',
					'message' => __( 'No recipe found.', 'tasty-recipes-lite' ),
				)
			);
		}

		foreach ( Recipe::get_attributes() as $field => $meta ) {
			if ( ! isset( $post_recipe[ $field ] ) ) {
				continue;
			}

			$setter = "set_{$field}";

			$sanitize_callback = ! empty( $meta['sanitize_callback'] ) ? $meta['sanitize_callback'] : 'sanitize_text_field';
			if ( 'intval' === $sanitize_callback && empty( $post_recipe[ $field ] ) ) {
				$data = '';
			} else {
				$data = wp_unslash( $sanitize_callback( $post_recipe[ $field ] ) );
			}

			/**
			 * Permit modification of data before saving it to the database.
			 *
			 * @param mixed  $data  Data to be saved.
			 * @param string $field Field to be saved.
			 */
			$data = apply_filters( 'tasty_recipes_pre_save_editor_data', $data, $field );

			if ( method_exists( $recipe, $setter ) ) {
				$recipe->$setter( $data );
				// Allow saving of any nutrition attributes that have been added.
			} elseif ( in_array( $field, Recipe::get_nutrition_attribute_keys(), true ) ) {
				update_post_meta( $recipe->get_id(), $field, $data );
			}
		}

		/**
		 * Allows Tasty Links to save its own data.
		 *
		 * @param array  $data   Recipe data to save (not sanitized).
		 * @param object $recipe Recipe instance.
		 */
		do_action( 'tasty_recipes_after_save_editor_data', $post_recipe, $recipe );

		$image_id = $recipe->get_image_id();
		if ( $image_id ) {
			Content_Model::generate_attachment_image_sizes( $image_id );
		}

		if ( ! $post ) {
			wp_send_json_success(
				array(
					'recipe' => get_post( $recipe->get_id() ),
				)
			);
		}

		$shortcode = Shortcodes::get_shortcode_for_recipe( $recipe );
		self::render_shortcode_response( $shortcode, $post );
	}

	/**
	 * Renders a shortcode response with its corresponding JSON.
	 *
	 * @param string $shortcode Shortcode string to render.
	 * @param object $post      Post containing the shortcode.
	 *
	 * @return void
	 */
	private static function render_shortcode_response( $shortcode, $post ) {
		setup_postdata( $post );
		$parsed = do_shortcode( $shortcode );

		if ( empty( $parsed ) ) {
			wp_send_json_error(
				array(
					'type'    => 'no-items',
					'message' => __( 'No recipe found.', 'tasty-recipes-lite' ),
				)
			);
		}

		$recipe_json = Tasty_Recipes::get_instance()->recipe_json;
		/**
		 * Permit modification of the recipe JSON before it's returned.
		 *
		 * @param array $recipe_json Existing recipe JSON blob.
		 */
		$recipe_json = apply_filters( 'tasty_recipes_shortcode_response_recipe_json', $recipe_json );
		// These fields are stored without paragraph tags,
		// so they need to be added for TinyMCE compat.
		foreach ( array(
			'description',
			'ingredients',
			'instructions',
			'notes',
		) as $field ) {
			$recipe_json[ $field ] = wpautop( $recipe_json[ $field ] );
		}
		wp_send_json_success(
			array(
				'head'      => '',
				'body'      => $parsed,
				'recipe'    => $recipe_json,
				'shortcode' => $shortcode,
			)
		);
	}

	/**
	 * Get recipe IDs from post if found.
	 *
	 * @since 3.8
	 *
	 * @param WP_Post $post Post object to extract recipes from.
	 *
	 * @return array
	 */
	private static function get_recipe_ids_from_post( $post ) {
		if ( ! self::post_has_recipes( $post ) ) {
			return array();
		}

		return array_unique( Tasty_Recipes::get_recipe_ids_from_content( $post->post_content ) );
	}

	/**
	 * Check if post has recipes.
	 *
	 * @since 3.8
	 *
	 * @param WP_Post $post Post object to check.
	 *
	 * @return bool
	 */
	private static function post_has_recipes( $post ) {
		return has_shortcode( $post->post_content, Shortcodes::RECIPE_SHORTCODE ) || Block_Editor::post_has_recipes( $post );
	}

	/**
	 * Assign parent post ID to recipes.
	 *
	 * @since 3.8
	 *
	 * @param int   $post_ID    Parent post ID.
	 * @param int[] $recipe_ids Array of recipe IDs to be assigned.
	 *
	 * @return void
	 */
	private static function assign_post_to_recipes( $post_ID, $recipe_ids ) {
		foreach ( $recipe_ids as $recipe_id ) {
			$recipe = Recipe::get_by_id( $recipe_id );
			if ( ! $recipe ) {
				continue;
			}
			$recipe->add_parent_post( $post_ID );
		}
	}

	/**
	 * Remove parent post ID from recipes.
	 *
	 * @since 3.8
	 *
	 * @param int   $post_ID    Parent post ID.
	 * @param int[] $recipe_ids Array of recipe IDs to be deassigned.
	 *
	 * @return void
	 */
	private static function deassign_post_to_recipes( $post_ID, $recipe_ids ) {
		foreach ( $recipe_ids as $recipe_id ) {
			$recipe = Recipe::get_by_id( $recipe_id );
			if ( ! $recipe ) {
				continue;
			}
			$recipe->remove_parent_post( $post_ID );
		}
	}

	/**
	 * Handle adding new post with recipes.
	 *
	 * @since 3.8
	 *
	 * @param int     $post_ID Created Post ID.
	 * @param WP_Post $post    Created Post object.
	 * @param bool    $update  True if updated post otherwise false for new posts.
	 *
	 * @return void
	 */
	public static function handle_add_new_post( $post_ID, $post, $update ) {
		if ( $update ) {
			return;
		}

		if ( 'tasty_recipe' === get_post_type( $post_ID ) || wp_is_post_revision( $post_ID ) ) {
			return;
		}

		self::assign_post_to_recipes(
			$post_ID,
			self::get_recipe_ids_from_post( $post )
		);
	}

	/**
	 * Handle edit post with recipes.
	 *
	 * @since 3.8
	 *
	 * @param int     $post_ID     Updated Post ID.
	 * @param WP_Post $post_after  After update post object.
	 * @param WP_Post $post_before Before update post object.
	 *
	 * @return void
	 */
	public static function handle_edit_post( $post_ID, $post_after, $post_before ) {
		if ( 'tasty_recipe' === get_post_type( $post_ID ) || wp_is_post_revision( $post_ID ) ) {
			return;
		}

		if ( $post_after->post_content === $post_before->post_content ) {
			return;
		}

		$recipes_before  = self::get_recipe_ids_from_post( $post_before );
		$recipes_after   = self::get_recipe_ids_from_post( $post_after );
		$removed_recipes = array_diff( $recipes_before, $recipes_after );

		self::assign_post_to_recipes( $post_ID, $recipes_after );
		self::deassign_post_to_recipes( $post_ID, $removed_recipes );
	}

	/**
	 * Get array of dismissed converters to be used in post metabox.
	 *
	 * @since 3.8
	 *
	 * @return array
	 */
	public static function get_dismissed_converters() {
		global $post;

		$post_id = $post ? $post->ID : Utils::get_param( 'post', 'intval', 0 );

		if ( empty( $post_id ) ) {
			return array();
		}

		$all_converters = Tasty_Recipes::get_converters();
		$output         = array();

		foreach ( $all_converters as $converter_key => $converter ) {
			if ( ! self::is_ignored_post_for_conversion_type( $post_id, $converter_key ) ) {
				continue;
			}

			$output[ $converter_key ] = array(
				'title' => $converter['label'],
				'url'   => self::get_revert_ignore_url( $converter_key, $post_id ),
			);
		}

		return $output;
	}

	/**
	 * Handles an AJAX request to dismiss the improved keys notice.
	 * 
	 * @since 1.2
	 *
	 * @return void
	 */
	public static function handle_wp_ajax_dismiss_improved_keys_notice() {
		check_ajax_referer( 'tasty_recipes_modify_recipe', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error();
		}

		update_option( \Tasty_Recipes::IMPROVED_KEYS_NOTICE_DISMISSED_OPTION, true );
		wp_send_json_success();
	}

	/**
	 * Register REST API routes for the editor.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public static function action_rest_api_init() {
		register_rest_route(
			'tasty-recipes/v1',
			'/taxonomy-terms',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'rest_search_taxonomy_terms' ),
					'permission_callback' => array( __CLASS__, 'rest_check_permission' ),
					'args'                => array(
						'taxonomy' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'search'   => array(
							'required'          => false,
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'rest_create_taxonomy_term' ),
					'permission_callback' => array( __CLASS__, 'rest_check_permission' ),
					'args'                => array(
						'taxonomy' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'name'     => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( __CLASS__, 'rest_update_taxonomy_term' ),
					'permission_callback' => array( __CLASS__, 'rest_check_permission' ),
					'args'                => array(
						'taxonomy' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'id'       => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
						'name'     => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( __CLASS__, 'rest_delete_taxonomy_term' ),
					'permission_callback' => array( __CLASS__, 'rest_check_permission' ),
					'args'                => array(
						'taxonomy' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'id'       => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);
	}

	/**
	 * Check REST API permission.
	 *
	 * @since 1.2
	 *
	 * @return bool
	 */
	public static function rest_check_permission() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * REST API callback to create a taxonomy term.
	 *
	 * @since 1.2
	 *
	 * @param object $request The request object.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public static function rest_create_taxonomy_term( $request ) {
		$taxonomy = $request->get_param( 'taxonomy' );
		$name     = $request->get_param( 'name' );

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error(
				'invalid_taxonomy',
				__( 'Invalid taxonomy.', 'tasty-recipes-lite' ),
				array( 'status' => 400 )
			);
		}

		$existing_term = get_term_by( 'name', $name, $taxonomy );
		if ( $existing_term ) {
			return rest_ensure_response(
				array(
					'value'     => $existing_term->name,
					'label'     => $existing_term->name,
					'id'        => $existing_term->term_id,
					'count'     => $existing_term->count,
					'isDefault' => Content_Model::is_default_term( $existing_term->name, $taxonomy ),
				)
			);
		}

		$result = wp_insert_term( $name, $taxonomy );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$term = get_term( $result['term_id'], $taxonomy );

		return rest_ensure_response(
			array(
				'value'     => $term->name,
				'label'     => $term->name,
				'id'        => $term->term_id,
				'count'     => $term->count,
				'isDefault' => Content_Model::is_default_term( $term->name, $taxonomy ),
			)
		);
	}

	/**
	 * REST API callback to update a taxonomy term.
	 *
	 * @since 1.2
	 *
	 * @param object $request The request object.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public static function rest_update_taxonomy_term( $request ) {
		$taxonomy = $request->get_param( 'taxonomy' );
		$id       = $request->get_param( 'id' );
		$name     = $request->get_param( 'name' );

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error(
				'invalid_taxonomy',
				__( 'Invalid taxonomy.', 'tasty-recipes-lite' ),
				array( 'status' => 400 )
			);
		}

		$term = get_term( $id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			return new \WP_Error(
				'invalid_term',
				__( 'Invalid term.', 'tasty-recipes-lite' ),
				array( 'status' => 404 )
			);
		}

		if ( Content_Model::is_default_term( $term->name, $taxonomy ) ) {
			return new \WP_Error(
				'default_term',
				__( 'Cannot edit default terms.', 'tasty-recipes-lite' ),
				array( 'status' => 403 )
			);
		}

		$result = wp_update_term(
			$id,
			$taxonomy,
			array(
				'name' => $name,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$updated_term = get_term( $result['term_id'], $taxonomy );

		return rest_ensure_response(
			array(
				'value'     => $updated_term->name,
				'label'     => $updated_term->name,
				'id'        => $updated_term->term_id,
				'count'     => $updated_term->count,
				'isDefault' => Content_Model::is_default_term( $updated_term->name, $taxonomy ),
			)
		);
	}

	/**
	 * REST API callback to delete a taxonomy term.
	 *
	 * @since 1.2
	 *
	 * @param object $request The request object.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public static function rest_delete_taxonomy_term( $request ) {
		$taxonomy = $request->get_param( 'taxonomy' );
		$id       = $request->get_param( 'id' );

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error(
				'invalid_taxonomy',
				__( 'Invalid taxonomy.', 'tasty-recipes-lite' ),
				array( 'status' => 400 )
			);
		}

		$term = get_term( $id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			return new \WP_Error(
				'invalid_term',
				__( 'Invalid term.', 'tasty-recipes-lite' ),
				array( 'status' => 404 )
			);
		}

		if ( Content_Model::is_default_term( $term->name, $taxonomy ) ) {
			return new \WP_Error(
				'default_term',
				__( 'Cannot delete default terms.', 'tasty-recipes-lite' ),
				array( 'status' => 403 )
			);
		}

		$result = wp_delete_term( $id, $taxonomy );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( true );
	}

	/**
	 * REST API callback to search taxonomy terms.
	 *
	 * @since 1.2
	 *
	 * @param object $request The request object.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public static function rest_search_taxonomy_terms( $request ) {
		$taxonomy = $request->get_param( 'taxonomy' );
		$search   = $request->get_param( 'search' );

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error(
				'invalid_taxonomy',
				__( 'Invalid taxonomy.', 'tasty-recipes-lite' ),
				array( 'status' => 400 )
			);
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'search'     => $search,
				'number'     => 20,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		$default_terms = Content_Model::get_default_taxonomy_terms();
		$defaults      = $default_terms[ $taxonomy ] ?? array();

		$default_options = array();
		$custom_options  = array();

		foreach ( $terms as $term ) {
			$option = array(
				'value'     => $term->name,
				'label'     => $term->name,
				'id'        => $term->term_id,
				'count'     => $term->count,
				'isDefault' => in_array( $term->name, $defaults, true ),
			);

			if ( $option['isDefault'] ) {
				$default_options[] = $option;
			} else {
				$custom_options[] = $option;
			}
		}

		$result = array();

		if ( ! empty( $default_options ) ) {
			$result[] = array(
				'label'   => '',
				'options' => $default_options,
			);
		}

		if ( ! empty( $custom_options ) ) {
			$result[] = array(
				'label'   => __( 'Created by you', 'tasty-recipes-lite' ),
				'options' => $custom_options,
			);
		}

		return rest_ensure_response( $result );
	}
}
