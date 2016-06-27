/*Admin Scripts for Braintree Payment Plugin*/
jQuery(document).ready(function(){

var BraintreeAdmin = function(){
	this.constructor();
};

BraintreeAdmin.prototype.constructor = function(){
	jQuery(document.body).on('change', '#sandbox_environment', this.switchEnvironment);
	jQuery(document.body).on('change', '#production_environment', this.switchEnvironment);
	jQuery(document.body).on('change', '#braintree_subscriptions', this.switchSubscriptions);
	jQuery(document.body).on('change', '#woocommerce_subscriptions', this.switchSubscriptions);
	jQuery(document.body).on('change', '#donation_form_layout', this.displayModalOptions);
	jQuery(document.body).on('click', '.donationColor', this.displayColorPicker);
	jQuery(document.body).on('change', '#donation_address', this.maybeShowDonationAddressItems);
	jQuery(document.body).on('click', '#add_merchant_account', this.addMerchantAccount);
	jQuery('.dashicons.dashicons-trash').on('click', this.deleteItem);
	jQuery(document.body).on('click', '.add_braintree_plan', this.addBraintreePlan);
	jQuery(document.body).on('click', '.remove_braintree_plan', this.removeBraintreePlan);
	jQuery(document.body).on('click', '.dynamic_descriptor_help', this.displayDynamicDescriptorHelp);
	jQuery(document.body).on('click', '.dynamic_descriptor_tutorial span.close', this.hideDynamicDescriptorHelp);
	jQuery(document.body).on('change', '#dynamic_descriptors', this.displayDynamicDescriptor);
}

BraintreeAdmin.prototype.switchEnvironment = function(){
	var id = jQuery(this).attr('id');
	var sandbox = "sandbox_environment";
	var production = "production_environment";
	var isChecked = jQuery(this).checked;
	if(id === sandbox){
		if(isChecked){
			document.getElementById(production).checked = true;
			jQuery(this).checked = false;
		}
		else{
			document.getElementById(production).checked = false;
			jQuery(this).checked = true;
		}
	}
	else{
		if(isChecked){
			document.getElementById(sandbox).checked = true;
			jQuery(this).checked = false;
		}
		else{
			document.getElementById(sandbox).checked = false;
			jQuery(this).checked = true;
		}
	}
	
};

BraintreeAdmin.prototype.switchSubscriptions = function(){
	if(jQuery(this).attr('id') === 'braintree_subscriptions'){
		if(jQuery(this).checked){
			jQuery(this).checked = false;
			jQuery('#woocommerce_subscriptions').attr('checked', true);
		}
		else{
			jQuery(this).checked = true;
			jQuery('#woocommerce_subscriptions').attr('checked', false);
		}
	}
	else{
		if(jQuery(this).checked){
			jQuery(this).checked = false;
			jQuery('#braintree_subscriptions').attr('checked', true);
		}
		else{
			jQuery(this).checked = true;
			jQuery('#braintree_subscriptions').attr('checked', false);
		}
	}
}

BraintreeAdmin.prototype.displayModalOptions = function(){
	if(jQuery(this).val() === 'modal'){
		jQuery('.modalOption').each(function(){
			jQuery(this).closest('tr').slideDown(200);
		})
	}
	else{
		jQuery('.modalOption').each(function(){
			jQuery(this).closest('tr').slideUp(200);
		})
	}
}

BraintreeAdmin.prototype.initializeModalOptions = function(){
	if(jQuery('#donation_form_layout').val() === 'modal'){
		jQuery('.modalOption').each(function(){
			jQuery(this).closest('tr').slideDown(200);
		})
	}
	else{
		jQuery('.modalOption').each(function(){
			jQuery(this).closest('tr').slideUp(200);
		})
	}
}

BraintreeAdmin.prototype.updateSubItems = function(){
	jQuery('.subItem').each(function(index){
		jQuery(this).closest('tr').addClass('tr--subItem');
	});
}

BraintreeAdmin.prototype.displayColorPicker = function(){
	jQuery('.donationColor').colorPicker();

}

BraintreeAdmin.prototype.maybeShowDonationAddressItems = function(){
	if(jQuery('#donation_address').is(':checked')){
		jQuery('.addressOption').each(function(){
			jQuery(this).closest('tr').show();
		});
	}
	else{
		jQuery('.addressOption').each(function(){
			jQuery(this).closest('tr').hide();
		});
	}
}

BraintreeAdmin.prototype.initializeColorPickers = function(){
	jQuery('.donationColor').each(function(){
		braintreeAdmin.displayColorPicker();
	})
}

BraintreeAdmin.prototype.initializeAddressOptions = function(){
	if(jQuery('#donation_address').val() !== 'yes'){
		jQuery('.addressOption').each(function(){
			jQuery(this).closest('tr').hide();
		})
	}
}

/*Add an input field for the merchant account.*/
BraintreeAdmin.prototype.addMerchantAccount = function(e){
	e.preventDefault();
	var currency = jQuery('#merchant_account_currency').val();
	var inputName = merchantParams.merchant_account_input;
	if(document.getElementById(inputName + '[' + currency + ']')){
		return;
	}
	var id = inputName + '['+currency+']';
	var name = inputName + '['+currency+']';
	var title = '<th><span>' + merchantParams.merchant_text.replace('%s', currency) + '</span></th>';
    var div = '<td><div><input type="text" value="" id="'+id+'" name="'+name+'"/>' + 
    '<span class="dashicons dashicons-trash"></span></div></td>';
	var html = title + div;
    jQuery('#merchant_accounts').append('<tr>' + html + '</tr>');
    braintreeAdmin.constructor();
}

BraintreeAdmin.prototype.deleteItem = function(){
	var settingName = jQuery(this).prev('input').attr('name');
	braintreeAdmin.ajaxDeleteItem(settingName);
	jQuery(this).closest('tr').remove();
	
}

BraintreeAdmin.prototype.ajaxDeleteItem = function(name){
	var url = ajaxurl;
	jQuery.ajax({
		url: url,
		type: 'POST',
		dataType: 'json',
		data: {action: 'braintree_for_woocommerce_delete_item', setting: name}
	}).done(function(response){
		if(response.result === 'success'){
			return true;
		}
		else{
			return false;
		}
	}).fail(function(response){
		return false;
	});
		
}

/*Add subscription plan to item.*/
BraintreeAdmin.prototype.addBraintreePlan = function(e){
	e.preventDefault();
	jQuery('.addPlanOverlay').fadeIn();
	var element = document.getElementById('braintree_subscriptions['+jQuery(this).attr('post_id')+']');
	var option = element.options[element.selectedIndex];
	var currency = jQuery(option).attr('currency');
	var braintree_plan = jQuery(option).val();
	
	jQuery.ajax({
		url: ajaxurl,
		type: 'POST',
		dataType: 'json',
		data: {action: 'add_braintree_plan', currency_code: currency, post_id: jQuery(this).attr('post_id'), braintree_plan: braintree_plan}
	}).done(function(response){
		if(response.result === 'success'){
			jQuery('.saved_braintree_plans').html(response.html);
			BraintreeAdmin.constructor();
			jQuery('.addPlanOverlay').fadeOut();
		}
		else{
			jQuery('.addPlanOverlay').fadeOut();
			window.alert(response.message);
		}
	}).fail(function(response){
		jQuery('.addPlanOverlay').fadeOut();
		window.alert(response.message);
	});
}

BraintreeAdmin.prototype.removeBraintreePlan = function(e){
	e.preventDefault();
	jQuery('.addPlanOverlay').fadeIn();
	var currency = jQuery(this).attr('currency');
	jQuery.ajax({
		url: ajaxurl,
		type: 'POST',
		dataType: 'json',
		data: {action: 'remove_braintree_plan', currency_code: currency, post_id: jQuery(this).attr('post_id')}
	}).done(function(response){
		if(response.result === 'success'){
			jQuery('.saved_braintree_plans').html(response.html);
			jQuery('.addPlanOverlay').fadeOut();
			BraintreeAdmin.constructor();
		}
		else{
			jQuery('.addPlanOverlay').fadeOut();
			window.alert(response.message);
		}
	}).fail(function(response){
		jQuery('.addPlanOverlay').fadeOut();
		window.alert(response.message);
	});
}

BraintreeAdmin.prototype.displayDynamicDescriptorHelp = function(){
	var tutorial = jQuery(this).attr('tutorial');
	jQuery('#dynamic_descriptor_tutorial'+tutorial).fadeIn();
}

BraintreeAdmin.prototype.hideDynamicDescriptorHelp = function(){
	jQuery('.dynamic_descriptor_tutorial').fadeOut();
}

BraintreeAdmin.prototype.displayDynamicDescriptor = function(){
	if( jQuery(this).is(':checked')){
		jQuery('.descriptorSubItem').each(function(){
			jQuery(this).closest('tr').slideDown(200);
		})
	}
	else{
		jQuery('.descriptorSubItem').each(function(){
			jQuery(this).closest('tr').slideUp(200);
		})
	}
}

BraintreeAdmin.prototype.initializeDescriptorItems = function(){
	if(jQuery('#dynamic_descriptors').is(':checked')){
		jQuery('.descriptorSubItem').each(function(){
			jQuery(this).closest('tr').slideDown(200);
		});
	}
	else{
		jQuery('.descriptorSubItem').each(function(){
			jQuery(this).closest('tr').slideUp(200);
		});
	}
}

var braintreeAdmin = new BraintreeAdmin();

braintreeAdmin.initializeColorPickers();
braintreeAdmin.updateSubItems();
braintreeAdmin.initializeModalOptions();
braintreeAdmin.initializeAddressOptions();
braintreeAdmin.initializeDescriptorItems();
braintreeAdmin.maybeShowDonationAddressItems();

/*jQuery(function(){
	jQuery('.donationColor').colorPicker();
});*/
})