<?php
/*
 Plugin Name: ClassiPress Google Checkout Gateway
 Plugin URI: http://www.appthemes.com/
 Description: Extend the ClassiPress application theme to include Google Checkout as an additional payment gateway.
 Author: AppThemes
 Version: 0.2
 Author URI: http://www.appthemes.com/
 License: GPLv2 or later
 */
 
 
 
  
/**
 * setup important items
 * @since 0.2
 *
 */
$plugin_path = WP_PLUGIN_URL . '/' . dirname( plugin_basename( __FILE__ ) );
 
load_plugin_textdomain( 'cpgc', null, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
 
 
 
 
/**
 * Payment gateways admin values plugin
 * This is pulled into the WordPress backend admin 
 * pages under the ClassiPress gateway page
 *
 * @since 0.2
 *
 * Array param definitions are as follows:
 * name    = field name
 * desc    = field description
 * tip     = question mark tooltip text
 * id      = database column name or the WP meta field name
 * css     = any on-the-fly styles you want to add to that field
 * type    = type of html field or tab start/end
 * req     = if the field is required or not (1=required)
 * min     = minimum number of characters allowed before saving data
 * std     = default value. not being used
 * js      = allows you to pass in javascript for onchange type events
 * vis     = if field should be visible or not. used for dropdown values field
 * visid   = this is the row css id that must correspond with the dropdown value that controls this field
 * options = array of drop-down option value/name combo
 *
 *
 */
function gcheckout_add_gateway_values() {
    global $app_abbr, $plugin_path, $action_gateway_values;
    
    $action_gateway_values = array (
        array( 'type' => 'tab', 'tabname' => __( 'Google Checkout', 'cpgc' ), 'id' => '' ),
        
            array(  'name' => __( 'Google Checkout Options', 'cpgc' ),
                    'type' => 'title',
                      'id' => '' ), 
                      
            array(  'name' => '<img src="' . $plugin_path . '/images/gcheckout.png" />',
                    'type' => 'logo',
                      'id' => ''),          
                      
            array(  'name' => __( 'Enable Google Checkout', 'cpgc' ),
                    'desc' => sprintf( __( "You must have a <a target='_new' href='%s'>Google Checkout</a> account setup before using this feature.", 'cpgc' ), 'http://checkout.google.com/' ),
                     'tip' => __( 'Set this to yes if you want to offer Google Checkout as a payment option on your site. Note: the &quot;Charge for Listing Ads&quot; option on the pricing page must be set to yes for this option to work.', 'cpgc' ),
                      'id' => $app_abbr.'_enable_gcheckout',
                     'css' => 'width:100px;',
                     'std' => '',
                      'js' => '',
                    'type' => 'select',
                 'options' => array(  'yes' => __( 'Yes', 'cpgc' ),
                                      'no'  => __( 'No', 'cpgc' ) ), 
            ),   
            
            array(  'name' => __( 'Merchant ID', 'cpgc' ),
                    'desc' => sprintf( __( "You can find this in your Google Checkout account under 'Settings' => '<a target='_new' href='%s'>Integration</a>'.", 'cpgc' ), 'https://checkout.google.com/sell/settings?section=Integration' ),
                    'tip'  => __( 'Enter your Google Checkout merchant ID. This is where your money gets sent.', 'cpgc' ),
                    'id'   => $app_abbr.'_gcheckout_merch_id',
                    'css'  => 'min-width:250px;',
                    'type' => 'text',
                    'req'  => '',
                    'min'  => '',
                    'std'  => '',
                    'vis'  => '', 
            ),
            
            array(  'name' => __( 'Merchant Key', 'cpgc' ),
                    'desc' => sprintf( __( "You can find this in your Google Checkout account under 'Settings' => '<a target='_new' href='%s'>Integration</a>'.", 'cpgc' ), 'https://checkout.google.com/sell/settings?section=Integration' ),
                     'tip' => __( 'Enter your Google Checkout merchant key.', 'cpgc' ),
                      'id' => $app_abbr.'_gcheckout_merch_key',
                     'css' => 'min-width:250px;',
                    'type' => 'text',
                     'req' => '',
                     'min' => '',
                     'std' => '',
                     'vis' => '',
            ),
            
            array(  'name' => __( 'Currency Code', 'cpgc' ),
                    'desc' => sprintf( __( "Enter your three-letter <a target='_new' href='%s'>ISO 4217 currency code</a>.", 'cpgc' ), 'http://en.wikipedia.org/wiki/ISO_4217#Active_codes' ),
                    'tip'  => __( 'Enter your three-letter currency code. For example, for US dollars enter USD. For euros enter EUR.', 'cpgc' ),
                    'id'   => $app_abbr.'_gcheckout_curr_code',
                    'css'  => 'width:50px;',
                    'type' => 'text',
                    'req'  => '',
                    'min'  => '',
                    'std'  => '',
                    'vis'  => '', 
            ),
            
            array(  'name' => __( 'Sandbox Mode', 'cpgc' ),
                    'desc' => '',
                     'tip' => __( 'By default Google checkout is set to live mode. If you would like to test and see if payments are being processed correctly, check this box to switch to sandbox mode.', 'cpgc' ),
                      'id' => $app_abbr.'_google_sandbox',
                     'css' => '',
                    'type' => 'checkbox',
                     'req' => '',
                     'min' => '',
                     'std' => '',
                     'vis' => '',
            ),
            
        array( 'type' => 'tabend', 'id' => '' ),
    );

}
add_action( 'cp_action_gateway_values', 'gcheckout_add_gateway_values' );




/**
 * add the option to the payment drop-down list on checkout
 *
 * @since 0.2
 * @param array $order_vals contains all the order values
 *
 */
function gcheckout_add_gateway_option() {
    global $app_abbr, $gateway_name;
    
    if ( get_option( $app_abbr.'_enable_gcheckout' ) == 'yes' ) {
        echo '<option value="gcheckout">' . __( 'Google Checkout', 'cpgc' ) . '</option>';
    }

}
add_action( 'cp_action_payment_method', 'gcheckout_add_gateway_option' );




/**
 * do all the payment processing work here
 *
 * @since 0.2
 * @url http://code.google.com/apis/checkout/developer/Google_Checkout_Basic_HTML_How_Checkout_Works.html
 * @param array $order_vals contains all the order values
 *
 */
function gcheckout_gateway_process( $order_vals ) {
    global $gateway_name, $app_abbr, $post_url;

    // if gateway wasn't selected then exit
    if ( $order_vals['cp_payment_method'] != 'gcheckout' ) 
        return;
      
    // get the merchant id
    $merch_id = get_option( $app_abbr.'_gcheckout_merch_id' );
    
    // get the currency code
    $curr_code = get_option( $app_abbr.'_gcheckout_curr_code' );
    
    // is this a test transaction?
    if ( get_option( $app_abbr.'_google_sandbox' ) == true ) 
        $post_url = 'https://sandbox.google.com/checkout/api/checkout/v2/requestForm/Merchant/' . $merch_id; 
    else 
        $post_url = 'https://checkout.google.com/api/checkout/v2/checkoutForm/Merchant/' . $merch_id; 
?>    

    <form name="paymentform" method="post" action="<?php echo esc_url( $post_url ) ?>" accept-charset="utf-8">

        <input type="hidden" name="item_name_1" value="<?php echo esc_attr( $order_vals['item_name'] ); ?>" />
        <input type="hidden" name="item_description_1" value="" />
        <input type="hidden" name="item_merchant_id_1" value="<?php echo esc_attr( $merch_key ); ?>"/>
        <input type="hidden" name="item_price_1" value="<?php echo esc_attr( $order_vals['item_amount'] ); ?>" />
        <input type="hidden" name="continue_url" value="<?php echo esc_attr( $order_vals['return_url'] ); ?>"/>
        <input type="hidden" name="item_currency_1" value="<?php echo esc_attr( $curr_code ); ?>" />
        <input type="hidden" name="item_quantity_1" value="1" />
        <input type="hidden" name="_charset_" />
        
        <center><input type="submit" class="btn_orange" value="<?php _e( 'Continue &rsaquo;&rsaquo;', 'cpgc' );?>" /></center>
        
        <script type="text/javascript"> setTimeout("document.paymentform.submit();", 500); </script>

    </form>

<?php
}
add_action( 'cp_action_gateway', 'gcheckout_gateway_process', 10, 1 );
?>