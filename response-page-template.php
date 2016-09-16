<?php get_header(); ?>
  <div class="container">
    <div class="row">
      <div class="col-xs-12 walker-response-wrap">
        <?php
            // Get responce data from the bank
            $responseCode = intval( wp_strip_all_tags( $_POST['ResponseCode'] ) );
            $reasonCodeDesc = wp_strip_all_tags( $_POST['ReasonCodeDesc'] );
            $order_number = wp_strip_all_tags( $_POST['OrderID'] );
            $refNo = intval( wp_strip_all_tags( $_POST['ReferenceNo'] ) );

            $order_id = intval( $order_number );
            $order = new WC_Order( $order_id );

            global $wpdb;
            $table_name = $wpdb->prefix . 'hnb_gateway_orders';

            if ( $responseCode === 1 ) {

              // Update the database with successfull Reference numbers
              $wpdb->update(
                $table_name,
                array(
                  'ref_num' => $refNo,
                  'status' => 'success'
                ),
                  array('order_number' => $order_number ),
                  array('%s','%s'),
                  array('%s')
                );

              // Update the order status
              $order->update_status( 'processing' );

              // Reduce stock levels
        			$order->reduce_order_stock();

              $display_msg = "Your order placed successfully. Please check your email for the invoice.";
            } else {

              // Update fail reasons
              $wpdb->update(
                $table_name,
                array(
                  'fail_reason' => $reasonCodeDesc,
                  'status' => 'fail'
                ),
                  array('order_number' => $order_number ),
                  array('%s','%s'),
                  array('%s')
                );

                // Update the order status - failed
                $order->update_status( 'cancelled' );

                $display_msg = "Order Failed <br/> Reason: " . $reasonCodeDesc;
            }
        ?>
        <span><?php echo $display_msg; ?></span>
      </div>
    </div>
  </div>
<?php get_footer(); ?>
