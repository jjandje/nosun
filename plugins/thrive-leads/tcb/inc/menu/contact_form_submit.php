<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package TCB2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}
?>
<div id="tve-contact_form_submit-component" class="tve-component" data-view="ContactFormSubmit">
	<div class="dropdown-header" data-prop="docked">
		<?php echo __( 'Contact Form Submit', 'thrive-cb' ); ?>
		<i></i>
	</div>
	<div class="dropdown-content">
		<div class="tve-control tcb-icon-side-wrapper" data-key="icon_side" data-icon="true" data-view="ButtonGroup"></div>
		<div class="tcb-text-center margin-top-10" data-icon="true">
			<span class="click tcb-text-uppercase clear-format" data-fn="remove_icon">
				<?php tcb_icon( 'close2' ) ?>&nbsp;<?php echo __( 'Remove Input Icon', 'thrive-cb' ) ?>
			</span>
		</div>
		<div class="tve-control" data-icon="false"  data-view="ModalPicker"></div>
		<hr>
		<div class="tve-control" data-view="MasterColor"></div>
		<hr>
		<div class="tve-control" data-view="ButtonWidth"></div>
		<hr>
		<div class="tve-control" data-view="ButtonAlign"></div>
		<hr>
		<div class="tve-control" data-key="style" data-initializer="button_style_control"></div>
	</div>
</div>
