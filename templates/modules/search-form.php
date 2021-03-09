<?php
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

?>
<div class="filters">
    <div class="inner">
        <form action="<?= esc_url( home_url( '/zoeken/' ) ); ?>" class="nosun-form" id="searchform" method="get" data-nonce="<?php echo wp_create_nonce('search-form'); ?>">
            <div class="clearfix"></div>
            <ul class="filter">
                <li class="input">
                    <div class="inner">
                        <input type="text" name="zoekterm" autocomplete="off" class="inputS" placeholder="Typ zoekterm..." value="<?= $selectedKeywords ?>">
                    </div>
                </li>

                <?php $destinations = FilterData::get_destinations();
                if ( ! empty( $destinations ) ): ?>
                    <li>
                        <div class="inner">
                            <span class="filter-title">Bestemmingen <span class="filter-title__count" id="countDestinations"></span></span>
                            <ul class="filter-selects two-columns">
                                <?php foreach ( $destinations as $destination ): ?>
                                    <li>
                                        <input type="checkbox"
                                               class="checkDestination"
                                               id="<?= $destination[ 'slug' ]; ?>"
                                               name="bestemming[]"
                                               value="<?= $destination[ 'slug' ]; ?>"
                                               <?php if (in_array($destination['slug'], $selectedDestinations)): echo " checked"; endif; ?>
                                        />
                                        <label for="<?= $destination[ 'slug' ]; ?>"><?= $destination[ 'name' ]; ?></label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </li>
                <?php endif; ?>

                <?php $types = FilterData::get_trip_types();
                if ( ! empty( $types ) ): ?>
                    <li>
                        <div class="inner">
                            <span class="filter-title">Reistype <span class="filter-title__count" id="countTypes"></span></span>
                            <ul class="filter-selects">
                                <?php foreach ( $types as $type ): ?>
                                    <li>
                                        <input type="checkbox"
                                               class="checkType"
                                               id="<?= $type[ 'slug' ]; ?>"
                                               name="type[]"
                                               value="<?= $type[ 'slug' ]; ?>"
                                               <?php if (in_array($type['slug'], $selectedTypes)): echo " checked"; endif; ?>
                                        />
                                        <label for="<?= $type[ 'slug' ]; ?>"><?= $type[ 'name' ]; ?></label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </li>
                <?php endif; ?>

                <li class="input">
                    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
                    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
                    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
                    <div class="inner">
                        <input type="text" name="datum" autocomplete="off" class="inputS" placeholder="Vetrekdatum" value="<?= $dateRangeFilter ?>">
                    </div>
                </li>

                <button type="submit" class="btn btn--pink btn--arrow-white btn--padding" id="searchsubmit">
                    <span class="text">Toon</span> <span class="amount"></span>
                    <span class="text-2">reizen</span>
                    <i class="loading fas fa-sync fa-spin"></i>
                </button>

                <div class="clearfix"></div>
            </ul>
            <button type="reset" class="resetfilters">Filterselectie wissen</button>
            <div class="clearfix"></div>

        </form>
    </div>
</div>
<div class="filter-mobile">
    <a href="<?= site_url( '/zoeken/' ); ?>" class="btn btn--pink btn--arrow-white">Zoek een reis</a>
</div>