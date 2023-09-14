<?php
class bmspay_Tikoma_Payment extends WC_Payment_Gateway {
  function __construct() {
    // global ID
    $this->id = "bmspay_tikoma_payment";
    // Show Title
    $this->method_title = __( "Tikoma", 'bmspay-tikoma-payment' );
    // Show Description
    $this->method_description = __( "Tikoma Payment Gateway Plug-in for WooCommerce", 'bmspay-tikoma-payment' );
    // vertical tab title
    $this->title = __( "Tikoma", 'bmspay-tikoma-payment' );
    $this->icon = null;
    $this->has_fields = true;
    // support default form with credit card
    $this->supports = array( 'default_credit_card_form' );
    // setting defines
    $this->init_form_fields();
    // load time variable setting
    $this->init_settings();
    
    // Turn these settings into variables we can use
    foreach ( $this->settings as $setting_key => $value ) {
      $this->$setting_key = $value;
    }
    
    // further check of SSL if you want
    add_action( 'admin_notices', array( $this,  'do_ssl_check' ) );
    
    // Save settings
    if ( is_admin() ) {
      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    }    
  } // Here is the  End __construct()
  // administration fields for specific Gateway
public function init_form_fields() {
    $this->form_fields = array(
      'enabled' => array(
        'title'    => __( 'Enable / Disable', 'bmspay-tikoma-payment' ),
        'label'    => __( 'Enable this payment gateway', 'bmspay-tikoma-payment' ),
        'type'    => 'checkbox',
        'default'  => 'no',
      ),
      'title' => array(
        'title'    => __( 'Title', 'bmspay-tikoma-payment' ),
        'type'    => 'text',
        'desc_tip'  => __( 'Payment title of checkout process.', 'bmspay-tikoma-payment' ),
        'default'  => __( 'Credit card', 'bmspay-tikoma-payment' ),
      ),
      'description' => array(
        'title'    => __( 'Description', 'bmspay-tikoma-payment' ),
        'type'    => 'textarea',
        'desc_tip'  => __( 'Payment title of checkout process.', 'bmspay-tikoma-payment' ),
        'default'  => __( 'Successfully payment through credit card.', 'bmspay-tikoma-payment' ),
        'css'    => 'max-width:450px;'
      ),
      'api_username' => array(
        'title'    => __( 'Username', 'bmspay-tikoma-payment' ),
        'type'    => 'text',
        'desc_tip'  => __( 'This is the username provided by Tikoma when you signed up for an account.', 'bmspay-tikoma-payment' ),
      ),
      'api_password' => array(
        'title'    => __( 'Password', 'bmspay-tikoma-payment' ),
        'type'    => 'password',
        'desc_tip'  => __( 'This is the password provided by Tikoma when you signed up for an account.', 'bmspay-tikoma-payment' ),
      ),
      'api_mid' => array(
        'title'    => __( 'MID', 'bmspay-tikoma-payment' ),
        'type'    => 'text',
        'desc_tip'  => __( 'This is the MID code provided by Tikoma when you signed up for an account.', 'bmspay-tikoma-payment' ),
      ),
      'api_cid' => array(
        'title'    => __( 'CID', 'bmspay-tikoma-payment' ),
        'type'    => 'text',
        'desc_tip'  => __( 'This is the CID code provided by Tikoma when you signed up for an account.', 'bmspay-tikoma-payment' ),
      ),
      'app_type' => array(
        'title'    => __( 'App Type', 'bmspay-tikoma-payment' ),
        'type'    => 'text',
        'desc_tip'  => __( 'This is the App Type provided by Tikoma when you signed up for an account.', 'bmspay-tikoma-payment' ),
      ),
      'app_key' => array(
        'title'    => __( 'App Key', 'bmspay-tikoma-payment' ),
        'type'    => 'password',
        'desc_tip'  => __( 'This is the App Key provided by Tikoma when you signed up for an account.', 'bmspay-tikoma-payment' ),
      ),
      'environment' => array(
        'title'    => __( 'Test Mode', 'bmspay-tikoma-payment' ),
        'label'    => __( 'Enable Test Mode', 'bmspay-tikoma-payment' ),
        'type'    => 'checkbox',
        'description' => __( 'This is the sandbox of the gateway.', 'bmspay-tikoma-payment' ),
        'default'  => 'no',
      )
    );    
  }
  
