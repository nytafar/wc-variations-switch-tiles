<?php
/**
 * Check: the tile template offers no option that only archived (private) variations use, still offers every
 * published one, and prints no debug output. Read-only. Exits non-zero on failure.
 *
 * Needs a variable product with an archived-only option (auto-found, or pass its ID) and a plugin that narrows
 * `woocommerce_dropdown_variation_attribute_options_args` for archived variations (kaupang-attribute-suite).
 * Run: gp wp <site> eval-file wp-content/plugins/wc-variations-switch-tiles/tools/check-archived-variations.php [product_id]
 */

defined('ABSPATH') || exit;

$fail  = 0;
$check = function ($ok, $msg) use (&$fail) {
	echo ($ok ? 'ok   ' : 'FAIL ') . $msg . "\n";
	$fail |= !$ok;
};

if (!function_exists('print_attribute_radio')) {
	echo "FAIL wc-variations-switch-tiles is not active\n";
	exit(1);
}

/** Expected options per attribute: values of published variations ("any" keeps all). */
$expected = function (WC_Product $product) {
	$out = array();
	foreach ($product->get_variation_attributes() as $name => $options) {
		$values = array();
		foreach ($product->get_children() as $child_id) {
			if ('publish' !== get_post_status($child_id)) {
				continue;
			}
			$value = (string) get_post_meta($child_id, wc_variation_attribute_name($name), true);
			if ('' === $value) {
				$values = $options;
				break;
			}
			$values[] = $value;
		}
		$out[sanitize_title($name)] = array('all' => array_values($options), 'allowed' => array_values(array_intersect($options, $values)));
	}
	return $out;
};

$product_id = isset($args[0]) ? absint($args[0]) : 0;
if (!$product_id) {
	foreach (wc_get_products(array('type' => 'variable', 'status' => 'publish', 'limit' => -1, 'return' => 'ids')) as $id) {
		foreach ($expected(wc_get_product($id)) as $e) {
			if (count($e['allowed']) && count($e['allowed']) < count($e['all'])) {
				$product_id = $id;
				break 2;
			}
		}
	}
}
$product = $product_id ? wc_get_product($product_id) : null;
if (!$product || !$product->is_type('variable')) {
	echo "FAIL no variable product with an archived-only option (disable a variation, or pass a product ID)\n";
	exit(1);
}
echo "product {$product_id} ({$product->get_slug()})\n";

$GLOBALS['product'] = $product;
$template = dirname(__DIR__) . '/templates/single-product/add-to-cart/variable.php';
$exp      = $expected($product);

// Both modes: variation JSON inlined, and AJAX mode (more variations than woocommerce_ajax_variation_threshold).
foreach (array('inline' => $product->get_available_variations(), 'ajax' => false) as $mode => $available_variations) {
	$attributes          = $product->get_variation_attributes();
	$selected_attributes = $product->get_default_attributes();
	ob_start();
	include $template;
	$html = ob_get_clean();

	preg_match_all('/name="attribute_([^"]+)" value="([^"]*)"/', $html, $m, PREG_SET_ORDER);
	$tiles = array();
	foreach ($m as $hit) {
		$tiles[$hit[1]][] = html_entity_decode($hit[2], ENT_QUOTES);
	}

	foreach ($exp as $attr => $e) {
		$got      = isset($tiles[$attr]) ? $tiles[$attr] : array();
		$archived = array_diff($e['all'], $e['allowed']);
		$check(!array_intersect($archived, $got), "$mode $attr: no archived-only tile (" . implode(', ', $archived ?: array('none')) . ') — got ' . implode(', ', $got));
		$check(!array_diff($e['allowed'], $got), "$mode $attr: every published option is a tile (" . implode(', ', $e['allowed']) . ')');
	}
	$check(false === strpos($html, '<!-- Debug'), "$mode: no debug comment");
	$check(false === strpos($html, '[INACTIVE]'), "$mode: no [INACTIVE] label");
}

if ($fail && !has_filter('woocommerce_dropdown_variation_attribute_options_args')) {
	echo "hint: nothing hooks woocommerce_dropdown_variation_attribute_options_args — is kaupang-attribute-suite active?\n";
}
echo $fail ? "FAILED\n" : "PASSED\n";
exit($fail ? 1 : 0);
