<?php
/**
 * Thin WP_HTML_Tag_Processor subclass that exposes token positions.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Placement;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes the byte offset right after the current tag.
 */
class TagScanner extends \WP_HTML_Tag_Processor {

	/**
	 * Byte offset just after the current tag's closing `>`, or -1.
	 *
	 * @return int
	 */
	public function offset_after_current_tag() {
		if ( ! $this->set_bookmark( 'peyvanda_here' ) || ! isset( $this->bookmarks['peyvanda_here'] ) ) {
			return -1;
		}
		$start = (int) $this->bookmarks['peyvanda_here']->start;
		$this->release_bookmark( 'peyvanda_here' );

		$end = strpos( $this->html, '>', $start );
		return false === $end ? -1 : $end + 1;
	}
}
