<?php
/**
 * Template Name: Zoeken
 */

use lib\controllers\Template;
use Roots\Sage\FilterData;

$selectedDestinations = FilterData::get_selected_destinations();
$selectedTypes = FilterData::get_selected_types();
$selectedKeywords = $_GET['zoekterm'] ?? '';
$selectedDateRange = FilterData::get_selected_date_range();
$dateRangeFilter = '';
if (!empty($selectedDateRange)) {
    $startDate = $selectedDateRange['start_date']->format('d-m-Y');
    $endDate = $selectedDateRange['end_date']->format('d-m-Y');
    $dateRangeFilter = "{$startDate} - {$endDate}";
}
$templatePostIds = FilterData::handle_search_query_vars();
$numTemplates = count($templatePostIds);
?>
<div class="container">
    <?php get_template_part('templates/modules/search-form'); ?>
    <div class="search-filters">
        <div class="inner">
            <div class="search-filters__terms">
                <?php if (!empty($selectedKeywords) || !empty($selectedDestinations) || !empty($selectedTypes) || !empty($selectedDateRange)): ?>
                    <span class="terms-title">Zoektermen: </span>
                <?php endif; ?>
                <ul>
                    <?php if (!empty($selectedKeywords)): ?>
                        <li>
                            <a href="<?= FilterData::make_new_filter_link('', '', true); ?>">
                                <span class="btn btn--yellow filterInput"><?= $selectedKeywords; ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($selectedDestinations)):
                        foreach ($selectedDestinations as $selectedDestination): ?>
                            <li>
                                <a href="<?= FilterData::make_new_filter_link($selectedDestination); ?>">
                                    <span class="btn btn--yellow filterDestination"
                                          data-destinationterm="<?= $selectedDestination; ?>"><?= $selectedDestination; ?></span>
                                </a>
                            </li>
                        <?php endforeach;
                    endif; ?>
                    <?php if (!empty($selectedTypes)):
                        foreach ($selectedTypes as $selectedType): ?>
                            <li>
                                <a href="<?= FilterData::make_new_filter_link('', $selectedType); ?>">
                                    <span class="btn btn--yellow filterTerm"
                                          data-typeterm="<?= $selectedType; ?>"><?= $selectedType; ?></span>
                                </a>
                            </li>
                        <?php endforeach;
                    endif; ?>
                    <?php if (!empty($dateRangeFilter)): ?>
                        <li>
                            <a href="<?= FilterData::make_new_filter_link('', '', false, true); ?>">
                                <span class="btn btn--yellow filterDateRange"><?= $dateRangeFilter; ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="clearfix"></div>
            <div class="border"></div>
            <?php
            if ($numTemplates === 1):
                $resultText = "1 reis gevonden";
            elseif ($numTemplates === 0):
                $resultText = "Geen reizen gevonden";
            else:
                $resultText = "{$numTemplates} reizen gevonden";
            endif;
            ?>
            <span class="search-filters__count"><?= $resultText; ?></span>
            <div class="search-filters__filters">
                <span class="filters-title">Rangschikken op: </span>
                <ul>
                    <li><span class="btn btn--blue active">Meest populair</span></li>
                    <li><span class="btn btn--blue">Prijs hoog/laag</span></li>
                    <li><span class="btn btn--blue">Best beoordeeld</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
if (!empty($templatePostIds)):
    global $post; ?>
    <div class="trip-blocks">
        <div class="container">
            <?php             foreach ($templatePostIds as $postId):
                $post = get_post($postId);
                setup_postdata($post);
                get_template_part('templates/content-product');
            endforeach;
            wp_reset_postdata();
            ?>
        </div>
    </div>
<?php else: ?>
    <div class="container">
        <div class="no-results-found">
            <h2>Probeer de filter aan te passen.</h2>
        </div>
    </div>
<?php endif; ?>
<div class="clearfix"></div>
<?php get_template_part( 'templates/modules/usps' ); ?>
