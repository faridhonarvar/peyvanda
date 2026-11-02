=== Peyvanda – Related Posts & Products Inside Content ===
Contributors: honarvar
Tags: related posts, related products, woocommerce, internal links, rtl
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Beautiful, lightweight related posts and WooCommerce product boxes placed inside your content — after paragraph N, with a shortcode or a block.

== Description ==

Peyvanda shows "Read more" and "Related products" boxes **in the middle of your content**, where readers actually see them, to increase page views, time on site and product sales.

**Fast by design**

* Zero front-end JavaScript.
* Each design's stylesheet is under 4 KB and loads only on pages that show a box.
* Related items are calculated when a post is saved, not on every page view.

**Never clashes with your theme**

* Inherits your theme's font, everything else is scoped to the box.
* Full RTL and LTR support (logical CSS properties). Ships with Persian (fa_IR) translation.

**Features**

* Boxes: create as many as you like, each with its own heading, source, number of items, design and placement.
* Sources: posts and WooCommerce products (image, name, price).
* Automatic matching by shared tags and categories, with a fallback to latest posts or best-selling products.
* Manual picks: choose up to 3 items on any post with a live search.
* Placement: automatically after paragraph N (never inside tables, quotes, lists or code), shortcode `[peyvanda id="12"]`, "Peyvanda box" block, and a "Peyvanda marker" block to choose the exact spot.
* 3 designs: list with thumbnails, horizontal cards, minimal text list. Accent color, spacing and corner radius settings.
* Accessible markup: labelled landmark, focusable links, visible focus styles.
* No external requests, no tracking.

**For developers**

Filters and actions: `peyvanda_items`, `peyvanda_should_render`, `peyvanda_item_html`, `peyvanda_manual_items`, `peyvanda_manual_metabox_limits`, `peyvanda_box_settings_tabs`, `peyvanda_sanitize_box_settings`, `peyvanda_max_auto_boxes`, `peyvanda_register_designs`, `peyvanda_register_sources`, `peyvanda_save_box_settings`, `peyvanda_loaded`.

== Installation ==

1. Upload the plugin and activate it.
2. A starter box ("Read more") is created and inserted after the 3rd paragraph of your posts.
3. Go to **Peyvanda → Boxes** to edit it or create new boxes, and **Peyvanda → Settings** for site-wide options.

== Frequently Asked Questions ==

= Does it slow my site down? =

No. There is no front-end JavaScript, styles are tiny and only load where a box is shown, and related items are pre-computed when you save a post.

= Can I choose the items myself? =

Yes. Use the "Peyvanda: related items" panel on the post edit screen to pick up to 3 items. They always come first.

= How do I put the box at a specific spot? =

Add the "Peyvanda marker" block where you want the automatic box, or use the "Peyvanda box" block / `[peyvanda id="12"]` shortcode to place any box.

= Does it work with my theme's dark mode? =

Yes. Colors are derived from your accent color with transparency, and text color is inherited from the theme.

== Changelog ==

= 0.1.0 =
* First release: boxes, automatic insertion after paragraph N, shortcode, box and marker blocks, three designs, manual picks, Persian translation.
