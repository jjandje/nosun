<script type="text/javascript">
	( function ( $ ) {
		var _DELTA = 200, //for slide top animation {transform: translateY(-200px)}
			$window = $( window ),
			trigger_elements = function ( elements ) {
				elements.each( function () {
					var elem = $( this );
					if ( elem.parents( '.tve_p_lb_content' ).length ) {
						elem.parents( '.tve_p_lb_content' ).on( 'tve.lightbox-open', function () {
							if ( ! elem.hasClass( 'tve-viewport-triggered' ) ) {
								elem.trigger( 'tve-viewport' ).addClass( 'tve-viewport-triggered' );
							}
						} );
						return;
					}

					/**
					 * keep this for emergency :)
					 */
//					var is_in_viewport = elem.offset().top + _DELTA < $window.height() + $window.scrollTop() && elem.offset().top + elem.outerHeight() > $window.scrollTop() + _DELTA;

					var et = elem.offset().top, //element top
						eh = elem.outerHeight(),
						//window height
						wh = $window.height(),
						//window scroll
						ws = $window.scrollTop();

					if ( et < 0 ) { //in case the element has animation slide top and is outside top of document (-200px)
						et += _DELTA;
					}

					if ( et >= wh + ws ) { //in case the element has animation slide bottom and is outside bottom of document
						et -= _DELTA;
					}

					//lower the element top to its median;
//					et += ( eh / 2 );

					//element is in view port if its top >= window scroll and its top <= window scroll + window height
					var _is_in_viewport = et >= ws && et <= ws + wh;

					if ( _is_in_viewport ) {
						elem.trigger( 'tve-viewport' ).addClass( 'tve-viewport-triggered' );
					}
				} );
			},
			trigger_exit = function ( elements ) {
				elements.each( function () {
					var elem = $( this );

					/**
					 * keep this for emergency
					 */
//					var _is_out_viewport = elem.offset().top > $window.height() + $window.scrollTop() || elem.offset().top + elem.outerHeight() < $window.scrollTop();

					var et = elem.offset().top, // > 0 ? elem.offset().top : elem.offset().top + _DELTA, //element top
						eh = elem.outerHeight(),
						//window height
						wh = $window.height(),
						//window scroll
						ws = $window.scrollTop();

					if ( et < 0 ) {
						et += _DELTA;
					}

					//lower the element to to its median
					et += ( eh / 2 );

					//element is out if its top < window scroll OR its top is > window scroll + window height
					var _is_out_viewport = et < ws || et > ws + wh;

					if ( _is_out_viewport ) {
						elem.trigger( 'tve-viewport-leave' ).removeClass( 'tve-viewport-triggered' );
					}
				} );
			};
		$( document ).ready( function () {
			var $to_test = $( '.tve_et_tve-viewport' );
			$window.scroll( function () {
				trigger_elements( $to_test.filter( ':not(.tve-viewport-triggered)' ) );
				trigger_exit( $to_test.filter( '.tve-viewport-triggered' ) );
			} );
			setTimeout( function () {
				trigger_elements( $to_test );
			}, 200 );
		} );
	} )
	( jQuery );
</script>
