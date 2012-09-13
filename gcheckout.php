<?php
/*
 Plugin Name: ClassiPress Google Wallet Gateway
 Plugin URI: http://www.appthemes.com/
 Description: Extend the ClassiPress application theme to include Google Wallet as an additional payment gateway.
 Author: AppThemes
 Version: 0.3
 Author URI: http://www.appthemes.com/
 License: GPLv2 or later
 */




/**
 * setup important items
 * @since 0.2
 *
 */
$plugin_path = WP_PLUGIN_URL . '/' . dirname( plugin_basename( __FILE__ ) );
define('CPGC_TD', 'cpgc');
load_plugin_textdomain( CPGC_TD, null, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );




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
	global $app_abbr, $action_gateway_values;

	$gc_gateway_values = array (

		array( 'type' => 'tab', 'tabname' => __('Google Wallet', CPGC_TD), 'id' => '' ),

					array(  'name' => __('Google Wallet Options', CPGC_TD),
									'type' => 'title',
									'id' => '' ),

								array(  'name' => '<img src="' . plugins_url( '/images/gcheckout.png', __FILE__ ) . '" />',
												'type' => 'logo',
												'id' => ''),

								array(  'name' => __('Enable Google Wallet', CPGC_TD),
												'desc' => sprintf( __("You must have a <a target='_new' href='%s'>Google Wallet</a> account setup before using this feature.", CPGC_TD), 'http://checkout.google.com/' ),
												'tip' => __('Set this to yes if you want to offer Google Wallet as a payment option on your site. Note: the &quot;Charge for Listing Ads&quot; option on the pricing page must be set to yes for this option to work.', CPGC_TD),
												'id' => $app_abbr.'_enable_gcheckout',
												'css' => 'width:100px;',
												'std' => '',
												'js' => '',
												'type' => 'select',
												'options' => array( 'yes' => __('Yes', CPGC_TD),
																						'no'  => __('No', CPGC_TD) )),

								array(  'name' => __('Merchant ID', CPGC_TD),
												'desc' => sprintf( __("You can find this in your Google Wallet account under 'Settings' => '<a target='_new' href='%s'>Integration</a>'.", CPGC_TD), 'https://checkout.google.com/sell/settings?section=Integration' ),
												'tip'  => __('Enter your Google Wallet merchant ID. This is where your money gets sent.', CPGC_TD),
												'id'   => $app_abbr.'_gcheckout_merch_id',
												'css'  => 'min-width:250px;',
												'type' => 'text',
												'req'  => '',
												'min'  => '',
												'std'  => '',
												'vis'  => ''),

								array(  'name' => __('Merchant Key', CPGC_TD),
												'desc' => sprintf( __("You can find this in your Google Wallet account under 'Settings' => '<a target='_new' href='%s'>Integration</a>'.", CPGC_TD), 'https://checkout.google.com/sell/settings?section=Integration' ),
												'tip' => __('Enter your Google Wallet merchant key.', CPGC_TD),
												'id' => $app_abbr.'_gcheckout_merch_key',
												'css' => 'min-width:250px;',
												'type' => 'text',
												'req' => '',
												'min' => '',
												'std' => '',
												'vis' => ''),

								array(  'name' => __('Currency Code', CPGC_TD),
												'desc' => sprintf( __("Enter your three-letter <a target='_new' href='%s'>ISO 4217 currency code</a>.", CPGC_TD), 'http://en.wikipedia.org/wiki/ISO_4217#Active_codes' ),
												'tip'  => __('Enter your three-letter currency code. For example, for US dollars enter USD. For euros enter EUR.', CPGC_TD),
												'id'   => $app_abbr.'_gcheckout_curr_code',
												'css'  => 'width:50px;',
												'type' => 'text',
												'req'  => '',
												'min'  => '',
												'std'  => '',
												'vis'  => ''),

								array(  'name' => __('Sandbox Mode', CPGC_TD),
												'desc' => '',
												'tip' => __('By default Google Wallet is set to live mode. If you would like to test and see if payments are being processed correctly, check this box to switch to sandbox mode.', CPGC_TD),
												'id' => $app_abbr.'_google_sandbox',
												'css' => '',
												'type' => 'checkbox',
												'req' => '',
												'min' => '',
												'std' => '',
												'vis' => ''),

		array( 'type' => 'tabend', 'id' => '' ),

	);

	// merge the above options with any passed into via the hook
	$action_gateway_values = array_merge( (array)$action_gateway_values, (array)$gc_gateway_values);

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

	if ( get_option( $app_abbr.'_enable_gcheckout' ) == 'yes' )
		echo '<option value="gcheckout">' . __('Google Wallet', CPGC_TD) . '</option>';

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
	global $gateway_name, $app_abbr, $post_url, $userdata;

	get_currentuserinfo(); // grabs the user info and puts into vars

	// if gateway wasn't selected then exit
	if ( $order_vals['cp_payment_method'] != 'gcheckout' )
		return;

	// get the merchant id
	$merch_id = get_option( $app_abbr.'_gcheckout_merch_id' );

	// get the currency code
	$curr_code = get_option( $app_abbr.'_gcheckout_curr_code' );

	// is this a test transaction?
	if ( get_option( $app_abbr.'_google_sandbox' ) == true )
		$post_url = 'https://sandbox.google.com/checkout/api/checkout/v2/checkoutForm/Merchant/' . $merch_id;
	else
		$post_url = 'https://checkout.google.com/api/checkout/v2/checkoutForm/Merchant/' . $merch_id;

	$back_url = add_query_arg( array( 'oid' => $order_vals['oid'], 'gcheckout' => $order_vals['oid'] .'_'. $userdata->ID ), CP_DASHBOARD_URL );
	$back_url = wp_nonce_url( $back_url, $order_vals['oid'] );
