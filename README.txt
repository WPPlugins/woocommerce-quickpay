=== WooCommerce QuickPay ===
Contributors: PerfectSolution
Tags: gateway, woo commerce, quickpay, quick pay, gateway, integration, woocommerce, woocommerce quickpay, payment, payment gateway, psp
Requires at least: 4.0.0
Tested up to: 4.8.0
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrates your QuickPay payment gateway into your WooCommerce installation.

== Description ==
With WooCommerce QuickPay, you are able to integrate your QuickPay gateway to your WooCommerce install. With a wide list of API features including secure capturing, refunding and cancelling payments directly from your WooCommerce order overview. This is only a part of the many features found in this plugin.

== Installation ==
1. Upload the 'woocommerce-quickpay' folder to /wp-content/plugins/ on your server.
2. Log in to Wordpress administration, click on the 'Plugins' tab.
3. Find WooCommerce QuickPay in the plugin overview and activate it.
4. Go to WooCommerce -> Settings -> Payment Gateways -> QuickPay.
5. Fill in all the fields in the "QuickPay account" section and save the settings.
6. You are good to go.

== Dependencies ==
General:
1. PHP: >= 5.3
2. WooCommerce >= 2.5
3. If WooCommerce Subscriptions is used, the required minimum version is >= 2.0

== Changelog ==
= 4.8.2 =
* Add filter woocommerce_quickpay_order_number_for_api
* Change order of transaction ID meta key searches

= 4.8.1 =
* Remove SWIPP as possible payment option icon.
* Add setting: Autocompletion of successful renewal/recurring orders.
* Add payment type check in woocommerce_order_status_completed to early break out if a different gateway is used on the order.
* Fix issue where fee was not capturable from the order view with MobilePay payments.

= 4.8.0 =
* Add WooCommerce 3 compatibility release
* Add filter woocommerce_quickpay_transaction_params_variables
* Add filter woocommerce_quickpay_is_request_to_change_payment
* Add subscription status check in the subscription_cancellation hook to avoid transactions being cancelled on subscriptions that are actually active.
* Bulk action to retry failed payments and activate the subscription on successful captures.
* Add transaction metadata accessor method
* Add transaction state accessor method
* Add shipping to transaction basket items.
* Fix typo in Paypal on icon selection
* Remove SWIPP support
* Isolating meta view to separate view file.
* Fix incorrect page check for adding meta boxes.

= 4.7.0 =
* Minor settings helper text updates.
* Add support for qTranslateX in the callback handler. Added logic to prevent browser redirects resulting in callback data loss.
* WP-SpamShield - Bypass security check on QuickPay callbacks.
* Improve product switching (downgrade/upgrade)
* Fix syntax error in classes/updates/woocommerce-quickpay-update-4.6.php resulting in update not completing in case of caught exceptions.
* Remove obsolete Google Analytics Client ID setting.

= 4.6.8 =
* Fix issues with WooCommerce-check failing on network-sites.

= 4.6.7 =
* Add dependency check before loading class files to avoid site crashes in case WooCommerce is disabled.

= 4.6.6 =
* Exclude TRANSACTION_ID from being copied from subscriptions to renewal orders.
* Update translations

= 4.6.5 =
* Make WC_QuickPay_Views::get_view PHP 5.3 compatible.
* Patch cases where transaction ID was not always found on renewal orders.

= 4.6.4 =
* Fix issue with WC_QuickPay_Install not being included properly on plugin activation

= 4.6.3 =
* Remove: WC_QuickPay_Install_Helper
* Improvement: Stop relying on register_activation_hook when upgrading.
* Improvement: Show admin notice when a database upgrade is required. This action must be triggered manually and it will run in the background.
* Add views folder
* Add WC_QuickPay_Views to simplify view handling.

= 4.6.2 =
* Fix issue with older PHP version not bein able to use return value in write context in WC_QuickPay_Settings.

= 4.6.1 =
* Replaced Paii logo with Swipp

