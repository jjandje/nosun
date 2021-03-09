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
<div id="tve-contact_form-component" class="tve-component" data-view="ContactForm">
	<div class="dropdown-header" data-prop="docked">
		<?php echo __( 'Contact Form', 'thrive-cb' ); ?>
		<i></i>
	</div>
	<div class="dropdown-content">
		<div class="row">
			<div class="col-xs-6 padding-top-5"><?php echo __( 'Form Fields', 'thrive-cb' ); ?></div>
			<div class="col-xs-6 margin-top-5">
				<button class="tcb-right tve-button blue click margin-bottom-10" id="tcb-add-contact-form-item" data-fn="add_cf_item">
					<?php echo __( 'ADD NEW', 'thrive-cb' ) ?>
				</button>
			</div>
		</div>
		<div class="tve-control" data-key="FieldsControl" data-initializer="get_fields_control"></div>
		<hr class="margin-top-20">
		<div class="tve-control" data-view="AddRemoveLabels"></div>
		<div class="tve-control tcb-cf-add-remove-req-mark-control" data-view="AddRemoveRequiredMarks"></div>
		<hr>
		<div class="row middle-xs">
			<div class="col-xs-12 tcb-text-center">
				<button class="blue button-link click margin-top-15 margin-bottom-15" data-fn="manage_settings" style="font-size: 13px;">
					<?php tcb_icon( 'envelope' ); ?>
					<?php echo __( 'Email & after submit setup', 'thrive-cb' ); ?>
				</button>
			</div>
		</div>
		<div class="tve-advanced-controls extend-grey">
			<div class="dropdown-header" data-prop="advanced">
				<span>
					<?php echo __( 'Advanced', 'thrive-cb' ); ?>
				</span>
				<i></i>
			</div>
			<div class="dropdown-content clear-top">
				<button class="tve-button blue long click" data-fn="manage_error_messages">
					<?php echo __( 'Edit error messages', 'thrive-cb' ) ?>
				</button>
			</div>
		</div>
	</div>
</div>
