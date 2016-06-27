<?php
if (! defined ( 'ABSPATH' )) {
	exit (); // Exit if accessed directly
}

/**
 * Doantion class which handles donation payments.
 *
 * @author Clayton Rogers
 * @since 3/18/16
 */
class Braintree_Donations {
	public static $_isModal;
	public static $_captureName;
	public static $_captureAddress;
	public static $_captureEmail;
	public static function init() {
		/* Short code for donation container. */
		add_shortcode ( 'braintree_donations', __CLASS__ . '::showDonationContainer' );
		
		/* Load scripts and styles. */
		add_action ( 'braintree_for_woocommerce_load_scripts', __CLASS__ . '::loadScripts' );
		
		/* Add ajax action for processing payment. */
		add_action ( 'wp_ajax_process_braintree_donation', __CLASS__ . '::processDonation' );
		
		/* Add ajax actino for users with no privileges. */
		add_action ( 'wp_ajax_nopriv_process_braintree_donation', __CLASS__ . '::processDonation' );
		
		self::initializeVariables ();
	}
	public static function initializeVariables() {
		self::$_isModal = BT_Manager ()->get_option ( 'donation_form_layout' ) === 'modal' ? true : false;
		self::$_captureAddress = BT_Manager ()->isActive ( 'donation_address' );
		self::$_captureName = BT_Manager ()->isActive ( 'donation_name' );
		self::$_captureEmail = BT_Manager ()->isActive ( 'donation_email' );
	}
	public static function loadScripts() {
		wp_enqueue_script ( 'braintree-dropin-script', BRAINTREE_DROPIN_JS, null, null, false );
		if (self::$_isModal) {
			wp_enqueue_style ( 'braintree-donation-style', WC_BRAINTREE_ASSETS . 'css/braintree-donations-modal.css' );
			wp_enqueue_script ( 'braintree-modal-donations', WC_BRAINTREE_ASSETS . 'js/donations-modal.js', array (
					'jquery' 
			), BT_Manager ()->version, true );
		} else {
			wp_enqueue_style ( 'braintree-donation-style', WC_BRAINTREE_ASSETS . 'css/braintree-donations-inline.css' );
			wp_enqueue_script ( 'braintree-inline-donations', WC_BRAINTREE_ASSETS . 'js/donations-inline.js', array (
					'jquery' 
			), BT_Manager ()->version, true );
		}
	}
	
	/**
	 * Process the donation payments.
	 */
	public static function processDonation() {
		$attrs = array (
				'amount' => BT_Manager ()->getRequestParameter ( 'donation_amount' ),
				'paymentMethodNonce' => BT_Manager ()->getRequestParameter ( 'payment_method_nonce' ),
				'options' => array (
						'submitForSettlement' => true 
				) 
		);
		$option = BT_Manager ()->get_option ( 'donation_merchant_account_id' );
		if (! empty ( $option )) {
			$attrs ['merchantAccountId'] = $option;
		}
		if (self::$_captureName) {
			$name = BT_Manager ()->getRequestParameter ( 'donor_name' );
			$firstAndLast = explode ( ' ', $name );
			$attrs ['billing'] = array (
					'firstName' => $firstAndLast [0],
					'lastName' => $firstAndLast [1] 
			);
			$attrs ['customer'] = array (
					'firstName' => $firstAndLast [0],
					'lastName' => $firstAndLast [1] 
			);
		}
		if (self::$_captureAddress) {
			if (! isset ( $attrs ['billing'] )) {
				$attrs ['billing'] = array ();
			}
			$attrs ['billing'] ['postalCode'] = BT_Manager ()->getRequestParameter ( 'postal_code' );
			$attrs ['billing'] ['streetAddress'] = BT_Manager ()->getRequestParameter ( 'street_address' );
		}
		if (self::$_captureEmail) {
			if (! isset ( $attrs ['customer'] )) {
				$attrs ['customer'] = array ();
			}
			$attrs ['customer'] ['email'] = BT_Manager ()->getRequestParameter ( 'billing_email' );
		}
		$response = BT_Manager ()->sale ( $attrs );
		if ($response->success) {
			BT_Manager ()->log->writeToLog ( sprintf ( 'Donation success: Response: %s', print_r( $response, true ) ) );
			wp_send_json ( array (
					'result' => 'success',
					'url' => self::getOption ( 'donation_success_url' ) 
			) );
		} else {
			BT_Manager ()->log->writeToLog ( sprintf ( 'Donation failure: Response: %s', print_r ( $response, true ) ) );
			wp_send_json ( array (
					'result' => 'failure',
					'message' => $response->message 
			) );
		}
	}
	
