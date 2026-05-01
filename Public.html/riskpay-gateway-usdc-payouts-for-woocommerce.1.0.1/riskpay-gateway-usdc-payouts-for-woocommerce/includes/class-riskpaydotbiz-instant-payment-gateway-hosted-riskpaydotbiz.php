<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', 'riskpaydotbiz_hosted_riskpaydotbiz_gateway');

function riskpaydotbiz_hosted_riskpaydotbiz_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }


class RiskPayDotBiz_Instant_Payment_Gateway_Hosted_RiskPayDotBiz extends WC_Payment_Gateway {

    protected $icon_url;
    protected $promo_code;
    protected $hostedriskpaydotbiz_wallet_address;

    public function __construct() {
        $this->id                 = 'riskpaydotbiz-instant-payment-gateway-hosted-riskpaydotbiz';
        $this->icon               = sanitize_url($this->get_option('icon_url'));
        $this->promo_code         = sanitize_text_field($this->get_option('promo_code'));
        $this->method_title       = esc_html__('Credit Cards - Debit Cards - SEPA Bank Transfer Hosted Mode (RiskPay.biz Multi-Provider)', 'riskpay-gateway-usdc-payouts-for-woocommerce'); // Escaping title
        $this->method_description = esc_html__('Multi-Providers credit card hosted payment gateway with automatic customer geo location detection for highest conversion and maximum security.', 'riskpay-gateway-usdc-payouts-for-woocommerce'); // Escaping description
        $this->has_fields         = false;

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = sanitize_text_field($this->get_option('title'));
        $this->description = sanitize_text_field($this->get_option('description'));

        // Use the configured settings for redirect and icon URLs
        $this->hostedriskpaydotbiz_wallet_address = sanitize_text_field($this->get_option('hostedriskpaydotbiz_wallet_address'));
        $this->promo_code   = sanitize_text_field($this->get_option('promo_code'));
        $this->icon_url     = sanitize_url($this->get_option('icon_url'));

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => esc_html__('Enable/Disable', 'riskpay-gateway-usdc-payouts-for-woocommerce'), // Escaping title
                'type'    => 'checkbox',
                'label'   => esc_html__('Enable RiskPay.biz payment gateway', 'riskpay-gateway-usdc-payouts-for-woocommerce'), // Escaping label
                'default' => 'no',
            ),
            'title' => array(
                'title'       => esc_html__('Title', 'riskpay-gateway-usdc-payouts-for-woocommerce'), // Escaping title
                'type'        => 'text',
                'description' => esc_html__('Payment method title that users will see during checkout.', 'riskpay-gateway-usdc-payouts-for-woocommerce'), // Escaping description
                'default'     => esc_html__('Credit Card', 'riskpay-gateway-usdc-payouts-for-woocommerce'), // Escaping default value
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => esc_html__('Description', 'riskpay-gateway-usdc-payouts-for-woocommerce'), // Escaping title
                'type'        => 'textarea',
                'description' => esc_html__('Payment method description that users will see during checkout.', 'riskpay-gateway-usdc-payouts-for-woocommerce'), // Escaping description
                'default'     => esc_html__('Pay via credit card', 'riskpay-gateway-usdc-payouts-for-woocommerce'), // Escaping default value
                'desc_tip'    => true,
            ),
            'hostedriskpaydotbiz_wallet_address' => array(
                'title'       => esc_html__('Wallet Address', 'riskpay-gateway-usdc-payouts-for-woocommerce'), // Escaping title
                'type'        => 'text',
                'description' => esc_html__('Insert your USDC (Polygon) wallet address to receive instant payouts. Payouts maybe sent in USDC or USDT (Polygon or BEP-20) or POL native token. Same wallet should work to receive all. Make sure you use a self-custodial wallet to receive payouts.', 'riskpay-gateway-usdc-payouts-for-woocommerce'), // Escaping description
                'desc_tip'    => true,
            ),
            'promo_code' => array(
                'title'       => esc_html__('Promo Code', 'riskpay-gateway-usdc-payouts-for-woocommerce'), // Escaping title
                'type'        => 'text',
                'description' => esc_html__('Enter your promo code to get additional discounts.', 'riskpay-gateway-usdc-payouts-for-woocommerce'), // Escaping description
                'desc_tip'    => true,
            ),
            'icon_url' => array(
                'title'       => esc_html__('Icon URL', 'riskpay-gateway-usdc-payouts-for-woocommerce'), // Escaping title
                'type'        => 'url',
                'description' => esc_html__('Enter the URL of the icon image for the payment method.', 'riskpay-gateway-usdc-payouts-for-woocommerce'), // Escaping description
                'desc_tip'    => true,
            ),
        );
    }
	 // Add this method to validate the wallet address in wp-admin
    public function process_admin_options() {
		if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'woocommerce-settings')) {
    WC_Admin_Settings::add_error(__('Nonce verification failed. Please try again.', 'riskpay-gateway-usdc-payouts-for-woocommerce'));
    return false;
}
        $hostedriskpaydotbiz_admin_wallet_address = isset($_POST[$this->plugin_id . $this->id . '_hosted_riskpaydotbiz_wallet_address']) ? sanitize_text_field( wp_unslash( $_POST[$this->plugin_id . $this->id . '_hosted_riskpaydotbiz_wallet_address'])) : '';

        // Check if wallet address starts with "0x"
        if (substr($hostedriskpaydotbiz_admin_wallet_address, 0, 2) !== '0x') {
            WC_Admin_Settings::add_error(__('Invalid Wallet Address: Please insert your USDC Polygon wallet address.', 'riskpay-gateway-usdc-payouts-for-woocommerce'));
            return false;
        }

        // Check if wallet address matches the USDC contract address
        if (strtolower($hostedriskpaydotbiz_admin_wallet_address) === '0x3c499c542cef5e3811e1192ce70d8cc03d5c3359') {
            WC_Admin_Settings::add_error(__('Invalid Wallet Address: Please insert your USDC Polygon wallet address.', 'riskpay-gateway-usdc-payouts-for-woocommerce'));
            return false;
        }

        // Proceed with the default processing if validations pass
        return parent::process_admin_options();
    }
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $riskpaydotbiz_hosted_riskpaydotbiz_currency = get_woocommerce_currency();
		$riskpaydotbiz_hosted_riskpaydotbiz_total = $order->get_total();
		$riskpaydotbiz_hosted_riskpaydotbiz_nonce = wp_create_nonce( 'riskpaydotbiz_hosted_riskpaydotbiz_nonce_' . $order_id );
		$riskpaydotbiz_hosted_riskpaydotbiz_callback = add_query_arg(array('order_id' => $order_id, 'nonce' => $riskpaydotbiz_hosted_riskpaydotbiz_nonce,), rest_url('riskpaydotbiz/v1/riskpaydotbiz-hosted-riskpaydotbiz/'));
		$riskpaydotbiz_hosted_riskpaydotbiz_email = urlencode(sanitize_email($order->get_billing_email()));
		$riskpaydotbiz_hosted_riskpaydotbiz_final_total = $riskpaydotbiz_hosted_riskpaydotbiz_total;
		
		if ($riskpaydotbiz_hosted_riskpaydotbiz_currency === 'USD') {
		$riskpaydotbiz_hosted_riskpaydotbiz_reference_total = (float)$riskpaydotbiz_hosted_riskpaydotbiz_final_total;
		} else {
		
$riskpaydotbiz_hosted_riskpaydotbiz_response = wp_remote_get('https://api.riskpay.biz/control/convert.php?value=' . $riskpaydotbiz_hosted_riskpaydotbiz_total . '&from=' . strtolower($riskpaydotbiz_hosted_riskpaydotbiz_currency), array('timeout' => 30));

if (is_wp_error($riskpaydotbiz_hosted_riskpaydotbiz_response)) {
    // Handle error
    riskpaydotbiz_add_notice(__('Payment error:', 'riskpay-gateway-usdc-payouts-for-woocommerce') . __('Payment could not be processed due to failed currency conversion process, please try again', 'riskpay-gateway-usdc-payouts-for-woocommerce'), 'error');
    return null;
} else {

$riskpaydotbiz_hosted_riskpaydotbiz_body = wp_remote_retrieve_body($riskpaydotbiz_hosted_riskpaydotbiz_response);
$riskpaydotbiz_hosted_riskpaydotbiz_conversion_resp = json_decode($riskpaydotbiz_hosted_riskpaydotbiz_body, true);

if ($riskpaydotbiz_hosted_riskpaydotbiz_conversion_resp && isset($riskpaydotbiz_hosted_riskpaydotbiz_conversion_resp['value_coin'])) {
    // Escape output
    $riskpaydotbiz_hosted_riskpaydotbiz_finalusd_total	= sanitize_text_field($riskpaydotbiz_hosted_riskpaydotbiz_conversion_resp['value_coin']);
    $riskpaydotbiz_hosted_riskpaydotbiz_reference_total = (float)$riskpaydotbiz_hosted_riskpaydotbiz_finalusd_total;	
} else {
    riskpaydotbiz_add_notice(__('Payment error:', 'riskpay-gateway-usdc-payouts-for-woocommerce') . __('Payment could not be processed, please try again (unsupported store currency)', 'riskpay-gateway-usdc-payouts-for-woocommerce'), 'error');
    return null;
}	
		}
		}
	
