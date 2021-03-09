<?php
use Roots\Sage\Assets;
if (is_single() && !empty(get_field('template_usps'))):
    $usps = get_field('template_usps');
    shuffle($usps);
    $usps = array_slice($usps, 0, 4); ?>
    <section class="usps">
        <div class="container">
            <?php foreach ($usps as $usp): ?>
                <div class="usp">
                    <div class="usp__inner">
                        <img src="<?= Assets\asset_path('images/icon-check.png'); ?>" alt="">
                        <span><?= $usp['title']; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php else:
    if (have_rows('option_usps', 'option')) : ?>
        <section class="usps">
            <div class="container">
                <?php while (have_rows('option_usps', 'option')) : the_row(); ?>
                    <div class="usp">
                        <div class="usp__inner">
                            <img src="<?= Assets\asset_path('images/icon-check.png'); ?>"
                                 alt="icon <?php the_sub_field('option_usps_usp'); ?>">
                            <span><?php the_sub_field('option_usps_usp'); ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>
