<#if(typeof no_item !== 'undefined' && no_item){#>
<div class="list-no-items tcb-text-center"><?php echo __( 'None', 'thrive-cb' ) ?></div>
<#} else {#>
	<div class="preview-list-item tcb-relative<#=item.get('second_level') ? ' second-level':'' #><#=view.has_settings_icon() ? '' : ' click'#><#=item.get('icon') ? '' : ' no-icon'#>"
	     data-fn="item_click" data-index="<#=item.index#>" data-id="<#=item.get('item_id')#>" data-label="<#=item.get('label')#>">
		<div class="row middle-xs between-xs">
			<div class="col-xs-2 item-icon">
				<#=item.get('icon')#>
			</div>
			<div class="col-xs-8 tcb-relative item-label">
				<div class="preview-list-sort-handle clearfix">
					<span class="col-sep tcb-left"></span>
					<span class="col-sep tcb-left margin-left-5"></span>
				</div>
				<span class="tcb-truncate"><#=item.get('label')#></span>
			</div>
			<div class="col-xs-2 clearfix">
				<div class="tcb-right">

					<span title="<?php esc_attr_e( 'Edit', 'thrive-cb' ) ?>" class="click" data-fn-click="tab_click" data-index="<#=item.index#>" data-label="<#=item.get('label')#>" data-child-index="<#= item.child_index #>">
						<?php tcb_icon( 'edit' ) ?>
					</span>

					<span title="<?php esc_attr_e( 'Remove', 'thrive-cb' ) ?>" class="click" data-fn="item_remove" data-index="<#=item.index#>" <#= item.child_index!==''? 'data-child-index="'+ item.child_index +'"':'' #>>
						<?php tcb_icon( 'close2' ) ?>
					</span>
				</div>
			</div>
		</div>
	</div>
	<#}#>