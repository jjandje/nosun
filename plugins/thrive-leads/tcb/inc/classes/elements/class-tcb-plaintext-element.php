<?php
/**
 * Created by PhpStorm.
 * User: Ovidiu
 * Date: 5/3/2018
 * Time: 5:16 PM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class TCB_Plaintext_Element
 */
class TCB_Plaintext_Element extends TCB_Text_Element {
	/**
	 * Name of the element
	 *
	 * @return string
	 */
	public function name() {
		return __( 'Plain Text', 'thrive-cb' );
	}

	/**
	 * Element alternate. Used for search
	 *
	 * @return string
	 */
	public function alternate() {
		return 'plain,text';
	}

	/**
	 * Return icon class needed for display in menu
	 *
	 * @return string
	 */
	public function icon() {
		return 'plain_text';
	}

	/**
	 * Plain Text Element identifier
	 *
	 * @return string
	 */
	public function identifier() {
		return '.thrv-plain-text';
	}

	/**
	 * Inherits the components from the text Element
	 *
	 * @return null|string
	 */
	public function inherit_components_from() {
		return 'text';
	}

	/**
	 * Element category that will be displayed in the sidebar
	 *
	 * @return string
	 */
	public function category() {
		return $this->get_thrive_advanced_label();
	}
}
