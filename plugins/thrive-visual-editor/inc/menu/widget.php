<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

?>
<div id="tve-widget-component" class="tve-component" data-view="Widget">
	<div class="dropdown-header" data-prop="docked">
		<?php echo __( 'Widget', 'thrive-cb' ); ?>
		<i></i>
	</div>
	<div class="dropdown-content">
		<form class="widget-form">
		</form>
		<button class="tve-button click green" data-fn="update_widget">
			<?php echo __( 'Update Widget', 'thrive-cb' ); ?>
		</button>
	</div>
</div>
