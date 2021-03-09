<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-visual-editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}
$admin_base_url = admin_url( '/', is_ssl() ? 'https' : 'admin' );
// for some reason, the above line does not work in some instances
if ( is_ssl() ) {
	$admin_base_url = str_replace( 'http://', 'https://', $admin_base_url );
}
?>

<div id="tve-menu-component" class="tve-component" data-view="CustomMenu">
	<div class="action-group">
		<div class="dropdown-header" data-prop="docked">
			<div class="group-description">
				<?php echo __( 'Custom Menu Options', 'thrive-cb' ); ?>
			</div>
			<i></i>
		</div>
		<div class="dropdown-content">
			<div class="tve-control tve-select-menu hide-tablet hide-mobile" data-key="SelectMenu" data-initializer="selectMenu"></div>
			<div class="row hide-tablet hide-mobile">
				<div class="col-xs-12">
					<span class="blue-text">
						<svg class="tcb-icon tcb-icon-info"><use xlink:href="#icon-info"></use></svg>
						<a class="tve-edit-menu tve-wpmenu-info" href="<?php echo $admin_base_url; ?>nav-menus.php?action=edit&menu=0" target="_blank">
							<?php echo __( 'Click here to edit your WordPress menu.', 'thrive-cb' ) ?>
						</a>
						<span class="tve-edit-menu tve-custommenu-info" >
							<?php echo __( 'Switching between menus resets the styling to default.', 'thrive-cb' ) ?>
						</span>
					</span>
				</div>
			</div>
			<hr class="hide-tablet hide-mobile">
			<div class="desktop-display">
				<div class="tve-control tve-menu-direction margin-top-10" data-key="MenuDirection" data-initializer="menuDirection"></div>
			</div>
			<div class="mobile-display hide-desktop">
				<div class="tve-control tve-mobile-side margin-top-10" data-key="MobileSide" data-initializer="mobileSide"></div>
				<hr>
				<div class="tve-control" data-view="MenuState"></div>
			</div>
			<hr>
			<div class="tve-menu-spacing-control">
				<p class="group-label grey-text">
					<?php echo __( 'Spacing', 'thrive-cb' ); ?>
				</p>
				<div class="tve-control" data-view="MenuSpacing"></div>
				<div class="tve-control tve-menu-spacing tve-menu-spacing-horizontal" data-view="HorizontalSpacing"></div>
				<div class="tve-control tve-menu-spacing tve-menu-spacing-vertical" data-view="VerticalSpacing"></div>
				<div class="tve-control tve-menu-spacing tve-menu-spacing-between" data-view="BetweenSpacing"></div>
			</div>
			<hr>
			<div class="tve-control" data-view="SwitchToIcon"></div>

			<div class="mobile-display hide-desktop">
				<hr>
				<div class="tve-control tve-mobile-icon margin-top-10" data-key="MobileIcon" data-initializer="mobileIcon"></div>
			</div>
			<hr>
			<div class="tve-control tve-menu-style margin-top-10" data-key="MenuStyle" data-initializer="menuStyle"></div>
			<div class=" hide-tablet hide-mobile">
				<hr>
				<div class="tve-control tve-menu-dropdown-icon margin-top-10" data-key="DropdownIcon" data-initializer="dropdownIcon"></div>
				<div class="tve-add-menu-item">
					<hr>
					<div class="row">
						<div class="col-xs-5 group-label grey-text margin-top-5">
							Menu Items
						</div>
						<div class="col-xs-7">
							<button class="tve-button blue click" data-fn-click="add_menu_item">
								<?php echo __( 'Add new', 'thrive-cb' ) ?>
							</button>
						</div>
					</div>
				</div>
				<div class="tve-control tve-order-list" data-key="OrderList" data-initializer="orderItems"></div>
			</div>
		</div>
	</div>
</div>

