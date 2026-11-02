<?php
/**
 * Inserts HTML after the Nth top-level paragraph.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Placement;

defined( 'ABSPATH' ) || exit;

/**
 * Safe insertion using the HTML API: never inside tables, quotes, lists, code, figures or columns.
 */
final class ParagraphInserter {

	/**
	 * Elements whose inner paragraphs must not be counted.
	 *
	 * @var string[]
	 */
	const FORBIDDEN = array( 'TABLE', 'BLOCKQUOTE', 'UL', 'OL', 'DL', 'PRE', 'CODE', 'FIGURE', 'DETAILS', 'ASIDE', 'NAV', 'FORM', 'BUTTON', 'TEMPLATE', 'Q' );

	/**
	 * Class names of DIVs whose inner paragraphs must not be counted (block columns, tabs, …).
	 *
	 * @var string[]
	 */
	const FORBIDDEN_DIV_CLASSES = array( 'wp-block-column', 'wp-block-media-text', 'wp-block-pullquote', 'wp-block-cover', 'pvd-box' );

	/**
	 * Inserts `$insert` after paragraph number `$n`.
	 *
	 * @param string $html         Content HTML.
	 * @param string $insert       HTML to insert.
	 * @param int    $n            Paragraph number (1-based).
	 * @param bool   $append_short When the content has fewer paragraphs: append at the end (true) or skip (false).
	 * @return string
	 */
	public static function insert( $html, $insert, $n, $append_short = true ) {
		$n      = max( 1, (int) $n );
		$offset = self::offset_after_paragraph( (string) $html, $n );

		if ( $offset < 0 ) {
			return $append_short ? $html . "\n" . $insert : $html;
		}

		return substr( $html, 0, $offset ) . "\n" . $insert . substr( $html, $offset );
	}

	/**
	 * Byte offset right after the closing `</p>` of paragraph `$n`, or -1 if there are fewer.
	 *
	 * @param string $html HTML.
	 * @param int    $n    Paragraph number (1-based).
	 * @return int
	 */
	public static function offset_after_paragraph( $html, $n ) {
		if ( '' === $html || false === stripos( $html, '</p' ) ) {
			return -1;
		}

		$scanner   = new TagScanner( $html );
		$forbidden = 0;
		$divs      = array();
		$count     = 0;

		while ( $scanner->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
			$tag    = $scanner->get_tag();
			$closer = $scanner->is_tag_closer();

			if ( 'DIV' === $tag ) {
				if ( $closer ) {
					if ( $divs && array_pop( $divs ) ) {
						--$forbidden;
					}
				} else {
					$is_forbidden = self::is_forbidden_div( (string) $scanner->get_attribute( 'class' ) );
					$divs[]       = $is_forbidden;
					if ( $is_forbidden ) {
						++$forbidden;
					}
				}
				continue;
			}

			if ( in_array( $tag, self::FORBIDDEN, true ) ) {
				$forbidden += $closer ? -1 : 1;
				$forbidden  = max( 0, $forbidden );
				continue;
			}

			if ( 'P' === $tag && $closer && 0 === $forbidden ) {
				++$count;
				if ( $count === $n ) {
					return $scanner->offset_after_current_tag();
				}
			}
		}

		return -1;
	}

	/**
	 * Whether a DIV's class list makes it a forbidden container.
	 *
	 * @param string $classes Class attribute.
	 * @return bool
	 */
	private static function is_forbidden_div( $classes ) {
		if ( '' === $classes ) {
			return false;
		}
		$list = preg_split( '/\s+/', $classes );
		return (bool) array_intersect( (array) $list, self::FORBIDDEN_DIV_CLASSES );
	}
}
