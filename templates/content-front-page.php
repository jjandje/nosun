<?php use lib\controllers\Destination;
use lib\controllers\Template;
global $post; ?>

<section class="home-hero">
    <?php $home_header_background = get_field('home_header_background'); ?>
    <div class="home-hero__container"
         style="background-image: url(<?= wp_get_attachment_image_url($home_header_background, 'full'); ?>);">
        <div class="container">
            <div class="hero-title">
                <h1><?php the_field('home_header_title'); ?></h1>
                <span class="subtitle"><?php the_field('home_header_subtitle'); ?></span>
            </div>
            <div class="clearfix"></div>
            <?php get_template_part('templates/modules/search-form'); ?>
        </div>
    </div>
</section>

<?php if (have_rows('home_content_blocks')): ?>
    <?php while (have_rows('home_content_blocks')): the_row(); ?>
        <?php if (get_row_layout() === 'usps'): ?>
            <?php get_template_part('templates/modules/usps'); ?>
        <?php elseif (get_row_layout() === 'popular'): ?>
            <?php get_template_part('templates/modules/popular-products'); ?>
        <?php elseif (get_row_layout() === 'guarantees'): ?>
            <section class="bg-yellow row-padding">
                <div class="container">
                    <h2 class="page-title-border lowercase">noSun garanties</h2>
                    <?php get_template_part('templates/modules/guarantees'); ?>
                </div>
            </section>
        <?php elseif (get_row_layout() === 'newsletter'): ?>
            <section class="bg-grey row-padding">
                <div class="container">
                    <h2 class="page-title-border lowercase"><?php the_field('newsletter_title', 'option'); ?></h2>
                    <p class="p--width-60"><?php the_field('newsletter_text', 'option'); ?></p>
                    <?php get_template_part('templates/modules/newsletter'); ?>
                </div>
            </section>
        <?php elseif (get_row_layout() === 'vlogs'): ?>
            <section class="bg-blue row-padding">
                <div class="container">
                    <h2 class="page-title-border white">Vlogs</h2>
                    <?php get_template_part('templates/modules/vlogs'); ?>
                </div>
            </section>
        <?php elseif (get_row_layout() === 'destinations'): ?>
            <?php $destinations = Destination::all();
            if (!empty($destinations)): ?>
                <section class="trip-blocks front-page">
                    <div class="container">
                        <div class="title-wrapper padding-bottom">
                            <h2 class="page-title-border large">Bestemmingen</h2>
                        </div>
                        <?php foreach ($destinations as $post):
                            setup_postdata($post);
                            get_template_part('templates/content-destination');
                        endforeach;
                        wp_reset_postdata(); ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php elseif (get_row_layout() === 'lastminutes'): ?>
            <section class="trip-blocks">
                <div class="title-wrapper padding-bottom">
                    <h2 class="page-title-border large">Lastminute reizen</h2>
                </div>
                <div class="clearfix"></div>
                <div class="container">
                    <?php
                    $templates = Template::get_last_minutes();
                    if (!empty($templates)):
                        global $post;
                        foreach ($templates as $post):
                            setup_postdata($post);
                            get_template_part('templates/content-product');
                        endforeach;
                        wp_reset_postdata();
                        $pageLink = get_page_by_path('groepsreizen/last-minute-groepsreizen');
                        if (!empty($pageLink)): ?>
                            <div class="button-wrapper text-center">
                                <a href="<?= get_the_permalink($pageLink); ?>"
                                   class="btn btn--transparant btn--padding btn--arrow-white">Meer lastminute reizen</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="no-results-found no-results-found--no-padding">
                            <h2>Helaas zijn er momenteel geen lastminute reizen beschikbaar</h2>
                        </div>
                        <?php $allTripsPage = get_page_by_path('groepsreizen/alle-reizen');
                        if (!empty($allTripsPage)): ?>
                            <div class="button-wrapper text-center">
                                <a href="<?= get_the_permalink($allTripsPage); ?>"
                                   class="btn btn--transparant btn--padding btn--arrow-white">Alle reizen</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </section>
        <?php elseif (get_row_layout() === 'trip_type'): ?>
            <?php $typeTerm = get_sub_field('type');
            $amount = get_sub_field('amount');
            $pageLink = get_sub_field('page_link');
            if (!empty($typeTerm) && !empty($amount)): ?>
                <section class="trip-blocks">
                    <div class="title-wrapper padding-bottom">
                        <h2 class="page-title-border large"><?= $typeTerm->name; ?></h2>
                    </div>
                    <div class="clearfix"></div>
                    <div class="container">
                        <?php
                        $templates = Template::get_by_terms('trip-type', [$typeTerm->term_id], $amount);
                        if (empty($templates)):
                            $templates = Template::get_alternatives($typeTerm->term_id, $amount);
                            if (!empty($templates)): ?>
                                <div class="no-results-found no-results-found--no-padding">
                                    <h2>Helaas zijn er momenteel geen <?= $typeTerm->name; ?> beschikbaar</h2>
                                    <h2>Hier zijn wat leuke alternatieven:</h2>
                                </div>
                            <?php endif;
                        endif;
                        if (!empty($templates)):
                            foreach ($templates as $post):
                                setup_postdata($post);
                                get_template_part('templates/content-product');
                            endforeach;
                            wp_reset_postdata();
                            if (!empty($pageLink)): ?>
                                <div class="button-wrapper text-center">
                                    <a href="<?= $pageLink; ?>"
                                       class="btn btn--transparant btn--padding btn--arrow-white">Meer <?= $typeTerm->name; ?></a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="no-results-found no-results-found--no-padding">
                                <h2>Helaas zijn er momenteel geen <?= $typeTerm->name; ?> beschikbaar</h2>
                            </div>
                            <?php $allTripsPage = get_page_by_path('groepsreizen/alle-reizen');
                            if (!empty($allTripsPage)): ?>
                                <div class="button-wrapper text-center">
                                    <a href="<?= get_the_permalink($allTripsPage); ?>"
                                       class="btn btn--transparant btn--padding btn--arrow-white">Alle reizen</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    <?php endwhile; ?>
