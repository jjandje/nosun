<?php
$menus = tve_get_custom_menus();

$attributes = array(
	'menu_id'          => isset( $_POST['menu_id'] ) ? $_POST['menu_id'] : ( ! empty( $menus[0] ) ? $menus[0]['id'] : 'custom' ),
	'color'            => isset( $_POST['colour'] ) ? $_POST['colour'] : 'tve_red',
	'dir'              => isset( $_POST['dir'] ) ? $_POST['dir'] : 'tve_horizontal',
	'font_class'       => isset( $_POST['font_class'] ) ? $_POST['font_class'] : '',
	'font_size'        => isset( $_POST['font_size'] ) ? $_POST['font_size'] : '',
	'ul_attr'          => isset( $_POST['ul_attr'] ) ? $_POST['ul_attr'] : '',
	'link_attr'        => isset( $_POST['link_attr'] ) ? $_POST['link_attr'] : '',
	'top_link_attr'    => isset( $_POST['top_link_attr'] ) ? $_POST['top_link_attr'] : '',
	'trigger_attr'     => isset( $_POST['trigger_attr'] ) ? $_POST['trigger_attr'] : '',
	'primary'          => isset( $_POST['primary'] ) && ( $_POST['primary'] == 'true' || $_POST['primary'] == '1' ) ? 1 : '',
	'head_css'         => isset( $_POST['head_css'] ) ? $_POST['head_css'] : '',
	'background_hover' => isset( $_POST['background_hover'] ) ? $_POST['background_hover'] : '',
	'main_hover'       => isset( $_POST['main_hover'] ) ? $_POST['main_hover'] : '',
	'child_hover'      => isset( $_POST['child_hover'] ) ? $_POST['child_hover'] : '',
	'group_edit'       => ! empty( $_POST['group_edit'] ) && is_array( $_POST['group_edit'] ) ? $_POST['group_edit'] : array(),
	'menu_style'       => isset( $_POST['menu_style'] ) ? $_POST['menu_style'] : '',
	'dropdown_icon'    => isset( $_POST['dropdown_icon'] ) ? $_POST['dropdown_icon'] : 'style_1',
	'mobile_side'      => isset( $_POST['mobile_side'] ) ? $_POST['mobile_side'] : '',
	'mobile_icon'      => isset( $_POST['mobile_icon'] ) ? $_POST['mobile_icon'] : '',
	'switch_to_icon'   => isset( $_POST['switch_to_icon'] ) ? $_POST['switch_to_icon'] : 'tablet,mobile',
);
if ( ! $attributes['dropdown_icon'] && $attributes['dir'] === 'tve_vertical' ) {
	$icon_styles                 = tcb_elements()->element_factory( 'menu' )->get_icon_styles();
	$styles                      = array_keys( $icon_styles );
	$attributes['dropdown_icon'] = reset( $styles );
}

$attributes['font_class'] .= ( ! empty( $_POST['custom_class'] ) ? ' ' . $_POST['custom_class'] : '' );
$switch_to_icon           = strpos( $attributes['switch_to_icon'], 'desktop' ) === false ? '' : 'tve-custom-menu-switch-icon-desktop';
?>
<?php if ( empty( $_POST['nowrap'] ) ) : ?>
<div class="thrv_wrapper thrv_widget_menu tve-custom-menu-upgrade tve-mobile-dropdown <?php echo $switch_to_icon; ?><?php echo $attributes['menu_style'] ? 'tve-menu-style-' . $attributes['menu_style'] : '' ?> <?php echo $attributes['dropdown_icon'] ? 'tve-dropdown-icon-' . $attributes['dropdown_icon'] : '' ?> <?php echo $attributes['mobile_side'] ? 'tve-mobile-side-' . $attributes['mobile_side'] : '' ?> <?php echo $attributes['mobile_icon'] ? 'tve-mobile-icon-' . $attributes['mobile_icon'] : '' ?> " data-tve-style="<?php echo $attributes['dir'] ?>" data-tve-switch-icon="<?php echo $attributes['switch_to_icon'] ?>">
	<?php endif ?>
	<div class="thrive-shortcode-config"
		 style="display: none !important"><?php echo '__CONFIG_widget_menu__' . json_encode( $attributes ) . '__CONFIG_widget_menu__' ?></div>
	<?php
	if ( $attributes['menu_id'] === 'custom' ) {
		echo tve_render_custom_menu( $attributes );
	} else {
		echo tve_render_widget_menu( $attributes );
	}
	?>
	<?php if ( empty( $_POST['nowrap'] ) ) : ?>
</div>
<?php endif ?>