= 4.6.0 =
* Feature: Add basket content to transactions.
* Feature: Always add invoice + shipping information on transactions.
* Feature: Add Klarna as separate payment method.
* Feature: Add Swipp as separate payment method.
* Feature: Add Sofort as separate payment method
* Feature: New filters added. (woocommerce_quickpay_transaction_params_shipping, woocommerce_quickpay_transaction_params_invoice, woocommerce_quickpay_transaction_params_basket)
* Feature: Visualize required settings on the settings page.
* Feature: Add admin notice if required fields are not configured.
* Feature: Add button in the plugin settings' "Logs"-section for easy debug log access.
* Feature: Add direct link to the wiki from the settings page.
* Feature: Add live API key validator on the settings page.
* Feature: Simplifying the settings page by removing unused fields.
* Feature: Add hook 'woocommerce_quickpay_loaded'.
* Feature: Add hook 'woocommerce_quickpay_accepted_callback_status_{$state}'.
* Removed: Autocapture settings for subscriptions. Subscriptions now rely on the main autocapture settings (Physical/virtual products).
* Removed: WC_QuickPay_Order::get_callback_url - deprecated since 4.2.0.
* Bug: Remove subscription cancellation from callback handler, on 'cancel'-callbacks to avoid situations where subscriptions ends up in a faulty "Pending Cancellation" state.
* Bug: Fix bug where fees area added on top of each other.
* Bug: Clean up old payment IDs and payment links before creating a new payment link used to update a credit card. Legacy data caused problems in some cases.
* Improvement: Complete refactoring of how subscriptions are handled. The subscription transaction ID is now stored on the 'shop_subscription'-post. Now only payment transactions are stored on regular orders which should improve the renewal/capturing process and make the UI more intuitive. This should also eliminate a lot of quirks when it comes to renewal orders.


= 4.5.6 =
* Fix bug where certain customers are not able to manually pay a failed recurring order.
* Add convenience wrapper WC_QuickPay_Subscription::cart_contains_failed_renewal_order_payment()
* Add convenience wrapper WC_QuickPay_Subscription::get_subscription_for_renewal_order()
* Add convenience wrapper WC_QuickPay_Subscription::get_subscriptions_for_order()
* Add convenience wrapper WC_QuickPay_Subscription::cart_contains_renewal()
* Add ?synchronized query parameter to recurring requests.
* Add WC_QuickPay_Order::get_payment_method_change_count()
* Add WC_QuickPay_Order::increase_payment_method_change_count()
* Hook into woocommerce_subscription_payment_method_updated_to_*
* Use $order->update_status on failed recurring payments instead of WC_Subscriptions_Manager::process_subscription_payment_failure_on_order to get a correct count of failed payments.
* Append the payment count (or timestamp to ensure backwards compatibility) to the order numbers sent to the QuickPay API when manually paying a failed recurring order.

= 4.5.5 =
* Fix: Problem with fees being incorrectly stored when using custom decimal pointers. Rely on wp_format_decimals.

= 4.5.4 =
* Add support for subscription_payment_method_change_customer
* Add transaction state check in WC_QuickPay::subscription_cancel
* Add WC_QuickPay_Order::is_request_to_change_payment()

= 4.5.3 =
* Add possibility to disable transaction information in the order overview
* Fix bug in WC_QuickPay_Helper::price_multiply which didn't properly format prices where are not standard English format.
* Add WC_QuickPay_Helper::price_multiplied_to_float
* Add WC_QuickPay_Helper::price_custom_to_multiplied
* Add unit tests and composer.json to repository

= 4.5.2 =
* Fix problem where settings could not be saved for MobilePay and ViaBill

= 4.5.1 =
* Fix problems with some merchants experiencing failed orders after successful payments.

= 4.5.0 =
* Add WC_QuickPay_Order::has_quickpay_payment().
* Add WC_QuickPay_API_Transaction::get_brand().
* Add WC_QuickPay_API_Transaction::get_currency().
* Add WC_QuickPay_API_Transaction::get_balance().
* Add WC_QuickPay_API_Transaction::get_formatted_balance().
* Add WC_QuickPay_API_Transaction::get_remaining_balance().
* Add WC_QuickPay_API_Transaction::get_formatted_remaining_balance().
* Add WC_QuickPay_API_Transaction::is_operation_approved( $operation ).
* Add WC_QuickPay::plugins_url.
* Add WC_QuickPay_Helper::has_preorder_plugin.
* Feature: Add support for WooCommerce Pre Orders
* Feature: Add Card icons to transaction meta data. Issue #62986808298852.
* Feature: Add possibility to capture a specified amount and not only the full order amount.
* Add Translation template (woo-quickpay.pot).
* Fix: Meta-box being shown when any transactionID if mapped on the order. Issue #145750965321211.
* Fix: Avoid multiple hooks and filters. Thanks to David Tolnem for investigating and providing code example.
* Improvement: Compressed PNG card icons.
* Improvement: Update existing payment links on process payment.
* Improvement: Stop clearing the customer basket on payment processing. This step has been moved to "thank_you"-page.
* Improvement: Update translations.
* Rename WC_QuickPay_API_Transaction::create_link to WC_QuickPay_API_Transaction::patch_link.
* Remove: WC_QuickPay::prepare_extras()