$riskpaydotbiz_hosted_riskpaydotbiz_gen_wallet = wp_remote_get('https://api.riskpay.biz/control/wallet.php?address=' . $this->hostedriskpaydotbiz_wallet_address  . '&promo=' . $this->promo_code . '&callback=' . urlencode($riskpaydotbiz_hosted_riskpaydotbiz_callback), array('timeout' => 30));

if (is_wp_error($riskpaydotbiz_hosted_riskpaydotbiz_gen_wallet)) {
    // Handle error
    riskpaydotbiz_add_notice(__('Wallet error:', 'riskpay-gateway-usdc-payouts-for-woocommerce') . __('Payment could not be processed due to incorrect payout wallet settings, please contact website admin', 'riskpay-gateway-usdc-payouts-for-woocommerce'), 'error');
    return null;
} else {
	$riskpaydotbiz_hosted_riskpaydotbiz_wallet_body = wp_remote_retrieve_body($riskpaydotbiz_hosted_riskpaydotbiz_gen_wallet);
	$riskpaydotbiz_hosted_riskpaydotbiz_wallet_decbody = json_decode($riskpaydotbiz_hosted_riskpaydotbiz_wallet_body, true);

 // Check if decoding was successful
    if ($riskpaydotbiz_hosted_riskpaydotbiz_wallet_decbody && isset($riskpaydotbiz_hosted_riskpaydotbiz_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $riskpaydotbiz_hosted_riskpaydotbiz_gen_addressIn = wp_kses_post($riskpaydotbiz_hosted_riskpaydotbiz_wallet_decbody['address_in']);
        $riskpaydotbiz_hosted_riskpaydotbiz_gen_polygon_addressIn = sanitize_text_field($riskpaydotbiz_hosted_riskpaydotbiz_wallet_decbody['polygon_address_in']);
		$riskpaydotbiz_hosted_riskpaydotbiz_gen_callback = sanitize_url($riskpaydotbiz_hosted_riskpaydotbiz_wallet_decbody['callback_url']);
		// Save $hostedriskpaydotbizresponse in order meta data
    $order->add_meta_data('riskpaydotbiz_hosted_riskpaydotbiz_tracking_address', $riskpaydotbiz_hosted_riskpaydotbiz_gen_addressIn, true);
    $order->add_meta_data('riskpaydotbiz_hosted_riskpaydotbiz_polygon_temporary_order_wallet_address', $riskpaydotbiz_hosted_riskpaydotbiz_gen_polygon_addressIn, true);
    $order->add_meta_data('riskpaydotbiz_hosted_riskpaydotbiz_callback', $riskpaydotbiz_hosted_riskpaydotbiz_gen_callback, true);
	$order->add_meta_data('riskpaydotbiz_hosted_riskpaydotbiz_converted_amount', $riskpaydotbiz_hosted_riskpaydotbiz_final_total, true);
	$order->add_meta_data('riskpaydotbiz_hosted_riskpaydotbiz_expected_amount', $riskpaydotbiz_hosted_riskpaydotbiz_reference_total, true);
	$order->add_meta_data('riskpaydotbiz_hosted_riskpaydotbiz_nonce', $riskpaydotbiz_hosted_riskpaydotbiz_nonce, true);
    $order->save();
    } else {
        riskpaydotbiz_add_notice(__('Payment error:', 'riskpay-gateway-usdc-payouts-for-woocommerce') . __('Payment could not be processed, please try again (wallet address error)', 'riskpay-gateway-usdc-payouts-for-woocommerce'), 'error');

        return null;
    }
}

// Check if the Checkout page is using Checkout Blocks
if (riskpaydotbiz_is_checkout_block()) {
    global $woocommerce;
	$woocommerce->cart->empty_cart();
}

        // Redirect to payment page
        return array(
            'result'   => 'success',
            'redirect' => 'https://checkout.riskpay.biz/pay.php?address=' . $riskpaydotbiz_hosted_riskpaydotbiz_gen_addressIn  . '&promo=' . $this->promo_code . '&amount=' . (float)$riskpaydotbiz_hosted_riskpaydotbiz_final_total . '&email=' . $riskpaydotbiz_hosted_riskpaydotbiz_email . '&currency=' . $riskpaydotbiz_hosted_riskpaydotbiz_currency,
        );
    }

