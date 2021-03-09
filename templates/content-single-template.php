<?php

use lib\controllers\Template;
use Roots\Sage\Assets;

// Template data
$fields = get_fields();
$bannerImage = $fields['template_banner_image'];
$headerSubtitle = $fields['template_subtitle'];
$numberOfDaysRange = Template::get_num_days_range();
$lowestPrice = Template::get_lowest_price();
$youtubeURL = $fields['template_youtube_url'];
preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/", $youtubeURL, $matches);
$youtubeCode = !empty($matches[1]) ? trim($matches[1]) : '';
$googleMapsURL = $fields['template_map_url'];
$productImages = $fields['template_product_images'];
if (!empty($productImages)) {
    usort($productImages, function ($a, $b) {
        if ($a['index'] === $b['index']) return 0;
        return $a['index'] < $b['index'] ? -1 : 1;
    });
}
$highlights = $fields['template_highlights'];
$importantInformation = $fields['template_important_information'];
$packItems = $fields['template_pack_items'];
$productDays = $fields['template_product_days'];
if (!empty($productDays)) {
    usort($productDays, function ($a, $b) {
        if ($a['index'] === $b['index']) return 0;
        return $a['index'] < $b['index'] ? -1 : 1;
    });
}
$included = $fields['template_included_items'];
$excluded = $fields['template_excluded_items'];
// Taxonomies
$tripTypeTerms = wp_get_post_terms(get_the_ID(), "trip-type");
$primaryTermObject = new WPSEO_Primary_Term('destination', get_the_ID());
$primaryDestinationTermId = $primaryTermObject->get_primary_term();
if (!empty($primaryDestinationTermId)) {
    $primaryDestination = get_term_by('id', $primaryDestinationTermId, 'destination');
}
global $post;
$trips = Template::get_trips();
$insurances = Template::get_insurances();
// Ratings
global $ratings;
$ratings = isset($fields['template_ratings']) ? $fields['template_ratings'] : [];
$ratingsTotal = get_field('ratings_total', 'options');
$ratingsMean = get_field('ratings_mean', 'options');
// Accommodations
$accommodations = Template::get_accommodations(get_the_ID());
$templateAssumaxId = get_post_meta(get_the_ID(), '_assumax_id', true);
?>
<script type="text/javascript">
    jQuery( document ).ready(function() {
        waitForFbq(function () {
            fbq('track', 'ViewContent', {
                content_ids: ['<?= $templateAssumaxId; ?>'],
                content_type: 'product_group',
                value: <?= $lowestPrice; ?>,
                currency: 'EUR'
            });
        });
    });
</script>

