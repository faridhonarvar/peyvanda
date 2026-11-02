<?php
/**
 * Tests for insertion after paragraph N.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Tests\Unit;

use Peyvanda\Placement\ParagraphInserter;
use PHPUnit\Framework\TestCase;

final class ParagraphInserterTest extends TestCase {

	const BOX = '<aside class="pvd-box">BOX</aside>';

	private static function insert( $html, $n, $append = true ) {
		return str_replace( "\n", '', ParagraphInserter::insert( $html, self::BOX, $n, $append ) );
	}

	public function test_inserts_after_nth_paragraph() {
		$html = '<p>One</p><p>Two</p><p>Three</p>';
		$this->assertSame( '<p>One</p><p>Two</p>' . self::BOX . '<p>Three</p>', self::insert( $html, 2 ) );
	}

	public function test_paragraph_with_attributes_and_inline_markup() {
		$html = '<p class="a">One <a href="#">link</a></p><p>Two</p>';
		$this->assertSame( '<p class="a">One <a href="#">link</a></p>' . self::BOX . '<p>Two</p>', self::insert( $html, 1 ) );
	}

	public function test_skips_paragraphs_inside_blockquote() {
		$html = '<p>One</p><blockquote><p>Quote</p><p>Quote 2</p></blockquote><p>Two</p><p>Three</p>';
		$this->assertSame( '<p>One</p><blockquote><p>Quote</p><p>Quote 2</p></blockquote><p>Two</p>' . self::BOX . '<p>Three</p>', self::insert( $html, 2 ) );
	}

	public function test_skips_paragraphs_inside_tables_lists_and_code() {
		$html = '<table><tr><td><p>Cell</p></td></tr></table><ul><li><p>Item</p></li></ul><pre><code>&lt;p&gt;x&lt;/p&gt;</code></pre><p>One</p>';
		$this->assertSame( $html . self::BOX, self::insert( $html, 1 ) );
	}

	public function test_skips_nested_forbidden_containers() {
		$html = '<blockquote><ul><li><p>a</p></li></ul><p>b</p></blockquote><p>One</p><p>Two</p>';
		$this->assertSame( '<blockquote><ul><li><p>a</p></li></ul><p>b</p></blockquote><p>One</p>' . self::BOX . '<p>Two</p>', self::insert( $html, 1 ) );
	}

	public function test_skips_paragraphs_inside_block_columns_but_not_plain_groups() {
		$html = '<div class="wp-block-columns"><div class="wp-block-column"><p>Col</p></div></div><div class="wp-block-group"><p>One</p></div><p>Two</p>';
		$this->assertSame( '<div class="wp-block-columns"><div class="wp-block-column"><p>Col</p></div></div><div class="wp-block-group"><p>One</p>' . self::BOX . '</div><p>Two</p>', self::insert( $html, 1 ) );
	}

	public function test_ignores_paragraph_tags_inside_comments_and_scripts() {
		$html = '<!-- <p>x</p> --><script>var a = "<p>y</p>";</script><p>One</p><p>Two</p>';
		$this->assertSame( '<!-- <p>x</p> --><script>var a = "<p>y</p>";</script><p>One</p>' . self::BOX . '<p>Two</p>', self::insert( $html, 1 ) );
	}

	public function test_short_content_appends_at_end() {
		$html = '<p>One</p>';
		$this->assertSame( '<p>One</p>' . self::BOX, self::insert( $html, 3 ) );
	}

	public function test_short_content_can_be_skipped() {
		$html = '<p>One</p>';
		$this->assertSame( $html, self::insert( $html, 3, false ) );
	}

	public function test_handles_persian_multibyte_text() {
		$html = '<p>سلام دنیا</p><p>دوم</p>';
		$this->assertSame( '<p>سلام دنیا</p>' . self::BOX . '<p>دوم</p>', self::insert( $html, 1 ) );
	}

	public function test_uppercase_tags() {
		$html = '<P>One</P><P>Two</P>';
		$this->assertSame( '<P>One</P>' . self::BOX . '<P>Two</P>', self::insert( $html, 1 ) );
	}

	public function test_n_below_one_is_treated_as_one() {
		$this->assertSame( '<p>a</p>' . self::BOX . '<p>b</p>', self::insert( '<p>a</p><p>b</p>', 0 ) );
	}

	public function test_empty_content() {
		$this->assertSame( self::BOX, self::insert( '', 2 ) );
	}
}
