<?php use Roots\Sage\Helpers;
use Roots\Sage\Titles; ?>

<?php
if ( is_archive() || is_tax() ) {

	if ( get_post_type() == 'blog' ) {
		$page_header_image_background = get_field( 'blog_header_image', 'option' );
		$page_header_image_background = wp_get_attachment_image_url( $page_header_image_background, 'full' );

	} else if ( get_post_type() == 'destination' ) {
		$page_header_image_background = get_field( 'destinations_header_image', 'option' );
		$page_header_image_background = wp_get_attachment_image_url( $page_header_image_background, 'full' );
	}

	if ( empty( $page_header_image_background ) ) {
		$page_header_image_background = get_stylesheet_directory_uri() . '/dist/images/page-header-default.jpg';
	}

} else if ( is_single() && get_post_type() == 'blog' ) {
	if(get_field('blog_header_background')){
		$image = get_field('blog_header_background');
		$image = wp_get_attachment_image_src($image, 'full');
		$page_header_image_background = $image[0];
	} else {
		$page_header_image_background = get_the_post_thumbnail_url( get_the_ID(), 'full' );
	}
} else {
    $page_header_image_background = get_field( 'page_header_image_background' );
    if ( $page_header_image_background )
        $page_header_image_background = wp_get_attachment_image_url( $page_header_image_background, 'full' );
    else
        $page_header_image_background = get_stylesheet_directory_uri() . '/dist/images/page-header-default.jpg';
} ?>
	<div class="page-header-image" style="background-image: url(<?= $page_header_image_background; ?>);">
		<?php if ( is_archive() || is_tax() ): ?>
			<?php if ( get_post_type() == 'destination' && get_field( 'destinations_header_title', 'option' ) ) : ?>
				<h1><?= get_field( 'destinations_header_title', 'option' ); ?></h1><br />
			<?php elseif ( get_post_type() == 'blog' && get_field( 'blog_header_title', 'option' ) ): ?>
				<h1><?= get_field( 'blog_header_title', 'option' ); ?></h1><br />
			<?php else: ?>
				<h1><?= Helpers::title(); ?></h1><br />
			<?php endif; ?>
		<?php elseif ( get_field( 'page_header_image_title' ) ): ?>
			<h1><?php the_field( 'page_header_image_title' ); ?></h1><br />
		<?php else: ?>
			<h1><?= Helpers::title(); ?></h1><br />
		<?php endif; ?>
		<?php if ( get_field( 'page_header_image_subtitle' ) && ! is_tax() && ! is_archive() ): ?>
			<h2><?php the_field( 'page_header_image_subtitle' ); ?></h2>
		<?php endif; ?>
	</div>

<?php
if ( function_exists( 'yoast_breadcrumb' ) ) : ?>
	<div class="container">
		<div class="breadcrumb-wrapper">
			<?php yoast_breadcrumb( '
<p id="breadcrumbs">', '</p>
' ); ?>
		</div>
	</div>
<?php endif; ?>