= 4.4.5 =
* Add support for multiple subscriptions.

= 4.4.4 =
* Fix problem with Paii attempted to be loaded after removal.

= 4.4.3 =
* Only make transaction status checks on orders with _transaction_id AND payment methods 'quickpay', 'mobilepay' and 'viabill'
* Remove Paii gateway instance

= 4.4.2 =
* Fix I18n textdomain load bug
* Add wpml-config.xml
* Add title to wpml-config.xml
* Add description to wpml-config.xml
* Add checkout_button_text to wpml-config.xml
* Add 'order_post_id' param to callback URL on recurring payments to ensure compatability with third party software changing the order number.
* Add maxlength on text_on_statement

= 4.4.1 =
* Fix incosistent subscription check which might cause problems for some shops.

= 4.4.0 =
* Update translations
* Change QuickPay_Helper::get_callback_url() to use site_url instead of home_url. This ensures callbacks to always reach the Wordpress core.
* Add WC_QuickPay_Subscription as convenience wrapper
* Support for WooCommerce Subscriptions > 2.x
* Removed support for WooCommerce Subscriptions 1.x.x
* Refactor the method for checking if WC Subscriptions is enabled to support flexible folder names.
* Deprecate the TRANSACTION_ID meta tag.
* Refactor WC_QuickPay_Order::get_transaction_id - rely on the built in transaction ID if available.
* Rely on WC_QuickPay::scheduled_subscription_payment() when creating the initial subscription payment.
* Add curl_request_url to WC_QuickPay_Exception to optimize troubleshooting.
* Add possibility to clear the debug logs.

= 4.3.5 =
* Add: WC_QuickPay_API_Subscriptions::process_recurring_response().
* Fix: First autocapture on subscriptions not working.
* Fix: Problems with recurring payment references not working properly.
* Remove: recurring from callback_handler switch.

= 4.3.4 =
* Minor update to WC_QuickPay_Order::get_clean_order_number() to prevent hash-tags in order numbers, which is occasionally added by some shops.

= 4.3.3 =
* Change method descriptions.
* Disable unnecessary debug information.

= 4.3.2 =
* Fix: Short order numbers resulted in gateway errors.

= 4.3.1 =
* Feature: Add support for both fixed currency and auto-currency. Auto currency should be used when supporting multiple currencies on a web shop.

= 4.3 =
* Tweak: Refactor filter: woocommerce_order_status_completed. Now using the passed post_id.
* Feature: Add setting, checkout_button_text - button text shown when choosing payment.
* Feature: Add property WC_QuickPay::$order_button_text.
* Feature: Add WC_QuickPay_Install to handle DB updates for this and future versions.
* Feature: Add setting, quickpay_autocapture_virtual - Makes it possible for you to set a different autocapture configuration for virtual products. If the order contains both a virtual and a non-virtual product, it will default to the configuration set in "quickpay_autocapture".
* Add filter: woocommerce_quickpay_transaction_link_params.
* Fix: Paii specific settings (category, reference_title, product_id).
* Remove: WC_QuickPay_Helper::prefix_order_number().
* Feature: Support "WooCommerce Sequential Order Numbers" order number prefix/suffix.
* Remove: WC_QuickPay::find_order_by_order_number() - rely on the post ID now stored on the transaction.
* Fix: Remove currency from recurring requests
* Feature: Add support for text_on_statement for Clearhaus customers.
* Feature: Add customer_email to payment/subscription links. (Used for PayPal transactions).
* Feature: Add support for subscription_payment_method_change
* Feature: Add transaction ID, transaction order ID, payment ID and payment links to the meta content box for easy access and better debugging.
* Update translations.

= 4.2.2 =
* Fix: Payment icons not working in WooCommerce 2.4.
* Fix: JSON encode and typecast error objects in case no specific error message is set from QuickPay
* Fix: Add additional params to http_build_query to support server setups requirering param 2+3 to work properly
* Fix: Remove obosolete quickpay_paybuttontext setting from instances
* Tweak: Move woocommerce_order_complete hook outside is_admin check
* Tweak: Add post data params to API exceptions
* Tweak: Wrap process payment in try/catch and write any errors to WC system logs.

= 4.2.1 =
* Reintroduce merchant ID for support usability
* Update keys
* Update translations

