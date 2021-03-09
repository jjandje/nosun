<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-leads
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}
?>
<div class="setting-item click" data-fn="tl_template_chooser" data-alternate="">
	<?php tcb_icon( 'change_lp' ); ?>
    <span class="tve-s-name">
	<?php echo __( 'Choose Opt-In Template', 'thrive-leads' ); ?>
    </span>
</div>

<div class="setting-item click" data-fn="setting" data-setting="tl_template_save" data-alternate="">
	<?php tcb_icon( 'save_usertemp' ); ?>
    <span class="tve-s-name">
	<?php echo __( 'Save Template', 'thrive-cb' ); ?>
    </span>
</div>

<div class="setting-item click" data-fn="tl_template_reset" data-alternate="">
	<?php tcb_icon( 'reset_2default' ); ?>
    <span class="tve-s-name">
	<?php echo __( 'Reset to default content', 'thrive-leads' ); ?>
    </span>
</div>
