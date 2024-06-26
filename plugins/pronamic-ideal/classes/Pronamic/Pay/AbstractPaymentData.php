<?php

/**
 * Title: Abstract payment data class
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @since 1.4.0
 */
abstract class Pronamic_Pay_AbstractPaymentData implements Pronamic_Pay_PaymentDataInterface {
	private $entrance_code;

	protected $recurring;

	//////////////////////////////////////////////////

	public function __construct() {
		$this->entrance_code = uniqid();
	}

	public function get_user_id() {
		return get_current_user_id();
	}

	//////////////////////////////////////////////////

	public abstract function get_source();

	public function get_source_id() {
		return $this->get_order_id();
	}

	//////////////////////////////////////////////////

	public function get_title() {
		return $this->get_description();
	}

	public abstract function get_description();

	public abstract function get_order_id();

	public abstract function get_items();

	public function get_amount() {
		return $this->get_items()->get_amount();
	}

	//////////////////////////////////////////////////
	// Customer
	//////////////////////////////////////////////////

	public function get_email() {
		return null;
	}

	/**
	 * Get customer name.
	 *
	 * @deprecated deprecated since version 4.0.1, use get_customer_name() instead.
	 */
	public function getCustomerName() {
		return $this->get_customer_name();
	}

	public function get_customer_name() {
		return null;
	}

	/**
	 * Get owner address.
	 *
	 * @deprecated deprecated since version 4.0.1, use get_address() instead.
	 */
	public function getOwnerAddress() {
		return $this->get_address();
	}

	public function get_address() {
		return null;
	}

	/**
	 * Get owner city.
	 *
	 * @deprecated deprecated since version 4.0.1, use get_city() instead.
	 */
	public function getOwnerCity() {
		return $this->get_city();
	}

	public function get_city() {
		return null;
	}

	/**
	 * Get owner zip.
	 *
	 * @deprecated deprecated since version 4.0.1, use get_zip() instead.
	 */
	public function getOwnerZip() {
		return $this->get_zip();
	}

	public function get_zip() {
		return null;
	}

	public function get_country() {
		return null;
	}

	public function get_telephone_number() {
		return null;
	}

	//////////////////////////////////////////////////
	// Currency
	//////////////////////////////////////////////////

	/**
	 * Get the curreny alphabetic code
	 *
	 *  @return string
	 */
	public abstract function get_currency_alphabetic_code();

	/**
	 * Get currency numeric code
	 *
	 * @return Ambigous <string, NULL>
	 */
	public function get_currency_numeric_code() {
		return Pronamic_WP_Currency::transform_code_to_number( $this->get_currency_alphabetic_code() );
	}

	/**
	 * Helper function to get the curreny alphabetic code
	 *
	 * @return string
	 */
	public function get_currency() {
		return $this->get_currency_alphabetic_code();
	}

	//////////////////////////////////////////////////
	// Language
	//////////////////////////////////////////////////

	/**
	 * Get the language code (ISO639)
	 *
	 * @see http://www.w3.org/WAI/ER/IG/ert/iso639.htm
	 *
	 * @return string
	 */
	public abstract function get_language();

	/**
	 * Get the language (ISO639) and country (ISO3166) code
	 *
	 * @see http://www.w3.org/WAI/ER/IG/ert/iso639.htm
	 * @see http://www.iso.org/iso/home/standards/country_codes.htm
	 *
	 * @return string
	 */
	public abstract function get_language_and_country();

	//////////////////////////////////////////////////
	// Entrance code
	//////////////////////////////////////////////////

	public function get_entrance_code() {
		return $this->entrance_code;
	}

	//////////////////////////////////////////////////
	// Issuer
	//////////////////////////////////////////////////

	public function get_issuer( $payment_method = null ) {
		if ( Pronamic_WP_Pay_PaymentMethods::CREDIT_CARD === $payment_method ) {
			return $this->get_credit_card_issuer_id();
		}

		return $this->get_issuer_id();
	}

	public function get_issuer_id() {
		return filter_input( INPUT_POST, 'pronamic_ideal_issuer_id', FILTER_SANITIZE_STRING );
	}

	public function get_credit_card_issuer_id() {
		return filter_input( INPUT_POST, 'pronamic_credit_card_issuer_id', FILTER_SANITIZE_STRING );
	}

	/**
	 * Get credit card object
	 *
	 * @return Pronamic_Pay_CreditCard
	 */
	public function get_credit_card() {
		return null;
	}

	//////////////////////////////////////////////////
	// Subscription
	//////////////////////////////////////////////////

	/**
	 * Subscription
	 *
	 * @return false|Pronamic_Pay_Subscription
	 */
	public function get_subscription() {
		return false;
	}

	/**
	 * Subscription ID
	 *
	 * @return int
	 */
	public abstract function get_subscription_id();

	/**
	 * Is this a recurring (not first) payment?
	 *
	 * @return bool
	 */
	public function get_recurring() {
		return $this->recurring;
	}

	/**
	 * Set recurring
	 *
	 * @param bool $recurring
	 */
	public function set_recurring( $recurring ) {
		$this->recurring = $recurring;
	}
}
