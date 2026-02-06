<?php
/**
 * Manage Tasty Recipes from WP-CLI.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

use Tasty_Recipes;
use Tasty_Recipes\Utils;
use WP_CLI;
use WP_Comment_Query;

/**
 * Manage Tasty Recipes from WP-CLI.
 */
class CLI {

	/**
	 * Recalculates recipe ratings.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : Run against specific post ids.
	 *
	 * @subcommand recalculate-recipe-ratings
	 *
	 * @param array $args Unnamed positional arguments.
	 *
	 * @return void
	 */
	public function recalculate_recipe_ratings( $args ) {

		if ( ! empty( $args ) ) {
			$posts = array_map( 'get_post', $args );
		} else {
			$query = new \WP_Query(
				array(
					'posts_per_page'         => -1,
					'post_status'            => 'publish',
					'post_type'              => 'any',
					'no_found_rows'          => true,
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,
				)
			);
			$posts = $query->posts;
		}

		foreach ( $posts as $post ) {
			// Only process when there is one embedded recipe in a post.
			$recipes = Tasty_Recipes::get_recipes_for_post( $post->ID );
			if ( empty( $recipes ) ) {
				WP_CLI::log( sprintf( 'No recipes found in %s (%d), skipping.', $post->post_title, $post->ID ) );
				continue;
			}
			if ( 1 !== count( $recipes ) ) {
				WP_CLI::log( sprintf( 'Too many recipes found in %s (%d), skipping.', $post->post_title, $post->ID ) );
				continue;
			}

			$recipe = reset( $recipes );
			\Tasty_Recipes\Ratings::update_recipe_rating( $recipe, $post->ID );
			WP_CLI::log( sprintf( 'Recalculating recipe rating for %s (%d)', $post->post_title, $post->ID ) );
		}

		WP_CLI::success( 'Process complete.' );
	}

	/**
	 * Remove ratings stored directly on a recipe record.
	 *
	 * ## OPTIONS
	 *
	 * [--post_id=<post_id>]
	 * : The recipe post ID.
	 *
	 * [--origin=<origin>]
	 * : Where the ratings originated. "srp" is currently the only valid value.
	 *
	 * [--action=<action>]
	 * : Whether the recipe ratings should be archived or deleted. Default is archive.
	 *
	 * @subcommand remove-recipe-ratings
	 *
	 * @param array $_          Unnamed positional arguments.
	 * @param array $assoc_args Arguments passed to the command.
	 *
	 * @return void
	 */
	public function remove_recipe_ratings( $_, $assoc_args ) {
		if ( ! empty( $assoc_args['post_id'] ) ) {
			$post_id = (int) $assoc_args['post_id'];
		} else {
			WP_CLI::error( 'Please specify a post ID' );
		}

		if ( empty( $assoc_args['origin'] ) || 'srp' !== $assoc_args['origin'] ) {
			WP_CLI::error( 'Simple Recipes Pro (srp) is the only supported origin.' );
		}

		if ( ! empty( $assoc_args['action'] ) && in_array( $assoc_args['action'], array( 'archive', 'delete' ), true ) ) {
			$action = $assoc_args['action'];
		} elseif ( ! empty( $assoc_args['action'] ) ) {
			WP_CLI::error( 'Invalid action specified' );
		} else {
			$action = 'archive';
		}

		// Retrieve the old SRP ratings data on the post.
		$rating = get_post_meta( $post_id, 'srp_ratings', true );

		// If the data should be archived, store it under a different meta key.
		if ( $rating && 'archive' === $action ) {
			update_post_meta( $post_id, 'archive_srp_ratings', $rating );
		}

		delete_post_meta( $post_id, 'srp_ratings' );

		WP_CLI::success( 'Recipe ratings data removed on recipe.' );
	}

