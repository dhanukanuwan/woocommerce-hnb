<?php
/*
* Plugin Name: WooCommerce HNB Gateway
* Plugin URI: http://codeart.lk
* Description: Extends WooCommerce with HNB Payment gateway.
* Version: 1.0.0
* Author: Dhanuka Nuwan Gunarathna
* Author URI: http://codeart.lk
* Text Domain: wc-gateway-hnb
* Copyright: © 2016 Dhanuka Nuwan Gunarathna.
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

/**
 * Create a new table for HNB payment gateway on plugin activation
 *
 * @since 1.0.0
 * @return This will create a table called hnb_gateway_orders
 */
register_activation_hook( __FILE__, 'hnb_gateway_create_db' );

function hnb_gateway_create_db() {
  global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . 'hnb_gateway_orders';

  $sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		order_id mediumint(9) NOT NULL,
    order_number TEXT NOT NULL,
		customer_first_name TEXT NOT NULL,
		customer_last_name TEXT NOT NULL,
		customer_email TEXT NOT NULL,
		order_total TEXT NOT NULL,
		ref_num TEXT NOT NULL,
		status TEXT NOT NULL,
		fail_reason TEXT NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

/**
 * Add the gateway to WC Available Gateways
 *
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + HNB gateway
 */
function wc_hnb_add_to_gateways( $gateways ) {
	$gateways[] = 'WC_Gateway_HNB';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_hnb_add_to_gateways' );

/**
 * Adds plugin page links
 *
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_hnb_gateway_plugin_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=hnb_gateway' ) . '">' . __( 'Configure', 'wc-gateway-hnb' ) . '</a>'
	);
	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_hnb_gateway_plugin_links' );

/**
 * HNB Payment Gateway
 *
 * Provides a payment gateway for WooCommerce powered shops with HNB.
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class       WC_Gateway_HNB
 * @extends     WC_Payment_Gateway
 * @version     1.0.0
 * @package     WooCommerce/Classes/Payment
 * @author      Dhanuka Nuwan Gunarathna
 */
add_action( 'plugins_loaded', 'wc_hnb_gateway_init', 11 );

function wc_hnb_gateway_init() {

    class WC_Gateway_HNB extends WC_Payment_Gateway {

        /**
  		 * Constructor for the gateway.
  		 */
  		public function __construct() {

  			$this->id                 = 'hnb_gateway';
  			$this->icon               = apply_filters('woocommerce_hnb_icon', '');
  			$this->has_fields         = false;
  			$this->method_title       = __( 'HNB Payment Gateway', 'wc-gateway-hnb' );
  			$this->method_description = __( 'Allows to pay using credit/debit card with HNB', 'wc-gateway-hnb' );

  			// Load the settings.
  			$this->init_form_fields();
  			$this->init_settings();

  			// Define user set variables
  			$this->title        = $this->get_option( 'title' );
  			$this->description  = $this->get_option( 'description' );
  			$this->instructions = $this->get_option( 'instructions', $this->description );

  			// Actions
  			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
  			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

  			// Customer Emails
  			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
  		}


      /**
		 * Initialize Gateway Settings Form Fields
		 */
  		public function init_form_fields() {

  			$this->form_fields = apply_filters( 'wc_hnb_form_fields', array(

  				'enabled' => array(
  					'title'   => __( 'Enable/Disable', 'wc-gateway-hnb' ),
  					'type'    => 'checkbox',
  					'label'   => __( 'Enable HNB Payment Gateway', 'wc-gateway-hnb' ),
  					'default' => 'no'
  				),

  				'title' => array(
  					'title'       => __( 'Title', 'wc-gateway-hnb' ),
  					'type'        => 'text',
  					'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-hnb' ),
  					'default'     => __( 'HNB Payment Gateway', 'wc-gateway-hnb' ),
  					'desc_tip'    => true,
  				),

  				'description' => array(
  					'title'       => __( 'Description', 'wc-gateway-hnb' ),
  					'type'        => 'textarea',
  					'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-gateway-hnb' ),
  					'default'     => __( 'Pay using your credit/debit card with HNB.', 'wc-gateway-hnb' ),
  					'desc_tip'    => true,
  				),

  				'instructions' => array(
  					'title'       => __( 'Instructions', 'wc-gateway-hnb' ),
  					'type'        => 'textarea',
  					'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc-gateway-hnb' ),
  					'default'     => '',
  					'desc_tip'    => true,
  				),
  			) );
  		}


      /**
		  * Output for the order received page.
		 */
  		public function thankyou_page() {
  			if ( $this->instructions ) {
  				echo wpautop( wptexturize( $this->instructions ) );
  			}
  		}


      /**
		 * Add content to the WC emails.
		 *
		 * @access public
		 * @param WC_Order $order
		 * @param bool $sent_to_admin
		 * @param bool $plain_text
		 */
  		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {

  			if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
  				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
  			}
  		}


      /**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
  		public function process_payment( $order_id ) {

  			$order = wc_get_order( $order_id );

  			// Mark as on-hold (we're awaiting the payment)
  			$order->update_status( 'on-hold', __( 'Awaiting HNB payment', 'wc-gateway-hnb' ) );

  			// Reduce stock levels
  			//$order->reduce_order_stock();

  			// Remove cart
  			WC()->cart->empty_cart();

        // Get return page
        $return_page = get_page_by_title( 'Send To HNB' );
        $return_page_url = get_permalink( $return_page->ID ) . '?id=' . $order_id;

        $this->insert_data_to_database_table( $order, $order_id );

  			// Return thankyou redirect
  			return array(
  				'result' 	=> 'success',
  				'redirect'	=> $return_page_url
  			);
  		}

      /**
     * Insert order information to database
     *
     * @param int $order, $order_id
     */
      protected function insert_data_to_database_table( $order, $order_id ) {
        global $wpdb;
      	$table_name = $wpdb->prefix . 'hnb_gateway_orders';
        $order_number = 'shop' . $order_id;
        $order_total = $order->get_total();

        $wpdb->insert( $table_name, array(
  				'order_id' => $order_id,
          'order_number' => $order_number,
  				'customer_first_name' => $order->billing_first_name,
  				'customer_last_name' => $order->billing_last_name,
  				'customer_email'	=> $order->billing_email,
  				'order_total' => $order_total,
  			), array('%d', '%s', '%s', '%s', '%s', '%s'));
      }

    } // end \WC_Gateway_HNB class

    add_filter( 'template_include', 'redirect_page_template', 99 );

    function redirect_page_template( $template ) {

    	if ( is_page( 'send-to-hnb' )  ) {
    		$redirect_template = dirname( __FILE__ ) . '/redirect-page-template.php';
    		if ( '' != $redirect_template ) {
    			return $redirect_template ;
    		}
    	}

			if ( is_page( 'shop-response' ) ) {
				$response_template = dirname( __FILE__ ) . '/response-page-template.php';
				if ( '' != $response_template ) {
					return $response_template ;
				}
			}

    	return $template;
    }
}
