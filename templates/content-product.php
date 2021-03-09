<?php

use lib\controllers\Template;

$profileImage = get_field('template_profile_image');
$tripTypeTerms = wp_get_post_terms(get_the_ID(), "trip-type");
$primaryDestination = Template::get_primary_destination(get_the_ID());
$nextDeparture = Template::get_next_departure();
$numberOfDaysRange = Template::get_num_days_range();
$lowestPrice = Template::get_lowest_price();
global $ratings;
$ratings = get_field('template_ratings');
// if (!empty($nextDeparture)): // @since 26-05-2020
?>
<div class="trip-blocks__trip">
    <div class="inner">
        <div class="trip-blocks__image">
            <?php if (get_field('template_map_url')) : ?>
            <a class="route" href="<?= get_permalink() . "#route-map"; ?>"></a><?php endif; ?>
            <a href="<?= get_permalink(); ?>" title="<?= get_the_title(); ?>">
                <img src="<?= wp_get_attachment_image_url($profileImage, 'popular-trip-thumb'); ?>"
                     alt="<?= get_post_meta($profileImage, '_wp_attachment_image_alt', true); ?>">
                    <div class="vertrek-datum">
                	<?php if (!empty($nextDeparture)): ?>
                        <small>Eerstvolgende vertrekdatum</small>
                        <span><?= $nextDeparture->format("d/m/Y") ?></span>
					<?php else : ?>
						<small>Geen vertrekdatum gepland</small>
                	<?php endif; ?>
                    </div>
            </a>
        </div>
        <ul class="trip-blocks__list">
            <?php if (!empty($tripTypeTerms)): ?>
                <li>
                    <img src="<?= Roots\Sage\Assets\asset_path('images/icon-rondreis.png'); ?>"
                         alt="icon singlerondreis"> <?= $tripTypeTerms[0]->name; ?>
                </li>
            <?php endif; ?>
            <?php if (!empty($primaryDestination)): ?>
                <li>
                    <img src="<?= Roots\Sage\Assets\asset_path('images/icon-pointer.png'); ?>"
                         alt="icon locatie alleen op vakantie"> <?= $primaryDestination->name; ?>
                </li>
            <?php endif; ?>
            <?php if (isset($numberOfDaysRange)): ?>
                <li>
                    <img src="<?= Roots\Sage\Assets\asset_path('images/icon-time.png'); ?>"
                         alt="icon single reizen last minute"> <?= $numberOfDaysRange; ?> dagen
                </li>
            <?php endif; ?>
        </ul>
        <div class="trip-blocks__list">
            <span>Vanaf: </span><span><b><?= isset($lowestPrice) ? "&euro;{$lowestPrice},-" : "Geen prijs beschikbaar"; ?></b></span>
            <a href="#" class="booking-costs"><i class="fas fa-info-circle"></i></a>
        </div>
        <a href="<?= get_permalink(); ?>" title="<?= get_the_title(); ?>" class="trip-blocks__title">
            <?= get_the_title(); ?>
        </a>
        <span class="trip-blocks__subtitle"><?= get_field('template_subtitle2'); ?></span>
        <?php if (!empty($ratings)): ?>
            <?php get_template_part('templates/modules/ratings-total'); ?>
        <?php endif; ?>
        <a href="<?= get_the_permalink(); ?>" class="btn btn--pink btn--arrow-white btn--absolute">Bekijk reis</a>
    </div>
</div>
<?php // endif; // @since 26-05-2020 ?>
