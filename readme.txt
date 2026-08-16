=== Broadside Blocks ===
Contributors: shadowsoftware
Tags: blocks, editorial, faq, table of contents, schema
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 8.5
Stable tag: 1.3.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The editorial blocks and masthead furniture for the Broadside newspaper theme — a short answer, key takeaways, a self-building contents, an FAQ that emits schema.

== Description ==

Broadside Blocks is the companion plugin to the **Broadside** theme. It provides the
seventeen blocks that make a WordPress site read like a newspaper.

**Why a plugin and not part of the theme?** Because WordPress is right about this.
The Theme Directory forbids a theme from registering blocks, and the test behind that
rule is a good one:

> Themes handle presentation. Plugins handle content.
> **Anything you lose when you switch themes belongs in a plugin.**

A Short Answer, an FAQ, a Sources list — that is *content*. It lives in your post. If
it shipped inside a theme, changing theme would silently blank it and you would have
no way to get it back. So it lives here, where it is safe.

= The editorial blocks =

* **Short Answer** — a direct, quotable answer at the top, for the reader in a hurry
  and the search engine that wants to cite you.
* **Key Takeaways** — the three or four things a reader should leave with.
* **Table of Contents** — builds itself from your headings. Rename a heading and the
  contents follow; there is nothing to keep in sync by hand.
* **FAQ** — questions and answers that also emit valid FAQPage structured data.
* **Sources & References** — the receipts.
* **Disclosure Table** — a comparison table whose partner links are always marked
  `rel="sponsored nofollow"` and which always prints a disclosure line.

= The masthead furniture =

Nameplate, folio rule, utility bar, colophon, byline, author bio, editorial standards,
the newsletter panel, the section grid, related stories and the lead-story well.

= It needs the Broadside theme =

These blocks render the theme's Customizer settings and call its template tags. Under
any other theme they render nothing at all — deliberately, and without a fatal error.
Install [Broadside](https://github.com/shadow-software/broadside-theme-for-wordpress);
WordPress 6.5+ will offer this plugin alongside it.

== Installation ==

1. Install and activate the **Broadside** theme.
2. WordPress will offer to install this plugin alongside it. Accept.
3. The blocks appear in the editor under the **Broadside — editorial** category.

== Frequently Asked Questions ==

= I activated the plugin and nothing appears. =

The blocks need the Broadside theme. Under any other theme they render nothing, on
purpose, rather than fataling. An admin notice will tell you so.

= The table of contents is empty. =

It builds itself from the `h2` and `h3` headings in the post. A post with no headings
has no contents to list. Add a heading and it appears.

== Changelog ==

= 1.3.3 =
* Prefix: all plugin functions, hooks, and usermeta use the `broadside_blocks_` prefix (WordPress.org uniqueness requirement). Theme helpers such as `shadow_digest_get()` are unchanged — they live in the Broadside theme.
* Filter `shadow_digest_emit_faq_schema` renamed to `broadside_blocks_emit_faq_schema`.
* Usermeta `shadow_digest_role` migrated to `broadside_blocks_role` (legacy key still read until saved).

= 1.3.2 =
* FAQPage JSON-LD uses default wp_json_encode() flags so solidus is escaped (no script breakout).
* Plugin URI points at this plugin's public repository.
* Removed load_plugin_textdomain(); WordPress.org loads translations automatically.

= 1.3.1 =
* Moved into its own repository, with its own CI and its own signed release zips.
* Version brought back into step with the Broadside theme, which it ships with.

= 1.2.0 =
* Initial release, split out of the Broadside theme: WordPress.org does not permit a
  theme to register blocks, and it is right not to.
* Fixed: the blocks were never in their own editor category. The theme registered one
  slug while all 17 block.json files asked for another, so every block quietly landed
  in the editor's default bucket.

== Copyright ==

Broadside Blocks, Copyright 2026 Shadow Software LLC.
Distributed under the terms of the GNU GPL v2 or later.
It bundles no third-party code, no images, no icons and no JavaScript libraries.
