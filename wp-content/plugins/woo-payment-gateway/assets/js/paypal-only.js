var PayPalCheckout = function(){
	this.clientToken = document.getElementById('client_token').value;
	this.setForm();
	this.setupEvents();
}

var createPayPalCheckout = function(){
	window.paypalCheckout = new PayPalCheckout();
	paypalCheckout.setup();
}

PayPalCheckout.prototype.setupEvents = function(){
	jQuery('form.checkout').on('checkout_place_order', this.checkoutPlaceOrder);
	jQuery(document.body).on('checkout_error', this.checkoutError);
}

PayPalCheckout.prototype.setForm = function(){
	if(jQuery('form.checkout').length > 0){
		this.form = '#'+jQuery('form.checkout').attr('id');
	}
	else{
		this.form = '#'+jQuery('#order_review').attr('id');
	}
}

PayPalCheckout.prototype.setup = function(){
	if(paypalCheckout.integration){
		return;
	}
	if(paypalCheckout.setupCalled){
		return;
	}
	paypalCheckout.setupCalled = true;
	braintree.setup(paypalCheckout.clientToken, "paypal",{
			container: 'dropin-container',
			onPaymentMethodReceived: function(response){
				paypalCheckout.onPaymentMethodReceived(response);
			},
			onReady: function(integration){
				paypalCheckout.intregration = integration;
			}
	});		
}

PayPalCheckout.prototype.onPaymentMethodReceived = function(response){
	paypalCheckout.paymentMethodReceived = true;
	var element = document.getElementById('payment_method_nonce');
	if(element != null){
		element.value = response.nonce;
	}
	else{
		element = document.createElement('input');
		element.type = 'hidden';
		element.name = 'payment_method_nonce';
		element.id = 'payment_method_nonce';
		element.value = response.nonce;
		jQuery(paypalCheckout.form).append(element);
	}
}

PayPalCheckout.prototype.teardown = function(){
	if(paypalCheckout.integration){
		paypalCheckout.integration.teardown();
		paypalCheckout.integration = null;
	}
}

PayPalCheckout.prototype.checkoutPlaceOrder = function(){
	if(paypalCheckout.isGatewaySelected){
		if(paypalCheckout.paymentMethodReceived){
			return true;
		}
		else{
			return false;
		}
	}

}

PayPalCheckout.prototype.isGatewaySelected = function(){
	return document.getElementById('payment_method_braintree_payment_gateway').checked;
}

PayPalCheckout.prototype.checkoutError = function(){
	paypalCheckout.paymentMethodReceieved = false;
}

jQuery(document.body).on('paypal_container_ready', createPayPalCheckout);