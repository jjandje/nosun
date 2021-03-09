<?php

use Roots\Sage\Wrapper;

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<?php get_template_part('templates/head'); ?>
<body <?php body_class(); ?>>
<!-- Google Tag Manager (noscript) -->
<noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WBD7JT" height="0" width="0"
            style="display:none;visibility:hidden"></iframe>
</noscript>
<!-- End Google Tag Manager (noscript) -->
<!--[if IE]>
<div class="alert alert-warning">
	<?php _e('You are using an <strong>outdated</strong> browser. Please
	<a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.', 'sage'); ?>
</div>
<![endif]-->

<?php
do_action('get_header');
if (basename(get_page_template()) !== 'template-booked.php') {
    get_template_part('templates/header');
}
?>

<div class="wrap" role="document">
    <main class="main">
        <?php include Wrapper\template_path(); ?>
    </main>
</div>

<?php
do_action('get_footer');
if (basename(get_page_template()) !== 'template-booked.php') {
    get_template_part('templates/footer');
}
wp_footer();
?>

</body>
<script src="<?php echo get_stylesheet_directory_uri(); ?>/lang/calendar-nl.js"></script>
</html>
