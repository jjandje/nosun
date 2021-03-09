<?php

require_once 'class-tcb-label-element.php';

/**
 * Class TCB_Label_Disabled_Element
 *
 * Non edited label element. For inline text we use typography control
 */
class TCB_Menu_Item_Element extends TCB_Label_Element {

	/**
	 * Name of the element
	 *
	 * @return string
	 */
	public function name() {
		return __( 'Menu Item', 'thrive-cb' );
	}

	/**
	 * Section element identifier
	 *
	 * @return string
	 */
	public function identifier() {
		return '.thrv-menu-item-element';
	}

	/**
	 * There is no need for HTML for this element since we need it only for control filter
	 *
	 * @return string
	 */
	protected function html() {
		return '';
	}

	/**
	 * Removes the unnecessary components from the element json string
	 *
	 * @return array
	 */
	protected function general_components() {
		$general_components = parent::general_components();

		if ( isset( $general_components['animation'] ) ) {
			unset( $general_components['animation'] );
		}

		if ( isset( $general_components['responsive'] ) ) {
			unset( $general_components['responsive'] );
		}
		if ( isset( $general_components['styles-templates'] ) ) {
			unset( $general_components['styles-templates'] );
		}

		return $general_components;
	}

	/**
	 * Component and control config
	 *
	 * @return array
	 */
	public function own_components() {
		return array(
			'typography' => array(
				'disabled_controls' => array(
					'.tve-advanced-controls',
					'TextAlign',
				),
				'config'            => array(
					'FontColor'  => array(
						'css_suffix' => ' > a',
						'use_fill'   => true,
						'important'  => true,
					),
					'FontSize'   => array(
						'css_suffix' => ' > a',
						'important'  => true,
					),
					'FontFace'   => array(
						'css_suffix' => ' > a',
						'important'  => true,
					),
					'TextStyle'  => array(
						'css_suffix' => ' > a',
						'important'  => true,
					),
					'LineHeight' => array(
						'css_suffix' => ' > a',
						'important'  => true,
					),
				),
			),
			'background' => array(
				'config' => array(
					'ColorPicker' => array(
						'config' => array(
							'important' => false,
						),
					),
				),
			),
			'layout'     => array(
				'disabled_controls' => array(
					'.tve-advanced-controls',
					'MaxWidth',
					'Alignment',
					'hr',
				),
			),
			'borders'    => array(
				'config' => array(
					'Corners' => array(
						'overflow' => false,
					),
				),
			),
		);
	}
}
