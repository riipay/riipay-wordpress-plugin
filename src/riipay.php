<?php

class riipay extends WC_Payment_Gateway
{
    /**
     * Url to Riipay Payment Page when in Sandbox Mode
     */
    const SANDBOX_URL = 'https://secure.uat.riipay.my/v1/payment';
    /**
     * Url to Riipay Payment Page when in Production Mode
     */
    const PRODUCTION_URL = 'https://secure.riipay.my/v1/payment';

    public function __construct()
    {
        global $woocommerce;

        $this->id = 'riipay';
        $this->method_title = __( 'riipay', 'riipay' );
        $this->method_description = __( 'Riipay Payment Gateway Plugin for WooCommerce', 'riipay' );
        $this->has_fields = false;
        $this->icon = 'https://d3pv8fjwcfshvi.cloudfront.net/public/icons/android-icon-48x48.png';

        $this->title = __( 'riipay', 'riipay' );

        if ( is_admin() ) {
            $this->init_form_fields();
        }

        $this->init_settings();

        foreach ( $this->settings as $key => $value ) {
            $this->$key = $value;
        }

        if ( !$this->is_available() ) {
            $this->enabled = 'no';
        }

        if ( is_admin() ) {
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        add_action( 'woocommerce_api_' . $this->id, array( $this, 'check_response' ) );
        add_filter( 'woocommerce_gateway_description', array( $this, 'riipay_custom_description' ), 10, 2 );

        add_action( 'woocommerce_order_status_on-hold_to_failed', array( $this, 'increase_stock' ), 10, 1 );
        add_action( 'woocommerce_order_status_failed_to_on-hold', array( $this, 'reduce_stock' ), 10, 1 );

        add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'woo_change_order_received_text' ), 10, 2 );
    }

    public function init_form_fields()
    {
        $extra_info_text = 'For Production Environment, please update your details at <a href="https://merchant.riipay.my/profile/update" target="_blank">Riipay Merchant Portal Settings</a>.<br />';
        $extra_info_text .= 'For Sandbox Environment, please update your details at <a href="https://merchant.uat.riipay.my/profile/update" target="_blank">Riipay Sandbox Merchant Portal Settings</a>.<br /><br />';
        $extra_info_text .= 'You may use the suggested values as follows: <br />';

        $return_url = home_url( '/' ) . 'wc-api/' . $this->id;
        $callback_url = add_query_arg( array( 'callback' => 1 ), $return_url );
        $extra_info_text .= '<b>Return URL</b>: ' . $return_url  . '<br />';
        $extra_info_text .= '<b>Callback URL</b>: ' .  $callback_url;

        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable Riipay', 'riipay' ),
                'label' => __( 'Enable', 'riipay' ),
                'type' => 'checkbox',
                'default' => 'no',
            ),
            'title' =>  array(
                'title' => __( 'Title', 'riipay' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout e.g. Riipay 0% Instalment Payment', 'riipay' ),
                'desc_tip' => false,
                'default' => __( 'Riipay 0% Instalment Payment', 'riipay' ),
            ),
            'description' => array(
                'title' => __( 'Checkout Message', 'riipay' ),
                'type' => 'textarea',
                'description' => __( 'This controls the message which the user sees during checkout', 'riipay' ),
                'desc_tip' => false,
                'default' => __('Split your purchase into 3 interest-free payments, due monthly.', 'riipay')
            ),
            'merchant_code' =>  array(
                'title' => __( 'Merchant Code', 'riipay' ),
                'type' => 'text',
                'description' => __( 'Merchant Code provided by riipay', 'riipay' ),
                'desc_tip' => false,
                'default' => '',
                'required' => true,
            ),
            'secret_key' =>  array(
                'title' => __( 'Secret Key', 'riipay' ),
                'type' => 'text',
                'description' => __( 'Secret Key provided by riipay', 'riipay' ),
                'desc_tip' => false,
                'default' => '',
                'required' => true,
            ),
            'min_amount' => array(
                'title' => __( 'Minimum Order Amount', 'riipay' ),
                'type' => 'text',
                'description' => __( 'Minimum order amount required to use this payment gateway', 'riipay' ),
                'desc_tip' => false,
                'default' => 0,
            ),
            'max_amount' => array(
                'title' => __( 'Maximum Order Amount', 'riipay' ),
                'type' => 'text',
                'description' => __( 'Maximum order amount to use this payment gateway. For orders exceeding RM1000, Riipay will reject payments until further notice at the moment.', 'riipay' ),
                'desc_tip' => false,
                'default' => 1000,
            ),
            'environment' => array(
                'title' => __( 'Environment', 'riipay' ),
                'type' => 'select',
                'description' => __( 'Choose whether you wish to use sandbox or production mode. You may use sandbox mode to test payments.', 'riipay' ),
                'default' => 'production',
                'desc_tip'    => false,
                'options'     => array(
                    'production'	=> __( 'Production', 'riipay' ),
                    'sandbox'	=> __( 'Sandbox', 'riipay' )
                ),
            ),
            'custom_product_price' => array(
                'title' => __( 'Riipay Product Price', 'riipay' ),
                'label' => __( 'Enable', 'riipay' ),
                'description' => __('Enable custom product price text with riipay instalment information. Note: this does not work with WooCommerce Blocks All Products', 'riipay'),
                'type' => 'checkbox',
                'default' => 'yes',
            ),
            'extra_info' => array(
                'title' => __( 'Extra Information', 'riipay' ),
                'type' => 'title',
                'description' => __( $extra_info_text , 'riipay' ),
            ),
        );
    }

    public function get_url()
    {
        return $this->get_option( 'environment' ) == 'sandbox' ? self::SANDBOX_URL : self::PRODUCTION_URL;
    }

    public function is_available()
    {
        if ( !$this->validate_admin_input() ) {
            return false;
        }

        if ( !$this->validate_currencies() ) {
            return false;
        }

        if ( is_admin() ) {
            return true;
        }

        if ( $this->min_amount > 0 ) {
            $total = $this->get_total_amount();
            $available = ( $this->min_amount <= $total ) && ( $total <= $this->max_amount );

            if ( is_wc_endpoint_url( 'order-pay' ) ) {
                $this->chosen = true;
            }

            return $available;
        }

        return parent::is_available();
    }

    public function validate_admin_input()
    {

        $valid = true;
        if ( empty($this->merchant_code) ) {
            add_action('admin_notices', array(
                    &$this,
                    'merchant_code_missing_message')
            );
            $valid = false;
        }

        if ( empty($this->secret_key) ) {
            add_action('admin_notices', array(
                    &$this,
                    'secret_key_missing_message')
            );
            $valid = false;
        }

        return $valid;
    }

    private function validate_currencies()
    {
        if (!in_array( get_woocommerce_currency(),
            apply_filters( 'riipay_supported_currencies', array('MYR') ),
            true) ) {

            add_action( 'admin_notices', array(
                    &$this,
                    'unsupported_currency_notice')
            );

            return false;
        }
        return true;
    }

    public function unsupported_currency_notice()
    {
        $message = '<div class="error">';
        $message .= '<p>' . sprintf("<strong>Riipay Disabled</strong> WooCommerce currency option is not supported by Riipay Settings. %sClick here to configure%s", '<a href="' . esc_url(admin_url( 'admin.php?page=wc-settings&tab=checkout&section=riipay' )) . '">',  '</a>') . '</p>';
        $message .= '</div>';

        echo $message;
    }

    public function merchant_code_missing_message()
    {
        return $this->key_missing_message('Merchant Code');
    }

    public function secret_key_missing_message()
    {
        return $this->key_missing_message('Secret Key');
    }

    public function key_missing_message($error_type)
    {
        $message = '<div class="error">';
        $message .= '<p>' . sprintf("<strong>Riipay Disabled</strong> You should set your $error_type in Riipay Settings. %sClick here to configure%s", '<a href="' . esc_url(admin_url( 'admin.php?page=wc-settings&tab=checkout&section=riipay' )) . '">', '</a>') . '</p>';
        $message .= '</div>';

        echo $message;
    }

    public function get_total_amount()
    {
        global $woocommerce;

        $total = 0;
        if ( is_wc_endpoint_url( 'order-pay' ) ) {
            $order_id = get_query_var('order-pay');
            $order = new WC_Order( $order_id );
            $total = $order->get_total();
        } elseif ( $woocommerce->cart  ) {
            $total = $woocommerce->cart->get_total('') ;
        }

        return $total;
    }

    public function get_first_payment_value()
    {
        if ( is_admin() ) {
            return '';
        }

        $total = $this->get_total_amount();
        $each_payment = $total / 3;
        $each_payment = number_format($each_payment, 2);

        return sprintf( '%s %s', get_woocommerce_currency_symbol(), $each_payment );
    }

    public function riipay_custom_description( $description, $payment_id )
    {
        if ( is_admin() || $payment_id !== 'riipay' ) {
            return $description;
        }

        $html = $description;
        $html .= '<p> Pay ';
        $html .= $this->get_first_payment_value();
        $html .= ' now. </p>';
        $html .= '<p>Any undisplayed remainders will be applied to the first repayment amount. ';

        $url = esc_url( sprintf('%s/preview?merchant_code=%s&amount=%s', $this->get_url(), $this->get_option( 'merchant_code' ), $this->get_total_amount() ) );
        $html .= sprintf('<a href="%s" onclick="window.open(\'%s\', \'popup\', \'width=600,height=700\'); return false;" target="popup" style="text-decoration: underline;">More info</a>', $url, $url);
        $html .= '</p>';

        return $html;
    }

    public function process_payment( $order_id )
    {
        $order = new WC_Order( $order_id );
        $merchant_code = $this->merchant_code;
        $reference = $order->get_order_number();
        $description = 'Payment for order ' . $reference;
        $currency_code = $order->get_currency();
        $amount = number_format( $order->get_total(), 2, '.', '' );
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $customer_phone = $order->get_billing_phone();
        $customer_email = $order->get_billing_email();
        $customer_ip = $order->get_customer_ip_address();
        $signature = md5($merchant_code . $this->secret_key . $reference . $currency_code . $amount);
        $return_url = WC()->api_request_url( get_class($this) );
        $callback_url = add_query_arg( array('callback' => 1), $return_url );

        $arguments = array(
            'merchant_code' => $merchant_code,
            'reference' => $reference,
            'description' => $description,
            'currency_code' => $currency_code,
            'amount' => $amount,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'customer_ip' => $customer_ip,
            'return_url' => $return_url,
            'callback_url' => $callback_url,
            'signature' => $signature,
        );

        $get_arguments = '';
        foreach($arguments as $key => $value) {
            if ($get_arguments !== '') {
                $get_arguments .= '&';
            } else {
                $get_arguments .= '?';
            }

            $get_arguments .= $key . '=' . $value;
        }

        return array(
            'result' => 'success',
            'redirect' => $this->get_url() . $get_arguments,
        );
    }

    public function check_response()
    {
        $is_callback = $_REQUEST['callback'] == 1 ? true : false;
        $method = strtoupper( $_SERVER['REQUEST_METHOD'] );
        $content_type = $_SERVER['HTTP_CONTENT_TYPE'];

        $data = [];
        if ( $method == 'GET' ) {
            $data = $_GET;
        } elseif ( ( $method == 'POST' ) && ( strpos( $content_type, 'application/json' )  !== false ) ) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
        } elseif ( $method == 'POST' ) {
            $data = stripslashes_deep( $_POST );
        }

        $status_code = trim( sanitize_text_field( $data['status_code'] ) );
        $status_message = trim( sanitize_text_field( $data['status_message'] ) );
        $signature = trim( sanitize_text_field( $data['signature'] ) );
        $transaction_reference = trim( sanitize_text_field( $data['transaction_reference'] ) );
        $reference = trim( sanitize_text_field( $data['reference'] ) );
        $error_code = trim( sanitize_text_field( $data['error_code'] ) );
        $error_message = trim( sanitize_text_field( $data['error_message'] ) );

        $order = new WC_Order( $reference );
        $merchant_code = $this->merchant_code;
        $secret_key = $this->secret_key;
        $order_reference = $order->get_order_number();
        $currency_code = $order->get_currency();
        $amount = number_format( $order->get_total(), 2, '.', '' );
        $order_status = $order->get_status();

        $order_signature = md5($merchant_code . $secret_key . $order_reference . $currency_code . $amount . $transaction_reference . $status_code);

        $valid = ( $order_signature === $signature );

        if ( !$valid ) {
            $order->update_status( 'failed' );
            $order->add_order_note( __(  sprintf('Invalid Signature: %s. Expected: %s', $signature, $order_signature) , 'riipay' ));

            if ( !$is_callback ) {
                wc_add_notice(__('Invalid Signature. Please choose a valid payment method to proceed', 'riipay'), 'error');
                wp_redirect( $order->get_checkout_payment_url() );
            }

            echo 'Invalid Signature. Expected:  ' . $order_signature;
            exit();
        }

        $note = $status_message . sprintf(' [%s]', $transaction_reference );

        if ( in_array( strtolower($order_status), array( 'pending', 'failed', 'on-hold', 'unpaid' ) ) ) {
            if ( $status_code == 'F') {
                $order->update_status( 'failed', __(  $note , 'riipay' ), 1 );
                $order->add_order_note( __( $error_code . ': ' . $error_message, 'riipay'), 1 );
            } elseif ( $status_code == 'A' ) {
                $order->update_status( 'on-hold', __( $note, 'riipay' ));
            } elseif ( $status_code == 'S' ) {
                $order->payment_complete( $transaction_reference );
            }
        }

        if ( $is_callback ) {
            echo 'OK';
        } else {
            wp_redirect( $order->get_checkout_order_received_url() );
        }

        exit();
    }

    public function increase_stock( $order )
    {
        $this->adjust_stock( $order, 'increase' );
    }

    public function reduce_stock( $order )
    {
        $this->adjust_stock( $order, 'reduce' );
    }

    protected function adjust_stock( $order, $operation )
    {
        if ( is_a( $order, 'WC_Order' ) ) {
            $order_id = $order->get_id();
        } else {
            $order = wc_get_order( $order );
        }

        if ( get_option('woocommerce_manage_stock') !== 'yes' || $order->get_item_count() <= 0 ) {
            return;
        }

        $changes = [];
        foreach ( $order->get_items() as $item ) {
            //only adjust stock once for each item
            $qty = $item->get_meta( '_reduced_stock', true );
            if ( $operation == 'increase' && $qty <= 0 ) {
                continue;
            } elseif ( $operation == 'reduce' && $qty > 0 ) {
                continue;
            }

            //only adjust stock if product exists or product enables inventory management
            $product = $item->get_product();
            if ( !$product || !$product->managing_stock() ) {
                continue;
            }

            $old_qty = $product->get_stock_quantity();
            $new_qty = wc_update_product_stock( $product, $qty, $operation );

            if ( $operation == 'increase' ) {
                $item->delete_meta_data( '_reduced_stock' );
            } elseif ( $operation == 'reduce' ) {
                $item->add_meta_data( '_reduced_stock', $qty, true );
            }
            $item->save();

            $changes[] = sprintf('%s %s→%s', $product->get_formatted_name(), $old_qty, $new_qty);
        }

        if ( $changes ) {
            $order_note = sprintf( 'Stock levels %sd: %s', $operation, implode( ', ', $changes ) );
            $order->add_order_note( $order_note );
        }
    }

    public function woo_change_order_received_text( $text, $order)
    {
        if ( $order->has_status( 'on-hold' ) ) {
            $new_text = $text;
            $new_text .= '<br />You current order status is On Hold. Please note that the payment status will take some time to update.';
            return $new_text;
        } elseif ( $order->has_status( 'pending' ) || $order->has_status( 'unpaid' ) ) {
            $new_text = $text;
            $new_text .= '<br />Kindly make payment so that we can process your order as soon as possible.';
            $new_text .= '<br/>';
            $new_text .= sprintf( '<a href="%s" class="button pay">Pay</a>', $order->get_checkout_payment_url() );
            return $new_text;
        }

        return $text;
    }
}