<?php endif; ?>

<section class="home-hero second">
	<?php $home_header_background = get_field( 'home_header_background' ); ?>
	<div class="home-hero__container home-hero__container--second" style="background-image: url(<?= wp_get_attachment_image_url( $home_header_background, 'full' ); ?>);">
		<div class="container">
			<div class="hero-title">
				<h2><?php the_field( 'home_header_title' ); ?></h2>
				<span class="subtitle"><?php the_field( 'home_header_subtitle' ); ?></span>
			</div>
		</div>
	</div>
</section>

<?php if (have_rows('option_customers_say', 'option')) : ?>
    <section class="customers-say">
        <div class="container">
            <div class="title-wrapper padding-bottom">
                <h2 class="page-title-border large">Klanten zeggen</h2>
            </div>
            <?php while (have_rows('option_customers_say', 'option')) : the_row();
                $image = wp_get_attachment_image_src(get_sub_field('customers_say_image'), 'large')[0]; ?>
                <div class="block">
                    <div class="inner">
                        <h3>"<?php the_sub_field('customers_say_quote'); ?>"</h3>
                        <img src="<?= $image; ?>" alt="<?= $image; ?>">
                        <p class="name"><?php the_sub_field('customers_say_name'); ?></p>
                        <p class="quote">"<?php the_sub_field('customers_say_extra_quote'); ?>"</p>
                        <p><?php the_sub_field('customers_say_text'); ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
<?php endif; ?>

<?php if (have_rows('known_from_list', 'option')) : ?>
    <section class="known-row bg-grey row-padding">
        <div class="container">
            <div class="title-wrapper">
                <h3 class="page-title-border">Bekend van</h3>
            </div>
            <ul>
                <?php while (have_rows('known_from_list', 'option')) : the_row(); ?>
                    <?php $known_from_item = get_sub_field('known_from_item'); ?>
                    <li>
                        <img src="<?php echo wp_get_attachment_image_url($known_from_item, 'medium'); ?>"
                             alt="<?php the_sub_field('known_from_item_alt'); ?>">
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </section>
<?php endif; ?>
