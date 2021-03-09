( function ( $ ) {
	var ThriveGutenbergSwitch = {

		init: function () {
			var self = this,
				$gutenberg = $( '#editor' ),
				$switchButton = $( '#thrive-gutenberg-switch' ).html();

			setTimeout( function () {
				var $myelement = $( "<div></div>" ).append( $switchButton ),
					$thrive_swich_button = $myelement.find( '#thrive_preview_button' ),
					$thrive_swich_box = $myelement.find( '.postbox' );

				if ( $thrive_swich_box.length ) {
					$gutenberg.find( '.editor-post-title' ).parent().append( $switchButton );
					$gutenberg.find( '.editor-block-list__layout' ).hide();

				} else if ( TCB_Post_Edit_Data.landing_page ) {

					var $postbox = $( '#thrive_preview_button' ).closest( '.postbox' );
					var $postbox_clone = $postbox.clone();

					$postbox.remove();
					$gutenberg.find( '.editor-block-list__layout' ).hide();
					$gutenberg.find( '.editor-post-title' ).parent().append( $switchButton );
					$gutenberg.find( '.editor-post-title' ).parent().append( $postbox_clone );
				} else {
					$gutenberg.find( '.edit-post-header-toolbar' ).append( $thrive_swich_button );
					$( '#thrive_preview_button' ).on( 'click', function () {
						$gutenberg.find( '.editor-block-list__layout' ).hide();
					} );

				}
				$( '#tcb2-show-wp-editor' ).on( 'click', function () {
					var $editlink = $gutenberg.find( '.tcb-enable-editor' ),
						$postbox = $editlink.closest( '.postbox' );

					$.ajax( {
						type: 'post',
						url: ajaxurl,
						dataType: 'json',
						data: {
							_nonce: TCB_Post_Edit_Data.admin_nonce,
							post_id: this.getAttribute( 'data-id' ),
							action: 'tcb_admin_ajax_controller',
							route: 'disable_tcb'
						}
					} ).done( function ( response ) {

					} );

					$postbox.next( '.tcb-flags' ).find( 'input' ).prop( 'disabled', false );
					$postbox.remove();
					$gutenberg.find( '.editor-block-list__layout' ).show();
					$gutenberg.find( '.edit-post-header-toolbar' ).append( $thrive_swich_button );
				} );
			}, 1 );
		}
	};

	$( function () {
		ThriveGutenbergSwitch.init();
	} );

}( jQuery ) );