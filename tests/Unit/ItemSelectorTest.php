<?php
/**
 * Tests for the item selection priority.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Tests\Unit;

use Peyvanda\Matching\ItemSelector;
use PHPUnit\Framework\TestCase;

final class ItemSelectorTest extends TestCase {

	private static function all_valid() {
		return static function () {
			return true;
		};
	}

	private static function fallback( array $ids, &$called = null ) {
		return static function () use ( $ids, &$called ) {
			$called = true;
			return $ids;
		};
	}

	public function test_manual_items_come_first_in_order() {
		$ids = ItemSelector::select( array( 9, 3 ), array( 1, 2 ), self::fallback( array() ), array(), 4, true, self::all_valid() );
		$this->assertSame( array( 9, 3, 1, 2 ), $ids );
	}

	public function test_manual_only_when_fill_disabled() {
		$ids = ItemSelector::select( array( 9 ), array( 1, 2 ), self::fallback( array( 5 ) ), array(), 3, false, self::all_valid() );
		$this->assertSame( array( 9 ), $ids );
	}

	public function test_fill_disabled_without_manual_still_uses_automatic() {
		$ids = ItemSelector::select( array(), array( 1, 2 ), self::fallback( array() ), array(), 3, false, self::all_valid() );
		$this->assertSame( array( 1, 2 ), $ids );
	}

	public function test_current_post_is_never_shown() {
		$ids = ItemSelector::select( array( 7 ), array( 7, 1 ), self::fallback( array( 7, 2 ) ), array( 7 ), 3, true, self::all_valid() );
		$this->assertSame( array( 1, 2 ), $ids );
	}

	public function test_duplicates_are_removed() {
		$ids = ItemSelector::select( array( 1 ), array( 1, 2, 2 ), self::fallback( array( 2, 3 ) ), array(), 5, true, self::all_valid() );
		$this->assertSame( array( 1, 2, 3 ), $ids );
	}

	public function test_invalid_items_are_skipped() {
		$valid = static function ( $id ) {
			return 4 !== $id;
		};
		$ids   = ItemSelector::select( array( 4, 5 ), array( 6 ), self::fallback( array() ), array(), 3, true, $valid );
		$this->assertSame( array( 5, 6 ), $ids );
	}

	public function test_fallback_is_lazy() {
		$called = false;
		ItemSelector::select( array(), array( 1, 2, 3 ), self::fallback( array( 4 ), $called ), array(), 3, true, self::all_valid() );
		$this->assertFalse( $called );
	}

	public function test_fallback_fills_remaining_slots() {
		$ids = ItemSelector::select( array(), array( 1 ), self::fallback( array( 8, 9, 10 ) ), array(), 3, true, self::all_valid() );
		$this->assertSame( array( 1, 8, 9 ), $ids );
	}

	public function test_count_is_respected() {
		$ids = ItemSelector::select( array( 1, 2, 3 ), array( 4 ), self::fallback( array() ), array(), 2, true, self::all_valid() );
		$this->assertSame( array( 1, 2 ), $ids );
	}

	public function test_non_positive_ids_are_ignored() {
		$ids = ItemSelector::select( array( 0, -3, '5' ), array(), self::fallback( array() ), array(), 3, true, self::all_valid() );
		$this->assertSame( array( 5 ), $ids );
	}
}
