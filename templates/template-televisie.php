<?php
/**
 * Template Name: Televisie
 */
if ( is_user_logged_in() & current_user_can( 'administrator' ) ):
?>
<script>

    jQuery( document ).ready( function () {

        jQuery( '#calendar' ).fullCalendar( {
            height: 'parent',
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,basicWeek,basicDay'
            },
			eventTextColor: 'white',
            defaultDate: '<?php echo date('Y-m-d'); ?>',
            navLinks: true, // can click day/week names to navigate views
            editable: true,
            eventLimit: true,
            displayEventTime: true,
            displayEventEnd: true,
            eventRender: function(eventObj, $el) {
                jQuery($el).webuiPopover({
					title: eventObj.title,
					content: eventObj.description,
                    trigger: 'hover'
                });
            },

            events: [
				<?php

	            function random_color_part() {
		            $dt = '';
		            for($o=1;$o<=3;$o++)
		            {
			            $dt .= str_pad( dechex( mt_rand( 0, 127 ) ), 2, '0', STR_PAD_LEFT);
		            }
		            return $dt;
	            }

				$posts = get_posts( array (
					'posts_per_page' => - 1,
					'post_type'      => 'product'
				) );

				if( $posts ): ?>
				<?php foreach( $posts as $post ):
				setup_postdata( $post );

				if ( have_rows( 'trip_dates' ) ) :
				$trip_dates = get_field( 'trip_dates' );
				$count_guaranteed = 0;
				$today = date( "Y-m-d" );
				$today_time = strtotime( $today );
				$available_dates = 0;
				$eventcolor = random_color_part();

				foreach ( $trip_dates as $trip_date ) {
					if ( $trip_date[ 'trip_dates_guaranteed' ] == true ) {
						$count_guaranteed ++;
					}
				}

				while ( have_rows( 'trip_dates' ) ) : the_row();
				if ( get_sub_field( 'trip_dates_availabilty' ) == 'red' ) {
					continue;
				} elseif ( strtotime( get_sub_field( 'trip_dates_date' ) ) <= $today_time ) {
					continue;
				} else {
					$available_dates ++;
					$availability = '';
					switch ( get_sub_field( 'trip_dates_availabilty' ) ) {
						case 'green':
							$availability = 'green';
							break;
						case 'orange':
							$availability = 'yellow';
							break;
					}
				}; ?>
                {
                    title: '<?php the_title(); ?> (<?= get_field('trip_dates_guide'); ?>)',
                    start: '<?= date_i18n( 'Y-m-d', strtotime( get_sub_field( 'trip_dates_date' ) ) ); ?>',
                    end: '<?= date_i18n( 'Y-m-d', strtotime( get_sub_field( 'trip_dates_date' ) . ' + ' . get_field( 'trip_amount_days' ) . ' days' ) ); ?>',
					color: '#<?=$eventcolor; ?>',
                    TextColor: '#ffffff',
                    description: '<strong>Reisbegeleider</strong><br /> <?= get_field('trip_dates_guide'); ?><br /><strong>Min aantal personen:</strong> <?=get_field('trip_min_passengers'); ?><br /><strong>Max aantal personen:</strong> <?=get_field('trip_max_passengers'); ?>'
                },
				<?php
				endwhile;
				endif;
				endforeach;
				wp_reset_postdata();
				endif;
				endif; ?>
            ]
        } );
    } );
</script>

<div id="calendar"></div>


