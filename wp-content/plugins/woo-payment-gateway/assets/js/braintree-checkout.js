/*Braintree checkout script*/
var BraintreeUtils = function(clientToken) {
	this.constructor();
};

var createBraintreeUtils = function() {
	braintreeUtils = new BraintreeUtils();
	if (!braintreeUtils.hasSavedPaymentMethods()) {
		braintreeUtils.setup();
	} else {
		braintreeUtils.selectDefaultMethod();
	}
}

/* Contructor for BraintreeUtils class. */
BraintreeUtils.prototype.constructor = function() {
	this.clientToken = document.getElementById('client_token') ? document.getElementById('client_token').value : null;
	this.setForm();
	jQuery('form.checkout').attr('id', 'checkout');
	this.setupEvents();
}

BraintreeUtils.prototype.setForm = function() {
	if (jQuery('form.checkout').length > 0) {
		this.form = '#' + jQuery('form.checkout').attr('id');
	} else {
		this.form = '#' + jQuery('#order_review').attr('id');
	}
}

/* Setup events nevessary for braintree checkout. */
BraintreeUtils.prototype.setupEvents = function() {
	jQuery(document.body).on('click', '#add_new_method', this.addNewMethod);
	jQuery(document.body).on('click', '#cancel_add_new', this.cancelAddNew);
	jQuery(document.body).on('click', '.payment-method-item',
			this.paymentMethodSelected);
	jQuery(document.body).on('change', 'input[name="payment_method"]',
			this.removeExtraForms);
	jQuery('form.checkout').on('checkout_place_order', this.checkoutPlaceOrder);
	jQuery(document.body).on('checkout_error', this.checkoutError);
}

/* Setup the Braintree integration. */
BraintreeUtils.prototype.setup = function() {
	if (braintreeUtils.integration) {
		return false;
	}
	if (braintreeUtils.setupCalled) {
		return false;
	}
	braintree.setup(braintreeUtils.clientToken, 'dropin',
			{
				container : 'dropin-container',
				form : jQuery('form.checkout').length > 0 ? 'checkout'
						: 'order_review',
				onReady : function(integration) {
					if (!braintreeUtils.integration) {
						braintreeUtils.integration = integration;
					}
					braintreeUtils.removeExtraForms();
				},
				onPaymentMethodReceived : function(response) {
					braintreeUtils.onPaymentMethodReceived(response);
				}
			});
	braintreeUtils.setupCalled = true;
	braintreeUtils.removeExtraForms();
}

BraintreeUtils.prototype.removeExtraForms = function() {
	jQuery.each(jQuery('#dropin-container').children('iFrame'),
			function(index) {
				if (index > 0) {
					jQuery(this).remove();
				}
			});
}

/* Check if the payment gateway is selected. */
BraintreeUtils.prototype.isGatewaySelected = function() {
	return document.getElementById('payment_method_braintree_payment_gateway').checked;
}

BraintreeUtils.prototype.isPaymentMethodSelected = function() {
	if (jQuery('#selected_payment_method').length > 0
			&& jQuery('#selected_payment_method').val() !== "") {
		return true;
	}
	return false;
}

BraintreeUtils.prototype.selectDefaultMethod = function() {
	jQuery('.payment-method-item').each(function() {
		if(jQuery(this).hasClass('selected')){
			jQuery('#selected_payment_method').val(jQuery(this).attr('payment-token'));
		}
	});
}

/* Validate the Braintree has created the nonce. If no nonce, then return false. */
BraintreeUtils.prototype.checkoutPlaceOrder = function() {
	if (braintreeUtils.isGatewaySelected()) {
		if (braintreeUtils.isPaymentMethodSelected()) {
			return true;
		} else {
			if (braintreeUtils.paymentMethodReceived) {
				return true;
			}
			return false;
		}
	}
}

/* Handle the Braintree response when the payment method is received. */
BraintreeUtils.prototype.onPaymentMethodReceived = function(response) {
	braintreeUtils.paymentMethodReceived = true;
	var element = document.getElementById('payment_method_nonce');
	if (element != null) {
		element.value = response.nonce;
	} else {
		element = document.createElement('input');
		element.type = 'hidden';
		element.name = 'payment_method_nonce';
		element.id = 'payment_method_nonce';
		element.value = response.nonce;
		jQuery(braintreeUtils.form).append(element);
	}
	jQuery(braintreeUtils.form).submit();
}

BraintreeUtils.prototype.checkoutError = function() {
	braintreeUtils.teardown();
	braintreeUtils.setup();
	braintreeUtils.paymentMethodReceived = false;
}

BraintreeUtils.prototype.teardown = function() {
	if (braintreeUtils.integration) {
		braintreeUtils.integration.teardown();
		braintreeUtils.integration = null;
		braintreeUtils.setupCalled = false;
	}
}

BraintreeUtils.prototype.addNewMethod = function() {
	braintreeUtils.removeSelectedPaymentMethod();
	braintreeUtils.hidePaymentMethods();
	braintreeUtils.setup();
	braintreeUtils.showDropinContainer();
}

BraintreeUtils.prototype.cancelAddNew = function() {
	braintreeUtils.teardown();
	braintreeUtils.hideDropinContainer();
	braintreeUtils.selectDefaultMethod();
	braintreeUtils.showPaymentMethods();
}

BraintreeUtils.prototype.showDropinContainer = function() {
	jQuery('.save-payment-method-label').slideDown(400);
	jQuery('#dropin-container').slideDown(400);
}

BraintreeUtils.prototype.hideDropinContainer = function() {
	jQuery('.save-payment-method-label').slideUp(400);
	jQuery('#dropin-container').slideUp(400);
}

BraintreeUtils.prototype.hidePaymentMethods = function() {
	jQuery('#saved_payment_methods').slideUp(400);
}

BraintreeUtils.prototype.showPaymentMethods = function() {
	jQuery('#saved_payment_methods').slideDown(400);
}

BraintreeUtils.prototype.removeSelectedPaymentMethod = function() {
	jQuery('#selected_payment_method').val("");
}

BraintreeUtils.prototype.paymentMethodSelected = function() {
	jQuery('.payment-method-item').each(function() {
		jQuery(this).removeClass('selected');
	});
	jQuery('#selected_payment_method').val(jQuery(this).attr('payment-token'));
	jQuery(this).addClass('selected');
}

BraintreeUtils.prototype.isChangePaymentRequest = function() {
	return jQuery('#order_review').length > 0
			&& !document.getElementById('checkout');
}

BraintreeUtils.prototype.submit = function(e) {
	if (braintreeUtils.isGatewaySelected()) {
		if (braintreeUtils.isPaymentMethodSelected()) {
			return true;
		} else {
			if (jQuery(this).trigger('checkout_place_order') !== false) {
				jQuery(this).off('submit', braintreeUtils.submit);
				jQuery(braintreeUtils.form).submit();
			} else {
				return false;
			}
		}
	}
}

BraintreeUtils.prototype.hasSavedPaymentMethods = function() {
	return jQuery('#saved_payment_methods').length > 0;
}

var braintreeUtils = null;

jQuery(document.body).on('dropin_container_ready', createBraintreeUtils);