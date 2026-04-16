<?php
/**
 * Plugin Name: Woocommerce Variations Switch tiles (inline-JS test variant)
 * Plugin URI:  TBA
 * Description: Experimental variant of the Switch Tiles plugin that keeps WooCommerce's default variation script and injects a small inline radio-to-select sync shim instead. Activate only one of the two plugin files at a time.
 * Version:     2.2.0
 * Author:      Lasse Jellum
 * Author URI:  https://jellum.net
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * WC requires at least: 3.0
 * WC tested up to:      9.5
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'VST_PLUGIN_FILE' ) ) {
	define( 'VST_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'VST_PLUGIN_VERSION' ) ) {
	define( 'VST_PLUGIN_VERSION', '2.2.0' );
}

add_action( 'before_woocommerce_init', static function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			VST_PLUGIN_FILE,
			true
		);
	}
} );

add_action( 'plugins_loaded', static function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	require_once __DIR__ . '/includes/class-vst-offline-variations.php';
	VST_Offline_Variations::init();

	new WC_Radio_Buttons();
} );

if ( ! class_exists( 'WC_Radio_Buttons' ) ) {

	class WC_Radio_Buttons {

		const VERSION = VST_PLUGIN_VERSION;

		private $plugin_path;
		private $plugin_url;

		public function __construct() {
			add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 10, 3 );
			add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ), 999 );
		}

		public function get_plugin_path() {
			if ( ! $this->plugin_path ) {
				$this->plugin_path = plugin_dir_path( VST_PLUGIN_FILE );
			}
			return $this->plugin_path;
		}

		public function get_plugin_url() {
			if ( ! $this->plugin_url ) {
				$this->plugin_url = plugin_dir_url( VST_PLUGIN_FILE );
			}
			return $this->plugin_url;
		}

		public function locate_template( $template, $template_name, $template_path ) {
			$_template = $template;

			if ( ! $template_path ) {
				$template_path = WC()->template_path();
			}

			$plugin_path = $this->get_plugin_path() . 'templates/';

			$template = locate_template( array(
				trailingslashit( $template_path ) . $template_name,
				$template_name,
			) );

			if ( ! $template && file_exists( $plugin_path . $template_name ) ) {
				$template = $plugin_path . $template_name;
			}

			if ( ! $template ) {
				$template = $_template;
			}

			return $template;
		}

		public function load_scripts() {
			if ( ! is_product() ) {
				return;
			}

			wp_enqueue_style(
				'radio-variations',
				$this->get_plugin_url() . 'assets/radio-variations.css',
				array(),
				VST_PLUGIN_VERSION
			);

			wp_add_inline_script( 'wc-add-to-cart-variation', self::get_inline_sync_script() );

			add_action( 'wp_footer', array( $this, 'render_footer_svg' ) );
		}

		private static function get_inline_sync_script() {
			return <<<'JS'
jQuery(function($){
  function sync($radio){
    var name=$radio.attr("name");
    var value=$radio.val();
    var $form=$radio.closest("form.variations_form");
    if(!$form.length){return;}
    var $select=$form.find("select[name=\""+name+"\"]");
    if(!$select.length){return;}
    if($select.val()!==value){
      $select.val(value).trigger("change");
    } else {
      $select.trigger("change");
    }
  }

  $(document).on("change", "form.variations_form input[type=radio][name^=attribute_]", function(){
    sync($(this));
  });

  $("form.variations_form input[type=radio][name^=attribute_]:checked").each(function(){
    sync($(this));
  });
});
JS;
		}

		public function render_footer_svg() {
			?>
			<svg width="0" height="0" aria-hidden="true" focusable="false" style="position:absolute">
				<defs>
					<symbol id="kapsler" x="0px" y="0px" width="380.738px" height="380.739px" viewBox="0 0 380.738 380.739" xml:space="preserve">
						<path d="M345.698,35.031C302.871-7.818,237.54-11.92,199.79,25.829L96.977,128.655l-71.141,71.129
								c-37.75,37.761-33.637,103.082,9.19,145.943c42.838,42.826,108.17,46.928,145.919,9.189l71.129-71.141l102.825-102.814
								C392.66,143.201,388.536,77.87,345.698,35.031z M162.61,336.559c-27.583,27.583-76.578,23.447-109.227-9.19
								c-32.637-32.648-36.773-81.633-9.19-109.228l71.129-71.129L233.74,265.43L162.61,336.559z"/>
					</symbol>
					<symbol id="pulver" x="0px" y="0px" width="256px" height="256px" viewBox="0 0 256 256" xml:space="preserve">
						<path d="M247.769,150.406c-0.621-7.079-8.779-11.157-16.669-15.101c-3.163-1.582-8.436-4.217-9.388-5.624
								c-2.966-7.613-4.592-8.336-16.975-13.843c-3.595-1.599-8.07-3.589-13.989-6.339c-16.138-7.497-28.605-17.704-38.623-25.906
								c-6.096-4.991-11.361-9.301-16.257-12.053c-7.321-4.115-13.951-4.706-19.367-1.956c-8.019,4.071-25.936,23.747-37.833,34.045
								c-16.338,14.143-45.719,32.539-59.563,36.186c-15.422,4.062-16.859,15.213,9.184,18.589c46.661,6.05,36.308,16.493,81.825,19.16
								c28.434,1.666,104.438-8.216,129.889-17.625c2.583-0.71,4.328-1.732,5.642-3.149C247.248,155.058,247.983,152.851,247.769,150.406z
								M110.318,148.391c-2.175,0.002-3.94-1.759-3.942-3.934s1.759-3.94,3.934-3.942c2.175-0.002,3.94,1.759,3.942,3.934
								C114.254,146.623,112.493,148.389,110.318,148.391z M121.98,136.174c-1.933,0.002-3.502-1.563-3.504-3.497s1.563-3.502,3.497-3.504
								s3.502,1.563,3.504,3.497S123.914,136.172,121.98,136.174z M129.877,155.371c-2.417,0.003-4.378-1.954-4.38-4.371
								c-0.003-2.417,1.954-4.378,4.371-4.38c2.417-0.003,4.378,1.954,4.38,4.371S132.294,155.368,129.877,155.371z M153.353,120.756
								c-11.005-12.065-17.739-30.072-23.983-41.293c0.519,0.224,1.052,0.486,1.596,0.792c4.137,2.325,9.088,6.379,14.823,11.074
								c10.453,8.558,23.462,19.208,40.745,27.237c5.994,2.785,10.51,4.793,14.138,6.407c5.331,2.371,8.548,3.802,9.865,4.825
								c0.632,0.49,0.825,0.858,1.89,3.598c1.81,4.659,7.552,7.529,14.201,10.853c2.536,1.268,6.914,3.531,9.521,5.373
								c0.595,0.42,0.382,1.355-0.339,1.461c-2.599,0.381-7.172,0.66-14.533,0.182C204.288,150.163,175.392,144.919,153.353,120.756z
								M70.992,179.579c0,2.777-2.252,5.029-5.029,5.029s-5.029-2.252-5.029-5.029c0-2.777,2.252-5.029,5.029-5.029
								S70.992,176.802,70.992,179.579z M53.12,171.698c0,2.449-1.986,4.435-4.435,4.435s-4.435-1.986-4.435-4.435s1.986-4.435,4.435-4.435
								S53.12,169.249,53.12,171.698z M33.06,181.874c0,2.209-1.791,4-4,4s-4-1.791-4-4s1.791-4,4-4S33.06,179.665,33.06,181.874z
								M219.25,177.749c0,2.278-1.847,4.125-4.125,4.125S211,180.027,211,177.749s1.847-4.125,4.125-4.125S219.25,175.471,219.25,177.749z"/>
					</symbol>
				</defs>
			</svg>
			<?php
		}
	}
}

if ( ! function_exists( 'print_attribute_radio' ) ) {
	function print_attribute_radio( $checked_value, $value, $label, $name, $extra_class = '' ) {
		$product = $GLOBALS['product'] ?? wc_get_product();
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$input_name     = 'attribute_' . esc_attr( $name );
		$esc_value      = esc_attr( $value );
		$id             = esc_attr( $name . '_v_' . $value . $product->get_id() );
		$checked        = checked( $checked_value, $value, false );
		$filtered_label = apply_filters( 'woocommerce_variation_option_name', $label, esc_attr( $name ) );
		$class_attr     = ! empty( $extra_class ) ? ' class="' . esc_attr( $extra_class ) . '"' : '';

		printf(
			'<label for="%3$s"%6$s>
				<input type="radio" name="%1$s" value="%2$s" id="%3$s" %4$s>
				<div>
					<svg aria-hidden="true" focusable="false"><use href="#%2$s" /></svg>
					<span>%5$s</span></div></label>',
			$input_name,
			$esc_value,
			$id,
			$checked,
			$filtered_label,
			$class_attr
		);
	}
}