	/**
	 * Remove ratings stored on a post with which a recipe is associated and its comments.
	 *
	 * ## OPTIONS
	 *
	 * [--post_id=<post_id>]
	 * : The post ID.
	 *
	 * [--origin=<origin>]
	 * : Where the ratings originated. "srp" is currently the only valid value.
	 *
	 * [--action=<action>]
	 * : Whether the recipe ratings should be archived or deleted. Default is archive.
	 *
	 * @subcommand remove-post-ratings
	 *
	 * @param array $_          Unnamed positional arguments.
	 * @param array $assoc_args Arguments passed to the command.
	 *
	 * @return void
	 */
	public function remove_post_ratings( $_, $assoc_args ) {
		global $wpdb;

		if ( ! empty( $assoc_args['post_id'] ) ) {
			$post_id = (int) $assoc_args['post_id'];
		} else {
			WP_CLI::error( 'Please specify a post ID' );
		}

		if ( empty( $assoc_args['origin'] ) || 'srp' !== $assoc_args['origin'] ) {
			WP_CLI::error( 'Simple Recipes Pro (srp) is the only supported origin.' );
		}

		if ( ! empty( $assoc_args['action'] ) && in_array( $assoc_args['action'], array( 'archive', 'delete' ), true ) ) {
			$action = $assoc_args['action'];
		} elseif ( ! empty( $assoc_args['action'] ) ) {
			WP_CLI::error( 'Invalid action specified' );
		} else {
			$action = 'archive';
		}

		// Retrieve the old SRP ratings data on the post.
		$rating = get_post_meta( $post_id, '_ratings', true );

		// If the data should be archived, store it under a different meta key.
		if ( $rating && 'archive' === $action ) {
			update_post_meta( $post_id, 'archive_ratings', $rating );
		}

		delete_post_meta( $post_id, '_ratings' );

		$comment_query = new WP_Comment_Query(
			array(
				'post_id'    => $post_id,
				'fields'     => 'ids',
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'recipe_rating',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		foreach ( $comment_query->comments as $comment ) {
			if ( 'delete' === $action ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM $wpdb->commentmeta WHERE comment_id = %d AND meta_key = 'recipe_rating'",
						$comment
					)
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE $wpdb->commentmeta SET meta_key = 'archive_recipe_rating' WHERE comment_id = %d AND meta_key = 'recipe_rating'",
						$comment
					)
				);
			}
		}