	/**
	 * Method that renders the donation container.
	 */
	public static function showDonationContainer($atts) {
		do_action ( 'braintree_for_woocommerce_load_scripts' );
		ob_start ();
		self::generateClientToken ();
		self::getAjaxUrl ();
		if (self::$_isModal) {
			self::showModalDonationContainer ( $atts );
		} else {
			self::showInlineDonationContainer ( $atts );
		}
		$html = ob_get_clean ();
		return $html;
	}
	
	/**
	 * Method that generates the html for the modal donation container.
	 */
	public static function showModalDonationContainer($atts) {
		self::showModalButton ();
		self::buildModalContainer ( $atts );
	}
	
	/**
	 * Method that generates the inline html
	 */
	public static function showInlineDonationContainer($atts) {
		?>
<div class="overlay-payment-processing">
		  <?php self::getLoader()?>
		</div>
<div class="inline-donation-container">
	<div class="errorMessages" id="error_messages"></div>
	<div class="inline-donation-form">
		<form name="donation-form" id="donation-form">
		      <?php
		
		if (self::$_captureName) {
			echo self::buildInputField ( array (
					'type' => 'text',
					'placeholder' => __ ( 'Full Name', 'braintree' ),
					'class' => 'inline-input',
					'value' => '',
					'name' => 'donor_name',
					'id' => 'donor_name' 
			) );
		}
		if (self::$_captureAddress) {
			echo self::buildInputField ( array (
					'type' => 'text',
					'placeholder' => __ ( 'Street Address', 'braintree' ),
					'class' => 'inline-input',
					'name' => 'street_address',
					'id' => 'street_address' 
			) );
			echo self::buildInputField ( array (
					'type' => 'text',
					'placeholder' => __ ( 'Postal Code', 'braintree' ),
					'class' => 'inline-input',
					'name' => 'postal_code',
					'id' => 'postal_code' 
			) );
			echo self::buildSelectField ( array (
					'type' => 'select',
					'placeholder' => __ ( 'Country', 'braintree' ),
					'class' => 'inline-input',
					'options' => self::getCountryOptions ( self::getOption ( 'donation_default_country' ) ),
					'name' => 'billing_country',
					'id' => 'billing_country' 
			) );
		}
		if (self::$_captureEmail) {
			echo self::buildInputField ( array (
					'type' => 'text',
					'placeholder' => __ ( 'Email Address', 'braintree' ),
					'class' => 'inline-input',
					'name' => 'billing_email',
					'id' => 'billing_email' 
			) );
		}
		echo self::buildAmountField ( $atts );
		BT_Manager ()->getDonationDropinContainer ();
		?>
			  <div class="inline-submit-donation">
				<input type="submit" id="donation_submit" style="<?php echo self::getDonationButtonStyles()?>" value="<?php echo self::getOption('donation_button_text')?>"/>
			</div>
		</form>
	</div>
</div>
<?php
	}
	
	/**
	 * Method that generates the html button for the modal container.
	 */
	public static function showModalButton() {
		?>
<div class="div--modal-button">
	<button id="modal_button" style="<?php echo self::getModalButtonStyles()?>"><?php echo self::getOption('donation_modal_button_text');?></button>
</div>
<?php
	}
	
