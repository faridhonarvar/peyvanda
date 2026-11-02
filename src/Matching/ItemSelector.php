<?php
/**
 * Pure item-selection logic (no WordPress calls, unit tested).
 *
 * @package Peyvanda
 */

namespace Peyvanda\Matching;

defined( 'ABSPATH' ) || exit;

/**
 * Applies the priority order:
 * 1. manual picks, 2. automatic matches, 3. fallback.
 */
final class ItemSelector {

	/**
	 * Selects the final ordered list of item IDs.
	 *
	 * @param int[]    $manual       Manually picked IDs (absolute priority).
	 * @param int[]    $automatic    Automatically matched IDs (rules + taxonomy matches).
	 * @param callable $fallback     Returns fallback IDs; only called when still short of items.
	 * @param int[]    $exclude      IDs that must never be shown (e.g. the current post).
	 * @param int      $count        Number of items wanted.
	 * @param bool     $fill_manual  When manual picks exist, fill the remaining slots automatically.
	 * @param callable $is_valid     Receives an ID, returns whether it can be shown (published, in stock, …).
	 * @return int[]
	 */
	public static function select( array $manual, array $automatic, callable $fallback, array $exclude, $count, $fill_manual, callable $is_valid ) {
		$count    = max( 0, (int) $count );
		$excluded = array_flip( array_map( 'intval', $exclude ) );
		$selected = array();

		$take = static function ( array $ids ) use ( &$selected, &$excluded, $count, $is_valid ) {
			foreach ( $ids as $id ) {
				if ( count( $selected ) >= $count ) {
					return;
				}
				$id = (int) $id;
				if ( $id <= 0 || isset( $excluded[ $id ] ) ) {
					continue;
				}
				$excluded[ $id ] = true;
				if ( $is_valid( $id ) ) {
					$selected[] = $id;
				}
			}
		};

		$take( $manual );

		if ( $selected && ! $fill_manual ) {
			return $selected;
		}

		$take( $automatic );

		if ( count( $selected ) < $count ) {
			$take( (array) $fallback() );
		}

		return $selected;
	}
}
