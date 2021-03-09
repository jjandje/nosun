<h2 class="tcb-modal-title"><?php echo __( 'Set error message' ) ?></h2>

<div class="tcb-fields-error row"></div>

<div class="clearfix padding-bottom-10 row">
	<div class="col col-xs-12">
		<button type="button" class="medium grey text-only tve-button click" data-fn="restore_defaults">
			<?php tcb_icon( 'close' ) ?>
			<?php echo __( 'Restore errors to default' ) ?>
		</button>
	</div>
</div>

<div class="tcb-modal-footer clearfix padding-top-20 row">
	<div class="col col-xs-6">
		<button type="button" class="tcb-left tve-button medium text-only grey tcb-modal-cancel">
			<?php echo __( 'Cancel', 'thrive-cb' ) ?>
		</button>
	</div>
	<div class="col col-xs-6">
		<button type="button" class="tcb-right tve-button medium green tcb-modal-save">
			<?php echo __( 'Save', 'thrive-cb' ) ?>
		</button>
	</div>
</div>