?>

	<form name="paymentform" method="post" action="<?php echo esc_url( $post_url ) ?>" accept-charset="utf-8">

		<input type="hidden" name="item_name_1" value="<?php echo esc_attr( $order_vals['item_name'] ); ?>" />
		<input type="hidden" name="item_description_1" value="<?php echo esc_attr( $order_vals['item_name'] ); ?>" />
		<input type="hidden" name="item_merchant_id_1" value="<?php echo esc_attr( $merch_id ); ?>"/>
		<input type="hidden" name="item_price_1" value="<?php echo esc_attr( $order_vals['item_amount'] ); ?>" />
		<input type="hidden" name="item_currency_1" value="<?php echo esc_attr( $curr_code ); ?>" />
		<input type="hidden" name="item_quantity_1" value="1" />
		<input type="hidden" name="_charset_" value="utf-8" />
		<input type="hidden" name="continue_url" value="<?php echo esc_attr( $back_url ); ?>"/>
		<input type="hidden" name="shopping-cart.items.item-1.digital-content.url" value="<?php echo esc_attr( $back_url ); ?>" />

		<center><input type="submit" class="btn_orange" value="<?php _e('Continue &rsaquo;&rsaquo;', CPGC_TD); ?>" /></center>

		<script type="text/javascript"> setTimeout("document.paymentform.submit();", 500); </script>

	</form>

<?php
}
add_action( 'cp_action_gateway', 'gcheckout_gateway_process', 10, 1 );


/**
 * Payment processing for ad dashboard so ad owners can pay for unpaid ads
 * @since 0.3
 */
