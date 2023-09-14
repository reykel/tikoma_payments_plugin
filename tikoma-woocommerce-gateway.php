<?php

/*
Plugin Name: Tikoma Merchant Payment Gateway
Plugin URI: http://www.tikomamerchant.com/
Description: Tikoma Merchant custom payment gateway integration with Woocommerce.
Version: 1.0
*/

add_action( 'plugins_loaded', 'bmspay_tikoma_init', 0 );

function bmspay_tikoma_init() {
    //if condition use to do nothin while WooCommerce is not installed
  if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
  include_once( 'tikoma-woocommerce.php' );
  // class add it too WooCommerce
  add_filter( 'woocommerce_payment_gateways', 'bmspay_add_tikoma_gateway' );
  function bmspay_add_tikoma_gateway( $methods ) {
    $methods[] = 'bmspay_Tikoma_Payment';
    return $methods;
  }
}
// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'bmspay_tikoma_action_links' );
function bmspay_tikoma_action_links( $links ) {
  $plugin_links = array(
    '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'bmspay-tikoma-payment' ) . '</a>',
  );
  return array_merge( $plugin_links, $links );
}