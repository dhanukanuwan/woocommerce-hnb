<?php
// Check if the order id is set
if ( isset( $_GET['id'] ) ) {
  $order_id = intval( $_GET['id'] );

  if ( $order_id ) {

    get_header();

    global $wpdb;
    $table_name = $wpdb->prefix . 'hnb_gateway_orders';
    $order_details = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE order_id = %d", $order_id ), ARRAY_A );

    if ( ! empty( $order_details ) ) {

      $order_val = (int)$order_details[0]['order_total'];
			$order_val = $order_val*100;
			$max_amnt_chrs = '12';
			$chars_diff = $max_amnt_chrs - strlen( $order_val );
			$zeros = '';

			for ( $i=0; $i < $chars_diff; $i++ ) {
				$zeros .= "0";
			}
			$purchaseAmt = $zeros . $order_val;

      $version = "1.0.0";
			$merchantId = "06162900";
			$accuireId = "415738";
			//$purchaseAmt = "50";
			$purCurrency = "840";
			$purCurrencyEx = "2";
			$orderId = "shop" . $order_id;
			$hashString = "aMUt94P9" . $merchantId .  $accuireId . $orderId . $purchaseAmt . $purCurrency;

		  $enc = base64_encode(pack('H*', sha1($hashString))); ?>

      <form id="js-submit-payment" method="post" action="https://www.hnbpg.hnb.lk/SENTRY/PaymentGateway/Application/ReDirectLink.aspx" >
        <input id='Version' type='hidden' name='Version' value="<?php echo $version; ?>">
        <input id='MerID' type='hidden' value="<?php echo $merchantId ?>"  name='MerID' >
        <input id='AcqID' type='hidden' value="<?php echo $accuireId; ?>" name='AcqID' >
        <input id='MerRespURL' type='hidden' value="<?php bloginfo('url'); ?>/shop-response"  name='MerRespURL'>
        <input id='PurchaseCurrency' type='hidden' value='840'  name='PurchaseCurrency'>
        <input id='PurchaseCurrencyExponent' type='hidden' value='2'  name='PurchaseCurrencyExponent'>
        <input id='OrderID' type='hidden' value="<?php echo $orderId; ?>" name='OrderID' >
        <input id='SignatureMethod' type='hidden' value='SHA1'  name='SignatureMethod'>
        <input id='Signature' type='hidden' value="<?php echo $enc; ?>"  name='Signature'>
        <input id='CaptureFlag' type='hidden' value='A' name='CaptureFlag' >
        <input id='PurchaseAmt' type='hidden' value='<?php echo $purchaseAmt; ?>'  name='PurchaseAmt' >
  	</form>

  	<script type="text/javascript">
  		jQuery(document).ready(function($) {
  			jQuery('#js-submit-payment').submit();
  		});
  	</script>
  <?php  }

  } else {
    wp_die();
  }

} else {
  wp_die();
}

 ?>

<?php get_footer(); ?>
