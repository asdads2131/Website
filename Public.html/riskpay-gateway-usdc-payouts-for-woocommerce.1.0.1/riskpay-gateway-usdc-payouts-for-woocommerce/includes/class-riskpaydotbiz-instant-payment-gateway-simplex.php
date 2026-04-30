<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', 'riskpaydotbiz_simplex_gateway');

function riskpaydotbiz_simplex_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }


class RiskPayDotBiz_Instant_Payment_Gateway_Simplex extends WC_Payment_Gateway {

    protected $icon_url;
    protected $promo_code;
    protected $simplexcom_wallet_address;

    public function __construct() {
        $this->id                 = 'riskpaydotbiz-instant-payment-gateway-simplex';
        $this->icon               = sanitize_url($this->get_option('icon_url'));
        $this->promo_code         = sanitize_text_field($this->get_option('promo_code'));
        $this->method_title       = esc_html__('RiskPay Gateway: USDC Payouts for WooCommerce (simplex.com)', 'riskpay-gateway-usdc-payouts-for-woocommerce'); // Escaping title
        $this->method_description = esc_html__('Instant Approval High Risk Merchant Gateway with instant payouts to your USDC POLYGON wallet using simplex.com infrastructure', 'riskpay-gateway-usdc-payouts-for-woocommerce'); // Escaping description
        $this->has_fields         = false;

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = sanitize_text_field($this->get_option('title'));
        $this->description = sanitize_text_field($this->get_option('description'));

        // Use the configured settings for redirect and icon URLs
        $this->simplexcom_wallet_address = sanitize_text_field($this->get_option('simplexcom_wallet_address'));
        $this->promo_code   = sanitize_text_field($this->get_option('promo_code'));
        $this->icon_url     = sanitize_url($this->get_option('icon_url'));

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => esc_html__('Enable/Disable', 'riskpay-gateway-usdc-payouts-for-woocommerce'), // Escaping title
                'type'    => 'checkbox',
                'label'   => esc_html__('Enable simplex.com payment gateway', 'riskpay-gateway-usdc-payouts-for-woocommerce'), // Escaping label
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
            'simplexcom_wallet_address' => array(
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
        $simplexcom_admin_wallet_address = isset($_POST[$this->plugin_id . $this->id . '_simplexcom_wallet_address']) ? sanitize_text_field( wp_unslash( $_POST[$this->plugin_id . $this->id . '_simplexcom_wallet_address'])) : '';

        // Check if wallet address starts with "0x"
        if (substr($simplexcom_admin_wallet_address, 0, 2) !== '0x') {
            WC_Admin_Settings::add_error(__('Invalid Wallet Address: Please insert your USDC Polygon wallet address.', 'riskpay-gateway-usdc-payouts-for-woocommerce'));
            return false;
        }

        // Check if wallet address matches the USDC contract address
        if (strtolower($simplexcom_admin_wallet_address) === '0x3c499c542cef5e3811e1192ce70d8cc03d5c3359') {
            WC_Admin_Settings::add_error(__('Invalid Wallet Address: Please insert your USDC Polygon wallet address.', 'riskpay-gateway-usdc-payouts-for-woocommerce'));
            return false;
        }

        // Proceed with the default processing if validations pass
        return parent::process_admin_options();
    }
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $riskpaydotbiz_simplexcom_currency = get_woocommerce_currency();
		$riskpaydotbiz_simplexcom_total = $order->get_total();
		$riskpaydotbiz_simplexcom_nonce = wp_create_nonce( 'riskpaydotbiz_simplexcom_nonce_' . $order_id );
		$riskpaydotbiz_simplexcom_callback = add_query_arg(array('order_id' => $order_id, 'nonce' => $riskpaydotbiz_simplexcom_nonce,), rest_url('riskpaydotbiz/v1/riskpaydotbiz-simplexcom/'));
		$riskpaydotbiz_simplexcom_email = urlencode(sanitize_email($order->get_billing_email()));
		$riskpaydotbiz_simplexcom_final_total = $riskpaydotbiz_simplexcom_total;
		
if ($riskpaydotbiz_simplexcom_currency === 'USD') {
        $riskpaydotbiz_simplexcom_minimumcheck = $riskpaydotbiz_simplexcom_total;
		} else {
		
$riskpaydotbiz_simplexcom_minimumcheck_response = wp_remote_get('https://api.riskpay.biz/control/convert.php?value=' . $riskpaydotbiz_simplexcom_total . '&from=' . strtolower($riskpaydotbiz_simplexcom_currency), array('timeout' => 30));

if (is_wp_error($riskpaydotbiz_simplexcom_minimumcheck_response)) {
    // Handle error
    riskpaydotbiz_add_notice(__('Payment error:', 'riskpay-gateway-usdc-payouts-for-woocommerce') . __('Payment could not be processed due to failed currency conversion process, please try again', 'riskpay-gateway-usdc-payouts-for-woocommerce'), 'error');
    return null;
} else {

$riskpaydotbiz_simplexcom_minimumcheck_body = wp_remote_retrieve_body($riskpaydotbiz_simplexcom_minimumcheck_response);
$riskpaydotbiz_simplexcom_minimum_conversion_resp = json_decode($riskpaydotbiz_simplexcom_minimumcheck_body, true);

if ($riskpaydotbiz_simplexcom_minimum_conversion_resp && isset($riskpaydotbiz_simplexcom_minimum_conversion_resp['value_coin'])) {
    // Escape output
    $riskpaydotbiz_simplexcom_minimum_conversion_total	= sanitize_text_field($riskpaydotbiz_simplexcom_minimum_conversion_resp['value_coin']);
    $riskpaydotbiz_simplexcom_minimumcheck = (float)$riskpaydotbiz_simplexcom_minimum_conversion_total;	
} else {
    riskpaydotbiz_add_notice(__('Payment error:', 'riskpay-gateway-usdc-payouts-for-woocommerce') . __('Payment could not be processed, please try again (unsupported store currency)', 'riskpay-gateway-usdc-payouts-for-woocommerce'), 'error');
    return null;
}	
		}
		}
		
if ($riskpaydotbiz_simplexcom_minimumcheck < 50) {
riskpaydotbiz_add_notice(__('Payment error:', 'riskpay-gateway-usdc-payouts-for-woocommerce') . __('Order total for this payment provider must be $50 USD or more.', 'riskpay-gateway-usdc-payouts-for-woocommerce'), 'error');
return null;
}		
	
$riskpaydotbiz_simplexcom_gen_wallet = wp_remote_get('https://api.riskpay.biz/control/wallet.php?address=' . $this->simplexcom_wallet_address  . '&promo=' . $this->promo_code . '&callback=' . urlencode($riskpaydotbiz_simplexcom_callback), array('timeout' => 30));

if (is_wp_error($riskpaydotbiz_simplexcom_gen_wallet)) {
    // Handle error
    riskpaydotbiz_add_notice(__('Wallet error:', 'riskpay-gateway-usdc-payouts-for-woocommerce') . __('Payment could not be processed due to incorrect payout wallet settings, please contact website admin', 'riskpay-gateway-usdc-payouts-for-woocommerce'), 'error');
    return null;
} else {
	$riskpaydotbiz_simplexcom_wallet_body = wp_remote_retrieve_body($riskpaydotbiz_simplexcom_gen_wallet);
	$riskpaydotbiz_simplexcom_wallet_decbody = json_decode($riskpaydotbiz_simplexcom_wallet_body, true);

 // Check if decoding was successful
    if ($riskpaydotbiz_simplexcom_wallet_decbody && isset($riskpaydotbiz_simplexcom_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $riskpaydotbiz_simplexcom_gen_addressIn = wp_kses_post($riskpaydotbiz_simplexcom_wallet_decbody['address_in']);
        $riskpaydotbiz_simplexcom_gen_polygon_addressIn = sanitize_text_field($riskpaydotbiz_simplexcom_wallet_decbody['polygon_address_in']);
		$riskpaydotbiz_simplexcom_gen_callback = sanitize_url($riskpaydotbiz_simplexcom_wallet_decbody['callback_url']);
		// Save $simplexcomresponse in order meta data
    $order->add_meta_data('riskpaydotbiz_simplexcom_tracking_address', $riskpaydotbiz_simplexcom_gen_addressIn, true);
    $order->add_meta_data('riskpaydotbiz_simplexcom_polygon_temporary_order_wallet_address', $riskpaydotbiz_simplexcom_gen_polygon_addressIn, true);
    $order->add_meta_data('riskpaydotbiz_simplexcom_callback', $riskpaydotbiz_simplexcom_gen_callback, true);
	$order->add_meta_data('riskpaydotbiz_simplexcom_converted_amount', $riskpaydotbiz_simplexcom_final_total, true);
	$order->add_meta_data('riskpaydotbiz_simplexcom_nonce', $riskpaydotbiz_simplexcom_nonce, true);
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
            'redirect' => 'https://checkout.riskpay.biz/process-payment.php?address=' . $riskpaydotbiz_simplexcom_gen_addressIn  . '&promo=' . $this->promo_code . '&amount=' . (float)$riskpaydotbiz_simplexcom_final_total . '&provider=simplex&email=' . $riskpaydotbiz_simplexcom_email . '&currency=' . $riskpaydotbiz_simplexcom_currency,
        );
    }

public function riskpaydotbiz_instant_payment_gateway_get_icon_url() {
        return !empty($this->icon_url) ? esc_url($this->icon_url) : '';
    }
}

function riskpaydotbiz_add_instant_payment_gateway_simplex($gateways) {
    $gateways[] = 'RiskPayDotBiz_Instant_Payment_Gateway_Simplex';
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'riskpaydotbiz_add_instant_payment_gateway_simplex');
}

// Add custom endpoint for changing order status
function riskpaydotbiz_simplexcom_change_order_status_rest_endpoint() {
    // Register custom route
    register_rest_route( 'riskpaydotbiz/v1', '/riskpaydotbiz-simplexcom/', array(
        'methods'  => 'GET',
        'callback' => 'riskpaydotbiz_simplexcom_change_order_status_callback',
        'permission_callback' => '__return_true',
    ));
}
add_action( 'rest_api_init', 'riskpaydotbiz_simplexcom_change_order_status_rest_endpoint' );

// Callback function to change order status
function riskpaydotbiz_simplexcom_change_order_status_callback( $request ) {
    $order_id = absint($request->get_param( 'order_id' ));
	$riskpaydotbiz_simplexcomgetnonce = sanitize_text_field($request->get_param( 'nonce' ));
	$riskpaydotbiz_simplexcompaid_txid_out = sanitize_text_field($request->get_param('txid_out'));

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
    if ( empty( $riskpaydotbiz_simplexcomgetnonce ) || $order->get_meta('riskpaydotbiz_simplexcom_nonce', true) !== $riskpaydotbiz_simplexcomgetnonce ) {
        return new WP_Error( 'invalid_nonce', __( 'Invalid nonce.', 'riskpay-gateway-usdc-payouts-for-woocommerce' ), array( 'status' => 403 ) );
    }

    // Check if the order is pending and payment method is 'riskpaydotbiz-instant-payment-gateway-simplex'
    if ( $order && $order->get_status() !== 'processing' && $order->get_status() !== 'completed' && 'riskpaydotbiz-instant-payment-gateway-simplex' === $order->get_payment_method() ) {
        // Change order status to processing
		$order->payment_complete();
		/* translators: 1: Transaction ID */
		$order->add_order_note( sprintf(__('Payment completed by the provider TXID: %1$s', 'riskpay-gateway-usdc-payouts-for-woocommerce'), $riskpaydotbiz_simplexcompaid_txid_out) );
        // Return success response
        return array( 'message' => 'Order marked as paid and status changed.' );
    } else {
        // Return error response if conditions are not met
        return new WP_Error( 'order_not_eligible', __( 'Order is not eligible for status change.', 'riskpay-gateway-usdc-payouts-for-woocommerce' ), array( 'status' => 400 ) );
    }
}
