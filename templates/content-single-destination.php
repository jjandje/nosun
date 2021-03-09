<?php use lib\controllers\Template;

get_template_part('templates/page-header'); ?>
<div class="single-destination-wrapper">
    <section class="trip-blocks">
        <div class="container">
            <?php if (get_field('page_title_destinations')) : ?>
                <div class="title-wrapper">
                    <h2 class="regular-title"><?php the_field('page_title_destinations'); ?></h2>
                    <div class="clearfix"></div>
                    <br/>
                </div>
            <?php endif; ?>

            <?php if (get_field('destination_text_top')) : ?>
                <div class="content-wrapper">
                    <?php the_field('destination_text_top'); ?>
                </div>
                <div class="clearfix"></div>
            <?php endif; ?>

            <?php
            $linkedTerm = get_field('destination_term');
            $templatePosts = [];
            if (!empty($linkedTerm)):
                $templatePosts = Template::get_by_terms('destination', [$linkedTerm]);
                if (empty($templatePosts)):
                    $templatePosts = Template::get_alternatives($linkedTerm); ?>
                    <div class="no-results-found no-results-found--no-padding">
                        <h2>Helaas zijn er momenteel geen reizen beschikbaar voor <?= get_the_title(); ?>.</h2>
                        <h2>Hier zijn wat leuke alternatieven.</h2>
                    </div>
                <?php endif;
            endif;

            if (!empty($templatePosts)):
                foreach ($templatePosts as $post):
                    setup_postdata($post);
                    get_template_part('templates/content-product');
                    wp_reset_postdata();
                endforeach;
            else: ?>
                <div class="no-results-found no-results-found--no-padding">
                    <h2>Helaas zijn er momenteel geen reizen beschikbaar voor <?= get_the_title(); ?>.</h2>
                </div>
            <?php endif;
            $allTripsPage = get_page_by_path('groepsreizen/alle-reizen');
            if (!empty($allTripsPage)): // Temporarily hidden on request of noSun. ?>
                <div style="display: none;" class="button-wrapper">
                    <a href="<?= get_the_permalink($allTripsPage); ?>"
                       class="btn btn--pink btn--padding btn--arrow-white">Bekijk alle reizen</a>
                </div>
            <?php endif; ?>
            <script type="text/javascript">
                <?php
                $contentIds = [];
                if (!empty($templatePosts)) {
                    $contentIds = array_column($templatePosts, 'ID');
                }
                ?>
                jQuery(document).ready(function () {
                    waitForFbq(function () {
                        fbq('trackCustom', 'ViewCategory', {
                            content_name: '<? the_field('page_header_image_title'); ?>',
                            content_category: 'Bestemmingen > <?= $post->post_name; ?>',
                            content_ids: ['<?= implode("','", $contentIds); ?>'], // top 5-10 results
                            content_type: 'product'
                        });
                    });
                });
            </script>
        </div>
    </section>

    <?php if (get_field('destination_text_bottom')) : ?>
        <div class="container">
            <div class="content-wrapper">
                <?php the_field('destination_text_bottom'); ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (have_rows('destination_information_tabs')): ?>
        <?php $i = 0;
        $i2 = 0;
        ?>
        <div class="destination-information">
            <div class="container">

                <div class="section-title">
                    <h2 class="page-title-border">Informatie over groepsreizen naar <?php echo get_the_title() ?></h2>
                </div>
                <div class="destination-information__tabs">
                    <ul>
                        <?php while (have_rows('destination_information_tabs')) : the_row(); ?>
                            <?php $i++; ?>
                            <li>
                                <a href="#" class="<?= $i == 1 ? 'active' : ''; ?>"
                                   data-tab="<?= $i; ?>"><?php the_sub_field('destination_information_tabs_title'); ?></a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
                <div class="destination-information__data">
                    <?php while (have_rows('destination_information_tabs')) : the_row(); ?>
                        <?php $i2++; ?>
                        <div class="pane tab-<?= $i2; ?> <?= $i2 == 1 ? 'active' : ''; ?>">
                            <div class="section-title">
                                <h3><?php the_sub_field('destination_information_tabs_title') ?></h3>
                            </div>
                            <?php the_sub_field('destination_information_tabs_content') ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
