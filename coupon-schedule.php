<?php
/*
	Plugin Name: WooCommerce Coupon Schedule
	Description: Replaces default coupon expiry field with dedicated tab for coupon validity settings.
	Version: 0.1.2
	Author: Karolis Giedraitis
	Author URI: http://karolis.lt
	Text Domain: woocoupon-schedule
	License: GPL2 or later
*/

/*  
	Copyright 2014 Karolis Giedraitis  (email: info@karolis.lt)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WooCommerceCouponSchedule {
	public function WooCommerceCouponSchedule() {
		load_plugin_textdomain( 'woocoupon-schedule', false, basename( dirname( __FILE__ ) ) . '/languages' );

		// Check if WooCommerce is active
		if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			_e( "WooCommerce is not installed, therefore you can't use this plugin", 'woocoupon-schedule' );

			return null;	
		} else {
			add_action( 'admin_enqueue_scripts', array( $this, 'woocoupon_schedule_admin_scripts' ) );
			add_action( 'woocommerce_coupon_options_save', array( $this, 'woocoupon_schedule_add_meta' ), 10, 1 );
			add_action( 'woocommerce_coupon_data_panels', array( $this, 'woocoupon_schedule_panel' ) );
			add_filter( 'woocommerce_coupon_data_tabs', array( $this, 'woocoupon_schedule_add_data_tab_validity' ), 10, 1 );
			add_filter( 'woocommerce_coupon_is_valid', array( $this, 'woocoupon_schedule_check_validity' ), 10, 2 );
			add_action( 'manage_edit-shop_coupon_columns', array( $this, 'woocoupon_schedule_column' ), 11 );
			add_action( 'manage_shop_coupon_posts_custom_column', array( $this, 'woocoupon_schedule_show_column' ), 11 );
		}
	}

	function woocoupon_schedule_admin_scripts( $hook ) {
		if( 'shop_coupon' == get_post_type() && 'post.php' == $hook ) {			
			wp_register_style( 'woocoupon-schedule', plugin_dir_url( __FILE__ ) . 'assets/css/woocoupon.css', false, '0.1' );
			wp_enqueue_style( 'woocoupon-schedule' );

			wp_register_style( 'jquery-timepicker', plugin_dir_url( __FILE__ ) . 'assets/css/jquery.timepicker.css', false, '0.1' );
			wp_enqueue_style( 'jquery-timepicker' );

			wp_register_script( 'jquery-timepicker', plugin_dir_url( __FILE__ ) . 'assets/js/jquery.timepicker.min.js', false, null, true );
			wp_enqueue_script( 'jquery-timepicker', array( 'jquery' ) );

			wp_register_script( 'woocoupon-schedule', plugin_dir_url( __FILE__ ) . 'assets/js/woocoupon.js', false, null, true );
			wp_enqueue_script( 'woocoupon-schedule', array( 'jquery') );
		}
	}

	function woocoupon_schedule_check_validity( $valid, $coupon ) {
		$valid_from = get_post_meta( $coupon->id, 'woocoupon_schedule_coupon_valid_from_date_time', true );
		$valid_to = get_post_meta( $coupon->id, 'expiry_date', true );

		if ( current_time( 'timestamp' ) < strtotime( $valid_from ) ) {
			$valid = false;
		}

		if ( current_time( 'timestamp' ) > strtotime( $valid_to ) ) {
			$valid = false;
		}

		return $valid;
	}

	function woocoupon_schedule_add_data_tab_validity( $coupon_data_tabs ) {
		$coupon_data_tabs['woocoupon_schedule'] = array(
			'label'		=> __( 'Schedule', 'woocoupon-schedule' ),
			'target'	=> 'woocoupon_schedule_data',
			'class'		=> ''
		); 

		return $coupon_data_tabs;
	}

	function woocoupon_schedule_panel() {
		$valid_date_time = explode( ' ', get_post_meta( get_the_ID(), 'woocoupon_schedule_coupon_valid_from_date_time', true ) );
		$expiry_date_time = explode( ' ', get_post_meta( get_the_ID(), 'expiry_date', true ) );

		echo '<div id="woocoupon_schedule_data" class="panel woocommerce_options_panel">';
		echo '<div class="options_group">';
		
		woocommerce_wp_text_input( array(
			'id' 				=> 'woocoupon_schedule_coupon_valid_from_date',
			'label' 			=> __( 'Coupon valid from date', 'woocoupon-schedule' ),
			'placeholder' 		=> _x( 'YYYY-MM-DD', 'placeholder', 'woocoupon-schedule' ),
			'description' 		=> '',
			'class' 			=> 'date-picker short',
			'custom_attributes' => array( 'pattern' => "[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" ),
			'value' 			=> $valid_date_time[0]
		) );

		woocommerce_wp_text_input( array(
			'id' 				=> 'woocoupon_schedule_coupon_valid_from_time',
			'label' 			=> __( 'Coupon valid from time', 'woocoupon-schedule' ),
			'placeholder' 		=> _x( 'HH:MM', 'placeholder', 'woocoupon-schedule' ),
			'description' 		=> '',
			'class' 			=> 'short wcshe-timepicker',
			'custom_attributes' => array( 'pattern' => "(0[0-9]|1[0-9]|2[01234]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])" ),
			'value' 			=> $valid_date_time[1]
		) );

		woocommerce_wp_text_input( array(
			'id' 				=> 'woocoupon_schedule_coupon_expires_on_date',
			'label' 			=> __( 'Coupon expires on date', 'woocoupon-schedule' ),
			'placeholder' 		=> _x( 'YYYY-MM-DD', 'placeholder', 'woocoupon-schedule' ),
			'description' 		=> '',
			'class' 			=> 'date-picker short',
			'custom_attributes' => array( 'pattern' => "[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" ),
			'value' 			=> $expiry_date_time[0]
		) );

		woocommerce_wp_text_input( array(
			'id' 				=> 'woocoupon_schedule_coupon_expires_on_time',
			'label' 			=> __( 'Coupon expires on time', 'woocoupon-schedule' ),
			'placeholder' 		=> _x( 'HH:MM', 'placeholder', 'woocoupon-schedule' ),
			'description'	 	=> '',
			'class' 			=> 'short wcshe-timepicker',
			'custom_attributes' => array( 'pattern' => "(0[0-9]|1[0-9]|2[01234]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])" ),
			'value' 			=> $expiry_date_time[1]
		) );

		echo '</div>'; // end class="options_group"
		echo '</div>'; // end id="roni_coupon_validity_data"
	} 

	function woocoupon_schedule_add_meta( $id ) {
		update_post_meta( $id, 'woocoupon_schedule_coupon_valid_from_date_time', $_POST['woocoupon_schedule_coupon_valid_from_date'] . ' ' . $_POST['woocoupon_schedule_coupon_valid_from_time'] );
		update_post_meta( $id, 'expiry_date', $_POST['woocoupon_schedule_coupon_expires_on_date'] . ' ' . $_POST['woocoupon_schedule_coupon_expires_on_time'] );
	}

	function woocoupon_schedule_column( $columns ) {
		unset( $columns['expiry_date'] );

     	$columns['valid_from'] = __( 'Valid From', 'woocoupon-schedule' );
    	$columns['expiry_date_with_time'] = __( 'Expiry date', 'woocommerce' );

    	return $columns;
	}

	function woocoupon_schedule_show_column( $name ) {
	    global $post;

	    switch ( $name ) {
	        case 'expiry_date_with_time':
	            $expiry_date = get_post_meta( $post->ID, 'expiry_date', true );

				if ( $expiry_date )
					echo esc_html( date_i18n( 'F j, Y H:i', strtotime( $expiry_date ) ) );
				else
					echo '&ndash;';
	        break;
	        case 'valid_from':
	            $valid_from = get_post_meta( $post->ID, 'woocoupon_schedule_coupon_valid_from_date_time', true );

				if ( $valid_from )
					echo esc_html( date_i18n( 'F j, Y H:i', strtotime( $valid_from ) ) );
				else
					echo '&ndash;';
	        break;
	   }
	}
}

$woocoupon_schedule = new WooCommerceCouponSchedule();