  // Response handled for payment gateway
public function process_payment( $order_id ) {
    global $woocommerce;
    $customer_order = new WC_Order( $order_id );
    
    // checking for transiction
    $environment = ( $this->environment == "yes" ) ? 'TRUE' : 'FALSE';
    // Decide which URL to post to
    $environment_url = ( "FALSE" == $environment ) 
                               ? 'https://tikoma.com/api/Transactions/Sale'
               : 'https://tikoma.com/testing/api/Transactions/Sale';
    // This is where the fun stuff begins
    $payload = array(
      // Tikoma Credentials and API Info
    "UserName"=> $this->api_username,
    "Password"=> $this->api_password,
    "mid"=> $this->api_mid,
    "cid"=> $this->api_cid,
    "Amount"=> $customer_order->order_total,
    "TransactionType"=>1,
    "Track2"=> "",
    "ZipCode"=> $customer_order->billing_postcode,
    "ExpDate"=>str_replace( array( '/', ' '), '', $_POST['bmspay_tikoma_payment-card-expiry'] ),
    "CardNumber"=>str_replace( array(' ', '-' ), '', $_POST['bmspay_tikoma_payment-card-number'] ),
    "CVN"=>( isset( $_POST['bmspay_tikoma_payment-card-cvc'] ) ) ? $_POST['bmspay_tikoma_payment-card-cvc'] : '',
    "NameOnCard"=> $customer_order->billing_first_name,
    "AppKey"=>$this->app_key,
    "AppType"=> $this->app_type,
    "UserTransactionNumber"=> str_replace( "#", "", $customer_order->get_order_number() ),

      // Billing Information
      "x_first_name"           => $customer_order->billing_first_name,
      "x_last_name"            => $customer_order->billing_last_name,
      "x_address"              => $customer_order->billing_address_1,
      "x_city"                => $customer_order->billing_city,
      "x_state"                => $customer_order->billing_state,
      "x_zip"                  => $customer_order->billing_postcode,
      "x_country"              => $customer_order->billing_country,
      "x_phone"                => $customer_order->billing_phone,
      "x_email"                => $customer_order->billing_email,
      
      // Shipping Information
      "x_ship_to_first_name"   => $customer_order->shipping_first_name,
      "x_ship_to_last_name"    => $customer_order->shipping_last_name,
      "x_ship_to_company"      => $customer_order->shipping_company,
      "x_ship_to_address"      => $customer_order->shipping_address_1,
      "x_ship_to_city"         => $customer_order->shipping_city,
      "x_ship_to_country"      => $customer_order->shipping_country,
      "x_ship_to_state"        => $customer_order->shipping_state,
      "x_ship_to_zip"          => $customer_order->shipping_postcode,
      
      // information customer
      "x_cust_id"              => $customer_order->user_id,
      "x_customer_ip"          => $_SERVER['REMOTE_ADDR'],
      
    );
  
    // Send this payload to Tikoma for processing
    $response = wp_remote_post( $environment_url, array(
      'method'    => 'POST',
      'headers'  => array('Content-Type' => 'application/x-www-form-urlencoded'),
      'body'      => http_build_query( $payload ),
      'timeout'   => 90,
      'sslverify' => false,
    ) );

    if ( is_wp_error( $response ) ) 
      throw new Exception( __( 'There is issue for connectin payment gateway. Sorry for the inconvenience.', 'bmspay-tikoma-payment' ) );
    if ( empty( $response['body'] ) )
      throw new Exception( __( 'Tikoma\'s Response was not get any data.', 'bmspay-tikoma-payment' ) );
    
    // get body response while get not error
    $response_body = wp_remote_retrieve_body( $response );

    $resp = json_decode($response_body, true);


    if ( $resp['ResponseCode'] == 200 ) {
      // Payment successful

      $customer_order->add_order_note( __( 'Tikoma complete payment.', 'bmspay-tikoma-payment' ) );
                         
      // paid order marked
      $customer_order->payment_complete();
      // this is important part for empty cart
      $woocommerce->cart->empty_cart();
      // Redirect to thank you page
      return array(
        'result'   => 'success',
        'redirect' => $this->get_return_url( $customer_order ),
      );
    } else {
      //transaction fail
      wc_add_notice( $r['response_reason_text'], 'error' );
      $customer_order->add_order_note( 'Error: '. $r['response_reason_text'] );
    }
  }
  
  // Validate fields
  public function validate_fields() {
    return true;
  }
  public function do_ssl_check() {
    if( $this->enabled == "yes" ) {
      if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
        echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";  
      }
    }    
  }
}