= 4.2.0 =
* Deprecating WC_QuickPay::get_callback_url(). Use WC_QuickPay_Helper::get_callback_url() instead.
* Add QuickPay-Callback-Url to API request headers.
* Correct name casing in title and descriptions.
* Add method_title to instances
* Prefix subinstances with "QuickPay - %s" for usability reasons.
* Disable subscription support on MobilePay, Paii and ViaBill
* Add support for payment links. Removing old FORM method.
* Add tooltip descriptions to settings page
* Improved API error logging
* Add jQuery multiselect to 'Credit card icons'
* Change subscription description from "qp_subscription" to "woocommerce-subscription"
* Removed all settings and files related to the auto-redirect.
* Remove setting: quickpay_merchantid
* Remove setting: quickpay_redirect
* Remove setting: quickpay_redirectText
* Remove setting: quickpay_paybuttontext
* Add setting: quickpay_custom_variables
* Remove old tags before 3.0.6

= 4.1.0 =
* Add Google Analytics support
* Performance optimization: The order view is now making async requests to retrieve the transaction state.
* Add complete order reference in order overview
* Add version number to the plugin settings page
* Add support for multiple instances. Now it is possible to add MobilePay, Paii and viaBill as separate payment methods. Each instance is based on the core module settings to ensure a minimum amount of configuration.
* Add setting: quickpay_redirect - allows the shop owner to enable/disable the auto redirection in the checkout process.
* Remove setting: quickpay_mobilepay
* Remove setting: quickpay_viabill
* Remove setting: quickpay_labelCreditCard
* Remove setting: quickpay_labelViaBill
* Remove setting: quickpay_debug
* Fix problem with attempt of payment capture when setting order status to complete on a subscription order.
* Updated translations

= 4.0.7 =
* Add upgrade notiece for 4.0.0

= 4.0.6 =
* Activate autofee settings
* Implement upgrade notices inside the plugins section
* Update incorrect autofee key in recurring requests
* Update success response HTTP codes
* Typecasting response to string if no message object is available

= 4.0.5 =
* Add the possibility to set a custom branding ID

= 4.0.4 =
* Stop forcing HTTP on callbacks.

= 4.0.3 =
* Add WC_QuickPay_API_Subscription::is_action_allowed
* Manual AJAX actions handled for subscriptions

= 4.0.2 =
* Add mobilepay option
* Disabled viabill since the QuickPay API is not ready to support it yet.

= 4.0.1 =
* Add version parameter to the payment request

= 4.0.0 =
* Now only supports the new QuickPay gateway platform
* Introduce exception class QuickPay_Exception
* Introduce exception class QuickPay_API_Exception
* Introduce WC_QuickPay::process_refund to support "auto" gateway refunds
* Introduce WC_QuickPay_API
* Introduce WC_QuickPay_API_Payment
* Introduce WC_QuickPay_API_Subscription
* Introduce WC_QuickPay_Log - Debugging information is now added to WooCommerce system logs.
* Remove WC_QuickPay_Request
* Remove donation link

= 3.0.9 =
* Add support for important update notifications fetched from the README.txt file.

= 3.0.8 =
* Switched to WC_Order::get_total() instead of WC_Order::order_total to fix issues with WPML currencies.

= 3.0.6 =
* Added proper support for both Sequential Order Numbers FREE and Sequential Order Numbers PRO.

= 3.0.5 =
* Bugfix: 502 on checkout on shops hosted with wpengine.com.

= 3.0.4 =
* Add filter 'woocommerce_quickpay_currency' which can be used to dynamically edit the gateway currency
* Add filter 'woocommerce_quickpay_language' which can be used to dynamically edit the gateway language

= 3.0.3 =
* Added support for credit card icons in the settings.
* Re-implented auto redirect on checkout page

= 3.0.2 =
* Fixed MD5 hash problem when not in test mode

= 3.0.1 =
* Added refund support
* Update Danish i18n

= 3.0.0 =
* Completely refactored the plugin. The logic has been splitted into multiple classes, and a lot of bugs should've been eliminated with this version.
* Added ajax calls when using the API

= 2.1.6 =
* Optimized fee handling

= 2.1.5 =
* Added support for Paii

= 2.1.4 =
* Added action links to "Installed plugins" overview
* Fixed md5 checksum error caused by testmode
* Fixed problem with coupons not working properly on subscriptions
* Fixed problem with lagging the use of payment_complete() on successful payments

= 2.1.3 =
* Added i18n support, current supported languages: en_UK, da_DK
* Added possibility to add email instructions on the order confirmation. Thanks to Emil Eriksen for idea and contribution.
* Added possibility to change test mode directly in WooCommerce. Thanks to Emil Eriksen for idea and contribution.
* Added eye candy in form of SVN header banner
* Added donation link to all of you lovely fellows who might wanna donate a coin for our work.

