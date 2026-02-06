<?php
/**
 * Utility class deals with displaying time differences in a human readable format.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Utils;

/**
 * Utility class deals with human time diff.
 */
class HumanTimeDiff {

	/**
	 * Gets the time ago in words.
	 *
	 * @param int        $from   In seconds.
	 * @param int|string $to     In seconds.
	 * @param int        $levels Number of time units to show.
	 *
	 * @return string $time_ago
	 */
	public static function get( $from, $to = '', $levels = 1 ) {
		if ( empty( $to ) && 0 !== $to ) {
			$now = new \DateTime();
		} else {
			$now = new \DateTime( '@' . $to );
		}
		$ago = new \DateTime( '@' . $from );

		// Get the time difference.
		$diff_object = $now->diff( $ago );
		$diff        = get_object_vars( $diff_object );

		// Add week amount and update day amount.
		$diff['w']  = floor( $diff['d'] / 7 );
		$diff['d'] -= $diff['w'] * 7;

		$time_strings = self::get_time_strings();

		if ( ! is_numeric( $levels ) ) {
			// Show time in specified unit.
			$levels = self::get_unit( $levels );
			if ( isset( $time_strings[ $levels ] ) ) {
				$diff         = array(
					$levels => self::time_format( $levels, $diff ),
				);
				$time_strings = array(
					$levels => $time_strings[ $levels ],
				);
			}
			$levels = 1;
		}

		foreach ( $time_strings as $key => $time_string ) {
			if ( ! empty( $diff[ $key ] ) ) {
				$time_strings[ $key ] = $diff[ $key ] . ' ' . ( $diff[ $key ] > 1 ? $time_string[1] : $time_string[0] );
			} elseif ( isset( $diff[ $key ] ) && count( $time_strings ) === 1 ) {
				// Account for 0.
				$time_strings[ $key ] = $diff[ $key ] . ' ' . $time_string[1];
			} else {
				unset( $time_strings[ $key ] );
			}
		}

		$levels_deep  = $levels;
		$time_strings = array_slice( $time_strings, 0, absint( $levels_deep ) );
		return implode( ' ', $time_strings );
	}

	/**
	 * Get unit.
	 *
	 * @param string $unit Unit.
	 *
	 * @return int|string
	 */
	private static function get_unit( $unit ) {
		$units = self::get_time_strings();
		if ( isset( $units[ $unit ] ) || is_numeric( $unit ) ) {
			return $unit;
		}

		foreach ( $units as $key => $strings ) {
			if ( in_array( $unit, $strings, true ) ) {
				return $key;
			}
		}
		return 1;
	}

	/**
	 * Get time format.
	 *
	 * @param string $unit Unit.
	 * @param array  $diff Time difference.
	 *
	 * @return float|string
	 */
	private static function time_format( $unit, $diff ) {
		$return = array(
			'y' => 'y',
			'd' => 'days',
		);
		if ( isset( $return[ $unit ] ) ) {
			return $diff[ $return[ $unit ] ];
		}

		$total = $diff['days'] * self::convert_time( 'd', $unit );

		$times = array( 'h', 'i', 's' );

		foreach ( $times as $time ) {
			if ( ! isset( $diff[ $time ] ) ) {
				continue;
			}

			$total += $diff[ $time ] * self::convert_time( $time, $unit );
		}

		return floor( $total );
	}

	/**
	 * Convert time.
	 *
	 * @param string $from From unit.
	 * @param string $to   To unit.
	 *
	 * @return float
	 */
	private static function convert_time( $from, $to ) {
		$convert = array(
			's' => 1,
			'i' => MINUTE_IN_SECONDS,
			'h' => HOUR_IN_SECONDS,
			'd' => DAY_IN_SECONDS,
			'w' => WEEK_IN_SECONDS,
			'm' => DAY_IN_SECONDS * 30.42,
			'y' => DAY_IN_SECONDS * 365.25,
		);

		return $convert[ $from ] / $convert[ $to ];
	}

	/**
	 * Get time strings.
	 *
	 * @return array
	 */
	private static function get_time_strings() {
		return array(
			'y' => array(
				__( 'year', 'tasty-recipes-lite' ),
				__( 'years', 'tasty-recipes-lite' ),
				'year',
			),
			'm' => array(
				__( 'month', 'tasty-recipes-lite' ),
				__( 'months', 'tasty-recipes-lite' ),
				'month',
			),
			'w' => array(
				__( 'week', 'tasty-recipes-lite' ),
				__( 'weeks', 'tasty-recipes-lite' ),
				'week',
			),
			'd' => array(
				__( 'day', 'tasty-recipes-lite' ),
				__( 'days', 'tasty-recipes-lite' ),
				'day',
			),
			'h' => array(
				__( 'hour', 'tasty-recipes-lite' ),
				__( 'hours', 'tasty-recipes-lite' ),
				'hour',
			),
			'i' => array(
				__( 'minute', 'tasty-recipes-lite' ),
				__( 'minutes', 'tasty-recipes-lite' ),
				'minute',
			),
			's' => array(
				__( 'second', 'tasty-recipes-lite' ),
				__( 'seconds', 'tasty-recipes-lite' ),
				'second',
			),
		);
	}
}
