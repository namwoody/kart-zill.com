<?php
$user_id = wp_get_current_user ()->ID;
$clientToken = BT_Manager ()->getClientToken ();
if (BT_Manager ()->paypalOnly) {
	BT_Manager ()->getPayPalContainer ();
	?>
	<input type="hidden" id="client_token"
	value="<?php echo $clientToken ?>" />
	<script>
  	jQuery(document).ready(function(){jQuery(document.body).trigger('paypal_container_ready')});
	</script>
<?php
} else {
	BT_Manager ()->getDropinContainer ();
	echo WC_Braintree_Payments::getPaymentMethodForm ( $user_id );
	if (empty ( $clientToken )) {
	?>
	<div class="braintree-error-clientToken"><?php echo __('There was an error generating your Client Token. Please check your API Key configurations.', 'braintree')?></div>
	<?php
	} else {
		?>
	<input type="hidden" id="client_token" value="<?php echo $clientToken ?>" />
<?php 
}
?>
	<script>
			jQuery(document).ready(function(){
				jQuery(document.body).trigger('dropin_container_ready');
 	 		});
	</script>
<?php
}