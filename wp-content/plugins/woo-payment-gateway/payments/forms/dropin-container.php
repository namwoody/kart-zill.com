<?php
if (BT_Manager ()->getEnvironment () === 'sandbox') {
?>
	<div class="gateway-environment"><?php echo __('Sandbox Mode', 'braintree')?></div>
<?php
}

$paymentMethods = BT_Manager ()->get_option ( 'payment_methods' );
if (! empty ( $paymentMethods )) {
	?>
	<div class="accepted-payment-methods">
	<?php
	foreach ( $paymentMethods as $key => $method ) {
		if (! empty ( BT_Manager ()->settings ['payment_methods'] [$key] )) {
			?>
		<div class="payment-method">
		  <img src="<?php echo Braintree_PaymentMethods::paymentMethods()[$key]['src']?>" />
		</div>
		<?php
		}
	}
	?>
	</div>
<?php
}
?>
<div id="dropin-container"
	<?php echo BT_Manager()->customerHasPaymentMethods() ? 'style="display: none"' : ''?>>
<?php if(BT_Manager()->customerHasPaymentMethods()){?>
	<div class="payment-method-button">
		<span id="cancel_add_new"><?php echo __('Cancel', 'braintree')?></span>
	</div>
	<?php
}
?>
</div>
<?php
if (wp_get_current_user ()->ID) {
	?>
<label
	<?php echo BT_Manager()->customerHasPaymentMethods() ? 'style="display: none"' : ''?>
	class="save-payment-method-label"><span class="save-cc-helper"><?php echo __('Save', 'braintree')?></span>
	<div class="save-payment-method">
		<input type="checkbox" id="save_payment_method"
			name="save_payment_method"><label for="save_payment_method"></label>
	</div></label>
<?php
}
?>