public function riskpaydotbiz_instant_payment_gateway_get_icon_url() {
        return !empty($this->icon_url) ? esc_url($this->icon_url) : '';
    }
}

function riskpaydotbiz_add_instant_payment_gateway_hosted_riskpaydotbiz($gateways) {
    $gateways[] = 'RiskPayDotBiz_Instant_Payment_Gateway_Hosted_RiskPayDotBiz';
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'riskpaydotbiz_add_instant_payment_gateway_hosted_riskpaydotbiz');
}

// Add custom endpoint for changing order status
function riskpaydotbiz_hosted_riskpaydotbiz_change_order_status_rest_endpoint() {
    // Register custom route
    register_rest_route( 'riskpaydotbiz/v1', '/riskpaydotbiz-hosted-riskpaydotbiz/', array(
        'methods'  => 'GET',
        'callback' => 'riskpaydotbiz_hosted_riskpaydotbiz_change_order_status_callback',
        'permission_callback' => '__return_true',
    ));
}
add_action( 'rest_api_init', 'riskpaydotbiz_hosted_riskpaydotbiz_change_order_status_rest_endpoint' );

// Callback function to change order status
function riskpaydotbiz_hosted_riskpaydotbiz_change_order_status_callback( $request ) {
    $order_id = absint($request->get_param( 'order_id' ));
	$riskpaydotbiz_hosted_riskpaydotbizgetnonce = sanitize_text_field($request->get_param( 'nonce' ));
	$riskpaydotbiz_hosted_riskpaydotbizpaid_txid_out = sanitize_text_field($request->get_param('txid_out'));
	$riskpaydotbiz_hosted_riskpaydotbizpaid_value_coin = sanitize_text_field($request->get_param('value_coin'));
	$riskpaydotbiz_hosted_riskpaydotbizfloatpaid_value_coin = (float)$riskpaydotbiz_hosted_riskpaydotbizpaid_value_coin;

    // Check if order ID parameter exists
    if ( empty( $order_id ) ) {
        return new WP_Error( 'missing_order_id', __( 'Order ID parameter is missing.', 'riskpay-gateway-usdc-payouts-for-woocommerce' ), array( 'status' => 400 ) );
    }

    // Get order object
    $order = wc_get_order( $order_id );

    // Check if order exists
    if ( ! $order ) {
        return new WP_Error( 'invalid_order', __( 'Invalid order ID.', 'riskpay-gateway-usdc-payouts-for-woocommerce' ), array( 'status' => 404 ) );
    }
	
	// Verify nonce
    if ( empty( $riskpaydotbiz_hosted_riskpaydotbizgetnonce ) || $order->get_meta('riskpaydotbiz_hosted_riskpaydotbiz_nonce', true) !== $riskpaydotbiz_hosted_riskpaydotbizgetnonce ) {
        return new WP_Error( 'invalid_nonce', __( 'Invalid nonce.', 'riskpay-gateway-usdc-payouts-for-woocommerce' ), array( 'status' => 403 ) );
    }

    // Check if the order is pending and payment method is 'riskpaydotbiz-instant-payment-gateway-hosted-riskpaydotbiz'
    if ( $order && $order->get_status() !== 'processing' && $order->get_status() !== 'completed' && 'riskpaydotbiz-instant-payment-gateway-hosted-riskpaydotbiz' === $order->get_payment_method() ) {
	$riskpaydotbiz_hosted_riskpaydotbizexpected_amount = (float)$order->get_meta('riskpaydotbiz_hosted_riskpaydotbiz_expected_amount', true);
	$riskpaydotbiz_hosted_riskpaydotbizthreshold = 0.60 * $riskpaydotbiz_hosted_riskpaydotbizexpected_amount;
		if ( $riskpaydotbiz_hosted_riskpaydotbizfloatpaid_value_coin < $riskpaydotbiz_hosted_riskpaydotbizthreshold ) {
			// Mark the order as failed and add an order note
            $order->update_status('failed', __( 'Payment received is less than 60% of the order total. Customer may have changed the payment values on the checkout page.', 'riskpay-gateway-usdc-payouts-for-woocommerce' ));
            /* translators: 1: Transaction ID */
            $order->add_order_note(sprintf( __( 'Order marked as failed: Payment received is less than 60%% of the order total. Customer may have changed the payment values on the checkout page. TXID: %1$s', 'riskpay-gateway-usdc-payouts-for-woocommerce' ), $riskpaydotbiz_hosted_riskpaydotbizpaid_txid_out));
            return array( 'message' => 'Order status changed to failed due to partial payment.' );
			
		} else {
        // Change order status to processing
		$order->payment_complete();
		/* translators: 1: Transaction ID */
		$order->add_order_note( sprintf(__('Payment completed by the provider TXID: %1$s', 'riskpay-gateway-usdc-payouts-for-woocommerce'), $riskpaydotbiz_hosted_riskpaydotbizpaid_txid_out) );
        // Return success response
        return array( 'message' => 'Order marked as paid and status changed.' );
	}
    } else {
        // Return error response if conditions are not met
        return new WP_Error( 'order_not_eligible', __( 'Order is not eligible for status change.', 'riskpay-gateway-usdc-payouts-for-woocommerce' ), array( 'status' => 400 ) );
    }
}
