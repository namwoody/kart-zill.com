<?php
if (! defined ( 'ABSPATH' )) {
	exit (); // Exit if accessed directly
}
/**
 *
 * @author Clayton Rogers
 * @copyright Payment Plugins
 * @since 3/14/2016
 */
class WC_Braintree_Payments extends WC_Payment_Gateway {
	const gatewayName = 'braintree_payment_gateway';
	public $supports = array (
			'subscriptions',
			'products',
			'subscription_cancellation',
			'multiple_subscriptions',
			'subscription_amount_changes',
			'subscription_date_changes',
			'default_credit_card_form',
			'refunds',
			'pre-orders',
			'subscription_payment_method_change_admin',
			'gateway_scheduled_payments',
			'subscription_payment_method_change_customer' 
	);
	
	/**
	 * Initialize any data needed for payment processing with Braintree
	 */
	public function __construct() {
		$this->enabled = BT_Manager ()->isEnabled ();
		$this->id = self::gatewayName;
		$this->title = $this->get_option ( 'title_text' );
		$this->has_fields = true;
		add_action ( 'wp_enqueue_scripts', __CLASS__ . '::loadScripts' );
	}
	public static function init() {
		add_action ( 'woocommerce_before_checkout_form', __CLASS__ . '::maybeCreateBraintreeCustomer', 20 );
		add_action ( 'woocommerce_before_checkout_form', __CLASS__ . '::loadCustomerPaymentMethods', 40 );
		add_action ( 'woocommerce_created_customer', __CLASS__ . '::createBraintreeUserFromRegistration', 99, 2 );
		add_filter ( 'woocommerce_checkout_customer_userdata', __CLASS__ . '::updateBraintreeCustomer', 20, 2 );
	}
	public static function loadScripts() {
		if (BT_Manager ()->isActive ( 'enabled' )) {
			wp_enqueue_style ( 'braintree-payments-style', WC_BRAINTREE_ASSETS . 'css/braintree-payments.css' );
			wp_enqueue_script ( 'braintree-dropin', BRAINTREE_DROPIN_JS, null, true );
			if (BT_Manager ()->paypalOnly) {
				wp_enqueue_script ( 'braintree-checkout-script', WC_BRAINTREE_ASSETS . 'js/paypal-only.js', array (
						'wc-checkout' 
				), null, true );
			} else {
				wp_enqueue_script ( 'braintree-checkout-script', WC_BRAINTREE_ASSETS . 'js/braintree-checkout.js', array (
						'jquery' 
				), null, true );
			}
		}
	}
	public static function addGateway($methods = array()) {
		$methods [] = 'WC_Braintree_Payments';
		return $methods;
	}
	
	/**
	 * Process the payment for the order.
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Gateway::process_payment()
	 */
	public function process_payment($order_id) {
		$order = wc_get_order ( $order_id );
		if (self::isPaymentChangeRequest ()) {
			return array (
					'result' => 'success',
					'redirect' => $order->get_checkout_order_received_url () 
			);
		}
		if (self::subscriptionsActive () && $this->isSubscription ( $order_id )) {
			return $this->processSubscription ( $order_id );
		} elseif (BT_Manager ()->isActive ( 'braintree_subscriptions' ) && WC_Braintree_Subscriptions::orderContainsBraintreeSubscription ( $order_id )) {
			return WC_Braintree_Subscriptions::createBraintreeOnlySubscriptions ( wc_get_order ( $order_id ) );
		} else {
			$attribs = self::createOrderAttributes ( $order_id );
			
			/* Create the payment method in the vault if customer has selected to save the payment method. */
			if (self::getRequestParameter ( 'save_payment_method' )) {
				// $attribs['options']['storeInVaultOnSuccess'] = true;
				$response = BT_Manager ()->createBraintreePaymentMethod ( array (
						'paymentMethodNonce' => BT_Manager ()->getRequestParameter ( 'payment_method_nonce' ),
						'customerId' => BT_Manager ()->getBraintreeCustomerId ( wp_get_current_user ()->ID ),
						'billingAddress' => array (
								'countryCodeAlpha2' => $order->billing_country,
								'firstName' => $order->billing_first_name,
								'lastName' => $order->billing_last_name,
								'postalCode' => $order->billing_postcode,
								'streetAddress' => $order->billing_address_1 
						),
						'options' => array (
								'failOnDuplicatePaymentMethod' => BT_Manager ()->isActive ( 'fail_on_duplicate' ) ? true : false,
								'makeDefault' => true 
						) 
				) );
				if ($response->success) {
					$attribs ['paymentMethodToken'] = $response->paymentMethod->token;
				} else {
					return self::wooCoomerceError ( $response->message );
				}
			}
			
			$result = BT_Manager ()->woocommerceSale ( $attribs, wc_get_order ( $order_id ) );
			if ($result ['result'] === 'success') {
				WC ()->cart->empty_cart ();
			}
			return $result;
		}
	}
	
