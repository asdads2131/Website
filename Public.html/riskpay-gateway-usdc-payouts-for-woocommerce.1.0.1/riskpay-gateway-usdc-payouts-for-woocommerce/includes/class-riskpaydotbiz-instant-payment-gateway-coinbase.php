<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', 'riskpaydotbiz_coinbase_gateway');

function riskpaydotbiz_coinbase_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }


class RiskPayDotBiz_Instant_Payment_Gateway_Coinbase extends WC_Payment_Gateway {

    protected $icon_url;
    protected $promo_code;
    protected $coinbasecom_wallet_address;

    public function __construct() {
        $this->id                 = 'riskpaydotbiz-instant-payment-gateway-coinbase';
        $this->icon               = sanitize_url($this->get_option('icon_url'));
        $this->promo_code         = sanitize_text_field($this->get_option('promo_code'));
        $this->method_title       = esc_html__('RiskPay Gateway: USDC Payouts for WooCommerce (coinbase.com)', 'riskpay-gateway-usdc-payouts-for-woocommerce'); // Escaping title
        $this->method_description = esc_html__('Instant Approval High Risk Merchant Gateway with instant payouts to your USDC POLYGON wallet using coinbase.com infrastructure', 'riskpay-gateway-usdc-payouts-for-woocommerce'); // Escaping description
        $this->has_fields         = false;

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = sanitize_text_field($this->get_option('title'));
        $this->description = sanitize_text_field($this->get_option('description'));

        // Use the configured settings for redirect and icon URLs
        $this->coinbasecom_wallet_address = sanitize_text_field($this->get_option('coinbasecom_wallet_address'));
        $this->promo_code   = sanitize_text_field($this->get_option('promo_code'));
        $this->icon_url     = sanitize_url($this->get_option('icon_url'));

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => esc_html__('Enable/Disable', 'riskpay-gateway-usdc-payouts-for-woocommerce'), // Escaping title
                'type'    => 'checkbox',
                'label'   => esc_html__('Enable coinbase.com payment gateway', 'riskpay-gateway-usdc-payouts-for-woocommerce'), // Escaping label
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
            'coinbasecom_wallet_address' => array(
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
        $coinbasecom_admin_wallet_address = isset($_POST[$this->plugin_id . $this->id . '_coinbasecom_wallet_address']) ? sanitize_text_field( wp_unslash( $_POST[$this->plugin_id . $this->id . '_coinbasecom_wallet_address'])) : '';

        // Check if wallet address starts with "0x"
        if (substr($coinbasecom_admin_wallet_address, 0, 2) !== '0x') {
            WC_Admin_Settings::add_error(__('Invalid Wallet Address: Please insert your USDC Polygon wallet address.', 'riskpay-gateway-usdc-payouts-for-woocommerce'));
            return false;
        }

        // Check if wallet address matches the USDC contract address
        if (strtolower($coinbasecom_admin_wallet_address) === '0x3c499c542cef5e3811e1192ce70d8cc03d5c3359') {
            WC_Admin_Settings::add_error(__('Invalid Wallet Address: Please insert your USDC Polygon wallet address.', 'riskpay-gateway-usdc-payouts-for-woocommerce'));
            return false;
        }

        // Proceed with the default processing if validations pass
        return parent::process_admin_options();
    }
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $riskpaydotbiz_coinbasecom_currency = get_woocommerce_currency();
		$riskpaydotbiz_coinbasecom_total = $order->get_total();
		$riskpaydotbiz_coinbasecom_nonce = wp_create_nonce( 'riskpaydotbiz_coinbasecom_nonce_' . $order_id );
		$riskpaydotbiz_coinbasecom_callback = add_query_arg(array('order_id' => $order_id, 'nonce' => $riskpaydotbiz_coinbasecom_nonce,), rest_url('riskpaydotbiz/v1/riskpaydotbiz-coinbasecom/'));
		$riskpaydotbiz_coinbasecom_email = urlencode(sanitize_email($order->get_billing_email()));
		$riskpaydotbiz_coinbasecom_final_total = $riskpaydotbiz_coinbasecom_total;
		
		if ($riskpaydotbiz_coinbasecom_currency === 'USD') {
		$riskpaydotbiz_coinbasecom_reference_total = (float)$riskpaydotbiz_coinbasecom_final_total;
		} else {
		
$riskpaydotbiz_coinbasecom_response = wp_remote_get('https://api.riskpay.biz/control/convert.php?value=' . $riskpaydotbiz_coinbasecom_total . '&from=' . strtolower($riskpaydotbiz_coinbasecom_currency), array('timeout' => 30));

if (is_wp_error($riskpaydotbiz_coinbasecom_response)) {
    // Handle error
    riskpaydotbiz_add_notice(__('Payment error:', 'riskpay-gateway-usdc-payouts-for-woocommerce') . __('Payment could not be processed due to failed currency conversion process, please try again', 'riskpay-gateway-usdc-payouts-for-woocommerce'), 'error');
    return null;
} else {

$riskpaydotbiz_coinbasecom_body = wp_remote_retrieve_body($riskpaydotbiz_coinbasecom_response);
$riskpaydotbiz_coinbasecom_conversion_resp = json_decode($riskpaydotbiz_coinbasecom_body, true);

if ($riskpaydotbiz_coinbasecom_conversion_resp && isset($riskpaydotbiz_coinbasecom_conversion_resp['value_coin'])) {
    // Escape output
    $riskpaydotbiz_coinbasecom_finalusd_total	= sanitize_text_field($riskpaydotbiz_coinbasecom_conversion_resp['value_coin']);
    $riskpaydotbiz_coinbasecom_reference_total = (float)$riskpaydotbiz_coinbasecom_finalusd_total;	
} else {
    riskpaydotbiz_add_notice(__('Payment error:', 'riskpay-gateway-usdc-payouts-for-woocommerce') . __('Payment could not be processed, please try again (unsupported store currency)', 'riskpay-gateway-usdc-payouts-for-woocommerce'), 'error');
    return null;
}	
		}
		}

if ($riskpaydotbiz_coinbasecom_reference_total < 2) {
riskpaydotbiz_add_notice(__('Payment error:', 'riskpay-gateway-usdc-payouts-for-woocommerce') . __('Order total for this payment provider must be $2 USD or more.', 'riskpay-gateway-usdc-payouts-for-woocommerce'), 'error');
return null;
}	
	
$riskpaydotbiz_coinbasecom_gen_wallet = wp_remote_get('https://api.riskpay.biz/control/wallet.php?address=' . $this->coinbasecom_wallet_address  . '&promo=' . $this->promo_code . '&callback=' . urlencode($riskpaydotbiz_coinbasecom_callback), array('timeout' => 30));

if (is_wp_error($riskpaydotbiz_coinbasecom_gen_wallet)) {
    // Handle error
    riskpaydotbiz_add_notice(__('Wallet error:', 'riskpay-gateway-usdc-payouts-for-woocommerce') . __('Payment could not be processed due to incorrect payout wallet settings, please contact website admin', 'riskpay-gateway-usdc-payouts-for-woocommerce'), 'error');
    return null;
} else {
	$riskpaydotbiz_coinbasecom_wallet_body = wp_remote_retrieve_body($riskpaydotbiz_coinbasecom_gen_wallet);
	$riskpaydotbiz_coinbasecom_wallet_decbody = json_decode($riskpaydotbiz_coinbasecom_wallet_body, true);

 // Check if decoding was successful
    if ($riskpaydotbiz_coinbasecom_wallet_decbody && isset($riskpaydotbiz_coinbasecom_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $riskpaydotbiz_coinbasecom_gen_addressIn = wp_kses_post($riskpaydotbiz_coinbasecom_wallet_decbody['address_in']);
        $riskpaydotbiz_coinbasecom_gen_polygon_addressIn = sanitize_text_field($riskpaydotbiz_coinbasecom_wallet_decbody['polygon_address_in']);
		$riskpaydotbiz_coinbasecom_gen_callback = sanitize_url($riskpaydotbiz_coinbasecom_wallet_decbody['callback_url']);
		// Save $coinbasecomresponse in order meta data
    $order->add_meta_data('riskpaydotbiz_coinbasecom_tracking_address', $riskpaydotbiz_coinbasecom_gen_addressIn, true);
    $order->add_meta_data('riskpaydotbiz_coinbasecom_polygon_temporary_order_wallet_address', $riskpaydotbiz_coinbasecom_gen_polygon_addressIn, true);
    $order->add_meta_data('riskpaydotbiz_coinbasecom_callback', $riskpaydotbiz_coinbasecom_gen_callback, true);
	$order->add_meta_data('riskpaydotbiz_coinbasecom_converted_amount', $riskpaydotbiz_coinbasecom_final_total, true);
	$order->add_meta_data('riskpaydotbiz_coinbasecom_expected_amount', $riskpaydotbiz_coinbasecom_reference_total, true);
	$order->add_meta_data('riskpaydotbiz_coinbasecom_nonce', $riskpaydotbiz_coinbasecom_nonce, true);
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
            'redirect' => 'https://checkout.riskpay.biz/process-payment.php?address=' . $riskpaydotbiz_coinbasecom_gen_addressIn  . '&promo=' . $this->promo_code . '&amount=' . (float)$riskpaydotbiz_coinbasecom_final_total . '&provider=coinbase&email=' . $riskpaydotbiz_coinbasecom_email . '&currency=' . $riskpaydotbiz_coinbasecom_currency,
        );
    }

public function riskpaydotbiz_instant_payment_gateway_get_icon_url() {
        return !empty($this->icon_url) ? esc_url($this->icon_url) : '';
    }
}

function riskpaydotbiz_add_instant_payment_gateway_coinbase($gateways) {
    $gateways[] = 'RiskPayDotBiz_Instant_Payment_Gateway_Coinbase';
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'riskpaydotbiz_add_instant_payment_gateway_coinbase');
}

// Add custom endpoint for changing order status
function riskpaydotbiz_coinbasecom_change_order_status_rest_endpoint() {
    // Register custom route
    register_rest_route( 'riskpaydotbiz/v1', '/riskpaydotbiz-coinbasecom/', array(
        'methods'  => 'GET',
        'callback' => 'riskpaydotbiz_coinbasecom_change_order_status_callback',
        'permission_callback' => '__return_true',
    ));
}
add_action( 'rest_api_init', 'riskpaydotbiz_coinbasecom_change_order_status_rest_endpoint' );

// Callback function to change order status
function riskpaydotbiz_coinbasecom_change_order_status_callback( $request ) {
    $order_id = absint($request->get_param( 'order_id' ));
	$riskpaydotbiz_coinbasecomgetnonce = sanitize_text_field($request->get_param( 'nonce' ));
	$riskpaydotbiz_coinbasecompaid_txid_out = sanitize_text_field($request->get_param('txid_out'));
	$riskpaydotbiz_coinbasecompaid_value_coin = sanitize_text_field($request->get_param('value_coin'));
	$riskpaydotbiz_coinbasecomfloatpaid_value_coin = (float)$riskpaydotbiz_coinbasecompaid_value_coin;

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
    if ( empty( $riskpaydotbiz_coinbasecomgetnonce ) || $order->get_meta('riskpaydotbiz_coinbasecom_nonce', true) !== $riskpaydotbiz_coinbasecomgetnonce ) {
        return new WP_Error( 'invalid_nonce', __( 'Invalid nonce.', 'riskpay-gateway-usdc-payouts-for-woocommerce' ), array( 'status' => 403 ) );
    }

    // Check if the order is pending and payment method is 'riskpaydotbiz-instant-payment-gateway-coinbase'
    if ( $order && $order->get_status() !== 'processing' && $order->get_status() !== 'completed' && 'riskpaydotbiz-instant-payment-gateway-coinbase' === $order->get_payment_method() ) {
	$riskpaydotbiz_coinbasecomexpected_amount = (float)$order->get_meta('riskpaydotbiz_coinbasecom_expected_amount', true);
	$riskpaydotbiz_coinbasecomthreshold = 0.60 * $riskpaydotbiz_coinbasecomexpected_amount;
		if ( $riskpaydotbiz_coinbasecomfloatpaid_value_coin < $riskpaydotbiz_coinbasecomthreshold ) {
			// Mark the order as failed and add an order note
            $order->update_status('failed', __( 'Payment received is less than 60% of the order total. Customer may have changed the payment values on the checkout page.', 'riskpay-gateway-usdc-payouts-for-woocommerce' ));
            /* translators: 1: Transaction ID */
            $order->add_order_note(sprintf( __( 'Order marked as failed: Payment received is less than 60%% of the order total. Customer may have changed the payment values on the checkout page. TXID: %1$s', 'riskpay-gateway-usdc-payouts-for-woocommerce' ), $riskpaydotbiz_coinbasecompaid_txid_out));
            return array( 'message' => 'Order status changed to failed due to partial payment.' );
			
		} else {
        // Change order status to processing
		$order->payment_complete();
		/* translators: 1: Transaction ID */
		$order->add_order_note( sprintf(__('Payment completed by the provider TXID: %1$s', 'riskpay-gateway-usdc-payouts-for-woocommerce'), $riskpaydotbiz_coinbasecompaid_txid_out) );
        // Return success response
        return array( 'message' => 'Order marked as paid and status changed.' );
	}
    } else {
        // Return error response if conditions are not met
        return new WP_Error( 'order_not_eligible', __( 'Order is not eligible for status change.', 'riskpay-gateway-usdc-payouts-for-woocommerce' ), array( 'status' => 400 ) );
    }
}
