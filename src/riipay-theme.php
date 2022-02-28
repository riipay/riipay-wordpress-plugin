<?php

defined('ABSPATH') || exit;

add_filter( 'woocommerce_get_price_html', 'riipay_custom_price_html', 10, 2 );
function riipay_custom_price_html( $price_html, $product )
{
    $settings = get_option('woocommerce_riipay_settings');
    $enabled = ( $settings['enabled'] === 'yes' );
    $custom = isset( $settings['custom_product_price'] ) ?  ( $settings['custom_product_price'] === 'yes' ) : true;
    if ( !$enabled || !$custom ) {
        return $price_html;
    }

    //only visible to admin if under sandbox environment
    $visibility = isset($settings['visibility']) ? $settings['visibility'] : 'all';
    if ( $visibility === 'admin_only' && !current_user_can('administrator') ) {
        return $price_html;
    }

    $merchant_code = $settings['merchant_code'];
    if ( empty($merchant_code) ) {
        return $price_html;
    }

    //check if product price is within price range
    $productPrice = $product->get_price();
    $minProductPrice = isset($settings['min_product_price']) ? $settings['min_product_price'] : 0;
    $maxProductPrice = isset($settings['max_product_price']) && $settings['max_product_price'] ? $settings['max_product_price'] : 9999;
    if ($productPrice < $minProductPrice || $productPrice > $maxProductPrice) {
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

    $instalmentCount = $settings['number_of_instalment'] ? (int) $settings['number_of_instalment'] : 3;
    $showInstalmentPrice = $settings['show_split_price'] ? (bool) ( $settings['show_split_price'] === 'yes' ) : true;
    $logoVerticalAlign = $settings['logo_vertical_align'] ? $settings['logo_vertical_align'] : 'middle';
    $logoMarginBottom = $settings['logo_margin_bottom'] ? $settings['logo_margin_bottom'] : 0;

    $instalmentPrice = number_format(round($product->get_price() / $instalmentCount, 2), 2, '.', '');
    $html .= '<p class="riipay-product-widget" style="font-size: 12px; font-weight: 400; margin-bottom: 0; line-height: 20px;">';
    if ($showInstalmentPrice) {
        $html .= sprintf('or %s payments of <span style="font-weight: bold;">%s %s</span> with ', $instalmentCount, get_woocommerce_currency_symbol(), $instalmentPrice );
    } else {
        $html .= sprintf('or %s interest-free payments with ', $instalmentCount );
    }

    $html .= sprintf('<img src="%s" width="40px" style="all: unset; display: inline-block; vertical-align: %s; max-width: 40px; float: none; max-height: 20px; margin-bottom: %s">', 'https://firebasestorage.googleapis.com/v0/b/riipay-assets/o/logo%2Flogo-purple-light.png?alt=media&token=8fb76006-b822-4f94-ad3e-248813cc433b', $logoVerticalAlign, $logoMarginBottom);
    $html .= '</p><p style="font-size: 12px; font-weight: 400; margin-top: 0;">';
    $html .= sprintf('<a href="%s" onclick="window.open(\'%s\', \'popup\', \'width=600,height=700\'); return false;" target="popup" style="font-size: 12px; font-weight: 400; text-decoration: underline;">More info</a>', $url, $url);
    $html .= '</p>';

    return $html;
}
