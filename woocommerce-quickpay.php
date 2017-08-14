<?php

/*
Plugin Name: WooCommerce QuickPay
Plugin URI: http://wordpress.org/plugins/woocommerce-quickpay/
Description: Integrates your QuickPay payment gateway into your WooCommerce installation.
Version: 4.8.2
Author: Perfect Solution
Text Domain: woo-quickpay
Author URI: http://perfect-solution.dk
Wiki: http://quickpay.perfect-solution.dk/
*/

if (!defined('ABSPATH')) {
    exit;
}

define('WCQP_VERSION', '4.8.2');
define('WCQP_URL', plugins_url(__FILE__));
define('WCQP_PATH', plugin_dir_path(__FILE__));

add_action('plugins_loaded', 'init_quickpay_gateway', 0);

/**
 * Adds notice in case of WooCommerce being inactive
 */
function wc_quickpay_woocommerce_inactive_notice() {
	$class = 'notice notice-error';
	$headline = __('WooCommerce QuickPay requires WooCommerce to be active.', 'woo-quickpay');
	$message = __( 'Go to the plugins page to activate WooCommerce', 'woo-quickpay' );
	printf( '<div class="%1$s"><h2>%2$s</h2><p>%3$s</p></div>', $class, $headline, $message );
}

function init_quickpay_gateway()
{
	/**
	 * Required functions
	 */
	if ( ! function_exists( 'is_woocommerce_active' ) ) {
		require_once WCQP_PATH . 'woo-includes/woo-functions.php';
	}

	/**
	 * Check if WooCommerce is active, and if it isn't, disable Subscriptions.
	 *
	 * @since 1.0
	 */
	if ( ! is_woocommerce_active() ) {
		add_action( 'admin_notices', 'wc_quickpay_woocommerce_inactive_notice');
		return;
	}

    // Import helper classes
    require_once WCQP_PATH . 'classes/woocommerce-quickpay-install.php';
    require_once WCQP_PATH . 'classes/api/woocommerce-quickpay-api.php';
    require_once WCQP_PATH . 'classes/api/woocommerce-quickpay-api-transaction.php';
    require_once WCQP_PATH . 'classes/api/woocommerce-quickpay-api-payment.php';
    require_once WCQP_PATH . 'classes/api/woocommerce-quickpay-api-subscription.php';
    require_once WCQP_PATH . 'classes/woocommerce-quickpay-exceptions.php';
    require_once WCQP_PATH . 'classes/woocommerce-quickpay-log.php';
    require_once WCQP_PATH . 'classes/woocommerce-quickpay-helper.php';
    require_once WCQP_PATH . 'classes/woocommerce-quickpay-settings.php';
    require_once WCQP_PATH . 'classes/woocommerce-quickpay-order.php';
    require_once WCQP_PATH . 'classes/woocommerce-quickpay-subscription.php';
    require_once WCQP_PATH . 'classes/woocommerce-quickpay-countries.php';
    require_once WCQP_PATH . 'classes/woocommerce-quickpay-views.php';


    // Main class
    class WC_QuickPay extends WC_Payment_Gateway
    {

        /**
         * $_instance
         * @var mixed
         * @access public
         * @static
         */
        public static $_instance = NULL;

        /**
         * @var WC_QuickPay_Log
         */
        public $log;

        /**
         * get_instance
         *
         * Returns a new instance of self, if it does not already exist.
         *
         * @access public
         * @static
         * @return WC_QuickPay
         */
        public static function get_instance()
        {
            if ( null === self::$_instance ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }


        /**
         * __construct function.
         *
         * The class construct
         *
         * @access public
         * @return void
         */
        public function __construct()
        {
            $this->id = 'quickpay';
            $this->method_title = 'QuickPay';
            $this->icon = '';
            $this->has_fields = false;

            $this->supports = array(
                'subscriptions',
                'products',
                'subscription_cancellation',
                'subscription_reactivation',
                'subscription_suspension',
                'subscription_amount_changes',
                'subscription_date_changes',
                'subscription_payment_method_change_customer',
                'refunds',
                'multiple_subscriptions',
                'pre-orders'
            );

            $this->log = new WC_QuickPay_Log();

            // Load the form fields and settings
            $this->init_form_fields();
            $this->init_settings();

            // Get gateway variables
            $this->title = $this->s('title');
            $this->description = $this->s('description');
            $this->instructions = $this->s('instructions');
            $this->order_button_text = $this->s('checkout_button_text');

            do_action('woocommerce_quickpay_loaded');
        }


        /**
         * filter_load_instances function.
         *
         * Loads in extra instances of as separate gateways
         *
         * @access public static
         * @return void
         */
        public static function filter_load_instances($methods)
        {
            require_once WCQP_PATH . 'classes/instances/instance.php';
            require_once WCQP_PATH . 'classes/instances/mobilepay.php';
            require_once WCQP_PATH . 'classes/instances/viabill.php';
            require_once WCQP_PATH . 'classes/instances/klarna.php';
            require_once WCQP_PATH . 'classes/instances/sofort.php';

            $methods[] = 'WC_QuickPay_MobilePay';
            $methods[] = 'WC_QuickPay_ViaBill';
            $methods[] = 'WC_QuickPay_Klarna';
            $methods[] = 'WC_QuickPay_Sofort';

            return $methods;
        }


        /**
         * hooks_and_filters function.
         *
         * Applies plugin hooks and filters
         *
         * @access public
         * @return string
         */
        public function hooks_and_filters()
        {
            add_action('woocommerce_api_wc_' . $this->id, array($this, 'callback_handler'));
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
            add_action('woocommerce_order_status_completed', array($this, 'woocommerce_order_status_completed'));
            add_action('in_plugin_update_message-woocommerce-quickpay/woocommerce-quickpay.php', array(__CLASS__, 'in_plugin_update_message'));

            // WooCommerce Subscriptions hooks/filters
            add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'scheduled_subscription_payment'), 10, 2);
            add_action('woocommerce_subscription_cancelled_' . $this->id, array($this, 'subscription_cancellation'));
            add_action('woocommerce_subscription_payment_method_updated_to_' . $this->id, array($this, 'on_subscription_payment_method_updated_to_quickpay'), 10, 2);
            add_filter('wcs_renewal_order_meta_query', array($this, 'remove_failed_quickpay_attempts_meta_query'), 10);
            add_filter('wcs_renewal_order_meta_query', array($this, 'remove_legacy_transaction_id_meta_query'), 10);

            // Custom bulk actions
	        add_action( 'admin_footer-edit.php', array( $this, 'register_bulk_actions' ) );
	        add_action( 'load-edit.php', array( $this, 'handle_bulk_actions' ) );

            // WooCommerce Pre-Orders
            add_action('wc_pre_orders_process_pre_order_completion_payment_' . $this->id, array($this, 'process_pre_order_payments'));

            if (is_admin()) {
                add_action('admin_menu', 'WC_QuickPay_Helper::enqueue_stylesheet');
                add_action('admin_menu', 'WC_QuickPay_Helper::enqueue_javascript_backend');
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
                add_action('wp_ajax_quickpay_manual_transaction_actions', array($this, 'ajax_quickpay_manual_transaction_actions'));
                add_action('wp_ajax_quickpay_get_transaction_information', array($this, 'ajax_quickpay_get_transaction_information'));
                add_action('wp_ajax_quickpay_empty_logs', array($this, 'ajax_empty_logs'));
                add_action('wp_ajax_quickpay_ping_api', array($this, 'ajax_ping_api'));
                add_action('wp_ajax_quickpay_run_data_upgrader', 'WC_QuickPay_Install::ajax_run_upgrader');
                add_action('in_plugin_update_message-woocommerce-quickpay/woocommerce-quickpay.php', array(__CLASS__, 'in_plugin_update_message'));
            }

            // Make sure not to add these actions multiple times
            if (!has_action('init', 'WC_QuickPay_Helper::load_i18n')) {
                add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 2);
                add_action('add_meta_boxes', array($this, 'add_meta_boxes'));

                if (WC_QuickPay_Helper::option_is_enabled($this->s('quickpay_orders_transaction_info', 'yes'))) {
                    add_filter('manage_shop_order_posts_custom_column', array($this, 'apply_custom_order_data'));
                    add_filter('manage_shop_subscription_posts_custom_column', array($this, 'apply_custom_order_data'));
                }

                add_action('admin_notices', array($this, 'admin_notices'));
            }

            add_action('init', 'WC_QuickPay_Helper::load_i18n');


            add_filter('woocommerce_gateway_icon', array($this, 'apply_gateway_icons'), 2, 3);

            // Third party plugins
	        add_filter( 'qtranslate_language_detect_redirect', 'WC_QuickPay_Helper::qtranslate_prevent_redirect', 10, 3 );
	        add_filter( 'wpss_misc_form_spam_check_bypass', 'WC_QuickPay_Helper::spamshield_bypass_security_check', -10, 1 );
        }

        /**
         * s function.
         *
         * Returns a setting if set. Introduced to prevent undefined key when introducing new settings.
         *
         * @access public
         * @param $key
         * @param null $default
         * @return mixed
         */
        public function s($key, $default = NULL)
        {
            if (isset($this->settings[$key])) {
                return $this->settings[$key];
            }

            return !is_null($default) ? $default : '';
        }

        /**
         * Hook used to display admin notices
         */
        public function admin_notices()
        {
            WC_QuickPay_Settings::show_admin_setup_notices();
            WC_QuickPay_Install::show_update_warning();
        }


        /**
         * add_action_links function.
         *
         * Adds action links inside the plugin overview
         *
         * @access public static
         * @return array
         */
        public static function add_action_links($links)
        {
            $links = array_merge(array(
                '<a href="' . WC_QuickPay_Settings::get_settings_page_url() . '">' . __('Settings', 'woo-quickpay') . '</a>',
            ), $links);

            return $links;
        }


        /**
         * ajax_quickpay_manual_transaction_actions function.
         *
         * Ajax method taking manual transaction requests from wp-admin.
         *
         * @access public
         * @return void
         */
        public function ajax_quickpay_manual_transaction_actions()
        {
            if (isset($_REQUEST['quickpay_action']) AND isset($_REQUEST['post'])) {
                $param_action = $_REQUEST['quickpay_action'];
                $param_post = $_REQUEST['post'];

                $order = new WC_QuickPay_Order((int) $param_post);

                try {
                    $transaction_id = $order->get_transaction_id();

                    // Subscription
                    if ($order->contains_subscription()) {
                        $payment = new WC_QuickPay_API_Subscription();
                        $payment->get($transaction_id);
                    } // Payment
                    else {
                        $payment = new WC_QuickPay_API_Payment();
                        $payment->get($transaction_id);
                    }

                    $payment->get($transaction_id);

                    // Based on the current transaction state, we check if
                    // the requested action is allowed
                    if ($payment->is_action_allowed($param_action)) {
                        // Check if the action method is available in the payment class
                        if (method_exists($payment, $param_action)) {
                            // Fetch amount if sent.
                            $amount = isset($_REQUEST['quickpay_amount']) ? WC_QuickPay_Helper::price_custom_to_multiplied($_REQUEST['quickpay_amount']) : $payment->get_remaining_balance();

                            // Call the action method and parse the transaction id and order object
                            call_user_func_array(array($payment, $param_action), array($transaction_id, $order, WC_QuickPay_Helper::price_multiplied_to_float($amount)));
                        } else {
                            throw new QuickPay_API_Exception(sprintf("Unsupported action: %s.", $param_action));
                        }
                    } // The action was not allowed. Throw an exception
                    else {
                        throw new QuickPay_API_Exception(sprintf(
                            "Action: \"%s\", is not allowed for order #%d, with type state \"%s\"",
                            $param_action,
                            $order->get_clean_order_number(),
                            $payment->get_current_type()
                        ));
                    }
                } catch (QuickPay_Exception $e) {
                    $e->write_to_logs();
                } catch (QuickPay_API_Exception $e) {
                    $e->write_to_logs();
                }

            }
        }


        /**
         * ajax_quickpay_get_transaction_information function.
         *
         * Ajax method retrieving status information about a transaction
         *
         * @access public
         * @return json
         */
        public function ajax_quickpay_get_transaction_information()
        {
            try {
                if (isset($_REQUEST['quickpay-transaction-id']) && isset($_REQUEST['quickpay-post-id'])) {
                    $post_id = $_REQUEST['quickpay-post-id'];
                    $order = new WC_QuickPay_Order($post_id);
                    $transaction_id = $_REQUEST['quickpay-transaction-id'];

                    $data_transaction_id = $transaction_id;
                    $data_test = '';
                    $data_order = $order->get_transaction_order_id();

                    // Subscription
                    if (WC_QuickPay_Subscription::is_subscription($post_id)) {
                        $transaction = new WC_QuickPay_API_Subscription();
                        $transaction->get($transaction_id);
                        $status = $transaction->get_current_type() . ' (' . __('subscription', 'woo-quickpay') . ')';
                    } // Renewal failure
                    else if ($order->subscription_is_renewal_failure()) {
                        $data_transaction_id .= ' ( ' . __('initial order transaction ID', 'woo-quickpay') . ')';
                        $status = __('Failed renewal', 'woo-quickpay');
                    } // Payment
                    else {
                        $transaction = new WC_QuickPay_API_Payment();
                        $transaction->get($transaction_id);
                        $status = $transaction->get_current_type();
                    }

                    if (isset($transaction) AND is_object($transaction) AND $transaction->is_test()) {
                        $data_test = __('Test transaction', 'woo-quickpay');
                    }

                    $response = array(
                        'id' => array(
                            'value' => sprintf(__('Transaction ID: %s', 'woo-quickpay'), $data_transaction_id)
                        ),
                        'order' => array(
                            'value' => empty($data_order) ? '' : sprintf(__('Transaction Order ID: %s', 'woo-quickpay'), $data_order)
                        ),
                        'status' => array(
                            'value' => sprintf(__('Transaction state: %s', 'woo-quickpay'), $status),
                            'attr' => array(
                                'class' => 'woocommerce-quickpay-' . $status
                            )
                        ),
                        'test' => array(
                            'value' => $data_test,
                            'attr' => array(
                                'style' => empty($data_test) ? '' : 'color:red'
                            )
                        ),
                        'cardtype' => array(
                            'value' => sprintf('<img src="%s" />', WC_QuickPay_Helper::get_payment_type_logo($transaction->get_brand()))
                        )
                    );

                    echo json_encode($response);
                    exit;
                }
            } catch (QuickPay_API_Exception $e) {
                $e->write_to_logs();

                $response = array(
                    'error' => array(
                        'value' => $e->getMessage()
                    )
                );

                echo json_encode($response);
                exit;
            }
        }


        /**
         * ajax_empty_logs function.
         *
         * Ajax method to empty the debug logs
         *
         * @access public
         * @return json
         */
        public function ajax_empty_logs()
        {
            $this->log->clear();
            echo json_encode(array('status' => 'success', 'message' => 'Logs successfully emptied'));
            exit;
        }

        /**
         * Checks if an API key is able to connect to the API
         */
        public function ajax_ping_api()
        {
            $status = 'error';
            if (!empty($_POST['apiKey'])) {
                try {
                    $api = new WC_QuickPay_API(sanitize_text_field($_POST['apiKey']));
                    $api->get('/payments?page_size=1');
                    $status = 'success';
                } catch (QuickPay_API_Exception $e) {
                    //
                }
            }
            echo json_encode(array('status' => $status));
            exit;
        }

        /**
         * woocommerce_order_status_completed function.
         *
         * Captures one or several transactions when order state changes to complete.
         *
         * @access public
         * @return void
         */
        public function woocommerce_order_status_completed($post_id)
        {
            // Instantiate new order object
            $order = new WC_QuickPay_Order($post_id);

            // Check the gateway settings.
            if ($order->has_quickpay_payment() && WC_QuickPay_Helper::option_is_enabled($this->s('quickpay_captureoncomplete'))) {
                // Capture only orders that are actual payments (regular orders / recurring payments)
                if (!WC_QuickPay_Subscription::is_subscription($order)) {
                    $transaction_id = $order->get_transaction_id();
                    $payment = new WC_QuickPay_API_Payment();

                    // Check if there is a transaction ID
                    if ($transaction_id) {
                        // Retrieve resource data about the transaction
                        $payment->get($transaction_id);

                        // Check if the transaction can be captured
                        if ($payment->is_action_allowed('capture')) {
                            // Capture the payment
                            $payment->capture($transaction_id, $order);
                        }
                    }
                }
            }
        }


        /**
         * payment_fields function.
         *
         * Prints out the description of the gateway. Also adds two checkboxes for viaBill/creditcard for customers to choose how to pay.
         *
         * @access public
         * @return void
         */
        public function payment_fields()
        {
            if ($this->description) echo wpautop(wptexturize($this->description));
        }


        /**
         * receipt_page function.
         *
         * Shows the recipt. This is the very last step before opening the payment window.
         *
         * @access public
         * @return void
         */
        public function receipt_page($order)
        {
            echo $this->generate_quickpay_form($order);
        }

        /**
         * Processing payments on checkout
         * @param $order_id
         * @return array
         */
        public function process_payment($order_id)
        {
            try {
                // Instantiate order object
                $order = new WC_QuickPay_Order($order_id);

                // Does the order need a new QuickPay payment?
                $needs_payment = true;

                // Default redirect to
                $redirect_to = $this->get_return_url($order);

                // Instantiate a new transaction
	            $api_transaction = new WC_QuickPay_API_Payment();

	            // If the order is a subscripion or an attempt of updating the payment method
	            if (! WC_QuickPay_Subscription::cart_contains_switches() && ($order->contains_subscription() || $order->is_request_to_change_payment())) {
	            	// Instantiate a subscription transaction instead of a payment transaction
		            $api_transaction = new WC_QuickPay_API_Subscription();
		            // Clean up any legacy data regarding old payment links before creating a new payment.
		            $order->delete_payment_id();
		            $order->delete_payment_link();
	            }
	            // If the order contains a product switch and does not need a payment, we will skip the QuickPay
	            // payment window since we do not need to create a new payment nor modify an existing.
	            else if ($order->order_contains_switch() && ! $order->needs_payment()) {
		            $needs_payment = false;
	            }

                if ($needs_payment) {
	                // Create a new object
	                $payment = new stdClass();
	                // If a payment ID exists, go get it
	                $payment->id = $order->get_payment_id();
	                // Create a payment link
	                $link = new stdClass();
	                // If a payment link exists, go get it
	                $link->url = $order->get_payment_link();

	                // If the order does not already have a payment ID,
	                // we will create one an attach it to the order
	                // We also check if a payment already exists. If a link exists, we don't
	                // need to create a payment.
	                if (empty($payment->id) && empty($link->url)) {
		                $payment = $api_transaction->create($order);
		                $order->set_payment_id($payment->id);
	                }

	                // Create or update the payment link. This is necessary to do EVERY TIME
	                // to avoid fraud with changing amounts.
	                $link = $api_transaction->patch_link($payment->id, $order);

	                if (WC_QuickPay_Helper::is_url($link->url)) {
		                $order->set_payment_link($link->url);
	                }

	                // Overwrite the standard checkout url. Go to the QuickPay payment window.
	                if (WC_QuickPay_Helper::is_url($link->url)) {
	                	$redirect_to = $link->url;
	                }
                }

                // Perform redirect
                return array(
                    'result' => 'success',
                    'redirect' => $redirect_to
                );

            } catch (QuickPay_Exception $e) {
                $e->write_to_logs();
                wc_add_notice( $e->getMessage(), 'error' );
            }
        }

        /**
         * HOOK: Handles pre-order payments
         */
        public function process_pre_order_payments($order)
        {
            // Set order object
            $order = new WC_QuickPay_Order($order);

            // Get transaction ID
            $transaction_id = $order->get_transaction_id();

            // Check if there is a transaction ID
            if ($transaction_id) {
                try {
                    // Set payment object
                    $payment = new WC_QuickPay_API_Payment();

                    // Retrieve resource data about the transaction
                    $payment->get($transaction_id);

                    // Check if the transaction can be captured
                    if ($payment->is_action_allowed('capture')) {
                        try {
                            // Capture the payment
                            $payment->capture($transaction_id, $order);
                        } // Payment failed
                        catch (QuickPay_API_Exception $e) {
                            $this->log->add(
                                sprintf("Could not process pre-order payment for order: #%s with transaction id: %s. Payment failed. Exception: %s",
                                    $order->get_clean_order_number(), $transaction_id, $e->getMessage())
                            );

                            $order->update_status('failed');
                        }
                    }
                } catch (QuickPay_API_Exception $e) {
                    $this->log->add(
                        sprintf("Could not process pre-order payment for order: #%s with transaction id: %s. Transaction not found. Exception: %s",
                            $order->get_clean_order_number(), $transaction_id, $e->getMessage())
                    );
                }

            }
        }

        /**
         * Process refunds
         * WooCommerce 2.2 or later
         *
         * @param  int $order_id
         * @param  float $amount
         * @param  string $reason
         * @return bool|WP_Error
         */
        public function process_refund($order_id, $amount = NULL, $reason = '')
        {
            try {
                $order = new WC_QuickPay_Order($order_id);

                $transaction_id = $order->get_transaction_id();

                // Check if there is a transaction ID
                if (!$transaction_id) {
                    throw new QuickPay_Exception(sprintf(__("No transaction ID for order: %s", 'woo-quickpay'), $order_id));
                }

                // Create a payment instance and retrieve transaction information
                $payment = new WC_QuickPay_API_Payment();
                $payment->get($transaction_id);

                // Check if the transaction can be refunded
                if (!$payment->is_action_allowed('refund')) {
                    throw new QuickPay_Exception(__("Transaction state does not allow refunds.", 'woo-quickpay'));
                }

                // Perform a refund API request
                $payment->refund($transaction_id, $order, $amount);

                return TRUE;
            } catch (QuickPay_Exception $e) {
                $e->write_to_logs();
            } catch (QuickPay_API_Exception $e) {
                $e->write_to_logs();
            }

            return FALSE;
        }

        /**
         * Clear cart in case its not already done.
         * @return [type] [description]
         */
        public function thankyou_page()
        {
            global $woocommerce;
            $woocommerce->cart->empty_cart();
        }


        /**
         * scheduled_subscription_payment function.
         *
         * Runs every time a scheduled renewal of a subscription is required
         *
         * @access public
         * @return The API response
         */
        public function scheduled_subscription_payment($amount_to_charge, $renewal_order)
        {
            // Create subscription instance
            $transaction = new WC_QuickPay_API_Subscription();

            // Block the callback
            $transaction->block_callback = TRUE;

	        /** @var WC_Subscription $subscription */
	        // Get the subscription based on the renewal order
	        $subscription = WC_QuickPay_Subscription::get_subscriptions_for_renewal_order($renewal_order, $single = TRUE);

            $subscription_id = version_compare( WC_VERSION, '3.0', '<' ) ? $subscription->id : $subscription->get_id();

            // Make new instance to properly get the transaction ID with built in fallbacks.
            $subscription_order = new WC_QuickPay_Order($subscription_id);

            // Get the transaction ID from the subscription
            $transaction_id = $subscription_order->get_transaction_id();

            // Capture a recurring payment with fixed amount
            $response = $this->process_recurring_payment($transaction, $transaction_id, $amount_to_charge, $renewal_order);

            return $response;
        }


        /**
         * Wrapper to process a recurring payment on an order/subscription
         * @param WC_QuickPay_API_Subscription $transaction
         * @param $subscription_transaction_id
         * @param $amount_to_charge
         * @param $order
         * @return mixed
         */
        public function process_recurring_payment(WC_QuickPay_API_Subscription $transaction, $subscription_transaction_id, $amount_to_charge, $order)
        {
            if (!$order instanceof WC_QuickPay_Order) {
                $order = new WC_QuickPay_Order($order);
            }

            $response = NULL;
            try {
                // Block the callback
                $transaction->block_callback = TRUE;

                // Capture a recurring payment with fixed amount
                list($response) = $transaction->recurring($subscription_transaction_id, $order, $amount_to_charge);

                if (!$response->accepted) {
                    throw new QuickPay_Exception("Recurring payment not accepted by acquirer.");
                }

                // If there is a fee added to the transaction.
                if (!empty($response->fee)) {
                    $order->add_transaction_fee($response->fee);
                }
                // Process the recurring payment on the orders
                WC_QuickPay_Subscription::process_recurring_response($response, $order);

                // Reset failed attempts.
                $order->reset_failed_quickpay_payment_count();
            } catch (QuickPay_Exception $e) {
                $order->increase_failed_quickpay_payment_count();

                // Set the payment as failed
                $order->update_status('failed', 'Automatic renewal of ' . $order->get_order_number() . ' failed. Message: ' . $e->getMessage());

                // Write debug information to the logs
                $e->write_to_logs();
            } catch (QuickPay_API_Exception $e) {
                $order->increase_failed_quickpay_payment_count();

                // Set the payment as failed
                $order->update_status('failed', 'Automatic renewal of ' . $order->get_order_number() . ' failed. Message: ' . $e->getMessage());

                // Write debug information to the logs
                $e->write_to_logs();
            }

            return $response;
        }

        /**
         * Prevents the failed attempts count to be copied to renewal orders
         *
         * @param $order_meta_query
         * @return string
         */
        public function remove_failed_quickpay_attempts_meta_query($order_meta_query)
        {
            $order_meta_query .= " AND `meta_key` NOT IN ('" . WC_QuickPay_Order::META_FAILED_PAYMENT_COUNT . "')";
            $order_meta_query .= " AND `meta_key` NOT IN ('_quickpay_transaction_id')";

            return $order_meta_query;
        }

        /**
         * Prevents the legacy transaction ID from being copied to renewal orders
         *
         * @param $order_meta_query
         * @return string
         */
        public function remove_legacy_transaction_id_meta_query($order_meta_query)
        {
            $order_meta_query .= " AND `meta_key` NOT IN ('TRANSACTION_ID')";

            return $order_meta_query;
        }

        /**
         * Triggered when customers are changing payment method to QuickPay.
         *
         * @param $new_payment_method
         * @param $subscription
         * @param $old_payment_method
         */
        public function on_subscription_payment_method_updated_to_quickpay($subscription, $old_payment_method)
        {
            $subscription_id = version_compare( WC_VERSION, '3.0', '<' ) ? $subscription->id : $subscription->get_id();
            $order = new WC_QuickPay_Order($subscription_id);
            $order->increase_payment_method_change_count();
        }


        /**
         * subscription_cancellation function.
         *
         * Cancels a transaction when the subscription is cancelled
         *
         * @access public
         * @param WC_Order $order - WC_Order object
         * @return void
         */
        public function subscription_cancellation($order)
        {
        	if ('cancelled' !== $order->get_status()) {
        		return;
	        }

            try {
                if (WC_QuickPay_Subscription::is_subscription($order)) {
                    $order = new WC_QuickPay_Order($order);
                    $transaction_id = $order->get_transaction_id();

                    $subscription = new WC_QuickPay_API_Subscription();
                    $subscription->get($transaction_id);

                    if ($subscription->is_action_allowed('cancel')) {
                        $subscription->cancel($transaction_id);
                    }
                }
            } catch (QuickPay_Exception $e) {
                $e->write_to_logs();
            } catch (QuickPay_API_Exception $e) {
                $e->write_to_logs();
            }
        }

        /**
         * on_order_cancellation function.
         *
         * Is called when a customer cancels the payment process from the QuickPay payment window.
         *
         * @access public
         * @return void
         */
        public function on_order_cancellation($order_id)
        {
            $order = new WC_Order($order_id);

            // Redirect the customer to account page if the current order is failed
            if ($order->get_status() === 'failed') {
                $payment_failure_text = sprintf(__('<p><strong>Payment failure</strong> A problem with your payment on order <strong>#%i</strong> occured. Please try again to complete your order.</p>', 'woo-quickpay'), $order_id);

                wc_add_notice($payment_failure_text, 'error');

                wp_redirect(get_permalink(get_option('woocommerce_myaccount_page_id')));
            }

            $order->add_order_note(__('QuickPay Payment', 'woo-quickpay') . ': ' . __('Cancelled during process', 'woo-quickpay'));

            wc_add_notice(__('<p><strong>%s</strong>: %s</p>',
		            __('Payment cancelled', 'woo-quickpay'),
		            __('Due to cancellation of your payment, the order process was not completed. Please fulfill the payment to complete your order.', 'woo-quickpay')
                ),
            'error');
        }

        /**
         * callback_handler function.
         *
         * Is called after a payment has been submitted in the QuickPay payment window.
         *
         * @access public
         * @return void
         */
        public function callback_handler()
        {
            // Get callback body
            $request_body = file_get_contents("php://input");

            if(empty($request_body)) {
                return;
            }

            // Decode the body into JSON
            $json = json_decode($request_body);

            // Instantiate payment object
            $payment = new WC_QuickPay_API_Payment($json);

            // Fetch order number;
            $order_number = WC_QuickPay_Order::get_order_id_from_callback($json);

            // Fetch subscription post ID if present
            $subscription_id = WC_QuickPay_Order::get_subscription_id_from_callback($json);

            if (!empty($subscription_id)) {
                $subscription = new WC_QuickPay_Order($subscription_id);
            }

            if ($payment->is_authorized_callback($request_body)) {
                // Instantiate order object
                $order = new WC_QuickPay_Order($order_number);

                $order_id = version_compare( WC_VERSION, '3.0', '<' ) ? $order->id : $order->get_id();

                // Get last transaction in operation history
                $transaction = end($json->operations);

                // Is the transaction accepted and approved by QP / Acquirer?
                if ($json->accepted) {

                    // Perform action depending on the operation status type
                    try {
                        switch ($transaction->type) {
                            //
                            // Cancel callbacks are currently not supported by the QuickPay API
                            //
                            case 'cancel' :
                                // Write a note to the order history
                                $order->note(__('Payment cancelled.', 'woo-quickpay'));
                                break;

                            case 'capture' :
                                // Write a note to the order history
                                $order->note(__('Payment captured.', 'woo-quickpay'));
                                break;

                            case 'refund' :
                                $order->note(sprintf(__('Refunded %s %s', 'woo-quickpay'), WC_QuickPay_Helper::price_normalize($transaction->amount), $json->currency));
                                break;

                            case 'authorize' :
                                // Set the transaction order ID
                                $order->set_transaction_order_id($json->order_id);

                                // Remove payment link
                                $order->delete_payment_link();

                                // Remove payment ID, now we have the transaction ID
                                $order->delete_payment_id();

                                // Subscription authorization
                                if (!empty($subscription_id)) {
                                    // Write log
                                    $subscription->note(sprintf(__('Subscription authorized. Transaction ID: %s', 'woo-quickpay'), $json->id));
                                    // Activate the subscription

                                    // Check if there is an initial payment on the subscription.
                                    // We are saving the total before completing the original payment.
                                    // This gives us the correct payment for the auto initial payment on subscriptions.
                                    $subscription_initial_payment = $order->get_total();

                                    // Mark the payment as complete
                                    //$subscription->set_transaction_id($json->id);
	                                // Temporarily save the transaction ID on a custom meta row to avoid empty values in 3.0.
                                    update_post_meta( $subscription_id, '_quickpay_transaction_id', $json->id );
                                    //$subscription->payment_complete($json->id);
                                    $subscription->set_transaction_order_id($json->order_id);

                                    // Only make an instant payment if there is an initial payment
                                    if ($subscription_initial_payment > 0) {
                                        // Check if this is an order containing a subscription
                                        if (!WC_QuickPay_Subscription::is_subscription($order_id) && $order->contains_subscription()) {
                                            // Process a recurring payment.
                                            $this->process_recurring_payment(new WC_QuickPay_API_Subscription(), $json->id, $subscription_initial_payment, $order);
                                        }
                                    }
                                    // If there is no initial payment, we will mark the order as complete.
                                    // This is usually happening if a subscription has a free trial.
                                    else {
                                        $order->payment_complete();
                                    }

                                } // Regular payment authorization
                                else {
                                    // Add order transaction fee if available
                                    if (!empty($json->fee)) {
                                        $order->add_transaction_fee($json->fee);
                                    }

                                    // Check for pre-order
                                    if (WC_QuickPay_Helper::has_preorder_plugin() && WC_Pre_Orders_Order::order_contains_pre_order($order) && WC_Pre_Orders_Order::order_requires_payment_tokenization($order_id)) {
	                                    try {
		                                    // Set transaction ID without marking the payment as complete
		                                    $order->set_transaction_id($json->id);
	                                    } catch (WC_Data_Exception $e) {
	                                    	$this->log->add(__( 'An error occured while setting transaction id: %d on order %s. %s', $json->id, $order_id, $e->getMessage()));
	                                    }
                                        WC_Pre_Orders_Order::mark_order_as_pre_ordered($order);
                                    } // Regular product
                                    else {
                                        // Register the payment on the order
                                        $order->payment_complete($json->id);
                                    }

                                    // Write a note to the order history
                                    $order->note(sprintf(__('Payment authorized. Transaction ID: %s', 'woo-quickpay'), $json->id));
                                }
                                break;
                        }

                        do_action('woocommerce_quickpay_accepted_callback_status_' . $transaction->type, $order, $json);

                    } catch (QuickPay_API_Exception $e) {
                        $e->write_to_logs();
                    }
                }

                // The transaction was not accepted.
                // Print debug information to logs
                else {
                    // Write debug information
                    $this->log->separator();
                    $this->log->add(sprintf(__('Transaction failed for #%s.', 'woo-quickpay'), $order_number));
                    $this->log->add(sprintf(__('QuickPay status code: %s.', 'woo-quickpay'), $transaction->qp_status_code));
                    $this->log->add(sprintf(__('QuickPay status message: %s.', 'woo-quickpay'), $transaction->qp_status_msg));
                    $this->log->add(sprintf(__('Acquirer status code: %s', 'woo-quickpay'), $transaction->aq_status_code));
                    $this->log->add(sprintf(__('Acquirer status message: %s', 'woo-quickpay'), $transaction->aq_status_msg));
                    $this->log->separator();

                    if ($transaction->type == 'recurring') {
                        WC_Subscriptions_Manager::process_subscription_payment_failure_on_order($order);
                    }

                    if ('rejected' != $json->state) {
                        // Update the order statuses
                        if ($transaction->type == 'subscribe') {
                            WC_Subscriptions_Manager::process_subscription_payment_failure_on_order($order);
                        } else {
                            $order->update_status('failed');
                        }
                    }
                }
            } else {
                $this->log->add(sprintf(__('Invalid callback body for order #%s.', 'woo-quickpay'), $order_number));
            }
        }


        /**
         * init_form_fields function.
         *
         * Initiates the plugin settings form fields
         *
         * @access public
         * @return array
         */
        public function init_form_fields()
        {
            $this->form_fields = WC_QuickPay_Settings::get_fields();
        }


        /**
         * admin_options function.
         *
         * Prints the admin settings form
         *
         * @access public
         * @return string
         */
        public function admin_options()
        {
            echo "<h3>QuickPay - {$this->id}, v" . WCQP_VERSION . "</h3>";
            echo "<p>" . __('Allows you to receive payments via QuickPay.', 'woo-quickpay') . "</p>";

            WC_QuickPay_Settings::clear_logs_section();

            do_action('woocommerce_quickpay_settings_table_before');

            echo "<table class=\"form-table\">";
            $this->generate_settings_html();
            echo "</table";

            do_action('woocommerce_quickpay_settings_table_after');
        }


        /**
         * add_meta_boxes function.
         *
         * Adds the action meta box inside the single order view.
         *
         * @access public
         * @return void
         */
        public function add_meta_boxes()
        {
        	global $post;

			$screen = get_current_screen();
			$post_types = array('shop_order', 'shop_subscription');

            if ( in_array($screen->id, $post_types, true) && in_array( $post->post_type, $post_types, true ) ) {
                $order = new WC_QuickPay_Order($post->ID);
                if ($order->has_quickpay_payment()) {
                    add_meta_box('quickpay-payment-actions', __('QuickPay Payment', 'woo-quickpay'), array(&$this, 'meta_box_payment'), 'shop_order', 'side', 'high');
                    add_meta_box('quickpay-payment-actions', __('QuickPay Subscription', 'woo-quickpay'), array(&$this, 'meta_box_subscription'), 'shop_subscription', 'side', 'high');
                }
            }
        }


        /**
         * meta_box_payment function.
         *
         * Inserts the content of the API actions meta box - Payments
         *
         * @access public
         * @return void
         */
        public function meta_box_payment()
        {
            global $post;
            $order = new WC_QuickPay_Order($post->ID);

            $transaction_id = $order->get_transaction_id();
            if ($transaction_id && $order->has_quickpay_payment()) {
                try {
                    $transaction = new WC_QuickPay_API_Payment();
                    $transaction->get($transaction_id);
                    $status = $transaction->get_current_type();

                    echo "<p class=\"woocommerce-quickpay-{$status}\"><strong>" . __('Current payment state', 'woo-quickpay') . ": " . $status . "</strong></p>";

                    if ($transaction->is_action_allowed('standard_actions')) {
                        echo "<h4><strong>" . __('Actions', 'woo-quickpay') . "</strong></h4>";
                        echo "<ul class=\"order_action\">";

                        if ($transaction->is_action_allowed('capture')) {
                            echo "<li class=\"qp-full-width\"><a class=\"button button-primary\" data-action=\"capture\" data-confirm=\"" . __('You are about to CAPTURE this payment', 'woo-quickpay') . "\">" . sprintf(__('Capture Full Amount (%s)', 'woo-quickpay'), $transaction->get_formatted_remaining_balance()) . "</a></li>";
                        }

                        printf("<li class=\"qp-balance\"><span class=\"qp-balance__label\">%s:</span><span class=\"qp-balance__amount\"><span class='qp-balance__currency'>%s</span>%s</span></li>", __('Remaining balance', 'woo-quickpay'), $transaction->get_currency(), $transaction->get_formatted_remaining_balance());
                        printf("<li class=\"qp-balance last\"><span class=\"qp-balance__label\">%s:</span><span class=\"qp-balance__amount\"><span class='qp-balance__currency'>%s</span><input id='qp-balance__amount-field' type='text' value='%s' /></span></li>", __('Capture amount', 'woo-quickpay'), $transaction->get_currency(), $transaction->get_formatted_remaining_balance());

                        if ($transaction->is_action_allowed('capture')) {
                            echo "<li class=\"qp-full-width\"><a class=\"button\" data-action=\"captureAmount\" data-confirm=\"" . __('You are about to CAPTURE this payment', 'woo-quickpay') . "\">" . __('Capture Specified Amount', 'woo-quickpay') . "</a></li>";
                        }


                        if ($transaction->is_action_allowed('cancel')) {
                            echo "<li class=\"qp-full-width\"><a class=\"button\" data-action=\"cancel\" data-confirm=\"" . __('You are about to CANCEL this payment', 'woo-quickpay') . "\">" . __('Cancel', 'woo-quickpay') . "</a></li>";
                        }

                        echo "</ul>";
                    }

                    printf('<p><small><strong>%s:</strong> %d <span class="qp-meta-card"><img src="%s" /></span></small>',
                        __('Transaction ID', 'woo-quickpay'),
                        $transaction_id,
                        WC_Quickpay_Helper::get_payment_type_logo($transaction->get_brand())
                    );

                    $transaction_order_id = $order->get_transaction_order_id();
                    if (isset($transaction_order_id) && !empty($transaction_order_id)) {
                        printf('<p><small><strong>%s:</strong> %s</small>', __('Transaction Order ID', 'woo-quickpay'), $transaction_order_id);
                    }
                } catch (QuickPay_API_Exception $e) {
                    $e->write_to_logs();
                    $e->write_standard_warning();
                }
            }

            // Show payment ID and payment link for orders that have not yet
            // been paid. Show this information even if the transaction ID is missing.
            $payment_id = $order->get_payment_id();
            if (isset($payment_id) && !empty($payment_id)) {
                printf('<p><small><strong>%s:</strong> %d</small>', __('Payment ID', 'woo-quickpay'), $payment_id);
            }

            $payment_link = $order->get_payment_link();
            if (isset($payment_link) && !empty($payment_link)) {
                printf('<p><small><strong>%s:</strong> <br /><input type="text" style="%s"value="%s" readonly /></small></p>', __('Payment Link', 'woo-quickpay'), 'width:100%', $payment_link);
            }
        }


        /**
         * meta_box_payment function.
         *
         * Inserts the content of the API actions meta box - Subscriptions
         *
         * @access public
         * @return void
         */
        public function meta_box_subscription()
        {
            global $post;
            $order = new WC_QuickPay_Order($post->ID);

            $transaction_id = $order->get_transaction_id();
            if ($transaction_id && $order->has_quickpay_payment()) {
                try {

                    $transaction = new WC_QuickPay_API_Subscription();
                    $transaction->get($transaction_id);
                    $status = $transaction->get_current_type() . ' (' . __('subscription', 'woo-quickpay') . ')';

                    echo "<p class=\"woocommerce-quickpay-{$status}\"><strong>" . __('Current payment state', 'woo-quickpay') . ": " . $status . "</strong></p>";

                    printf('<p><small><strong>%s:</strong> %d <span class="qp-meta-card"><img src="%s" /></span></small>',
                        __('Transaction ID', 'woo-quickpay'),
                        $transaction_id,
                        WC_Quickpay_Helper::get_payment_type_logo($transaction->get_brand())
                    );

                    $transaction_order_id = $order->get_transaction_order_id();
                    if (isset($transaction_order_id) && !empty($transaction_order_id)) {
                        printf('<p><small><strong>%s:</strong> %s</small>', __('Transaction Order ID', 'woo-quickpay'), $transaction_order_id);
                    }
                } catch (QuickPay_API_Exception $e) {
                    $e->write_to_logs();
                    $e->write_standard_warning();
                }
            }
        }


	    /**
	     * email_instructions function.
	     *
	     * Adds custom text to the order confirmation email.
	     *
	     * @access public
	     *
	     * @param WC_Order $order
	     * @param boolean $sent_to_admin
	     *
	     * @return bool /string/void
	     */
        public function email_instructions($order, $sent_to_admin)
        {
            $payment_method = version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method : $order->get_payment_method();

            if ($sent_to_admin || ($order->get_status() !== 'processing' && $order->get_status() !== 'completed') || $payment_method !== 'quickpay') {
                return;
            }

            if ($this->instructions) {
                echo wpautop(wptexturize($this->instructions));
            }
        }


        /**
         * apply_custom_order_data function.
         *
         * Applies transaction ID and state to the order data overview
         *
         * @access public
         * @return void
         */
        public function apply_custom_order_data($column)
        {
            global $post, $woocommerce;

            $order = new WC_QuickPay_Order($post->ID);

            // ? ABOVE 2.1 : BELOW 2.1
            $check_column = version_compare($woocommerce->version, '2.1', '>') ? 'shipping_address' : 'billing_address';

            // Show transaction ID on the overview
            if (($post->post_type == 'shop_order' && $column == $check_column) || ($post->post_type == 'shop_subscription' && $column == 'order_title')) {
                // Insert transaction id and payment status if any
                $transaction_id = $order->get_transaction_id();

                if ($transaction_id && $order->has_quickpay_payment()) {
                	WC_QuickPay_Views::get_view('html-order-table-transaction-data.php', array(
                		'transaction_id' => $transaction_id,
		                'post_id' => $post->ID,
	                ));
                }
            }
        }

        /**
         * FILTER: apply_gateway_icons function.
         *
         * Sets gateway icons on frontend
         *
         * @access public
         * @return void
         */
        public function apply_gateway_icons($icon, $id)
        {
            if ($id == $this->id) {
                $icon = '';

                $icons = $this->s('quickpay_icons');

                if (!empty($icons)) {
                    $icons_maxheight = $this->gateway_icon_size();

                    foreach ($icons as $key => $item) {
                        $icon .= $this->gateway_icon_create($item, $icons_maxheight);
                    }
                }
            }

            return $icon;
        }


        /**
         * gateway_icon_create
         *
         * Helper to get the a gateway icon image tag
         *
         * @access protected
         * @return void
         */
        protected function gateway_icon_create($icon, $max_height)
        {
            $icon_url = WC_HTTPS::force_https_url(plugin_dir_url(__FILE__) . 'assets/images/cards/' . $icon . '.png');
            return '<img src="' . $icon_url . '" alt="' . esc_attr($this->get_title()) . '" style="max-height:' . $max_height . '"/>';
        }


        /**
         * gateway_icon_size
         *
         * Helper to get the a gateway icon image max height
         *
         * @access protected
         * @return void
         */
        protected function gateway_icon_size()
        {
            $settings_icons_maxheight = $this->s('quickpay_icons_maxheight');
            return !empty($settings_icons_maxheight) ? $settings_icons_maxheight . 'px' : '20px';
        }


	    /**
	     *
	     * get_gateway_currency
	     *
	     * Returns the gateway currency
	     *
	     * @access public
	     *
	     * @param WC_Order $order
	     *
	     * @return void
	     */
        public function get_gateway_currency($order)
        {
            if (WC_QuickPay_Helper::option_is_enabled($this->s('quickpay_currency_auto'))) {
                $currency = version_compare( WC_VERSION, '3.0', '<' ) ? $order->get_order_currency() : $order->get_currency();
            } else {
                $currency = $this->s('quickpay_currency');
            }

            $currency = apply_filters('woocommerce_quickpay_currency', $currency, $order);

            return $currency;
        }


        /**
         *
         * get_gateway_language
         *
         * Returns the gateway language
         *
         * @access public
         * @return string
         */
        public function get_gateway_language()
        {
            $language = apply_filters('woocommerce_quickpay_language', $this->s('quickpay_language'));
            return $language;
        }

	    /**
	     * Registers custom bulk actions
	     */
        public function register_bulk_actions() {
	        global $post_type;

	        if ( $post_type === 'shop_order' && WC_QuickPay_Subscription::plugin_is_active()) {
		        WC_QuickPay_Views::get_view('bulk-actions.php');
	        }
        }

	    /**
	     * Handles custom bulk actions
	     */
        public function handle_bulk_actions() {
	        $wp_list_table = _get_list_table( 'WP_Posts_List_Table' );

	        $action = $wp_list_table->current_action();

	        // Check for posts
	        if ( ! empty( $_GET['post'] ) ) {
		        $order_ids = $_GET['post'];

		        // Make sure the $posts variable is an array
		        if ( ! is_array( $order_ids ) ) {
			        $order_ids = array( $order_ids );
		        }
	        }

	        if ( current_user_can( 'manage_woocommerce' ) ) {
		        switch ( $action ) {
			        // 3. Perform the action
			        case 'quickpay_capture_recurring':
				        // Security check
				        $this->bulk_action_quickpay_capture_recurring( $order_ids );
				        break;
			        default:
				        return;
		        }
	        }

	        // 4. Redirect client
	        wp_redirect( $_SERVER['HTTP_REFERER'] );
	        exit;
        }

	    /**
	     * @param array $order_ids
	     */
        public function bulk_action_quickpay_capture_recurring( $order_ids = array() ) {
        	if (!empty($order_ids)) {
		        foreach ( $order_ids as $order_id ) {
					$order = new WC_QuickPay_Order($order_id);
                    $payment_method = version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method : $order->get_payment_method();
					if (WC_QuickPay_Subscription::is_renewal($order) && $order->needs_payment() && $payment_method === $this->id) {
						$this->scheduled_subscription_payment($order->get_total(), $order);
					}
		        }
	        }

        }


        /**
         *
         * in_plugin_update_message
         *
         * Show plugin changes. Code adapted from W3 Total Cache.
         *
         * @access public
         * @static
         * @return void
         */
        public static function in_plugin_update_message($args)
        {
            $transient_name = 'wcqp_upgrade_notice_' . $args['Version'];
            if (false === ($upgrade_notice = get_transient($transient_name))) {
                $response = wp_remote_get('https://plugins.svn.wordpress.org/woocommerce-quickpay/trunk/README.txt');

                if (!is_wp_error($response) && !empty($response['body'])) {
                    $upgrade_notice = self::parse_update_notice($response['body']);
                    set_transient($transient_name, $upgrade_notice, DAY_IN_SECONDS);
                }
            }

            echo wp_kses_post($upgrade_notice);
        }

        /**
         *
         * parse_update_notice
         *
         * Parse update notice from readme file.
         * @param  string $content
         * @return string
         */
        private static function parse_update_notice($content)
        {
            // Output Upgrade Notice
            $matches = null;
            $regexp = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote(WCQP_VERSION, '/' ) . '\s*=|$)~Uis';
            $upgrade_notice = '';

            if (preg_match($regexp, $content, $matches)) {
                $version = trim($matches[1]);
                $notices = (array)preg_split('~[\r\n]+~', trim($matches[2]));

                if (version_compare(WCQP_VERSION, $version, '<')) {

                    $upgrade_notice .= '<div class="wc_plugin_upgrade_notice">';

                    foreach ($notices as $index => $line) {
                        $upgrade_notice .= wp_kses_post(preg_replace('~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $line));
                    }

                    $upgrade_notice .= '</div> ';
                }
            }

            return wp_kses_post($upgrade_notice);
        }

        /**
         * path
         *
         * Returns a plugin URL path
         *
         * @param $path
         * @return mixed
         */
        public function plugin_url($path)
        {
            return plugins_url($path, __FILE__);
        }
    }

	/**
	 * Make the object available for later use
	 *
	 * @return WC_QuickPay
	 */
    function WC_QP()
    {
        return WC_QuickPay::get_instance();
    }

    // Instantiate
    WC_QP();
    WC_QP()->hooks_and_filters();

    // Add the gateway to WooCommerce
    function add_quickpay_gateway($methods)
    {
        $methods[] = 'WC_QuickPay';

        return apply_filters('woocommerce_quickpay_load_instances', $methods);
    }

    add_filter('woocommerce_payment_gateways', 'add_quickpay_gateway');
    add_filter('woocommerce_quickpay_load_instances', 'WC_QuickPay::filter_load_instances');
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'WC_QuickPay::add_action_links');
}

/**
 * Run installer
 * @param string __FILE__ - The current file
 * @param function - Do the installer/update logic.
 */
register_activation_hook(__FILE__, function () {
    require_once WCQP_PATH . 'classes/woocommerce-quickpay-install.php';

    // Run the installer on the first install.
    if (WC_QuickPay_Install::is_first_install()) {
        WC_QuickPay_Install::install();
    }
});