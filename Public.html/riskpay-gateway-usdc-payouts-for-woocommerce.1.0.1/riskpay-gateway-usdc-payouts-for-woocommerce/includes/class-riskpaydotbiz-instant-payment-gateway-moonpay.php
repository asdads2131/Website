<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', 'riskpaydotbiz_moonpay_gateway');

function riskpaydotbiz_moonpay_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

class RiskPayDotBiz_Instant_Payment_Gateway_Moonpay extends WC_Payment_Gateway {

    protected $icon_url;
    protected $promo_code;
    protected $moonpaycom_wallet_address;

    public function __construct() {
        $this->id                 = 'riskpaydotbiz-instant-payment-gateway-moonpay';
        $this->icon               = sanitize_url($this->get_option('icon_url'));
        $this->promo_code         = sanitize_text_field($this->get_option('promo_code'));
        $this->method_title       = esc_html__('RiskPay Gateway: USDC Payouts for WooCommerce (moonpay.com)', 'riskpay-gateway-usdc-payouts-for-woocommerce'); // Escaping title
        $this->method_description = esc_html__('Instant Approval High Risk Merchant Gateway with instant payouts to your USDC POLYGON wallet using moonpay.com infrastructure', 'riskpay-gateway-usdc-payouts-for-woocommerce'); // Escaping description
        $this->has_fields         = false;

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = sanitize_text_field($this->get_option('title'));
        $this->description = sanitize_text_field($this->get_option('description'));

        // Use the configured settings for redirect and icon URLs
        $this->moonpaycom_wallet_address = sanitize_text_field($this->get_option('moonpaycom_wallet_address'));
        $this->promo_code   = sanitize_text_field($this->get_option('promo_code'));
        $this->icon_url     = sanitize_url($this->get_option('icon_url'));

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => esc_html__('Enable/Disable', 'riskpay-gateway-usdc-payouts-for-woocommerce'), // Escaping title
                'type'    => 'checkbox',
                'label'   => esc_html__('Enable moonpay.com payment gateway', 'riskpay-gateway-usdc-payouts-for-woocommerce'), // Escaping label
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
            'moonpaycom_wallet_address' => array(
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
        $moonpaycom_admin_wallet_address = isset($_POST[$this->plugin_id . $this->id . '_moonpaycom_wallet_address']) ? sanitize_text_field( wp_unslash( $_POST[$this->plugin_id . $this->id . '_moonpaycom_wallet_address'])) : '';

        // Check if wallet address starts with "0x"
        if (substr($moonpaycom_admin_wallet_address, 0, 2) !== '0x') {
            WC_Admin_Settings::add_error(__('Invalid Wallet Address: Please insert your USDC Polygon wallet address.', 'riskpay-gateway-usdc-payouts-for-woocommerce'));
            return false;
        }

        // Check if wallet address matches the USDC contract address
        if (strtolower($moonpaycom_admin_wallet_address) === '0x3c499c542cef5e3811e1192ce70d8cc03d5c3359') {
            WC_Admin_Settings::add_error(__('Invalid Wallet Address: Please insert your USDC Polygon wallet address.', 'riskpay-gateway-usdc-payouts-for-woocommerce'));
            return false;
        }

        // Proceed with the default processing if validations pass
        return parent::process_admin_options();
    }
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $riskpaydotbiz_moonpaycom_currency = get_woocommerce_currency();
		$riskpaydotbiz_moonpaycom_total = $order->get_total();
		$riskpaydotbiz_moonpaycom_nonce = wp_create_nonce( 'riskpaydotbiz_moonpaycom_nonce_' . $order_id );
		$riskpaydotbiz_moonpaycom_callback = add_query_arg(array('order_id' => $order_id, 'nonce' => $riskpaydotbiz_moonpaycom_nonce,), rest_url('riskpaydotbiz/v1/riskpaydotbiz-moonpaycom/'));
		$riskpaydotbiz_moonpaycom_email = urlencode(sanitize_email($order->get_billing_email()));
		$riskpaydotbiz_moonpaycom_final_total = $riskpaydotbiz_moonpaycom_total;

if ($riskpaydotbiz_moonpaycom_currency === 'USD') {
        $riskpaydotbiz_moonpaycom_minimumcheck = $riskpaydotbiz_moonpaycom_total;
		} else {
		
$riskpaydotbiz_moonpaycom_minimumcheck_response = wp_remote_get('https://api.riskpay.biz/control/convert.php?value=' . $riskpaydotbiz_moonpaycom_total . '&from=' . strtolower($riskpaydotbiz_moonpaycom_currency), array('timeout' => 30));

if (is_wp_error($riskpaydotbiz_moonpaycom_minimumcheck_response)) {
    // Handle error
    riskpaydotbiz_add_notice(__('Payment error:', 'riskpay-gateway-usdc-payouts-for-woocommerce') . __('Payment could not be processed due to failed currency conversion process, please try again', 'riskpay-gateway-usdc-payouts-for-woocommerce'), 'error');
    return null;
} else {

$riskpaydotbiz_moonpaycom_minimumcheck_body = wp_remote_retrieve_body($riskpaydotbiz_moonpaycom_minimumcheck_response);
$riskpaydotbiz_moonpaycom_minimum_conversion_resp = json_decode($riskpaydotbiz_moonpaycom_minimumcheck_body, true);

if ($riskpaydotbiz_moonpaycom_minimum_conversion_resp && isset($riskpaydotbiz_moonpaycom_minimum_conversion_resp['value_coin'])) {
    // Escape output
    $riskpaydotbiz_moonpaycom_minimum_conversion_total	= sanitize_text_field($riskpaydotbiz_moonpaycom_minimum_conversion_resp['value_coin']);
    $riskpaydotbiz_moonpaycom_minimumcheck = (float)$riskpaydotbiz_moonpaycom_minimum_conversion_total;	
} else {
    riskpaydotbiz_add_notice(__('Payment error:', 'riskpay-gateway-usdc-payouts-for-woocommerce') . __('Payment could not be processed, please try again (unsupported store currency)', 'riskpay-gateway-usdc-payouts-for-woocommerce'), 'error');
    return null;
}	
		}
		}
		
if ($riskpaydotbiz_moonpaycom_minimumcheck < 20) {
riskpaydotbiz_add_notice(__('Payment error:', 'riskpay-gateway-usdc-payouts-for-woocommerce') . __('Order total for this payment provider must be $20 USD or more.', 'riskpay-gateway-usdc-payouts-for-woocommerce'), 'error');
return null;
}
	
$riskpaydotbiz_moonpaycom_gen_wallet = wp_remote_get('https://api.riskpay.biz/control/wallet.php?address=' . $this->moonpaycom_wallet_address  . '&promo=' . $this->promo_code . '&callback=' . urlencode($riskpaydotbiz_moonpaycom_callback), array('timeout' => 30));

if (is_wp_error($riskpaydotbiz_moonpaycom_gen_wallet)) {
    // Handle error
    riskpaydotbiz_add_notice(__('Wallet error:', 'riskpay-gateway-usdc-payouts-for-woocommerce') . __('Payment could not be processed due to incorrect payout wallet settings, please contact website admin', 'riskpay-gateway-usdc-payouts-for-woocommerce'), 'error');
    return null;
} else {
	$riskpaydotbiz_moonpaycom_wallet_body = wp_remote_retrieve_body($riskpaydotbiz_moonpaycom_gen_wallet);
	$riskpaydotbiz_moonpaycom_wallet_decbody = json_decode($riskpaydotbiz_moonpaycom_wallet_body, true);

 // Check if decoding was successful
    if ($riskpaydotbiz_moonpaycom_wallet_decbody && isset($riskpaydotbiz_moonpaycom_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $riskpaydotbiz_moonpaycom_gen_addressIn = wp_kses_post($riskpaydotbiz_moonpaycom_wallet_decbody['address_in']);
        $riskpaydotbiz_moonpaycom_gen_polygon_addressIn = sanitize_text_field($riskpaydotbiz_moonpaycom_wallet_decbody['polygon_address_in']);
		$riskpaydotbiz_moonpaycom_gen_callback = sanitize_url($riskpaydotbiz_moonpaycom_wallet_decbody['callback_url']);
		// Save $moonpaycomresponse in order meta data
    $order->add_meta_data('riskpaydotbiz_moonpaycom_tracking_address', $riskpaydotbiz_moonpaycom_gen_addressIn, true);
    $order->add_meta_data('riskpaydotbiz_moonpaycom_polygon_temporary_order_wallet_address', $riskpaydotbiz_moonpaycom_gen_polygon_addressIn, true);
    $order->add_meta_data('riskpaydotbiz_moonpaycom_callback', $riskpaydotbiz_moonpaycom_gen_callback, true);
	$order->add_meta_data('riskpaydotbiz_moonpaycom_converted_amount', $riskpaydotbiz_moonpaycom_final_total, true);
	$order->add_meta_data('riskpaydotbiz_moonpaycom_nonce', $riskpaydotbiz_moonpaycom_nonce, true);
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
            'redirect' => 'https://checkout.riskpay.biz/process-payment.php?address=' . $riskpaydotbiz_moonpaycom_gen_addressIn  . '&promo=' . $this->promo_code . '&amount=' . (float)$riskpaydotbiz_moonpaycom_final_total . '&provider=moonpay&email=' . $riskpaydotbiz_moonpaycom_email . '&currency=' . $riskpaydotbiz_moonpaycom_currency,
        );
    }

public function riskpaydotbiz_instant_payment_gateway_get_icon_url() {
        return !empty($this->icon_url) ? esc_url($this->icon_url) : '';
    }
}

function riskpaydotbiz_add_instant_payment_gateway_moonpay($gateways) {
    $gateways[] = 'RiskPayDotBiz_Instant_Payment_Gateway_Moonpay';
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'riskpaydotbiz_add_instant_payment_gateway_moonpay');
}

// Add custom endpoint for changing order status
function riskpaydotbiz_moonpaycom_change_order_status_rest_endpoint() {
    // Register custom route
    register_rest_route( 'riskpaydotbiz/v1', '/riskpaydotbiz-moonpaycom/', array(
        'methods'  => 'GET',
        'callback' => 'riskpaydotbiz_moonpaycom_change_order_status_callback',
        'permission_callback' => '__return_true',
    ));
}
add_action( 'rest_api_init', 'riskpaydotbiz_moonpaycom_change_order_status_rest_endpoint' );

// Callback function to change order status
function riskpaydotbiz_moonpaycom_change_order_status_callback( $request ) {
    $order_id = absint($request->get_param( 'order_id' ));
	$riskpaydotbiz_moonpaycomgetnonce = sanitize_text_field($request->get_param( 'nonce' ));
	$riskpaydotbiz_moonpaycompaid_txid_out = sanitize_text_field($request->get_param('txid_out'));

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
    if ( empty( $riskpaydotbiz_moonpaycomgetnonce ) || $order->get_meta('riskpaydotbiz_moonpaycom_nonce', true) !== $riskpaydotbiz_moonpaycomgetnonce ) {
        return new WP_Error( 'invalid_nonce', __( 'Invalid nonce.', 'riskpay-gateway-usdc-payouts-for-woocommerce' ), array( 'status' => 403 ) );
    }

    // Check if the order is pending and payment method is 'riskpaydotbiz-instant-payment-gateway-moonpay'
    if ( $order && $order->get_status() !== 'processing' && $order->get_status() !== 'completed' && 'riskpaydotbiz-instant-payment-gateway-moonpay' === $order->get_payment_method() ) {
        // Change order status to processing
		$order->payment_complete();
		/* translators: 1: Transaction ID */
		$order->add_order_note( sprintf(__('Payment completed by the provider TXID: %1$s', 'riskpay-gateway-usdc-payouts-for-woocommerce'), $riskpaydotbiz_moonpaycompaid_txid_out) );
        // Return success response
        return array( 'message' => 'Order marked as paid and status changed.' );
    } else {
        // Return error response if conditions are not met
        return new WP_Error( 'order_not_eligible', __( 'Order is not eligible for status change.', 'riskpay-gateway-usdc-payouts-for-woocommerce' ), array( 'status' => 400 ) );
    }
}
