<?php

  if( ! defined( 'ABSPATH' ) ) { exit; }
  
  define("BASEPATH", 1);
  require_once( REX_WC_DIR_PATH . 'includes/eventHandler.php' );
  require_once( REX_WC_DIR_PATH . 'rex-pay-api/rex-modal.php' );
      

  /**
   * Main Rexpay Gateway Class
   */
  class WC_Payment_Gateway_Rexpay extends WC_Payment_Gateway {

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct() {

		  $this->setup_properties();


      $this->init_form_fields();
      $this->init_settings();

		  // Get settings.
		  $this->title              = $this->get_option( 'title' );
		  $this->description        = $this->get_option( 'description' );
		  $this->widget_id          = $this->get_option( 'widget_id' );
		  // $this->enabled     		  = $this->get_option( 'enabled' );
      $this->testmode           = $this->get_option( 'testmode' ) === 'yes' ? true : false;
		  $this->client_id          = $this->get_option( 'live_client_id' );
		  $this->client_secret_key  = $this->get_option( 'live_client_secret_key' );
      $this->instructions       = $this->get_option( 'instructions' );
      $this->remove_cancel_order_button = $this->get_option( 'remove_cancel_order_button' ) === 'yes' ? true : false;
		  $this->payment_options    = $this->get_option( 'payment_options' );
      $this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes';
      $this->rexpay_generated_link = "";
      $this->payment_status = "pending";
      // $this->modal_logo = $this->get_option( 'modal_logo' );

      // enable saved cards
      // $this->saved_cards = $this->get_option( 'saved_cards' ) === 'yes' ? true : false;

      // declare support for Woocommerce subscription
      $this->supports = array(
        'products',
        'tokenization',
        'subscriptions',
        'subscription_cancellation', 
        'subscription_suspension', 
        'subscription_reactivation',
        'subscription_amount_changes',
        'subscription_date_changes',
        'subscription_payment_method_change',
        'subscription_payment_method_change_customer',
        'subscription_payment_method_change_admin',
        'multiple_subscriptions',
      );

      add_action( 'admin_notices', array( $this, 'admin_notices' ) );
      add_action( 'woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
      add_action( 'woocommerce_api_wc_payment_gateway_rexpay', array($this, 'rexpay_verify_payment'));

      add_action( 'woocommerce_api_wc_payment_gateway_rexpay', array($this, 'rexpay_webhooks'));
      
      if ( is_admin() ) {
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
      }

      add_action( 'woocommerce_thankyou_'. $this->id, array( $this, 'thankyou_page'));

      $this->load_scripts();

    }

  /**
	 * Setup general properties for the gateway.
	 */
	protected function setup_properties() {
		$this->id                 = 'rexpay';
		$this->icon               = apply_filters( 'woocommerce_rexpay_icon', plugins_url('assets/img/logo.PNG', REX_WC_PLUGIN_FILE) );
		$this->method_title       = __( 'RexPay Payments', 'rex-pay-woocommerce' );
		$this->method_description = __( 'RexPay is the fastest and easiest way to accept payment around the world. You can also accept payment  via CARD, USSD and Direct Debit.', 'rex-pay-woocommerce' );
		$this->has_fields         = false;
	}

    /**
     * Initial gateway settings form fields
     *
     * @return void
     */
    public function init_form_fields() {

      $this->form_fields = array(

        'enabled'                          => array(
          'title'       => __( 'Enable/Disable', 'rex-pay-woocommerce' ),
          'label'       => __( 'Enable or Disable Rexpay payment Gateway', 'rex-pay-woocommerce' ),
          'type'        => 'checkbox',
          'description' => __( 'Enable Rexpay as a payment option on the checkout page.', 'rex-pay-woocommerce' ),
          'default'     => 'no',
          'desc_tip'    => true,
        ),
        'title'                            => array(
          'title'       => __( 'Title', 'rex-pay-woocommerce' ),
          'type'        => 'text',
          'description' => __( 'This controls the payment method title which the user sees during checkout.', 'rex-pay-woocommerce' ),
          'default'     => __( 'RexPay Payment Gateway', 'rex-pay-woocommerce' ),
          'desc_tip'    => true,
        ),
        'description'                      => array(
          'title'       => __( 'Description', 'rex-pay-woocommerce' ),
          'type'        => 'textarea',
          'description' => __( 'This controls the payment method description which the user sees during checkout.', 'rex-pay-woocommerce' ),
          'default'     => __( 'Make payment using your debit and credit cards', 'rex-pay-woocommerce' ),
          'desc_tip'    => true,
        ),
        'testmode'                         => array(
          'title'       => __( 'Test mode', 'rex-pay-woocommerce' ),
          'label'       => __( 'Enable Test Mode', 'rex-pay-woocommerce' ),
          'type'        => 'checkbox',
          'description' => __( 'Test mode enables you to test payments before going live.', 'rex-pay-woocommerce' ),
          'default'     => 'yes',
          'desc_tip'    => true,
        ),
        'live_client_secret_key'                  => array(
          'title'       => __( 'Client Secret Key', 'rex-pay-woocommerce' ),
          'type'        => 'text',
          'description' => __( 'Enter your Client Secret Key here.', 'rex-pay-woocommerce' ),
          'default'     => '',
        ),
        'live_client_id'                  => array(
          'title'       => __( 'Client ID', 'rex-pay-woocommerce' ),
          'type'        => 'text',
          'description' => __( 'Enter your Client ID here.', 'rex-pay-woocommerce' ),
          'default'     => '',
        ),
        'remove_cancel_order_button'       => array(
          'title'       => __( 'Remove Cancel Order & Restore Cart Button', 'rex-pay-woocommerce' ),
          'label'       => __( 'Remove the cancel order & restore cart button on the pay for order page', 'rex-pay-woocommerce' ),
          'type'        => 'checkbox',
          'description' => '',
          'default'     => 'no',
        ),
        'payment_options' => array(
          'title'       => __( 'Payment Options', 'rex-pay-woocommerce' ),
          'type'        => 'select',
          'description' => __( 'Optional - Choice of payment method to use. Card, Account etc.', 'flw-payments' ),
          'options'     => array(
            'card'  => esc_html_x( 'Card Only',  'payment_options', 'rex-pay-woocommerce' )
          ),
          'default'     => ''
        ),
        'instructions'       => array(
          'title'       => __( 'Instructions', 'rex-pay-woocommerce' ),
          'type'        => 'textarea',
          'description' => __( 'Instructions that will be added to the thank you page.', 'rex-pay-woocommerce' ),
          'default'     => __( 'RexPay Payments before delivery.', 'rex-pay-woocommerce' ),
          'desc_tip'    => true,
        ),
        'enable_for_virtual' => array(
          'title'   => __( 'Accept for virtual orders', 'rex-pay-woocommerce' ),
          'label'   => __( 'Accept RexPay if the order is virtual', 'rex-pay-woocommerce' ),
          'type'    => 'checkbox',
          'default' => 'yes',
        ),

      );

    }

    /**
     * Process payment at checkout
     *
     * @return int $order_id
     */
    public function process_payment( $order_id ) {
      $order = wc_get_order( $order_id );
  
      return array(
        'result'   => 'success',
        'redirect' => $order->get_checkout_payment_url( true )
      );

    }

    
    /**
     * Handles admin notices
     *
     * @return void
     */
    public function admin_notices() {

      if ( 'no' == $this->enabled ) {
        return;
      }

      /**
       * Check if public key is provided
       */
      if ( ! $this->client_id || ! $this->client_secret_key ) {
        $mode = ($this->testmode) ? 'test' : 'live';
        echo '<div class="error"><p>';
        echo sprintf(
          'Provide your '.$mode .' Client Id and Client secret key <a href="%s">here</a> to be able to use the Rex-Pay Payment Gateway plugin.',
           admin_url( 'admin.php?page=wc-settings&tab=checkout&section=rexpay' )
         );
        echo '</p></div>';
        return;
      }

    }
    

    /**
     * Checkout receipt page
     *
     * @return void
     */
    public function receipt_page( $order ) {

      $order = wc_get_order( $order );
      echo '<p>'.__( 'Thank you for your order, please click the <b>Make Payment</b> button below to make payment. You will be redirected to a secure page where you can enter you card details or bank account details. <b>Please, do not close your browser at any point in this process.</b>', 'rex-pay-woocommerce' ).'</p>';
      if(!$this->remove_cancel_order_button){
        echo '<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">';
        echo __( 'Cancel order &amp; restore cart', 'rex-pay-woocommerce' ) . '</a> ';
      }
      
      echo '<a class="button alt  wc-forward" href="'.$this->rexpay_generated_link.'" id="#rexpay-payment-button" >Make Payment</a> ';
      // echo '<script>
      // let datlink = document.getElementById(\'rexpay-payment-button\');
      //     if(datlink != ""){
      //     document.location = "'.$this->rexpay_generated_link.'"
      //     }
      // </script>';
      
    }

    /**
     * Loads (enqueue) static files (js & css) for the checkout page
     *
     * @return void
     */
    public function load_scripts() {
      $secretKey = $this->client_secret_key;
      $clientId = $this->client_id;
      $testmode = $this->testmode;
      $payment1 = new RexApi($clientId, $secretKey, $testmode);

      $rexpay_auth = $payment1->get_api_auth();
      if ( ! is_checkout_pay_page() ) return;
      $payment_options = $this->payment_options;

      wp_enqueue_script( 'flw_js', plugins_url( 'assets/js/flw.js', REX_WC_PLUGIN_FILE ), array( 'jquery' ), '1.0.0', true );

      if ( get_query_var( 'order-pay' ) ) {
        
        $order_key = urldecode( $_REQUEST['key'] );
        $order_id  = absint( get_query_var( 'order-pay' ) );
        $txnref    = "WOOC_" . $order_id . '_' . time();
        $txnref    = filter_var($txnref, FILTER_SANITIZE_STRING);//sanitizr=e this field
        $cb_url = WC()->api_request_url( 'WC_Payment_Gateway_Rexpay' ).'?order_id='.$order_id.'&reference='.$txnref.'&auth='.$rexpay_auth ;
        $order     = wc_get_order( $order_id );
        

       
        if (version_compare(WOOCOMMERCE_VERSION, '2.7.0', '>=')){
              $amount    = $order->get_total();
              $email     = $order->get_billing_email();
              $currency     = $order->get_currency();
              $main_order_key = $order->get_order_key();
        }else{
            $args = array(
                'name'    => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'email'   => $order->get_billing_email(),
                'contact' => $order->get_billing_phone(),
            );
            $amount    = $order->get_total();
            $main_order_key = $order->get_order_key();
            $email     = $order->get_billing_email();
            $currency     = $order->get_currency();
        }
        
        // $amount    = $order->order_total;
        // $email     = $order->billing_email;
        // $currency     = $order->get_order_currency();
        
        //set the currency to route to their countries
        switch ($currency) {
            case 'KES':
              $this->country = 'KE';
              break;
            case 'GHS':
              $this->country = 'GH';
              break;
            case 'ZAR':
              $this->country = 'ZA';
              break;
            case 'TZS':
              $this->country = 'TZ';
              break;
            
            default:
              $this->country = 'NG';
              break;
        }


        $payment1->event_handler(new rexEventHandler($order));
        $payment1->set_payment_method();
        $payment1->set_email($order->get_billing_email());
        $payment1->set_amount($order->get_total());
        $payment1->set_currency($order->get_currency());
        $payment1->set_reference($txnref);
        $payment1->set_userID($order->get_billing_first_name()."".$order->get_billing_last_name());
        $payment1->set_callbackUrl($cb_url);
        $payment1->initiate_payment();
        $the_link = $payment1->get_payment_link();
        $this->rexpay_generated_link = $the_link;

        //exit();
        // if($this->payment_options == "ussd" || $this->payment_options == "account"){
        //   $payment1->set_bankCode();
        // }

        if ( $main_order_key == $order_key ) {

          $payment_args['payment_link'] = $the_link; 
         
        }

        update_post_meta( $order_id, '_rexpay_payment_txn_ref', $txnref );

      }

      wp_localize_script( 'flw_js', 'rexpay_payment_args', $payment_args );


    }

    /**
     * Verify payment made on the checkout page
     *
     * @return void
     */
    public function rexpay_verify_payment() {
      $clientId = $this->client_id; 
      $clientSecretKey = $this->client_secret_key;
      $testmode = $this->testmode; 
        
        if ( isset( $_GET['reference']) && isset( $_GET['order_id']) && isset( $_GET['auth']) ) {

            $txn_ref = isset($_GET['reference']) ? $_GET['reference'] : $_GET['reference'];
            $rex_auth = isset($_GET['auth']) ? $_GET['auth'] : $_GET['auth'];
            $order_id = urldecode($_GET['order_id']);
            $order = wc_get_order( $order_id );
            $payment = new RexApi($clientId, $secretKey, $testmode);
            //complete payment

            $payment->event_handler(new rexEventHandler($order));
            $payment->requery_transaction($txn_ref, $rex_auth);
            
            $redirect_url = $this->get_return_url( $order );
            header("Location: ".$redirect_url);
            die(); 
        }else{
          $payment = new RexApi($clientId, $secretKey, $testmode);
          
          $payment->event_handler(new rexEventHandler($order))->do_nothing();
            die();
        }
      
    }

    /**
	 * Process Webhook
	 */
    public function rexpay_webhooks() {
      // Retrieve the request's body
      $body = @file_get_contents("php://input");

      // retrieve the signature sent in the request header's.
      $signature = (isset($_SERVER['HTTP_VERIF_HASH']) ? $_SERVER['HTTP_VERIF_HASH'] : '');

      /* It is a good idea to log all events received. Add code *
      * here to log the signature and body to db or file       */

      if (!$signature) {
          // only a post with rave signature header gets our attention
          exit();
      }

      // Store the same signature on your server as an env variable and check against what was sent in the headers
      $local_signature = $this->get_option('secret_hash');

      // confirm the event's signature
      if( $signature !== $local_signature ){
        // silently forget this ever happened
        
      }
      sleep(10);

      http_response_code(200); // PHP 5.4 or greater
      // parse event (which is json string) as object
      // Give value to your customer but don't give any output
      // Remember that this is a call from rex-pay's servers and 
      // Your customer is not seeing the response here at all
      $response = json_decode($body);
      if ($response->status == 'successful') {

        $getOrderId = explode('_', $response->reference);
        $orderId = $getOrderId[1];
        // $order = wc_get_order( $orderId );
        $order = new WC_Order($orderId);

        if ($order->status == 'pending') {
          $order->update_status('processing');
          $order->add_order_note('Payment was successful on RexPay and verified via webhook');
          $customer_note  = 'Thank you for your order.<br>';

          $order->add_order_note( $customer_note, 1 );
      
          wc_add_notice( $customer_note, 'notice' );
        }

        
      }
      exit();    

    }

    /**
     * Output for the order recieved page
     */

    public function thankyou_page($orderid){

    //   $alert = '<div id="dialog" title="Basic dialog">
    //   <p>Your Transaction was : '. $this->payment_status.'</p>
    // </div>';'

    $order = wc_get_order( $orderid );
    $data_of_order = $order->get_data();
     if($data_of_order['status']){
      echo '<p>Your Transaction  (<b>'.$data_of_order['status'].'</b>)</p>';
     }
    

    }





  }
  
?>
