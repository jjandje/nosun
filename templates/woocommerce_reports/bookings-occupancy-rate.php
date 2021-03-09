<h1>Bezettingsgraad</h1>
<?php get_template_part('templates/woocommerce_reports/partials/errors'); ?>
<form method="post">
    <?php get_template_part('templates/woocommerce_reports/partials/filter', 'tripdate'); ?>
    <?php get_template_part('templates/woocommerce_reports/partials/filter', 'tripid'); ?>
    <input class="button btn" type="submit" value="Filteren">
    <?php wp_nonce_field( 'bookings-occupancy-rate', 'report-nonce' ); ?>
</form>
<hr>
<h2>Bezettingsgraad: <?= number_format($occupancyPercentage, 1); ?>%</h2>
<table class="wp-list-table widefat fixed striped sortable">
    <thead>
    <tr>
        <th>Assumax Id</th>
        <th>Titel</th>
        <th>Startdatum</th>
        <th>Einddatum</th>
        <th>Max # Reizigers</th>
        <th># Reizigers</th>
        <th>Bezettingsgraad</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach($results as $result): /** @var \lib\woocommerce_reports\models\TripReport $result*/?>
        <tr>
            <td><?= $result->NosunId;?></td>
            <td><?= $result->Title?></td>
            <td><?= date("d-m-Y",strtotime($result->StartDate));?></td>
            <td><?= date("d-m-Y",strtotime($result->EndDate));?></td>
            <td><?= $result->NCustomers;?></td>
            <td><?= $result->NEntries;?></td>
            <td><?= number_format($result->NEntries / $result->NCustomers * 100, 1) . "%";?></td>
        </tr>
    <?php endforeach;?>
    </tbody>
</table>
