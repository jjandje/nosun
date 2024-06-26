<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-visual-editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
} ?>

<div id="tve-button-component" class="tve-component" data-view="Button">
	<div class="text-options action-group">
		<div class="dropdown-header" data-prop="docked">
			<div class="group-description">
				<?php echo __( 'Button Options', 'thrive-cb' ); ?>
			</div>
			<i></i>
		</div>
		<div class="dropdown-content">
			<div class="hide-states">
				<div class="tve-control" data-key="style" data-initializer="button_style_control"></div>
				<div class="row">
					<div class="col-xs-4">
						<div class="tve-control padding-top-10" data-view="ButtonIcon"></div>
					</div>
					<div class="col-xs-8">
						<div class="tve-control padding-top-10" data-view="SecondaryText"></div>
					</div>
				</div>
				<div class="tcb-button-icon-controls tcb-hidden">
					<div class="tve-control tcb-icon-side-wrapper" data-key="icon_side" data-view="ButtonGroup"></div>
				</div>
				<hr>
			</div>

			<div class="tve-control" data-view="MasterColor"></div>

			<div class="hide-states">
				<hr>
				<div class="tve-control" data-key="ButtonSize" data-view="ButtonGroup"></div>
				<hr>
				<div class="tve-control" data-view="ButtonWidth"></div>
				<div class="tve-control padding-top-10" data-view="FullWidth"></div>
				<hr class="tcb-button-link-container-divider">
				<div class="row tcb-button-link-container">
					<div class="col-xs-10">
						<span class="grey-text"><?php echo __( 'Button Link', 'thrive-cb' ); ?></span>
					</div>
					<div class="col-xs-2" style="text-align: right;">
						<span class="click grey-text" data-fn="open_button_search_settings"><?php tcb_icon( 'settings' ) ?></span>
					</div>
					<div class="col-xs-12">
						<div id="tcb-button-link-search-control" style="position:absolute; width: 90%;"></div>
					</div>
				</div>
				<div class="row margin-top-30 padding-top-10 tcb-button-link-options-container">
					<div class="col-xs-6">
						<div class="tve-control" data-view="LinkNewTab"></div>
					</div>
					<div class="col-xs-6">
						<div class="tve-control" data-view="LinkNoFollow"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
