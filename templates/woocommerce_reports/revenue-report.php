<h1>Omzet</h1>
<?php get_template_part('templates/woocommerce_reports/partials/errors'); ?>
<form method="post">
    <?php get_template_part('templates/woocommerce_reports/partials/filter', 'week'); ?>
    <?php get_template_part('templates/woocommerce_reports/partials/filter', 'growpercentage'); ?>
    <input class="button btn" type="submit" value="Filteren">
    <?php wp_nonce_field( 'revenue-report', 'report-nonce' ); ?>
</form>
<hr>
<h2>Statistieken</h2>
<table class="wp-list-table widefat fixed striped">
    <tbody>
    <tr>
        <th>Doel omzet</th>
        <td>&euro; <?= number_format($targetRevenue / 100.0, 2 ,",", "."); ?></td>
    </tr>
    <tr>
        <th>Omzet verschil tegen vorig jaar</th>
        <td>&euro; <?= number_format($revenueDifference / 100.0, 2 ,",", "."); ?></td>
    </tr>
    <tr>
        <th>Huidige groeipercentage</th>
        <td><?= number_format($currentGrowth, 1 ,",", "."); ?>%</td>
    </tr>
    <tr>
        <th>Gemiddelde boeking omzet</th>
        <td>&euro; <?= number_format($averageBookingRevenue / 100.0, 2 ,",", "."); ?></td>
    </tr>
    <tr>
        <th>Benodigde omzet</th>
        <td>&euro; <?= number_format($revenueNeeded / 100.0, 2 ,",", "."); ?></td>
    </tr>
    <tr>
        <th>Aantal boekingen nodig voor doel</th>
        <td><?= ceil($bookingsNeeded);?></td>
    </tr>
    </tbody>
</table>
<hr>
<?php if(!empty($dataPerYear)):
    foreach($dataPerYear as $year => $data): ?>
    <h2><?= $year; ?> - Totale omzet: &euro; <?= number_format($revenuePerYear[$year] / 100.0, 2, ",", "."); ?> - Totaal aantal boekingen: <?= $bookingsPerYear[$year]; ?></h2>
    <table class="wp-list-table widefat fixed striped sortable">
        <thead>
        <tr>
            <th>Template Id</th>
            <th>Titel</th>
            <th>Aantal Boekingen</th>
            <th>Omzet (&euro; EUR)</th>
            <th>Groeipercentage</th>
        </tr>
        </thead>
        <tbody>
            <?php if (!empty($data)): foreach ($data as $element): ?>
            <tr>
                <td><?= $element["tripReport"]->TemplateId; ?></td>
                <td><?= $element["tripReport"]->Title; ?></td>
                <td><?= $element["nBookings"]; ?></td>
                <td><?= !empty($element["revenue"]) ? number_format($element["revenue"] / 100.0, 2, ",", "") : "0,-"; ?></td>
                <td><?= number_format($element["growPercentage"], 2, ",", ""); ?>%</td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    <br>
<?php endforeach; endif; ?>