<div data-postid="<?php the_ID(); ?>" id="product-<?php the_ID(); ?>" <?php post_class(); ?>
     xmlns="http://www.w3.org/1999/html">
    <div class="single-trip">
        <div class="container">
            <section class="single-trip__slider" style="width: 100%;">
                <div class="product-slider">
                    <div class="dotscontainer">
                        <div class="inner"></div>
                    </div>
                    <?php if (!empty($bannerImage)): ?>
                        <div class="product-slider__slide"
                             style="background-image: url('<?= wp_get_attachment_image_url($bannerImage, 'product-slider'); ?>');"></div>
                    <?php else : ?>
                        <div class="product-slider__slide"
                             style="background-image: url('<?= get_stylesheet_directory_uri(); ?>/dist/images/page-header-default.jpg');"></div>
                    <?php endif; ?>
                </div>
                <div class="product-slider-container">
                    <div class="container page-header-image"></div>
                </div>
            </section>
        </div>

        <section class="single-trip__travel-summary">
            <div class="container">
                <div class="trip-summary-container bg-yellow">
                    <div class="trip-title">
                        <h1 class=""><?php the_title(); ?></h1>
                    </div>
                    <div class="summary-column">
                        <h2 class="summary-column__title"><?php the_field('template_slogan'); ?></h2>
                        <span class="summary-column__subtitle"><?php the_field('template_subtitle2'); ?></span>
                        <?php if (!empty($ratings)): ?>
                            <?php get_template_part('templates/modules/ratings-total'); ?>
                            <?php if (!empty($ratingsTotal) && !empty($ratingsMean)): ?>
                                <script type="application/ld+json">
                                {
                                  "@context": "http://schema.org",
                                  "@type": "Product",
                                  "aggregateRating": {
                                    "@type": "AggregateRating",
                                    "bestRating": "5",
                                    "ratingCount": "<?= number_format((float)$ratingsTotal, 0); ?>",
                                    "ratingValue": "<?= number_format((float)$ratingsMean, 1); ?>"
                                  },
                                   "name": "<?php the_title(); ?>"
                                }

                                </script>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="summary-column summary-column--no-mp">
                        <table>
                            <tbody>
                            <tr>
                                <td>
                                    <?php if (!empty($tripTypeTerms)): ?>
                                        <?php foreach ($tripTypeTerms as $term): ?>
                                            <a href="<?= get_field('category_link', 'trip-type_'.$term->term_id); ?>">
                                            <?= $term->name; ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($primaryDestination)): ?>
                                        <a href="<?= get_term_link($primaryDestination); ?>">
                                        <span><img src="<?= Assets\asset_path('images/icon-pointer.png'); ?>"
                                                   alt="icon locatie alleen op vakantie"></span> <?= $primaryDestination->name; ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <?php if (!empty($numberOfDaysRange)): ?>
                                        <span><img src="<?= Assets\asset_path('images/icon-time.png'); ?>"
                                                   alt="icon single reizen last minute"></span> <?= $numberOfDaysRange; ?> dagen
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span><img src="<?= Assets\asset_path('images/icon-age.png'); ?>"
                                               alt="icon leeftijd single reizen"></span> 20+
                                    </a>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="summary-column__price">
                            <span>Prijs vanaf: </span><span><b><?= !empty($lowestPrice) ? "&euro;{$lowestPrice},-" : "-,-"; ?></b></span>
                            <a href="#" class="booking-costs"><i class="fas fa-info-circle"></i></a>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <a href="#" class="btn btn--pink btn--arrow-blue scroll-to-dates">Boek deze reis</a>
                </div>
            </div>
        </section>
        <section class="single-trip__introduction">
            <div class="container">
                <div class="introduction-inner">
                    <h2 class="regular-title"><?php the_field('template_subtitle'); ?></h2>
                    <p><?= get_the_content(); ?></p>
                </div>
                <div class="clearfix"></div>
            </div>
        </section>

        <?php if (!empty($highlights)): ?>
            <section class="single-trip__highlights" id="route_beschrijving">
                <div class="container border-grey">
                    <div class="left">
                        <h2 class="regular-title">Hoogtepunten van deze reis</h2>
                        <ul>
                            <?php foreach ($highlights as $highlight): ?>
                                <li><?= $highlight['title']; ?></li>
                            <?php endforeach; ?>
                            <div class="clearfix"></div>
                            <a class="btn btn--yellow btn--arrow-blue scroll-to-program" href="#">Bekijk het
                                programma</a>
                        </ul>
                    </div>
                    <div class="right route-map" data-map-url="<?= $googleMapsURL; ?>">
                        <?php if (!empty($googleMapsURL)): ?>
                            <div class="iframe-wrap-map"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="row-padding">
            <div class="container">
                <div class="tab-images images-introduction">
                    <ul>
                        <?php
                        if (!empty($productImages)):
                            $previewImages = array_slice($productImages, 0, !empty($youtubeURL) ? 3 : 4);
                            if (!empty($youtubeURL)): ?>
                                <li>
                                    <a href="#youtube_modal"
                                       style="background: url('https://img.youtube.com/vi/<?= $youtubeCode; ?>/hqdefault.jpg') center center;background-size: cover; ">
                                        <img
                                                src="<?= wp_get_attachment_image_url($previewImages[0]['image'], 'woocommerce_thumbnail'); ?>"
                                                alt="" style="visibility: hidden;">
                                        <img src="<?= Assets\asset_path('images/Playknop_noSun.svg'); ?>" alt="Afspelen"
                                             class="play-button">
                                    </a>
                                    <div class="remodal" data-remodal-id="youtube_modal">
                                        <button data-remodal-action="close" class="remodal-close"></button>
                                        <style type="text/css">
                                            .videoWrapper {
                                                position: relative;
                                                padding-bottom: 56.25%; /* 16:9 */
                                                padding-top: 25px;
                                                height: 0;
                                            }

                                            .videoWrapper iframe {
                                                position: absolute;
                                                top: 0;
                                                left: 0;
                                                width: 100%;
                                                height: 100%;
                                            }
                                        </style>
                                        <div class="videoWrapper">
                                            <iframe width="560" height="349"
                                                    src="http://www.youtube.com/embed/<?= $youtubeCode; ?>?rel=0&hd=1"
                                                    allowfullscreen></iframe>
                                        </div>
                                    </div>
                                </li>
                            <?php endif; ?>
                            <?php foreach ($previewImages as $image): ?>
                            <li>
                                <a href="<?= wp_get_attachment_image_url($image['image'], 'full'); ?>"
                                   data-lightbox="images"><img
                                            src="<?= wp_get_attachment_image_url($image['image'], 'woocommerce_thumbnail'); ?>"
                                            alt="<?= get_post_meta($image['image'], '_wp_attachment_image_alt', true); ?>"></a>
                            </li>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </section>

        <section class="bg-grey row-padding">
            <div class="container">
                <h2 class="page-title-border lowercase">noSun garanties</h2>
                <?php get_template_part('templates/modules/guarantees'); ?>
            </div>
        </section>

        <div class="trip-menu-container">
            <div class="trip-menu" id="tripMenu">
                <div class="container">
                    <ul>
                        <?php if (!empty($youtubeURL)): ?>
                            <li><a href="#anchorVideo">Video</a></li>
                        <?php endif; ?>
                        <?php if (!empty($productDays)): ?>
                            <li><a href="#anchorProgram">Programma</a></li>
                        <?php endif; ?>
                        <?php if (!empty($accommodations)): ?>
                            <li><a href="#anchorAccommodations">Accommodaties</a></li>
                        <?php endif; ?>
                        <?php if (!empty($productImages)): ?>
                            <li><a href="#anchorPhotos">Foto's</a></li>
                        <?php endif; ?>
                        <?php if (!empty($insurances)): ?>
                            <li><a href="#anchorInsurances">Verzekeringen</a></li>
                        <?php endif; ?>
                        <?php if (!empty($packItems)): ?>
                            <li><a href="#anchorBring">Wat neem je mee?</a></li>
                        <?php endif; ?>
                        <li><a href="#anchorDates">Vertrekdata</a></li>
                        <li class="li--book"><a href="#anchorDates">Boek reis</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <?php if (!empty($youtubeURL)): ?>
            <section class="trip-video" id="anchorVideo">
                <div class="container">
                    <h2 class="page-title-border lowercase">Videoimpressie <?php the_title(); ?></h2>
                    <p>
                        Eigenaar Martijn Boshuis (noBoss) test af en toe zijn eigen reizen, en maakt er dan een vlog
                        over.<br/>Hij heeft ook zijn eigen drone bij zich. Omdat het kan. Hieronder het filmpje:
                    </p>
                    <iframe width="560" height="349" src="http://www.youtube.com/embed/<?= $youtubeCode; ?>?rel=0&hd=1"
                            frameborder="0" allowfullscreen></iframe>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($productDays)): ?>
            <section class="trip-program-container" id="anchorProgram">
                <div class="container">
                    <div class="title-wrapper padding-bottom">
                        <h2 class="page-title-border large">Programma</h2>
                    </div>
                    <div class="clearfix"></div>
                    <ul class="trip-program">
                        <?php $dayCounter = 1; ?>
                        <?php foreach ($productDays as $index => $productDay): ?>
                            <li>
                                <div class="trip-program__image">
                                    <?= wp_get_attachment_image($productDay['image'], 'medium'); ?>
                                </div>
                                <div class="trip-program__content">
                                    <?php if (empty($productDay['days']) || intval($productDay['days']) === 1): ?>
                                        <span class="date">Dag <?= $dayCounter; ?></span>
                                        <?php $dayCounter++; ?>
                                    <?php else: ?>
                                        <span class="date">Dag <?= $dayCounter; ?> - <?= $dayCounter + intval($productDay['days']) - 1; ?></span>
                                        <?php $dayCounter += intval($productDay['days']); ?>
                                    <?php endif; ?>
                                    <span class="title"><?= $productDay['title']; ?></span>
                                    <?= $productDay['description']; ?>
                                </div>
                                <div class="clearfix"></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        <?php endif; ?>

        <section class="trip-book-cta container bg-yellow">
            <div class="text">
                <h2 class="page-title-border lowercase">Ik ga graag mee met <?php the_title(); ?></h2>
                <p>
                    <?php the_field('template_subtitle2'); ?>
                </p>
            </div>
            <div class="clearfix"></div>
            <a href="#" class="btn btn--pink btn--arrow-blue scroll-to-dates">Boek deze reis</a>
        </section>

        <?php if (!empty($accommodations)):
            global $accommodation; ?>
        <section class="trip-accommodation-container" id="anchorAccommodations">
            <div class="container">
                <div class="title-wrapper padding-bottom">
                    <h2 class="page-title-border large">Accommodaties</h2>
                </div>
                <div class="clearfix"></div>
                <?php foreach ($accommodations as $accommodation):
                    get_template_part('templates/modules/accommodations');
                endforeach; ?>
                <div class="clearfix"></div>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($productImages)): ?>
            <section class="trip-book-images bg-grey" id="anchorPhotos">
                <div class="container">
                    <div class="title-wrapper padding-bottom">
                        <h2 class="page-title-border large">Foto's</h2>
                    </div>
                    <ul>
                        <?php foreach ($productImages as $productImage): ?>
                            <?php if (empty($productImage['image'])) continue; ?>
                            <li>
                                <a href="<?= wp_get_attachment_image_url($productImage['image'], 'full'); ?>"
                                   data-lightbox="images"><img
                                            src="<?= wp_get_attachment_image_url($productImage['image'], 'woocommerce_thumbnail'); ?>"
                                            alt="<?= get_post_meta($productImage['image'], '_wp_attachment_image_alt', true); ?>">
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($insurances)): ?>
            <section class="trip-insurance-container" id="anchorInsurances">
                <div class="container">
                    <h2 class="page-title-border lowercase">Verzekeringen</h2>
                    <div class="col-10">
                        <?php foreach ($insurances as $insurance): ?>
                            <?php if (!isset($insurance['title']) || !isset($insurance['description'])) continue; ?>
                            <span class="title"><?= $insurance['title']; ?></span>
                            <p>
                                <?= $insurance['description']; ?>
                            </p>
                            <div class="clearfix"></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($packItems)): ?>
            <section class="trip-bring-container" id="anchorBring">
                <div class="container">
                    <h2 class="page-title-border lowercase">Wat neem je mee?</h2>
                    <div class="col-10">
                        <ul class="column">
                            <?php foreach ($packItems as $packItem): ?>
                                <li><?= $packItem['title']; ?></li>
                            <?php endforeach; ?>
                            <div class="clearfix"></div>
                        </ul>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($importantInformation)): ?>
            <section class="trip-bring-container" id="anchorImportantInformation">
                <div class="container">
                    <h2 class="page-title-border lowercase">Belangrijke informatie</h2>
                    <div class="col-10">
                        <p><?= $importantInformation; ?></p>
                        <div class="clearfix"></div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="single-trip__data bg-grey" id="anchorDates">
            <div class="container">
                <div class="data-filters" style="min-height: 100px;">
                    <h2 class="page-title-border">Vertrekdata</h2>
                    <p>Rangschikken op:</p>
                    <ul>
                        <li><a href="#" class="active" data-sort-value="original-order">Alle</a></li>
                        <li><a href="#" data-sort-value="available">Beschikbaar</a></li>
                        <li><a href="#" data-sort-value="price">Prijs laag/hoog</a></li>
                        <li><a href="#" data-sort-value="guaranteed">Gegarandeerd vertrek</a></li>
                    </ul>
                </div>
                <div class="clearfix"></div>

                <div class="data-grid">
                    <?php if (empty($trips)): ?>
                        <div class="no-results-found no-results-found--no-padding">
                            <h2>Geen beschikbare vertrekdata gevonden</h2>
                        </div>
                    <?php endif;
                    foreach ($trips as $trip) {
                        $post = $trip;
                        setup_postdata($post);
                        get_template_part('templates/modules/trip');
                        wp_reset_postdata();
                    }
                    ?>
                </div>
            </div>
        </section>

        <section class="single-trip__to-bring">
            <h2 class="regular-title">Reisinformatie wel/niet inbegrepen</h2>
            <div class="column">
                <?php if (!empty($included)): ?>
                    <ul>
                        <li><b>Wel inbegrepen:</b></li>
                        <?php foreach ($included as $item): ?>
                            <li>
                                <?= $item['title']; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a class="show-more">Toon meer <i class="fas fa-chevron-down"></i></a>
                <?php endif; ?>
            </div>
            <div class="border">
                <div class="inner"></div>
            </div>
            <div class="column">
                <?php if (!empty($excluded)): ?>
                    <ul>
                        <li><b>Niet inbegrepen:</b></li>
                        <?php foreach ($excluded as $item): ?>
                            <li>
                                <?= $item['title']; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a class="show-more">Toon meer <i class="fas fa-chevron-down"></i></a>
                <?php endif; ?>
            </div>
        </section>

        <?php get_template_part('templates/modules/ratings'); ?>

        <section class="trip-book-cta bg-yellow">
            <div class="container">
                <div class="text">
                    <h2 class="page-title-border lowercase">Ik ga graag mee met <?php the_title(); ?></h2>
                    <p>
                        <?php the_field('template_subtitle2'); ?>
                    </p>
                </div>
                <div class="clearfix"></div>
                <a href="#" class="btn btn--pink btn--arrow-blue scroll-to-dates">Boek deze reis</a>
            </div>
        </section>

        <?php get_template_part('templates/modules/popular-products'); ?>
        <?php get_template_part('templates/modules/usps'); ?>
    </div>
</div>

<script>
    jQuery(document).ready(function(){

        if(jQuery('#tripMenu').length > 0) {
            var eTop = jQuery( '#tripMenu' ).offset().top; //get the offset top of the element
            jQuery( window ).scroll( function () { //when window is scrolled
                if ( jQuery( this ).scrollTop() > (eTop + 50) ) {
                    jQuery( '#tripMenu' ).addClass( 'fixed' );
                } else {
                    jQuery( '#tripMenu' ).removeClass( 'fixed' );
                }
            } );
        }

        jQuery('#tripMenu a').on('click', function(event) {

            var target = jQuery(jQuery(this).attr('href'));
            if( target.length ) {
                event.preventDefault();
                jQuery('html, body').animate({
                    scrollTop: target.offset().top - 85
                }, 500);
            }
        });
    });
</script>
