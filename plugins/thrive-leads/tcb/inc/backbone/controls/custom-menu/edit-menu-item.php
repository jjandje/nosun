<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 5/23/2018
 * Time: 2:25 PM
 */
?>
<div class="row middle-xs">
	<div class="col col-xs-10">
		<?php echo __( 'Insert / edit hyperlink', 'thrive-cb' ) ?>
	</div>
	<div class="col col-xs-2 tcb-text-right">
		<span class="click" data-fn="open_settings">
			<?php tcb_icon( 'settings' ) ?>
		</span>
	</div>
</div>
<div class="tcb-link-search">
	<input type="text" class="tcb-menu-item-text tcb-dark" class="change target" data-fn="changed" value="" autocomplete="off">
</div>
<div id="a-link-main" class="tcb-link-search">
</div>
<label class="tcb-checkbox"><input type="checkbox" class="change target" data-fn="attr" data-attr="target" value="_blank"><span><?php echo __( 'Open in new tab', 'thrive-cb' ) ?></span></label>
<label class="tcb-checkbox"><input type="checkbox" class="change rel" data-fn="attr" data-attr="rel" value="nofollow"><span><?php echo __( 'Set link as nofollow', 'thrive-cb' ) ?></span></label>