= 2.1.2 =
* Fixed an undefined variable notices
* Switched from WC_Subscriptions_Order::get_price_per_period to WC_Subscriptions_Order::get_recurring_total
* Added payment transaction fee to orders
* Changed name to WooCommerce QuickPay

= 2.1.1 =
* Fixes FATAL ERROR bug on checkout introduced in 2.1.0
* Plugin URI in gateway-quickpay.php

= 2.1.0 =
* Bugfix: Static call to a non-static method caused strict errors.
* Added support for WooCommerce 2.1.

= 2.0.9 =
* Bug where custom meta boxes were not instantiated should be fixed in this version
* More currencies added (SEK, NOK, GBP)

= 2.0.8 =
* Fixed viabill cardtypelock

= 2.0.7 =
* Fixed bug where server complains about QuickPay SSL certificate.
* Changed iBill labels to viaBill
* Added the possibility to set a custom text on the checkout page right before the customer is redirected to the QuickPay payment window.
* Added the possibility to set a custom label to credit card and viaBill.

= 2.0.6 =
* Fixed bug where recurring payments were not being captured properly.
* Fixed undefined variable notice "params_string".

= 2.0.4 =
* Implemented a tweak to the "WooCommerce Sequential Order Numbers"-support which should fix any problems with WooCommerce QuickPay + Sequential order numbers.

= 2.0.3 =
* Fixing issues with cardtypelocks

= 2.0.2 =
* Enabling auto redirect on receipt page which accidently got disabled in 2.0.1

= 2.0.1 =
* Updated a hook causing problems with saving gateway settings.

= 2.0.0 =
* Build to work with WooCommerce 2.0.x or higher
* Refactoring the majority of existing methods to save a lot of code and implementing better API error handling.

= 1.4.0 =
* Implement WC_QuickPay::create_md5() which manually sets the order of the md5 checkpoints.
* Should fix payment integration and missing mails sent out to customers after implementation of protocol v7.

= 1.3.11 =
* Plugin now uses QuickPay version 7

= 1.3.10 =
* Feature: Allow customers to select between credit card and iBill when choosing QuickPay as pay method. Credit card is ticket as default option. 		NB: You are required to have an agreement with iBill in order to use this feature properly.

= 1.3.9 =
* 'Capture on complete' now also works on bulk actions.

= 1.3.8 =
* Short install guide added to README.txt

= 1.3.7 =
* 'Capture on complete' is implemented as an option in the gateway settings. It can be turned on/off. Default: Off
* This is a faster way to process your orders. When the order state is set to "completed", the payment will automatically be capture. This works in both the order overview and in the single order view.

= 1.3.6 =
* Bugfix: Implemented missing check for WC Subscriptions resulting in fatal error on api_action_router().


= 1.3.5 =
* Bugfix: Problem with transaction ID not being connected to an order [FIXED].

= 1.3.4 =
* Added better support for "WooCommerce Sequential Order Numbers".
* Automatically redirects after 5 seconds on "Checkout -> Pay"-page.

= 1.3.3 =
* Bugfix: Corrected bug not showing price corectly on subscriptions in payment window.

= 1.3.1 =
* Bugfix: Systems not having WooCommerce Subscriptions enabled got a fatal error on payment site.

= 1.3.0 =
* Added support for WooCommerce subscription.
* Now reduces stock when a payment is completed.

= 1.2.2 =
* Bugfix: Capturing payments from WooCommerce backend caused problems due to missing order_total param in cURL request.

= 1.2.1 =
* More minor changes to the payment cancellations from QuickPay form.

= 1.2.0 =
* Major rewriting of payments cancelled by customer.

= 1.1.3 =
* Implemented payment auto capturing.

= 1.1.2 =
* Link back to payment page after payment cancellation added.

= 1.1.1 =
* If a payment is cancelled by user, a $woocommerce->add_error will now be shown, notifying the customer about this. We also post a note to the order about cancellation.

= 1.1.0 =
* Changed plugin structure.
* core.js added to the plugin to avoid inline javascript.
* Implemented payment state and transaction id in order overview.
* Implemented payment handling in single order view.
* Added support for split payments
* If turned on in QuickPay Manager, shop owners may now split up the transactions.
* Rewritten and added a lot of the class methods.

= 1.0.1 =
*  Bugfix: Corrected a few unchecked variables that caused php notices in error logs.

== Upgrade Notice ==
= 4.6.0 =
4.6.0 is a major update and requires data migration. We strongly recommend saving a backup of your database before upgrading this plugin!