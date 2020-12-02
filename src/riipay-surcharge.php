<?php

defined('ABSPATH') || exit;

//add_action('woocommerce_cart_calculate_fees', 'riipay_custom_surcharge', 10 );
function riipay_custom_surcharge()
{
    if ( is_admin() && !defined( 'DOING_AJAX' ) ) {
        return;
    }

    if ( !is_checkout() ) {
        return;
    }

    $settings = get_option('woocommerce_riipay_settings');
    $type = $settings['surcharge_type'];
    if ( $type === 'none' ) {
        return;
    }

    global $woocommerce;
    $chosen = $woocommerce->session->get( 'chosen_payment_method' );
    if ( $chosen !== 'riipay' ) {
        return;
    }

    $surcharge = 0;
    $value = $settings['surcharge_value'];
    $label = $settings['surcharge_title'];

    if ( $type === 'amount' ) {
        $surcharge = $value;
    } elseif ( $type === 'percentage' ) {
        $total = $woocommerce->cart->get_cart_contents_total() + $woocommerce->cart->get_cart_contents_tax() + $woocommerce->cart->get_shipping_total() + $woocommerce->cart->get_shipping_tax();
        $surcharge = $total * $value / 100;
    }

    if ( $surcharge > 0 ) {
        $woocommerce->cart->add_fee( $label, $surcharge );
    }
}

//add_action('wp_enqueue_scripts', 'riipay_enqueue_scripts' );
function riipay_enqueue_scripts()
{
    wp_enqueue_script( 'riipay-script', plugins_url( 'riipay-script.js', __FILE__ ), array('jquery') );
}

//add_filter( 'woocommerce_locate_template', 'riipay_custom_plugin_template', 1, 3 );
function riipay_custom_plugin_template( $template, $template_name, $template_path ) {
    global $woocommerce;
    $_template = $template;
    if ( ! $template_path )
        $template_path = $woocommerce->template_url;

    $plugin_path  = untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) )  . '/template/woocommerce/';

    // Look for template template
//    $template = locate_template(
//        array(
//            $template_path . $template_name,
//            $template_name
//        )
//    );

    if( file_exists( $plugin_path . $template_name ) ) {
        $template = $plugin_path . $template_name;
    }

    if ( ! $template ) {
        $template = $_template;
    }

    return $template;
}