<?php use Roots\Sage\Assets; ?>
<?php if (have_rows('option_guarantees', 'option')) : ?>
    <section class="usps guarantees">
        <div class="container">
            <?php while (have_rows('option_guarantees', 'option')) : the_row(); ?>
                <div class="usp guarantees">
                    <div class="usp__inner">
                        <img src="<?= Assets\asset_path('images/icon-check.png'); ?>"
                             alt="icon <?php the_sub_field('option_usps_usp'); ?>">
                        <span class="title"><?php the_sub_field('option_guarantees_title'); ?></span>
                        <span><?php the_sub_field('option_guarantees_text'); ?></span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
<?php endif; ?>
