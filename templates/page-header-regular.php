<?php use Roots\Sage\Helpers; ?>
<div class="page-header <?= is_account_page() ? 'page-header--subtitle' : ''; ?>">
    <h1 class="page-title-border"><?= Helpers::title(); ?></h1>
    <?php if ( is_account_page() ): ?>
        <span>Accountpagina</span>
    <?php else:
        $bookingPostId = get_query_var('booking_post_id');
        if (!empty($bookingPostId)):
           $isOption = get_field('booking_is_option', $bookingPostId);
            if ($isOption): ?>
                <span>Je bekijkt een optie</span>
            <?php endif;
        endif;
    endif; ?>
</div>
<?php
if (function_exists('yoast_breadcrumb')): ?>
	<div class="container">
		<div class="breadcrumb-wrapper">
			<?php yoast_breadcrumb( '<p id="breadcrumbs">', '</p>' ); ?>
		</div>
	</div>
<?php endif; ?>
