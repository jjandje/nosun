<?php use Roots\Sage\Helpers;

$page_header_image_background = get_field( 'page_header_image_background' );
$extra_class                  = '';

if ( is_single() && get_post_type() == 'blog' ) {
	$extra_class = 'blog-header';
	if ( get_field( 'blog_header_background' ) ) {
		$image                        = get_field( 'blog_header_background' );
		$image                        = wp_get_attachment_image_src( $image, 'full' );
		$page_header_image_background = $image[ 0 ];
	} else {
		$page_header_image_background = get_the_post_thumbnail_url( get_the_ID(), 'full' );
	}
} else {
	$page_header_image_background = get_stylesheet_directory_uri() . '/dist/images/page-header-default.jpg';
}

?>
	<div class="page-header-image <?= $extra_class; ?>" style="background-image: url(<?= $page_header_image_background; ?>);">
		<div class="inner">
			<h1><?= Helpers::title(); ?></h1>
		</div>
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