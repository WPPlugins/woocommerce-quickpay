<?php

/**
 * WC_QuickPay_Helper class
 *
 * @class          WC_QuickPay_Helper
 * @version        1.0.0
 * @package        Woocommerce_QuickPay/Classes
 * @category       Class
 * @author         PerfectSolution
 */
class WC_QuickPay_Helper {


	/**
	 * price_normalize function.
	 *
	 * Returns the price with decimals. 1010 returns as 10.10.
	 *
	 * @access public static
	 * @return float
	 */
	public static function price_normalize( $price ) {
		return number_format( $price / 100, 2, wc_get_price_decimal_separator(), '' );
	}

	/**
	 * @param $price
	 *
	 * @return string
	 */
	public static function price_multiplied_to_float( $price ) {
		return number_format( $price / 100, 2, '.', '' );
	}

	/**
	 * Multiplies a custom formatted price based on the WooCommerce decimal- and thousand separators
	 *
	 * @param $price
	 */
	public static function price_custom_to_multiplied( $price ) {
		$decimal_separator  = get_option( 'woocommerce_price_decimal_sep' );
		$thousand_separator = get_option( 'woocommerce_price_thousand_sep' );

		$price = str_replace( $thousand_separator, '', $price );
		$price = str_replace( $decimal_separator, '.', $price );

		return self::price_multiply( $price );
	}

	/**
	 * price_multiply function.
	 *
	 * Returns the price with no decimals. 10.10 returns as 1010.
	 *
	 * @access public static
	 * @return integer
	 */
	public static function price_multiply( $price ) {
		return number_format( $price * 100, 0, '', '' );
	}

	/**
	 * enqueue_javascript_backend function.
	 *
	 * @access public static
	 * @return string
	 */
	public static function enqueue_javascript_backend() {
		wp_enqueue_script( 'quickpay-backend', plugins_url( '/assets/javascript/backend.js', dirname( __FILE__ ) ), array( 'jquery' ) );
		wp_localize_script( 'quickpay-backend', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}


	/**
	 * enqueue_stylesheet function.
	 *
	 * @access public static
	 * @return string
	 */
	public static function enqueue_stylesheet() {
		wp_enqueue_style( 'style', plugins_url( '/assets/stylesheets/woocommerce-quickpay.css', dirname( __FILE__ ) ) );
	}


	/**
	 * load_i18n function.
	 *
	 * @access public static
	 * @return void
	 */
	public static function load_i18n() {
		load_plugin_textdomain( 'woo-quickpay', false, dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/' );
	}


	/**
	 * option_is_enabled function.
	 *
	 * Checks if a setting options is enabled by checking on yes/no data.
	 *
	 * @access public static
	 * @return int
	 */
	public static function option_is_enabled( $value ) {
		return ( $value == 'yes' ) ? 1 : 0;
	}


	/**
	 * get_callback_url function
	 *
	 * Returns the order's main callback url
	 *
	 * @access public
	 * @return string
	 */
	public static function get_callback_url( $post_id = NULL ) {
		$args = array( 'wc-api' => 'WC_QuickPay');

		if( $post_id !== NULL ) {
			$args['order_post_id'] = $post_id;
		}

		$args = apply_filters('woocommerce_quickpay_callback_args', $args, $post_id);

		return add_query_arg( $args , site_url( '/' ) );
	}


	/**
	 * is_url function
	 *
	 * Checks if a string is a URL
	 *
	 * @access public
	 * @return string
	 */
	public static function is_url( $url ) {
		return ! filter_var( $url, FILTER_VALIDATE_URL ) === false;
	}

	/**
	 * @since 4.5.0
	 *
	 * @param $payment_type
	 *
	 * @return null
	 */
	public static function get_payment_type_logo( $payment_type ) {
		$logos = array(
			"american-express" => "americanexpress.png",
			"dankort"          => "dankort.png",
			"diners"           => "diners.png",
			"edankort"         => "edankort.png",
			"fbg1886"          => "forbrugsforeningen.png",
			"jcb"              => "jcb.png",
			"maestro"          => "maestro.png",
			"mastercard"       => "mastercard.png",
			"mastercard-debet" => "mastercard.png",
			"mobilepay"        => "mobilepay.png",
			"visa"             => "visa.png",
			"visa-electron"    => "visaelectron.png",
			"paypal"           => "paypal.png",
			"sofort"           => "sofort.png",
			"viabill"          => "viabill.png",
			"klarna"           => "klarna.png",
		);

		if ( array_key_exists( trim( $payment_type ), $logos ) ) {
			return WC_QP()->plugin_url( 'assets/images/cards/' . $logos[ $payment_type ] );
		}

		return null;
	}

	/**
	 * Checks if WooCommerce Pre-Orders is active
	 */
	public static function has_preorder_plugin() {
		return class_exists( 'WC_Pre_Orders' );
	}

	/**
	 * @param      $value
	 * @param null $default
	 *
	 * @return null
	 */
	public static function value( $value, $default = null ) {
		if ( empty( $value ) ) {
			return $default;
		}

		return $value;
	}

	/**
	 * Prevents qTranslate to make browser redirects resulting in missing callback data.
	 *
	 * @param $url_lang
	 * @param $url_orig
	 * @param $url_info
	 *
	 * @return bool
	 */
	public static function qtranslate_prevent_redirect( $url_lang, $url_orig, $url_info ) {
		// Prevent only on wc-api for this specific gateway
		if (isset( $url_info['query'] ) && stripos( $url_info['query'], 'wc-api=wc_quickpay' ) !== FALSE ) {
			return false;
		}
		return $url_lang;
	}

	/**
	 * @param $bypass
	 *
	 * @return bool
	 */
	public static function spamshield_bypass_security_check( $bypass ) {
		return isset($_GET['wc-api']) && strtolower($_GET['wc-api']) === 'wc_quickpay';
	}
}

?>