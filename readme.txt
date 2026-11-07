=== Peyvanda – Inline Related Posts, Products & Read More Boxes ===
Contributors: honarvar
Tags: inline related posts, related posts, related products, read more, internal links
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Insert related posts and WooCommerce products inside your content: after any paragraph, by shortcode or block. Zero JavaScript, RTL ready.

== Description ==

Most readers never reach the related posts at the bottom of a page. **Peyvanda puts them where people are actually reading: inside the article**, between paragraphs, as a clean "Read more" or "Related products" box.

More page views, longer visits, more internal links, and more product sales from your content.

= Inline related posts, placed safely =

* **Automatic insertion after paragraph N.** Choose the paragraph and Peyvanda does the rest on every post.
* **Never breaks your content.** Boxes are never placed inside tables, quotes, lists, code blocks, galleries or columns. Placement uses WordPress's own HTML parser.
* **Short posts handled.** Show the box at the end, or not at all, when a post has fewer paragraphs.
* **Live preview** in the box editor shows every change instantly, on desktop and mobile sizes.
* **Pick the exact spot** with the "Peyvanda marker" block, or place any box anywhere with the "Peyvanda box" block or the `[peyvanda id="12"]` shortcode.

= Related posts and related products =

* Show **posts** or **WooCommerce products** (image, name and price), even product boxes inside blog posts.
* **Automatic matching** by shared tags and categories, with a fallback to your latest posts or best-selling products.
* **Manual picks:** choose up to 3 items on any post with a live search. They always come first.
* Drafts, private items, the current post and out-of-stock products are never shown.

= Three designs that fit any theme =

* **List with thumbnails**, **horizontal cards** (1 or 2 columns) and a **minimal text list**.
* Set the accent color, spacing and corner radius per box.
* The box inherits your theme's font and adapts to light and dark themes. Its styles are fully scoped, so it never changes the rest of your site.

= Built for speed =

* **Zero front-end JavaScript.**
* Each design's stylesheet is under 4 KB and loads only on pages that show a box.
* Related items are calculated when you save a post, not on every page view.
* Images use `srcset`, lazy loading and fixed dimensions to avoid layout shift.

= Persian, Arabic and other RTL languages =

Full right-to-left support with logical CSS properties. A complete Persian (fa_IR) translation is included.

= Private and secure =

* No external requests, no tracking, no ads, no "powered by" links.
* All input is sanitized and checked with nonces and capabilities; all output is escaped.

= For developers =

Filters and actions: `peyvanda_items`, `peyvanda_should_render`, `peyvanda_item_html`, `peyvanda_manual_items`, `peyvanda_manual_metabox_limits`, `peyvanda_box_settings_tabs`, `peyvanda_sanitize_box_settings`, `peyvanda_max_auto_boxes`, `peyvanda_automatic_items`, `peyvanda_insert_box`, `peyvanda_design_field_label`, `peyvanda_show_notice_card`, `peyvanda_register_designs`, `peyvanda_register_sources`, `peyvanda_save_box_settings`, `peyvanda_loaded`.

Source code and issue tracker: [github.com/faridhonarvar/peyvanda](https://github.com/faridhonarvar/peyvanda)

== Installation ==

1. Install and activate the plugin from **Plugins → Add New**.
2. A starter "Read more" box is created and shown after the 3rd paragraph of your posts.
3. Edit it, or create more boxes, under **Peyvanda → Boxes**. Site-wide options are under **Peyvanda → Settings**.

== Frequently Asked Questions ==

= Will it slow my site down? =

No. There is no front-end JavaScript, styles are tiny and load only where a box is shown, and related items are pre-computed when you save a post.

= Will it break my content or layout? =

No. Boxes are only inserted between top-level paragraphs, never inside tables, quotes, lists, code or columns, and all box styles are scoped to the box.

= Can I choose the related items myself? =

Yes. Use the "Peyvanda: related items" panel on the post edit screen to pick up to 3 items. They always come first.

= How do I put the box at a specific spot? =

Add the "Peyvanda marker" block where you want the automatic box, or use the "Peyvanda box" block or the `[peyvanda id="12"]` shortcode to place any box.

= Can I show WooCommerce products inside blog posts? =

Yes. Create a box with "Products" as the source and choose "Posts" under "Show on". Products are matched to the post by shared tag and category names.

= Does it work with my theme's dark mode? =

Yes. Text color is inherited from your theme and backgrounds are derived from your accent color with transparency.

= Does it work with page builders and caching plugins? =

Peyvanda works through the standard WordPress content filter, so it works with the block editor, the classic editor and caching plugins. The shortcode works anywhere shortcodes run.

== Screenshots ==

1. A "Read more" box inserted automatically after the 3rd paragraph of a post.
2. Horizontal cards design in two columns.
3. Minimal text list on a Persian (RTL) site.
4. Box editor: choose the source, number of items and automatic matching.
5. Placement tab: insert after paragraph N and choose where the box appears.
6. Design tab: three designs with accent color, spacing and corner radius.
7. Pick related items by hand with a live search in the post editor.
8. Site-wide settings.

== Changelog ==

= 0.2.0 =
* New box editor: live preview of the box inside a real post, desktop and mobile sizes, and design cards that show each design before you pick it.
* Redesigned settings page.
* Developers: new `peyvanda_show_notice_card` filter.

= 0.1.4 =
* Developers: new `peyvanda_automatic_items`, `peyvanda_insert_box` and `peyvanda_design_field_label` filters for add-ons.

= 0.1.3 =
* Security hardening: box settings are sanitized as soon as they are received, and all box output, including filtered item HTML, is escaped.
* WooCommerce prices keep their bidi isolation markup, so currency and amount display in the right order on RTL sites.
* Translations are delivered through translate.wordpress.org.

= 0.1.2 =
* Performance: related-item and search queries no longer use NOT IN clauses.
* Stylesheets are formatted for readability.

= 0.1.1 =
* New plugin name: "Peyvanda – Inline Related Posts, Products & Read More Boxes".
* Box editor and settings: checkbox options are listed one per line.

= 0.1.0 =
* First release: boxes, automatic insertion after paragraph N, shortcode, box and marker blocks, three designs, manual picks, Persian translation.
