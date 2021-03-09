<?php
if (function_exists('yoast_breadcrumb')) : ?>
    <div class="container">
        <div class="breadcrumb-wrapper">
            <?php yoast_breadcrumb('<p id="breadcrumbs">', '</p>'); ?>
        </div>
    </div>
<?php endif; ?>
<div class="tourguide-wrapper">
    <h1 class="tourguide-wrapper__title page-title-border"><?= get_field('tourguide_nickname'); ?></h1>
    <div class="tourguide-wrapper__about">
        <div class="container">
            <?php $tourguide_images = get_field('tourguide_images'); ?>
            <?php if (!empty($tourguide_images)): ?>
                <img src="<?php echo wp_get_attachment_image_url($tourguide_images[0]['image'], 'popular-trip-thumb'); ?>"
                     class="tourguide-wrapper__image" alt="<?= get_the_title(); ?>">
            <?php endif; ?>
            <div class="clearfix"></div>
            <div class="tourguide-wrapper__container">
                <h2 class="tourguide-wrapper__slogan">
                    <?php the_field('tourguide_slogan'); ?>
                </h2>
                <?php the_field('tourguide_text'); ?>
            </div>
            <ul class="tourguide-wrapper__column">
                <?php if (get_field('tourguide_birth_city')): ?>
                    <li><span class="tourguide-wrapper__property">Geboren in</span><span
                                class="tourguide-wrapper__value"><?php the_field('tourguide_birth_city'); ?></span></li>
                <?php endif; ?>
                <?php if (get_field('tourguide_city')): ?>
                    <li><span class="tourguide-wrapper__property">Woont in</span><span
                                class="tourguide-wrapper__value"><?php the_field('tourguide_city'); ?></span></li>
                <?php endif; ?>
                <?php if (get_field('tourguide_since')): ?>
                    <li><span class="tourguide-wrapper__property">Begeleider sinds</span><span
                                class="tourguide-wrapper__value"><?php the_field('tourguide_since'); ?></span></li>
                <?php endif; ?>
                <?php if (get_field('tourguide_hobbies')): ?>
                    <li><span class="tourguide-wrapper__property">Hobby's</span><span
                                class="tourguide-wrapper__value"><?php the_field('tourguide_hobbies'); ?></span></li>
                <?php endif; ?>
            </ul>
            <ul class="tourguide-wrapper__column">
                <?php if (get_field('tourguide_guideplace')): ?>
                    <li><span class="tourguide-wrapper__property">Begeleid in</span><span
                                class="tourguide-wrapper__value"><?php the_field('tourguide_guideplace'); ?></span></li>
                <?php endif; ?>
                <?php if (get_field('tourguide_favorite')): ?>
                    <li><span class="tourguide-wrapper__property">Favoriete bestemming</span><span
                                class="tourguide-wrapper__value"><?php the_field('tourguide_favorite'); ?></span></li>
                <?php endif; ?>
                <?php if (get_field('tourguide_nosun')): ?>
                    <li><span class="tourguide-wrapper__property">Waarom noSun</span><span
                                class="tourguide-wrapper__value"><?php the_field('tourguide_nosun'); ?></span></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

