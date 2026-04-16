<?php
/**
 * Offline variation data module.
 *
 * Makes sure every piece of data the frontend switcher needs (price HTML,
 * description, image srcset, purchasable/stock flags, quantity bounds) is
 * already present in the inline variation blob rendered by WooCommerce, so
 * variation switching is a synchronous DOM update with zero network I/O.
 *
 * Granular stock quantities are intentionally omitted from the rendered HTML
 * so the product page stays cacheable. Cache busting on out-of-stock is
 * handled outside this plugin.
 *
 * @package WC_Variations_Switch_Tiles
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VST_Offline_Variations' ) ) {

	class VST_Offline_Variations {

		/**
		 * Bind filters. Idempotent; safe to call once from the plugin bootstrap.
		 */
		public static function init() {
			// Never fall back to AJAX variation lookup, regardless of variation count.
			add_filter( 'woocommerce_ajax_variation_threshold', array( __CLASS__, 'disable_ajax_threshold' ), 999 );

			// Always embed a rendered price_html, even when all variations share a price.
			add_filter( 'woocommerce_show_variation_price', '__return_true', 999 );

			// Fill gaps in the inline variation payload (description, srcset, stock flags).
			add_filter( 'woocommerce_available_variation', array( __CLASS__, 'enrich_variation' ), 20, 3 );

			// Strip specific stock quantities from variation availability HTML so the
			// product page HTML remains cacheable.
			add_filter( 'woocommerce_get_stock_html', array( __CLASS__, 'cache_safe_stock_html' ), 999, 2 );
		}

		/**
		 * Raise the threshold high enough that WC always embeds variations inline.
		 *
		 * @return int
		 */
		public static function disable_ajax_threshold() {
			return 9999;
		}

		/**
		 * Enrich a single variation's inline payload.
		 *
		 * @param array                $data      The variation data array WC is about to embed.
		 * @param WC_Product_Variable  $parent    Parent product.
		 * @param WC_Product_Variation $variation Variation object.
		 * @return array
		 */
		public static function enrich_variation( $data, $parent, $variation ) {
			if ( ! is_array( $data ) ) {
				return $data;
			}

			// Guarantee price_html even when WC's "prices vary" heuristic would blank it.
			if ( empty( $data['price_html'] ) ) {
				$data['price_html'] = '<span class="price">' . $variation->get_price_html() . '</span>';
			}

			// Renderable description HTML (WC ships raw-ish text in variation_description).
			$description                 = wp_kses_post( wc_format_content( $variation->get_description() ) );
			$data['description_html']    = $description;
			$data['variation_description'] = $description;

			// Mirror the naming used across the project; keep is_purchasable for BC.
			$data['purchasable'] = ! empty( $data['is_purchasable'] );

			// Coarse stock status (no quantity leak).
			$data['stock_status']       = $variation->get_stock_status();
			$data['backorders_allowed'] = $variation->backorders_allowed();

			// Keep quantity bounds generic so exact stock never hits the cached HTML.
			if ( empty( $data['max_qty'] ) ) {
				$data['max_qty'] = apply_filters( 'woocommerce_quantity_input_max', 9999, $variation );
			}

			// Backfill srcset/sizes for themes that trim them from the image array.
			$image_id = $variation->get_image_id() ? $variation->get_image_id() : $parent->get_image_id();
			if ( $image_id ) {
				if ( isset( $data['image'] ) && is_array( $data['image'] ) ) {
					if ( empty( $data['image']['srcset'] ) ) {
						$data['image']['srcset'] = (string) wp_get_attachment_image_srcset( $image_id, 'woocommerce_single' );
					}
					if ( empty( $data['image']['sizes'] ) ) {
						$data['image']['sizes'] = (string) wp_get_attachment_image_sizes( $image_id, 'woocommerce_single' );
					}
				}
			}

			return $data;
		}

		/**
		 * Cache-safe availability HTML for variations. Boolean in-stock / out-of-stock only.
		 *
		 * @param string     $html    Original availability HTML.
		 * @param WC_Product $product Product or variation object.
		 * @return string
		 */
		public static function cache_safe_stock_html( $html, $product ) {
			if ( ! $product instanceof WC_Product_Variation ) {
				return $html;
			}

			if ( $product->is_in_stock() ) {
				return '<p class="stock in-stock">' . esc_html__( 'In stock', 'woocommerce' ) . '</p>';
			}

			return '<p class="stock out-of-stock">' . esc_html__( 'Out of stock', 'woocommerce' ) . '</p>';
		}
	}
}
