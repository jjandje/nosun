<?php
/**
 * FileName  symbol.php.
 * @project: thrive-visual-editor
 * @developer: Dragos Petcu
 * @company: BitStone
 */
?>
<div id="tve-symbol-component" class="tve-component" data-view="Symbol">
	<div class="dropdown-header" data-prop="docked">
		<?php echo __( 'Symbol Options', 'thrive-cb' ); ?>
		<i></i>
	</div>
	<div class="dropdown-content">
		<div class="row padding-top-20 padding-bottom-10 tcb-text-center grey-text">
			<?php echo __('This is a symbol. You can edit it as a global element( it updates simultaneously all over the places you used it) or unlink it and you edit it as a regular element', 'thrive-tcb'); ?>
		</div>
		<hr>
		<div class="row padding-top-20 padding-bottom-10">
			<div class="col-xs-6 tcb-text-center">
				<button class="tve-button blue long click" data-fn="edit_symbol">
					<?php echo __( 'Edit as Symbol ', 'thrive-cb' ) ?>
				</button>
			</div>

			<div class="col-xs-6 tcb-text-center">
				<button class="tve-button grey long click" data-fn="unlink_symbol">
					<?php echo __( 'Unlink', 'thrive-cb' ) ?>
				</button>
			</div>
		</div>
	</div>
</div>