	/**
	 * Method that builds the modal container.
	 */
	public static function buildModalContainer($atts) {
		?>
<div class="overlay-payment-processing">
		  <?php self::getLoader()?>
		</div>
<div id="donation_overlay" class="modal-overlay">
	<div class="modal-container">
		<div id="donation_container">
			<div class="errorMessages" id="error_messages"></div>
			<div class="modal-button">
				<span id="cancel_donation"><?php echo __('Cancel', 'braintree')?></span>
			</div>
			<form name="donation-form" id="donation-form">
				  <?php
		
		if (self::$_captureName) {
			echo self::buildInputField ( array (
					'type' => 'text',
					'placeholder' => __ ( 'Full Name', 'braintree' ),
					'class' => 'modal-input',
					'value' => '',
					'name' => 'donor_name',
					'id' => 'donor_name' 
			) );
		}
		if (self::$_captureAddress) {
			echo self::buildInputField ( array (
					'type' => 'text',
					'placeholder' => __ ( 'Street Address', 'braintree' ),
					'class' => 'modal-input',
					'name' => 'street_address',
					'id' => 'street_address' 
			) );
			echo self::buildInputField ( array (
					'type' => 'text',
					'placeholder' => __ ( 'Postal Code', 'braintree' ),
					'class' => 'modal-input',
					'name' => 'postal_code',
					'id' => 'postal_code' 
			) );
			echo self::buildSelectField ( array (
					'type' => 'select',
					'placeholder' => __ ( 'Country', 'braintree' ),
					'class' => 'modal-input',
					'options' => self::getCountryOptions ( self::getOption ( 'donation_default_country' ) ),
					'name' => 'billing_country',
					'id' => 'billing_country' 
			) );
		}
		if (self::$_captureEmail) {
			echo self::buildInputField ( array (
					'type' => 'text',
					'placeholder' => __ ( 'Email Address', 'braintree' ),
					'class' => 'modal-input',
					'name' => 'billing_email',
					'id' => 'billing_email' 
			) );
		}
		echo self::buildAmountField ( $atts );
		BT_Manager ()->getDonationDropinContainer ();
		?>
					<div class="modal-submit-donation">
					<input type="submit" id="donation_submit" style="<?php echo self::getDonationButtonStyles()?>" value="<?php echo self::getOption('donation_button_text')?>"/>
				</div>
			</form>
		</div>
	</div>
</div>
<?php
	}
	public static function getModalButtonStyles() {
		$backgroundColor = self::getOption ( 'donation_modal_button_background' );
		$borderColor = self::getOption ( 'donation_modal_button_background' );
		$textColor = self::getOption ( 'donation_modal_button_text_color' );
		$style = 'background-color:' . $backgroundColor . '; border-color:' . $borderColor . '; color:' . $textColor;
		return $style;
	}
	public static function getDonationButtonStyles() {
		$backgroundColor = self::getOption ( 'donation_button_background' );
		$borderColor = self::getOption ( 'donation_button_border' );
		$textColor = self::getOption ( 'donation_button_text_color' );
		$style = 'background-color:' . $backgroundColor . '; border-color:' . $borderColor . '; color:' . $textColor;
		return $style;
	}
	public static function getOption($string) {
		return BT_Manager ()->get_option ( $string );
	}
	public static function buildInputField(array $attrs) {
		$html = '<div class="' . $attrs ['class'] . '"><div class="invalid-input-field"></div>
				<input type="' . $attrs ['type'] . '" name="' . $attrs ['name'] . '" id="' . $attrs ['id'] . '" placeholder="' . $attrs ['placeholder'] . '"/>
				</div>';
		return $html;
	}
	public static function buildSelectField(array $attrs) {
		$html = '<div class="' . $attrs ['class'] . '">
				<label for="' . $attrs ['id'] . '">
				<select type="' . $attrs ['type'] . '" name="' . $attrs ['name'] . '" id="' . $attrs ['id'] . '" placeholder="' . $attrs ['placeholder'] . '">
				' . $attrs ['options'] . '
				</select>
				</label>
				</div>';
		return $html;
	}
	public static function buildAmountField($atts) {
		$html = null;
		if (empty ( $atts )) {
			$html = self::buildInputField ( array (
					'type' => 'text',
					'placeholder' => self::getCurrency ( self::getOption ( 'donation_currency' ) ) . ' ' . __ ( 'Amount', 'braintree' ),
					'class' => 'div--donationInput',
					'name' => 'donation_amount',
					'id' => 'donation_amount' 
			) );
		} else if (count ( $atts ) == 1) {
			$html = '<div class="div--donationInput"><div class="invalid-input-field"></div>
				<span>' . self::getCurrency ( self::getOption ( 'donation_currency' ) ) . '</span><span>' . $atts [1] . '</span><input type="hidden" id="donation_amount" name="donation_amount" value="' . $atts [1] . '"/>
				</div>';
		} else {
			$html = '<div class="div--donationSelect"><span>' . __ ( 'Amount', 'braintree' ) . '</span><select id="donation_amount" name="donation_amount">';
			foreach ( $atts as $index => $attr ) {
				$html .= '<option value="' . $attr . '">' . self::getCurrency ( self::getOption ( 'donation_currency' ) ) . ' ' . $attr . '</option>';
			}
			$html .= '</select></div>';
		}
		return $html;
	}
	public static function generateClientToken() {
		?>
<input type="hidden" name="client_token" id="client_token"
	value="<?php echo BT_Manager()->getClientToken(wp_get_current_user()->ID)?>" />
<?php
	}
	public static function getAjaxUrl() {
		?>
<input type="hidden" name="ajax_url" id="ajax_url"
	value="<?php echo admin_url().'admin-ajax.php?action=process_braintree_donation'?>" />
<?php
	}
	public static function getCountryOptions($defaultCountry) {
		$html = null;
		foreach ( Braintree_Countries::$countries as $prefix => $country ) {
			$selected = $prefix === $defaultCountry ? 'selected' : '';
			$html .= '<option value="' . $prefix . '" ' . $selected . '>' . $country . '</option>';
		}
		return $html;
	}
	public static function getCurrency($country) {
		return Braintree_Currencies::getCurrencySymbol ( $country );
	}
	public static function getLoader() {
		?>
<div class="loader"></div>
<div class="indicator">
	<svg id="loader-svg-icon" width="14px" height="16px"
		viewBox="0 0 28 32" version="1.1" xmlns="http://www.w3.org/2000/svg"
		xmlns:xlink="http://www.w3.org/1999/xlink"
		xmlns:sketch="http://www.bohemiancoding.com/sketch/ns">
		          <g id="New-Customer" stroke="none" stroke-width="1"
			fill="none" fill-rule="evenodd" sketch:type="MSPage">
		            <g id="Loading" sketch:type="MSArtboardGroup"
			transform="translate(-526.000000, -915.000000)" fill="#FFFFFF">
		              <g id="Loading-Indicator" sketch:type="MSLayerGroup"
			transform="translate(468.000000, 862.000000)">
		                <g id="Secure"
			transform="translate(58.000000, 53.000000)"
			sketch:type="MSShapeGroup">
		                <path
			d="M6,10 L6,7.9998866 C6,3.57774184 9.581722,0 14,0 C18.4092877,0 22,3.58167123 22,7.9998866 L22,10 L18,10 L18,7.9947834 C18,5.78852545 16.2046438,4 14,4 C11.790861,4 10,5.79171562 10,7.9947834 L10,10 L6,10 Z M0.996534824,14 C0.446163838,14 0,14.4449463 0,14.9933977 L0,31.0066023 C0,31.5552407 0.439813137,32 0.996534824,32 L27.0034652,32 C27.5538362,32 28,31.5550537 28,31.0066023 L28,14.9933977 C28,14.4447593 27.5601869,14 27.0034652,14 L0.996534824,14 Z"
			id="Rectangle-520"></path>
		                </g>
		              </g>
		            </g>
		          </g>
		        </svg>
</div>
<?php
	}
}
Braintree_Donations::init ();