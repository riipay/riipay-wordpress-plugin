<?php

defined('ABSPATH') || exit;

add_filter( 'woocommerce_get_price_html', 'riipay_custom_price_html', 10, 2 );
function riipay_custom_price_html( $price_html, $product )
{
    $settings = get_option('woocommerce_riipay_settings');
    $enabled = ( $settings['enabled'] === 'yes' );
    $custom = ( $settings['custom_product_price'] === 'yes' );
    if ( !$enabled || !$custom ) {
        return $price_html;
    }

    $merchant_code = $settings['merchant_code'];
    if ( empty($merchant_code) ) {
        return $price_html;
    }

    $url = riipay::PRODUCTION_URL;
    $environment = $settings['environment'];
    if ( $environment === 'sandbox' ) {
        $url = riipay::SANDBOX_URL;
    }

    $url = add_query_arg( array(
        'merchant_code' => $merchant_code, 'amount' => $product->get_price()
        ), $url . '/preview');
    $html = $price_html;

    $html .= '<p style="font-size: 12px; font-weight: 400; margin-bottom: 0;">';
    $html .= 'or 3 interest-free payments with ';
    $html .= sprintf('<img src="%s" width="40px" style="all: unset; display: inline-block; vertical-align: middle; max-width: 40px; float: none">', 'https://secure.uat.riipay.my/images/logos/new/logo-purple-light.png');
    $html .= '</p><p style="font-size: 12px; font-weight: 400; margin-top: 0;">';
    $html .= sprintf('<a href="%s" onclick="window.open(\'%s\', \'popup\', \'width=600,height=700\'); return false;" target="popup" style="font-size: 12px; font-weight: 400; text-decoration: underline;">More info</a>', $url, $url);
    $html .= '</p>';

    return $html;
}