		WP_CLI::success( 'Recipe ratings data removed on post and comments.' );
	}

	/**
	 * Provide a list of recipes attached to specified posts.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : Run against specific post ids.
	 *
	 * [--format=<format>]
	 * : Output format for the results.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - count
	 * ---
	 *
	 * @subcommand get-post-recipes
	 *
	 * @param array $args       Unnamed positional arguments.
	 * @param array $assoc_args Arguments passed to the command.
	 *
	 * @return void
	 */
	public function get_post_recipes( $args, $assoc_args ) {

		if ( isset( $args ) ) {
			$posts = array_map( 'get_post', $args );
		} else {
			WP_CLI::error( 'Please specify a post ID.' );
		}

		$results = array();

		foreach ( $posts as $post ) {
			$recipes = Tasty_Recipes::get_recipes_for_post( $post->ID );

			foreach ( $recipes as $recipe ) {
				$results[] = array(
					'post_id'             => $post->ID,
					'post_title'          => $post->post_title,
					'tasty_recipes_id'    => $recipe->get_id(),
					'tasty_recipes_title' => $recipe->get_title(),
				);
			}
		}

		$headers = array();
		if ( ! empty( $results ) ) {
			$headers = array_keys( $results[0] );
		}

		WP_CLI\Utils\format_items( $assoc_args['format'], $results, $headers );
	}

	/**
	 * Prepares a CSV of video URLs from legacy WPRM data.
	 *
	 * ## OPTIONS
	 *
	 * [--database=<database>]
	 * : Name of the database to connect to, if needed.
	 *
	 * [--format=<format>]
	 * : Output format for the results.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - count
	 * ---
	 *
	 * @subcommand find-legacy-wprm-video-urls
	 *
	 * @param array $_          Unnamed positional arguments.
	 * @param array $assoc_args Arguments passed to the command.
	 *
	 * @return void
	 */
	public function find_legacy_wprm_video_urls( $_, $assoc_args ) { // phpcs:ignore SlevomatCodingStandard

		if ( ! empty( $assoc_args['database'] ) ) {
			$db = new \wpdb( DB_USER, DB_PASSWORD, $assoc_args['database'], DB_HOST );
			$db->set_prefix( 'wp_' );
		} else {
			$db = $GLOBALS['wpdb'];
		}

		$recipes = $db->get_results(
			"SELECT ID,post_title FROM {$db->posts} WHERE post_type='tasty_recipe'"
		);

		$results = array();
		foreach ( $recipes as $recipe ) {
			// Assume empty title isn't a valid recipe.
			if ( empty( $recipe->post_title ) ) {
				continue;
			}
			$existing = $db->get_var(
				$db->prepare(
					"SELECT meta_value FROM {$db->postmeta} WHERE meta_key='video_url' AND post_id=%d",
					$recipe->ID
				)
			);
			if ( $existing ) {
				continue;
			}

			$post_id           = null;
			$post_url          = null;
			$wprm_recipe_id    = null;
			$wprm_recipe_title = null;
			$match             = null;
			$video_url         = null;

			// Find the original post.
			$maybe_posts = $db->get_results(
				$db->prepare(
					"SELECT ID, post_title FROM {$db->posts} WHERE post_type='post' AND post_content LIKE %s",
					'%' . $recipe->ID . '%'
				)
			);
			if ( count( $maybe_posts ) === 1 ) {
				$post_id  = $maybe_posts[0]->ID;
				$post_url = sprintf(
					'https://www.fooddolls.com/?p=%d',
					$post_id
				);
			}

			// Join based on the recipe title.
			$maybe_wprms = $db->get_results(
				$db->prepare(
					"SELECT ID, post_title FROM {$db->posts} WHERE post_title=%s AND post_type='wprm_recipe'",
					$recipe->post_title
				)
			);
			if ( count( $maybe_wprms ) === 1 ) {
				$wprm_recipe_id    = $maybe_wprms[0]->ID;
				$wprm_recipe_title = $maybe_wprms[0]->post_title;
				$match             = 'title';
			}

			// Join based on a shared thumbnail.
			if ( ! $wprm_recipe_id ) {
				$thumbnail_id = $db->get_var(
					$db->prepare(
						"SELECT meta_value FROM {$db->postmeta} WHERE meta_key='_thumbnail_id' AND post_id=%d",
						$recipe->ID
					)
				);
				if ( $thumbnail_id ) {
					$maybe_wprms = $db->get_results(
						$db->prepare(
							"SELECT p.ID, p.post_title FROM {$db->posts} as p
							LEFT JOIN {$db->postmeta} as pm ON p.ID=pm.post_id
							WHERE p.post_type='wprm_recipe' AND pm.meta_key='_thumbnail_id' AND pm.meta_value=%s",
							$thumbnail_id
						)
					);
					if ( count( $maybe_wprms ) === 1 ) {
						$wprm_recipe_id    = $maybe_wprms[0]->ID;
						$wprm_recipe_title = $maybe_wprms[0]->post_title;
						$match             = 'thumbnail';
					}
				}
			}

			if ( $wprm_recipe_id ) {
				$video_url = $db->get_var(
					$db->prepare(
						"SELECT meta_value FROM {$db->postmeta} WHERE meta_key='wprm_video_embed' AND post_id=%d",
						$wprm_recipe_id
					)
				);
				if ( false !== stripos( $video_url, '<iframe' ) ) {
					$src = Utils::get_element_attribute( $video_url, 'iframe', 'src' );
					if ( $src ) {
						$youtube_id = Utils::get_youtube_id( $src );
						if ( $youtube_id ) {
							$video_url = sprintf(
								'https://www.youtube.com/watch?v=%s',
								$youtube_id
							);
						} else {
							$video_url = $src;
						}
					}
				}
			}

			$results[] = array(
				'post_id'             => $post_id,
				'post_url'            => $post_url,
				'tasty_recipes_id'    => $recipe->ID,
				'tasty_recipes_title' => $recipe->post_title,
				'match'               => $match,
				'wprm_recipe_id'      => $wprm_recipe_id,
				'wprm_recipe_title'   => $wprm_recipe_title,
				'video_url'           => $video_url,
			);
		}

		$headers = array();
		if ( ! empty( $results ) ) {
			$headers = array_keys( $results[0] );
		}
		WP_CLI\Utils\format_items( $assoc_args['format'], $results, $headers );
	}
}
