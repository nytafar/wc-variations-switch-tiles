<?php

/**
 * Variable product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/variable.php.
 *
 * @package WooCommerce/Templates
 * @version 9.5.0
 *
 * Modified to use radio buttons instead of dropdowns
 * @author 8manos
 */

defined('ABSPATH') || exit;

global $product;
global $woocommerce;

$attribute_keys = array_keys($attributes);
$variations_json = wp_json_encode($available_variations);
$variations_attr = function_exists('wc_esc_json') ? wc_esc_json($variations_json) : _wp_specialchars($variations_json, ENT_QUOTES, 'UTF-8', true);

// Debug information - safer approach
if (is_array($available_variations) && !empty($available_variations)) {
    $debug_first_variation = $available_variations[0];
    // Store debug info in a global variable for inspection
    $GLOBALS['wc_variation_debug'] = array(
        'first_variation' => $debug_first_variation,
        'attributes_sample' => isset($debug_first_variation['attributes']) ? $debug_first_variation['attributes'] : 'no_attributes'
    );
}

do_action('woocommerce_before_add_to_cart_form'); ?>

<form class="variations_form cart" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint($product->get_id()); ?>" data-product_variations="<?php echo $variations_attr; // WPCS: XSS ok. ?>">
	<?php do_action('woocommerce_before_variations_form'); ?>

	<?php if (empty($available_variations) && false !== $available_variations) : ?>
		<p class="stock out-of-stock"><?php echo esc_html(apply_filters('woocommerce_out_of_stock_message', __('This product is currently out of stock and unavailable.', 'woocommerce'))); ?></p>
	<?php else : ?>
		<table class="variations" cellspacing="0" role="presentation">
			<tbody>
				<?php foreach ($attributes as $name => $options) : ?>
					<?php $sanitized_name = sanitize_title($name); ?>
					<tr class="attribute-<?php echo esc_attr($sanitized_name); ?>">
						<th class="label"><label for="<?php echo esc_attr($sanitized_name); ?>"><?php echo wc_attribute_label($name); // WPCS: XSS ok. ?></label></th>
						<?php
						if (isset($_REQUEST['attribute_' . $sanitized_name])) {
							$checked_value = $_REQUEST['attribute_' . $sanitized_name];
						} elseif (isset($selected_attributes[$sanitized_name])) {
							$checked_value = $selected_attributes[$sanitized_name];
						} else {
							$checked_value = '';
						}
						?>
						<td class="value">
							<?php
							if (! empty($options)) {
								// Filter options based on available variations
								$valid_options = array();
								
								// Simpler approach to extract available attribute values
								$valid_options = array();
								$attr_key = 'attribute_' . $sanitized_name;
								
								// Debug info for variations
								$variation_debug = array();
								
								if (is_array($available_variations)) {
									// Check each variation
									foreach ($available_variations as $index => $variation) {
										// Track this variation for debugging
										$variation_debug[$index] = array(
											'purchasable' => isset($variation['is_purchasable']) ? $variation['is_purchasable'] : 'not_set',
											'in_stock' => isset($variation['is_in_stock']) ? $variation['is_in_stock'] : 'not_set',
											'attribute_value' => isset($variation['attributes'][$attr_key]) ? $variation['attributes'][$attr_key] : 'not_set'
										);
										
										// Only consider variations that are purchasable and in stock
										if (isset($variation['is_purchasable']) && $variation['is_purchasable'] && 
											$variation['is_in_stock']) {
											// If this attribute has an empty value, it means any value is acceptable
											if (isset($variation['attributes'][$attr_key])) {
												if ($variation['attributes'][$attr_key] === '') {
													// For 'any' value, all options are valid
													$valid_options = $options;
													break; // No need to continue checking
												} else {
													// Add this specific value to valid options
													$valid_options[] = $variation['attributes'][$attr_key];
												}
											}
										}
									}
									
									// Remove duplicates
									$valid_options = array_unique($valid_options);
								}
								
								// If no valid options found, use all options (fallback for safety)
								if (empty($valid_options)) {
									$valid_options = $options;
								}
								
								// Store for debugging
								if (!isset($GLOBALS['wc_variation_debug'])) {
									$GLOBALS['wc_variation_debug'] = array();
								}
								$GLOBALS['wc_variation_debug']['attribute_' . $sanitized_name] = array(
									'all_options' => $options,
									'valid_options' => $valid_options,
									'available_variations_count' => is_array($available_variations) ? count($available_variations) : 'not_array',
									'has_any_value' => isset($has_any_value) ? $has_any_value : 'not_set'
								);
								
								// Add a visible debug comment for this attribute
								echo '<!-- Debug for ' . esc_html($name) . ': ' . 
								     'Valid options: ' . esc_html(implode(', ', $valid_options)) . 
								     ' | All options: ' . esc_html(implode(', ', $options)) . ' -->';
								
								if (taxonomy_exists($name)) {
									// Get terms if this is a taxonomy - ordered. We need the names too.
									$terms = wc_get_product_terms($product->get_id(), $name, array('fields' => 'all'));

									foreach ($terms as $term) {
										// Skip if not in original options
										if (! in_array($term->slug, $options)) {
											continue;
										}
										// Check if option is in valid options
										$is_active = in_array($term->slug, $valid_options);
										// Add debug info to the term name to see active/inactive status
										$debug_name = $term->name . ($is_active ? '' : ' [INACTIVE]');
										// Pass additional class for inactive options
										print_attribute_radio($checked_value, $term->slug, $debug_name, $sanitized_name, $is_active ? '' : 'inactive');
									}
								} else {
									foreach ($options as $option) {
										// Check if option is in valid options
										$is_active = in_array($option, $valid_options);
										// Add debug info to the option name to see active/inactive status
										$debug_name = $option . ($is_active ? '' : ' [INACTIVE]');
										// Pass additional class for inactive options
										print_attribute_radio($checked_value, $option, $debug_name, $sanitized_name, $is_active ? '' : 'inactive');
									}
								}
							}
							?>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<?php
							echo end($attribute_keys) === $name ? wp_kses_post(apply_filters('woocommerce_reset_variations_link', '<button class="reset_variations"  aria-label="' . esc_html__('Clear options', 'woocommerce') . '">' . esc_html__('Clear', 'woocommerce') . '</button>')) : '';
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<div class="reset_variations_alert screen-reader-text" role="alert" aria-live="polite" aria-relevant="all"></div>
		<?php do_action('woocommerce_after_variations_table'); ?>

		<?php
		if (version_compare($woocommerce->version, '3.4.0') < 0) {
			do_action('woocommerce_before_add_to_cart_button');
		}
		?>

		<div class="single_variation_wrap">
			<?php
			do_action('woocommerce_before_single_variation');
			do_action('woocommerce_single_variation');
			do_action('woocommerce_after_single_variation');
			?>
		</div>

		<?php
		if (version_compare($woocommerce->version, '3.4.0') < 0) {
			do_action('woocommerce_after_add_to_cart_button');
		}
		?>
	<?php endif; ?>

	<?php do_action('woocommerce_after_variations_form'); ?>
</form>

<?php do_action('woocommerce_after_add_to_cart_form'); ?>

<?php
// Output debug information if user is an admin
if (current_user_can('manage_options') && isset($GLOBALS['wc_variation_debug'])) {
    echo '<!-- Debug Info: ' . esc_html(json_encode($GLOBALS['wc_variation_debug'])) . ' -->';
}
?>
