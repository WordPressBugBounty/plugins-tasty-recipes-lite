<?php
/**
 * Integrates Tasty Recipes with Akismet.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Integrations;

use Tasty_Recipes\Ratings;
use Tasty_Recipes\Utils;

/**
 * Integrates Tasty Recipes with Akismet.
 */
class Akismet {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Temp rating comment hash.
	 *
	 * @var string
	 */
	private $temp_rating_comment_hash;

	/**
	 * Get singleton instance.
	 *
	 * @return self|null
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->add_hooks();
		}
		return self::$instance;
	}

	/**
	 * Check if the Akismet plugin is active or not.
	 *
	 * @return bool
	 */
	private function is_active() {
		return defined( 'AKISMET_VERSION' );
	}

	/**
	 * Register integration hooks.
	 *
	 * @return void
	 */
	public function add_hooks() {
		if ( ! $this->is_active() ) {
			return;
		}

		add_filter( 'preprocess_comment', array( $this, 'before_akismet_preprocess_comment' ), 0 );
		add_filter( 'preprocess_comment', array( $this, 'after_akismet_preprocess_comment' ), 2 );
	}

	/**
	 * Change the comment content not to be empty before akismet preprocess comment.
	 *
	 * @param array $comment_data Comment details.
	 *
	 * @return array
	 */
	public function before_akismet_preprocess_comment( $comment_data ) {
		if ( ! isset( $comment_data['comment_meta'] ) || ! isset( $comment_data['comment_meta'][ Ratings::COMMENT_META_KEY ] ) ) {
			return $comment_data;
		}

		$min_rating = Ratings::get_minimum_rating_without_comment( $comment_data );
		$rating     = (int) $comment_data['comment_meta'][ Ratings::COMMENT_META_KEY ];

		if ( $rating >= $min_rating && empty( $comment_data['comment_content'] ) ) {
			$comment_data['comment_content'] = 'Rating ' . $rating;
			$this->temp_rating_comment_hash  = md5( $comment_data['comment_content'] );
		}

		return $comment_data;
	}

	/**
	 * Change the comment content to its original value (empty) After akismet preprocess comment.
	 *
	 * @param array $comment_data Comment details.
	 *
	 * @return array
	 */
	public function after_akismet_preprocess_comment( $comment_data ) {
		if ( empty( $this->temp_rating_comment_hash ) ) {
			return $comment_data;
		}

		$comment_hash = md5( $comment_data['comment_content'] );
		if ( $comment_hash === $this->temp_rating_comment_hash ) {
			$comment_data['comment_content'] = '';
		}

		return $comment_data;
	}
}