function gcheckout_dashboard_button( $the_id, $type = '' ) {
	global $wpdb, $app_abbr, $userdata;

	if( get_option($app_abbr.'_enable_gcheckout') != 'yes' )
		return;

	get_currentuserinfo(); // grabs the user info and puts into vars

	$pack = get_pack( $the_id );

	// figure out the number of days this ad was listed for
	if ( get_post_meta($the_id, 'cp_sys_ad_duration', true) ) $prun_period = get_post_meta($the_id, 'cp_sys_ad_duration', true); else $prun_period = get_option('cp_prun_period');

	//setup variables depending on the purchase type
	if ( isset( $pack->pack_name ) && stristr( $pack->pack_status, 'membership' ) ) {
		// Membership button not supported
		return;
	} else {
		$item_name = sprintf( __('Classified ad listing on %s for %s days', CPGC_TD), get_bloginfo('name'), $prun_period);
		$item_number = get_post_meta($the_id, 'cp_sys_ad_conf_id', true); 
		$amount = get_post_meta($the_id, 'cp_sys_total_ad_cost', true);
		$oid = get_post_meta( $the_id, 'cp_sys_ad_conf_id', true );
		$back_url = add_query_arg( array( 'oid' => $oid, 'gcheckout' => $oid .'_'. $userdata->ID ), CP_DASHBOARD_URL );
		$back_url = wp_nonce_url( $back_url, $oid );
	}

	// get the merchant id
	$merch_id = get_option($app_abbr.'_gcheckout_merch_id');

	// get the currency code
	$curr_code = get_option($app_abbr.'_gcheckout_curr_code');

	// is this a test transaction?
	if ( get_option($app_abbr.'_google_sandbox') == true )
		$post_url = 'https://sandbox.google.com/checkout/api/checkout/v2/checkoutForm/Merchant/' . $merch_id;
	else
		$post_url = 'https://checkout.google.com/api/checkout/v2/checkoutForm/Merchant/' . $merch_id;

?>

	<form name="paymentform" method="post" action="<?php echo esc_url( $post_url ) ?>" accept-charset="utf-8">

		<input type="hidden" name="item_name_1" value="<?php echo esc_attr( $item_name ); ?>" />
		<input type="hidden" name="item_description_1" value="<?php echo esc_attr( $item_name ); ?>" />
		<input type="hidden" name="item_merchant_id_1" value="<?php echo esc_attr( $merch_id ); ?>"/>
		<input type="hidden" name="item_price_1" value="<?php echo esc_attr( $amount ); ?>" />
		<input type="hidden" name="item_currency_1" value="<?php echo esc_attr( $curr_code ); ?>" />
		<input type="hidden" name="item_quantity_1" value="1" />
		<input type="hidden" name="_charset_" value="utf-8" />
		<input type="hidden" name="continue_url" value="<?php echo esc_attr( $back_url ); ?>"/>
		<input type="hidden" name="shopping-cart.items.item-1.digital-content.url" value="<?php echo esc_attr( $back_url ); ?>" />

		<input type="image" src="<?php echo plugins_url( '/images/gcheckout-button.png', __FILE__ ); ?>" name="submit" />

	</form>

<?php

}
add_action( 'cp_action_payment_button', 'gcheckout_dashboard_button', 10, 1 );


/**
 * Process Ad Payment
 * @since 0.3
 */
