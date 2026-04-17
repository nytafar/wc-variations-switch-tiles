=== WooCommerce Variations Switch Tiles ===
Contributors: lassejellum, mantish, mariankadanka
Tags: woocommerce, variations, switch tiles, radio buttons, svg icons
Requires at least: 4.4
Tested up to: 6.7
Stable tag: 2.1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Forked from Variations Radio Buttons for WooCommerce. Let customers pick product variations with rectangular switch tile buttons (with optional SVG icons) instead of dropdowns.

== Description ==

Switch tiles are more tactile and visual than dropdowns. This plugin replaces WooCommerce's variation `<select>` controls with rectangular tile buttons, optionally enriched with SVG icons, giving a clearer and more touch-friendly UX.

Tiles toggle dynamically so customers can't pick non-existent variation combinations.

This fork is currently hardcoded for a specific store (Myrvann). Planned work includes generalising the styling and SVG inputs so it can be reused on any store.

### Theme Compatibility

This plugin may not work out of the box with some themes. This is due to a theme having its own template for variation selection (`single-product/add-to-cart/variable.php`).

To make this plugin work with a non compatible theme, copy `plugins/wc-variations-switch-tiles/templates/single-product/add-to-cart/variable.php` to `themes/your-child-theme/woocommerce/single-product/add-to-cart/variable.php`.

### Github

Source code and contributions at [github](https://github.com/nytafar/wc-variations-switch-tiles).

Upstream project: [8manos/wc-variations-radio-buttons](https://github.com/8manos/wc-variations-radio-buttons).

== Changelog ==

= 2.1.2 =
* Rename plugin entry file to `wc-variations-switch-tiles.php` to match the fork identity.
* Rebrand plugin header and readme as Woocommerce Variations Switch Tiles.
* Bump tested-up-to and clean up legacy radio-buttons references.

= 2.1.1 =
* Fix variation not found message display
* Update template and script to match the latest in WooCommerce

= 2.1.0 =
* Add compatibility with WooCommerce HPOS feature.
* Update template and script to match the latest in WooCommerce
* Fix error selecting attributes of products with many variations

= 2.0.5 =
* Improve compatibility with WordPress 5.5+ by removing depreacted jQuery methods.

= 2.0.2 =
* Template file updated to match changes in WooCommerce 3.4.
* Fix duplication of custom fields.

= 2.0.1 =
* Fix issues in archive pages, where multiple products are present.
* Fix several issues with the gallery.
* Fix product dimensions and weight.
* Radio buttons disabled for product bundles.
* Add class so each attribute can be styled.
* Add the attribute name to the `woocommerce_variation_option_name` filter.

= 2.0.0 =
* Updated to match changes in WooCommerce 3.0.
* This version is not compatible with WooCommerce 2.x.
* Fix issues with the variation image not changing.

= 1.1.5 =
* Fix error when using product_page WooCommerce shortcode.
* Show alert when clicking disabled add to cart button.

= 1.1.4 =
* Avoid overwriting of custom add-to-cart button.

= 1.1.3 =
* Fixes clear button that got screwed on last version.

= 1.1.2 =
* Updated to match changes in WooCommerce 2.5.
* Image disappearing in certain themes fixed.

= 1.1.1 =
* Support for responsive images as in WordPress 4.4 and WooCommerce 2.4.11.
* Checked compatibility with WooCommerce 2.5.0-RC1.

= 1.1.0 =
* Full compatibility with WooCommerce 2.4.
* Variations are called via AJAX when there are many variations.
* Changed version requirements: At least WordPress 4.1 and WooCommerce 2.4.

= 1.0.3 =
* Now works with woocommerce_variation_is_active filter.
* Fix bug that prevented the plugin to work with custom attributes.
* Hopefuly no more "The plugin does not have a valid header." message.

= 1.0.2 =
* Now works better with some themes.

= 1.0.1 =
* WooCommerce 2.4 compatibility.

= 1.0 =
* First release.
