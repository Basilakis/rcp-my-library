<?php
/*
Plugin Name: Restrict Content Pro - Helper Classes
Description: Illustrates how to add custom user fields to the Restrict Content Pro registration form that can also be edited by the site admins
Version: 1.0
Author: Basilis Kanonidis
Author URI: http://creativeg.gr
Contributors: Basilis Kanonidis
*/

add_action('admin_init', 'disable_dashboard');

function disable_dashboard() {
    if (!is_user_logged_in()) {
        return null;
    }
    if (!current_user_can('administrator') && is_admin()) {
        wp_redirect(home_url());
        exit;
    }
}

/*
 * Show Subscription leves, with a custom ID
 */
function cg_custom_subscription_level_hook($ret, $level_id, $user_id) { 
    if($_GET['level']) {
        if( $_GET['level'] == 5 && $level_id == 5 ) {
           return true;     
        } else {
            return false;
        }
    } else {
        if( in_array( $level_id , array(1,3,4) ) ) {
            return true;
        } else {
            return false;
        } 
    }

  return $ret;
}
add_filter( 'rcp_show_subscription_level', 'cg_custom_subscription_level_hook', 1, 3 );

/*
 * Add extra fee, when expired subscription more 5 days
 */
function cg_custom_add_fee($registration) {
    $user_id = get_current_user_id();
    if($user_id > 0){
        $member = new RCP_Member( $user_id );
        $expirationdate = $member->get_expiration_date();  
        $subs = $registration->get_subscription();
        if (date(strtotime("+5 day", strtotime($expirationdate))) < time() && ($subs !=3 && $subs != 4)){
           $registration->add_fee(5, __( 'Additional fee', 'rcp' ), false, false );
        }
    }
}
add_action( 'rcp_registration_init', 'cg_custom_add_fee', 20 );

/**
 * Get the total spent for a customer.
 * When no customer is passed into the function, it will get the currently logged in user's total spent
 *
 * Usage <?php echo sumobi_edd_get_total_spent_for_customer( $user_id ); ?>
 */
function cg_rcp_get_total_spent_for_customer( $user_id = '' ) {
	
	// if no user ID is passed in, default to the currently logged in user
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	// get customers
	$members = new RCP_Member( $user_id );

	// get customers purchase values
	$purchase_values = array();

	if ( rcp_get_user_payments() ) {
		foreach ( rcp_get_user_payments() as $payment ) {
			$purchase_values[] = $payment->amount;
		}
	}
	
	// get the total spent and format it
	$total_spent = rcp_currency_filter(  array_sum( $purchase_values ) );

	// return the amount the customer has spent
	return $total_spent;
}

function cg_rcp_get_total_months_for_customer( $user_id = '' ) {
    
    // if no user ID is passed in, default to the currently logged in user
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	
	$timezone = get_option( 'timezone_string' );
    
	$timezone = ! empty( $timezone ) ? $timezone : 'UTC';
    
	$member   = new RCP_Member( $user_id );
    
	$joined_date = new \DateTime( $member->get_joined_date(), new \DateTimeZone( $timezone ) );

	$now = new \DateTime( 'now', new \DateTimeZone( $timezone ) );
    
    $interval = date_diff($joined_date, $now);
    
    return $interval->format('%m') + 1;

}
