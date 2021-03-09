<?php if ( have_rows( 'page_content' ) ): ?>
	<?php while ( have_rows( 'page_content' ) ) : the_row(); ?>
		<?php if ( get_row_layout() == 'page_content_row' ) : ?>
			<?php $greyBG = get_sub_field( 'page_content_row_bg' ); ?>
			<section class="content-row row-padding <?= $greyBG ? 'bg-grey' : ''; ?>">
				<div class="container">
					<div class="content-row__inner<?= get_sub_field('page_content_row_left_alignment') ? ' content-row__inner--left' : ''; ?>">
						<?php if(get_sub_field( 'page_content_row_title' )): ?>
						<h2 class="regular-title"><?php the_sub_field( 'page_content_row_title' ); ?></h2>
						<?php endif; ?>
						<?php the_sub_field( 'page_content_row_text' ); ?>

						<?php if ( get_sub_field( 'page_content_row_btn_show' ) == 1 ) : ?>
							<a href="<?php the_sub_field( 'page_content_row_btn_url' ); ?>" class="btn btn--yellow btn--arrow-blue">
								<?php the_sub_field( 'page_content_row_btn_text' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</section>
		<?php elseif ( get_row_layout() == 'page_content_row_two' ) : ?>
			<?php $greyBG = get_sub_field( 'page_content_row_two_bg' ); ?>
			<section class="content-row-two <?= $greyBG ? 'bg-grey' : ''; ?> row-padding">
				<div class="container">
					<?php if(get_sub_field( 'page_content_row_two_title' )): ?>
						<h2 class="page-title-border<?= get_sub_field( 'page_content_row_two_left_title' ) ? ' page-title-border--left' : ''; ?>"><?php the_sub_field( 'page_content_row_two_title' ); ?></h2>
					<?php endif; ?>
					<div class="content-row-two__column">
						<?php if ( get_sub_field( 'page_content_row_two_1_c_image' ) == 1 ) : ?>
							<?php $page_content_row_two_1_image = get_sub_field( 'page_content_row_two_1_image' ); ?>
							<img src="<?php echo wp_get_attachment_image_url( $page_content_row_two_1_image, 'full' ); ?>" alt="">
						<?php else: ?>
							<?php if(get_sub_field( 'page_content_row_two_1_title' )): ?>
								<span><?php the_sub_field( 'page_content_row_two_1_title' ); ?></span>
							<?php endif; ?>
							<?php the_sub_field( 'page_content_row_two_1_text' ); ?>
						<?php endif; ?>
					</div>
					<div class="content-row-two__column">
						<?php if ( get_sub_field( 'page_content_row_two_2_c_image' ) == 1 ) : ?>
							<?php $page_content_row_two_2_image = get_sub_field( 'page_content_row_two_2_image' ); ?>
							<img src="<?php echo wp_get_attachment_image_url( $page_content_row_two_2_image, 'full' ); ?>" alt="">
						<?php else: ?>
							<?php if(get_sub_field( 'page_content_row_two_2_title' )): ?>
								<span><?php the_sub_field( 'page_content_row_two_2_title' ); ?></span>
							<?php endif; ?>
							<?php the_sub_field( 'page_content_row_two_2_text' ); ?>
						<?php endif; ?>
					</div>
					<div class="clearfix"></div>
					<?php if ( get_sub_field( 'page_content_row_two_btn_c' ) == 1 ) : ?>
						<a href="<?php the_sub_field( 'page_content_row_two_btn_url' ); ?>" class="btn btn--yellow btn--arrow-blue"><?php the_sub_field( 'page_content_row_two_btn_text' ); ?></a>
					<?php endif; ?>
				</div>
			</section>
		<?php elseif ( get_row_layout() == 'page_content_row_highlight' ) : ?>
			<?php $page_content_row_highlight_image = get_sub_field( 'page_content_row_highlight_image' ); ?>
			<section class="content-row-bg" style="background-image: url(<?php echo wp_get_attachment_image_url( $page_content_row_highlight_image, 'full' ); ?>);">
				<h3><?php the_sub_field( 'page_content_row_highlight_title' ); ?></h3>
			</section>
		<?php endif; ?>
	<?php endwhile; ?>
    <?php     $repeaterResults = get_field('options_popular_pages', 'options');
    $popularPages = [];
    foreach ($repeaterResults as $repeaterResult) {
        $popularPages[] = $repeaterResult["options_popular_page"];
    }
    $permaLink = get_the_permalink();
    if (!empty($popularPages) && !empty($permaLink)) {
        if (in_array($permaLink, $popularPages, true)) get_template_part( 'templates/modules/popular-products' );
    }
    ?>
<?php endif; ?>