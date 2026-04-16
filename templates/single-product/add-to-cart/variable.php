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

defined( 'ABSPATH' ) || exit;

global $product;

$attribute_keys  = array_keys( $attributes );
$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' )
	? wc_esc_json( $variations_json )
	: _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

do_action( 'woocommerce_before_add_to_cart_form' ); ?>

<form class="variations_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo $variations_attr; // WPCS: XSS ok. ?>">
	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?></p>
	<?php else : ?>
		<table class="variations" cellspacing="0" role="presentation">
			<tbody>
				<?php foreach ( $attributes as $name => $options ) : ?>
					<?php $sanitized_name = sanitize_title( $name ); ?>
					<tr class="attribute-<?php echo esc_attr( $sanitized_name ); ?>">
						<th class="label"><label for="<?php echo esc_attr( $sanitized_name ); ?>"><?php echo wc_attribute_label( $name ); // WPCS: XSS ok. ?></label></th>
						<?php
						$request_key = 'attribute_' . $sanitized_name;
						if ( isset( $_REQUEST[ $request_key ] ) ) {
							$checked_value = wc_clean( wp_unslash( $_REQUEST[ $request_key ] ) );
						} elseif ( isset( $selected_attributes[ $sanitized_name ] ) ) {
							$checked_value = $selected_attributes[ $sanitized_name ];
						} else {
							$checked_value = '';
						}
						?>
						<td class="value">
							<?php
							if ( ! empty( $options ) ) {
								// Collect attribute values that map to at least one purchasable,
								// in-stock variation. Used to dim unavailable tiles.
								$valid_options = array();
								$attr_key      = 'attribute_' . $sanitized_name;

								if ( is_array( $available_variations ) ) {
									foreach ( $available_variations as $variation ) {
										if ( empty( $variation['is_purchasable'] ) || empty( $variation['is_in_stock'] ) ) {
											continue;
										}
										if ( ! isset( $variation['attributes'][ $attr_key ] ) ) {
											continue;
										}

										$value = $variation['attributes'][ $attr_key ];

										// An empty attribute value means "any" — all options qualify.
										if ( '' === $value ) {
											$valid_options = $options;
											break;
										}

										$valid_options[] = $value;
									}

									$valid_options = array_values( array_unique( $valid_options ) );
								}

								// Safety fallback: if filtering produced nothing, show everything.
								if ( empty( $valid_options ) ) {
									$valid_options = $options;
								}

								if ( taxonomy_exists( $name ) ) {
									$terms = wc_get_product_terms( $product->get_id(), $name, array( 'fields' => 'all' ) );

									foreach ( $terms as $term ) {
										if ( ! in_array( $term->slug, $options, true ) ) {
											continue;
										}
										$is_active = in_array( $term->slug, $valid_options, true );
										print_attribute_radio( $checked_value, $term->slug, $term->name, $sanitized_name, $is_active ? '' : 'inactive' );
									}
								} else {
									foreach ( $options as $option ) {
										$is_active = in_array( $option, $valid_options, true );
										print_attribute_radio( $checked_value, $option, $option, $sanitized_name, $is_active ? '' : 'inactive' );
									}
								}
							}
							?>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<?php
							echo end( $attribute_keys ) === $name ? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<button class="reset_variations"  aria-label="' . esc_html__( 'Clear options', 'woocommerce' ) . '">' . esc_html__( 'Clear', 'woocommerce' ) . '</button>' ) ) : '';
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<div class="reset_variations_alert screen-reader-text" role="alert" aria-live="polite" aria-relevant="all"></div>
		<?php do_action( 'woocommerce_after_variations_table' ); ?>

		<div class="single_variation_wrap">
			<?php
			do_action( 'woocommerce_before_single_variation' );
			do_action( 'woocommerce_single_variation' );
			do_action( 'woocommerce_after_single_variation' );
			?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_variations_form' ); ?>
</form>

<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>
