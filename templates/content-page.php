<?php
if(is_account_page()){
	get_template_part('templates/page-header-regular');
} else {
	get_template_part('templates/page-header');
}

if(get_field('page_header_image_show_search')): ?>
  <div class="container">
    <?php get_template_part('templates/modules/search-form'); ?>
  </div>
<?php
endif;
?>
<div class="page-wrapper">

	<?php get_template_part('templates/modules/page-content'); ?>

</div>

