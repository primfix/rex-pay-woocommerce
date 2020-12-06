<?php

/*
Plugin Name: RexPay Payment Gateway
Plugin URI: https://www.myrexpay.com/
Description: Official WooCommerce payment gateway for RexPay
Version: 1.0.0
Author: Global Accelerex
Author URI: https://www.globalaccelerex.com/
License: MIT License
WC requires at least:   3.0.0
WC tested up to:        4.0
text-domain: rex-pay-woocommerce
*/


if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

define( 'REX_WC_PLUGIN_FILE', __FILE__ );
define( 'REX_WC_DIR_PATH', plugin_dir_path( REX_WC_PLUGIN_FILE ) );



  function rexpay_woocommerce_init() {

    if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

    require_once( REX_WC_DIR_PATH . 'includes/class.rexpay_wc_payment_gateway.php' );

    // include subscription if exists
    if ( class_exists( 'WC_Subscriptions_Order' ) && class_exists( 'WC_Payment_Gateway_CC' ) ) {

      require_once( REX_WC_DIR_PATH . 'includes/class.rexpay_wc_subscription_payment.php' );
      
    }

    add_filter('woocommerce_payment_gateways', 'rexpay_woocommerce_add_gateway', 99 );
  }
  add_action('plugins_loaded', 'rexpay_woocommerce_init', 99);

  /**
   * Add the Settings link to the plugin
   *
   * @param  Array $links Existing links on the plugin page
   *
   * @return Array          Existing links with our settings link added
   */
  function rexpay_plugin_action_links( $links ) {

    $rave_settings_url = esc_url( get_admin_url( null, 'admin.php?page=wc-settings&tab=checkout&section=rexpay' ) );
    array_unshift( $links, "<a title='Rexpay Settings Page' href='$rave_settings_url'>Settings</a>" );

    return $links;

  }
  add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'rexpay_plugin_action_links' );

  /**
   * Add the Gateway to WooCommerce
   *
   * @param  Array $methods Existing gateways in WooCommerce
   *
   * @return Array          Gateway list with our gateway added
   */
  function rexpay_woocommerce_add_gateway($methods) {

    if (class_exists( 'WC_Payment_Gateway_CC' ) ) {


      $methods[] = 'WC_Payment_Gateway_Rexpay';
    }

    return $methods;

  }


?>