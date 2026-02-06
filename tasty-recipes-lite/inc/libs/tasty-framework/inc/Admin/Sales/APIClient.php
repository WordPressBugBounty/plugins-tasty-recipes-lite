<?php
/**
 * Sales API Client class.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Admin\Sales;

use Tasty\Framework\Abstracts\APIClient as AbstractAPIClient;
use Tasty\Framework\Traits\Singleton;
use Tasty\Framework\Traits\Who;
use Tasty\Framework\Utils\Vars;
use WP_Error;

/**
 * Sales API Client class.
 */
class APIClient extends AbstractAPIClient {

	use Singleton;
	use Who;

	/**
	 * Best sale.
	 *
	 * @var array|false|null
	 */
	private static $best_sale;

	/**
	 * Get the best sale.
	 *
	 * @return array|false
	 */
	public function get_best_sale() {
		if ( isset( self::$best_sale ) ) {
			return self::$best_sale;
		}

		$sales = $this->get_sales();
		if ( is_wp_error( $sales ) ) {
			self::$best_sale = false;
			return false;
		}

		$best_sale = false;
		foreach ( $sales as $sale ) {
			if ( ! $best_sale || $sale['discount_percent'] > $best_sale['discount_percent'] ) {
				$best_sale = $sale;
			}
		}

		self::$best_sale = $best_sale;
		return $best_sale;
	}

	/**
	 * Get text for best sale if applicable.
	 *
	 * @param string $key Key to get from the best sale.
	 *
	 * @return string|null Null if no sale is active, or the sale value is not truthy.
	 */
	public function get_best_sale_value( $key ) {
		$best_sale = $this->get_best_sale();
		return is_array( $best_sale ) && ! empty( $best_sale[ $key ] ) ? $best_sale[ $key ] : null;
	}

	/**
	 * Get sales (Raw request).
	 *
	 * @return array|WP_Error
	 */
	private function get_sales() {
		$transient_name      = 'tasty_sales';
		$this->response_body = get_transient( $transient_name );

		if ( false === $this->response_body ) {
			$success = $this->handle_get( '/wp-json/s11-sales/v1/list/' );
			if ( ! $success ) {
				return new WP_Error( 500, $this->error_message );
			}
			set_transient( $transient_name, $this->response_body, 3 * HOUR_IN_SECONDS );
		}

		$sales = array_filter(
			$this->response_body,
			function ( $sale ) {
				return $this->should_include_sale( $sale );
			}
		);
		if ( ! empty( $sales ) ) {
			return $sales;
		}

		$this->error_message = __( 'No sales found.', 'tasty-recipes-lite' );

		return new WP_Error( 500, $this->error_message );
	}

	/**
	 * Check if a sale should be included in the current context.
	 *
	 * @param array $sale The sale to check.
	 *
	 * @return bool
	 */
	public function should_include_sale( $sale ) {
		if ( empty( $sale['key'] ) || ! $this->is_in_correct_timeframe( $sale ) || ! $this->matches_ab_group( $sale ) ) {
			return false;
		}

		return empty( $sale['who'] ) || $this->matches_who( $sale['who'] );
	}

	/**
	 * Check if a sale is in the correct timeframe.
	 *
	 * @param array $sale The sale to check.
	 *
	 * @return bool
	 */
	private function is_in_correct_timeframe( $sale ) {
		if ( ! empty( $sale['expires'] ) && $sale['expires'] + DAY_IN_SECONDS < time() ) {
			return false;
		}

		return empty( $sale['starts'] ) || $sale['starts'] < time();
	}

	/**
	 * Check if a sale is a match for the applicable group (if one is defined).
	 *
	 * @param array $sale The sale to check.
	 *
	 * @return bool True if the sale is a match for the applicable group (if one is defined).
	 */
	private function matches_ab_group( $sale ) {
		if ( ! is_numeric( $sale['test_group'] ) ) {
			// No test group, so return true.
			return true;
		}

		$ab_group = $this->get_ab_group_for_current_site();
		return $ab_group === $sale['test_group'];
	}

	/**
	 * Get the AB group for the current site.
	 *
	 * @return int 1 or 0.
	 */
	private function get_ab_group_for_current_site() {
		$option_name = 'tasty_sale_ab_group';
		$option      = get_option( $option_name );
		if ( ! is_numeric( $option ) ) {
			// Generate either 0 or 1.
			$option = wp_rand( 0, 1 );
			update_option( $option_name, $option, false );
		}
		return (int) $option;
	}
}