function gcheckout_listener() {
	global $wpdb;

	if ( isset($_GET['gcheckout']) && !empty($_GET['_wpnonce']) ) {

		//step functions required to process orders
		include_once("wp-load.php");
		include_once (TEMPLATEPATH . '/includes/forms/step-functions.php');

		$pid = explode("_", $_GET['gcheckout']);

		if ( !wp_verify_nonce( $_GET['_wpnonce'], $pid[0] ) )
			return;

		$order_processed = false;
		$order = get_option("cp_order_".$pid[1]."_".$pid[0]);
		//make sure the order sent from payment gateway is logged in the database and that the current user created it
		if ( isset($order['order_id']) && $order['order_id'] == $pid[0] ) {
			$the_user = get_userdata($order['user_id']);
			$order['order_id'] = $pid[0];
			$order_processed = appthemes_process_membership_order($the_user, $order);
		}

		if ( $order_processed ) {
			//send email to user
			cp_owner_activated_membership_email($the_user, $order_processed);
			//admin email confirmation
			wp_mail(get_option('admin_email'), __('Google Wallet Activated Memebership', CPGC_TD), 
				__('A membership order has been completed. Check to make sure this is a valid order by Google Wallet orders page.', CPGC_TD) . PHP_EOL
				. __('Order ID: ', CPGC_TD) . print_r($order['order_id'], true) . PHP_EOL
				. __('User ID: ', CPGC_TD) . print_r($order['user_id'], true) . PHP_EOL
				. __('User Login: ', CPGC_TD) . print_r($the_user->user_login, true) . PHP_EOL
				. __('Pack Name: ', CPGC_TD) . print_r(stripslashes($order['pack_name']), true) . PHP_EOL
				. __('Total Cost: ', CPGC_TD) . print_r($order['total_cost'], true) . PHP_EOL
			);
		} else {
			$sql = $wpdb->prepare("SELECT p.ID, p.post_status
				FROM $wpdb->posts p, $wpdb->postmeta m
				WHERE p.ID = m.post_id
				AND p.post_status <> 'publish'
				AND m.meta_key = 'cp_sys_ad_conf_id'
				AND m.meta_value = %s
				", $pid[0]);

			$newadid = $wpdb->get_row($sql);

			// if the ad is found, then publish it
			if ( $newadid ) {
				$the_ad = array();
				$the_ad['ID'] = $newadid->ID;
				$the_ad['post_status'] = 'publish';
				$ad_id = wp_update_post($the_ad);

				$ad_length = get_post_meta($ad_id, 'cp_sys_ad_duration', true);
				$ad_length = empty($ad_length) ? get_option('cp_prun_period') : $ad_length;

				// set the ad listing expiration date
				$ad_expire_date = date_i18n('m/d/Y H:i:s', strtotime('+' . $ad_length . ' days')); // don't localize the word 'days'

				//now update the expiration date on the ad
				update_post_meta($ad_id, 'cp_sys_expire_date', $ad_expire_date);
			}
		}

		if ( $order_processed ) {
			$ad_id = '';
			$tr_subject = __('Membership Purchase', CPGC_TD);
			$tr_amount = $order['total_cost'];
		} else {
			$the_ad = get_post( $ad_id );
			$tr_subject = __('Ad Purchase', CPGC_TD);
			$tr_amount = get_post_meta($ad_id, 'cp_sys_total_ad_cost', true);;
		}
		$the_user = get_userdata($pid[1]);

		// check and make sure this transaction hasn't already been added
		$results = $wpdb->get_var( $wpdb->prepare( "SELECT item_number FROM $wpdb->cp_order_info WHERE item_number = %s LIMIT 1", appthemes_clean( $pid[0] ) ) );
		if ( $results )
			return;

		$data = array(
			'ad_id' => appthemes_clean($ad_id),
			'user_id' => appthemes_clean($the_user->ID),
			'first_name' => appthemes_clean($the_user->first_name),
			'last_name' => appthemes_clean($the_user->last_name),
			'payer_email' => appthemes_clean($the_user->user_email),
			'residence_country' => '',
			'transaction_subject' => appthemes_clean($pid[0]),
			'item_name' => appthemes_clean($tr_subject),
			'item_number' => appthemes_clean($pid[0]),
			'payment_type' => 'gcheckout',
			'payer_status' => '',
			'payer_id' => '',
			'receiver_id' => '',
			'parent_txn_id' => '',
			'txn_id' => appthemes_clean($pid[0]),
			'mc_gross' => appthemes_clean($tr_amount),
			'mc_fee' => '',
			'payment_status' => 'Completed',
			'pending_reason' => '',
			'txn_type' => '',
			'tax' => '',
			'mc_currency' => appthemes_clean(get_option('cp_gcheckout_curr_code')),
			'reason_code' => '',
			'custom' => appthemes_clean($pid[0]),
			'test_ipn' => '',
			'payment_date' => current_time('mysql'),
			'create_date' => current_time('mysql'),
		);

		$wpdb->insert( $wpdb->cp_order_info, $data );



	}

}
add_action('init', 'gcheckout_listener');

?>