	/**
	 * Retrieve the payment fields to be displayed on the checkout page.
	 * If the request is for a payment method change,
	 * render the payment methods form.
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Gateway::payment_fields()
	 */
	public function payment_fields() {
		include WC_BRAINTREE_PLUGIN . 'payments/forms/payment-form.php';
	}
	
	/**
	 * Process the refund for the given $order_id.
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Gateway::process_refund()
	 */
	public function process_refund($order_id, $amount = null, $reason = '') {
		if (! $this->canRefundOrder ( $order_id )) {
			BT_Manager ()->log->writeToLog ( sprintf ( 'Order %s cannot be refunded because there is 
					not a transaction associated with the order. If this order is associated with a subscription
					that has not processed a payment yet, then there will be no transaction available until the subscription payment is processed. .', $order_id ) );
			return new WP_Error ( 404, __ ( 'The order cannot be refunded because there is no transaction ID associated with the order. Check the debug log for more details.', 'braintree' ) );
		}
		try {
			return BT_Manager ()->refund ( $order_id, $amount, $reason );
		} catch ( Exception $e ) {
			return new WP_Error ( 404, $e->getMessage () );
		}
	}
	
	/**
	 * Create the Braintree Customer if none exists for the user.
	 */
	public static function maybeCreateBraintreeCustomer() {
		global $current_user;
		get_currentuserinfo ();
		$user_id = wp_get_current_user ()->ID;
		if ($user_id) {
			$customerId = BT_Manager ()->getBraintreeCustomerId ( $user_id );
			if (empty ( $customerId )) {
				$attribs = array ();
				$attribs ['firstName'] = get_user_meta ( $user_id, 'first_name', true );
				$attribs ['lastName'] = get_user_meta ( $user_id, 'last_name', true );
				$attribs ['email'] = $current_user->user_email;
				$attribs ['phone'] = get_user_meta ( $user_id, 'billing_phone', true );
				BT_Manager ()->createBraintreeCustomer ( $user_id, $attribs );
			}
		}
	}
	
	/**
	 *Create the Braintree user when the registration is performed for WooCommerce. If the user already has a Braintree
	 * customer_id then don't perform the create.
	 * @param int $customer_id        	
	 * @param array $new_customer_data        	
	 */
	public static function createBraintreeUserFromRegistration($user_id, $new_customer_data) {
		$customer_id = BT_Manager ()->getBraintreeCustomerId ( $user_id );
		if(!$customer_id){
			$attribs = array ();
			$attribs ['email'] = $new_customer_data ['user_email'];
			BT_Manager ()->createBraintreeCustomer ( $user_id, $attribs );
		}
		return true;
	}
	
	/**
	 *
	 * @param array $userdata        	
	 * @param WC_Checkout $wc_checkout        	
	 */
	public static function updateBraintreeCustomer($userdata, WC_Checkout $wc_checkout) {
		$attribs = array (
				'firstName' => $userdata ['first_name'],
				'lastName' => $userdata ['last_name'] 
		);
		if (! empty ( $wc_checkout->posted ['billing_email'] )) {
			$attribs ['email'] = $wc_checkout->posted ['billing_email'];
		}
		BT_Manager ()->updateBraintreeCustomer ( $userdata ['ID'], $attribs );
		return $userdata;
	}
	public function get_option($option, $default = null) {
		return BT_Manager ()->get_option ( $option );
	}
	
	/**
	 * Retrieve the parameter from the $_POST, $_GET, or $_REQUEST
	 *
	 * @param string $string        	
	 */
	public static function getRequestParameter($string) {
		$parameter;
		if (isset ( $_POST [$string] )) {
			$parameter = $_POST [$string];
		} elseif (isset ( $_GET [$string] )) {
			$parameter = $_GET [$string];
		} elseif (isset ( $_REQUEST [$string] )) {
			$parameter = $_REQUEST [$string];
		} else {
			$parameter = null;
		}
		return $parameter;
	}
	
	/**
	 * Create an array of attributes for the WooComerce Order.
	 *
	 * @param WC_Order|int $order_id        	
	 */
	public static function createOrderAttributes($order) {
		$attribs = array ();
		if (! is_object ( $order )) {
			$order = wc_get_order ( $order );
		}
		$attribs ['amount'] = $order->get_total ();
		$attribs ['billing'] = self::getBillingAddress ( $order );
		
		if (get_option ( 'woocommerce_calc_shipping' ) === 'yes') {
			$attribs ['shipping'] = self::getShippingAddress ( $order );
		}
		$attribs ['options'] = array (
				'submitForSettlement' => true 
		);
		$attribs ['orderId'] = self::getOrderId ( $order );
		/* add Merchant Account if configured. */
		$attribs = self::getMerchantAccountId ( $attribs );
		/* Add customer object to order */
		$attribs = self::getCustomerObject ( $attribs, $order );
		
		// Add the descriptor if enabled and the value is not empty.
		$attribs = self::getDynamicDescriptor ( $attribs );
		
		return $attribs;
	}
	
	/**
	 * Process the WC_Subscription.
	 * The subscription will be handled differently, based on whether or not
	 * braintree_subscriptions or woocommerce_subscriptions has been enabled by the admin.
	 *
	 * @param int $order_id        	
	 */
	public function processSubscription($order_id) {
		$result = null;
		$order = wc_get_order ( $order_id );
		if (BT_Manager ()->isActive ( 'braintree_subscriptions' )) {
			$result = WC_Braintree_Subscriptions::createBraintreeOnlySubscriptions ( $order );
		} else {
			$result = BT_Manager ()->processWooCommerceSubscriptions ( $order );
		}
		return $result;
	}
	public static function getMerchantAccountId($attribs = array()) {
		return BT_Manager ()->getMerchantAccountId ( $attribs );
	}
	public static function getCustomerObject($attribs = array(), $order) {
		return BT_Manager ()->getBraintreeCustomerObject ( $attribs, $order );
	}
	public static function getPaymentMethodFromRequest($attribs = array()) {
		return BT_Manager ()->getPaymentMethodFromRequest ( $attribs );
	}
	public static function wooCoomerceError($message) {
		wc_add_notice ( $message, 'error' );
		return array (
				'result' => 'failure',
				'redirect' => '' 
		);
	}
	public static function isPaymentChangeRequest() {
		return (isset ( $_REQUEST ['woocommerce_change_payment'] ) || isset ( $_REQUEST ['change_payment_method'] ));
	}
	public static function isPayForOrder() {
		return (isset ( $_REQUEST ['pay_for_order'] ) && $_REQUEST ['pay_for_order'] === 'true');
	}
	public static function subscriptionsActive() {
		$array = BT_Manager ()->getActivePlugins ();
		return (in_array ( 'woocommerce-subscriptions/woocommerce-subscriptions.php', $array ) || array_key_exists ( 'woocommerce-subscriptions/woocommerce-subscriptions.php', $array ));
	}
	public function isSubscription($order_id) {
		return WC_Braintree_Subscriptions::orderContainsSubscription ( $order_id );
	}
	
	/**
	 * Return the order prefix if one has been configured.
	 *
	 * @param WC_Order $order        	
	 * @return string $orderId
	 */
	public static function getOrderId(WC_Order $order) {
		$orderId = $order->id;
		if (self::subscriptionsActive () && wcs_order_contains_subscription ( $order )) {
			$orderId = BT_Manager ()->get_option ( 'woocommerce_subscriptions_prefix' ) . $orderId;
		} else {
			$orderId = BT_Manager ()->get_option ( 'order_prefix' ) . $orderId;
		}
		return $orderId;
	}
	
	/**
	 * Method that checks if the order has a valid transaction Id.
	 *
	 * @param int $order_id        	
	 */
	public function canRefundOrder($order_id) {
		return wc_get_order ( $order_id )->get_transaction_id ();
	}
	public static function getPaymentMethodForm($user_id) {
		include WC_BRAINTREE_PLUGIN . 'payments/forms/saved-payment-methods.php';
	}
	public static function loadCustomerPaymentMethods() {
		BT_Manager ()->loadCustomerPaymentMethods ();
	}
	public static function getBillingAddress(WC_Order $order) {
		$billing_address = array (
				'countryCodeAlpha2' => $order->billing_country,
				'firstName' => $order->billing_first_name,
				'lastName' => $order->billing_last_name,
				'postalCode' => $order->billing_postcode,
				'streetAddress' => $order->billing_address_1,
				'extendedAddress' => $order->billing_address_2 
		);
		if ($state = $order->billing_state) {
			$attribs ['billing'] ['region'] = $state;
		}
		return $billing_address;
	}
	public static function getShippingAddress(WC_Order $order) {
		$shipping_address = array (
				'countryCodeAlpha2' => $order->shipping_country,
				'firstName' => $order->shipping_first_name,
				'lastName' => $order->shipping_last_name,
				'locality' => $order->shipping_city,
				'postalCode' => $order->shipping_postcode,
				'region' => $order->shipping_state,
				'streetAddress' => $order->shipping_address_1,
				'extendedAddress' => $order->shipping_address_2 
		);
		
		return $shipping_address;
	}
	public static function getDynamicDescriptor($attribs) {
		if (BT_Manager ()->isActive ( 'dynamic_descriptors' )) {
			$name = BT_Manager ()->get_option ( 'dynamic_descriptor_name' );
			$number = BT_Manager ()->get_option ( 'dynamic_descriptor_phone' );
			$url = BT_Manager ()->get_option ( 'dynamic_descriptor_url' );
			$attribs ['descriptor'] = array ();
			if (! empty ( $name )) {
				$attribs ['descriptor'] ['name'] = $name;
			}
			if (! empty ( $number ) && preg_match ( '/^US/i', get_option ( 'woocommerce_default_country' ) )) { // For US only.
				$attribs ['descriptor'] ['phone'] = $number;
			}
			if (! empty ( $url )) {
				$attribs ['descriptor'] ['url'] = $url;
			}
		}
		return $attribs;
	}
	public function admin_options() {
		?>
<div class="admin-options-links">
	<ul>
		<li><a
			href="<?php echo get_admin_url().'admin.php?page=braintree-payment-settings'?>"><?php echo __('API Settings', 'braintree')?></a></li>
		<li><a
			href="<?php echo get_admin_url().'admin.php?page=braintree-woocommerce-settings'?>"><?php echo __('WooCommerce Settings', 'braintree')?></a></li>
		<li><a
			href="<?php echo get_admin_url().'admin.php?page=braintree-subscriptions-page'?>"><?php echo __('Subscription Settings', 'braintree')?></a></li>
		<li><a
			href="<?php echo get_admin_url().'admin.php?page=braintree-donations-page'?>"><?php echo __('Donation Settings', 'braintree')?></a></li>
		<li><a
			href="<?php echo get_admin_url().'admin.php?page=braintree-webhooks-page'?>"><?php echo __('Webhook Settings', 'braintree')?></a></li>
		<li><a
			href="<?php echo get_admin_url().'admin.php?page=braintree-debug-log'?>"><?php echo __('Debug Log', 'braintree')?></a></li>
		<li><a
			href="<?php echo get_admin_url().'admin.php?page=braintree-license-page'?>"><?php echo __('License Activation', 'braintree')?></a></li>
		<li><a
			href="<?php echo get_admin_url().'admin.php?page=braintree-payments-tutorials'?>"><?php echo __('Tutorials', 'braintree')?></a></li>
	</ul>
</div>
<?php
	}
}
add_filter ( 'woocommerce_payment_gateways', 'WC_Braintree_Payments::addGateway' );
WC_Braintree_Payments::init ();