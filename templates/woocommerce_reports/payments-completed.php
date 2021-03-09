<h1>Voltooide Betalingen</h1>
<?php get_template_part('templates/woocommerce_reports/partials/errors'); ?>
<form method="post">
    <?php get_template_part('templates/woocommerce_reports/partials/filter', 'paymentdate'); ?>
    <input class="button btn" type="submit" value="Filteren">
    <?php wp_nonce_field( 'payments-completed', 'report-nonce' ); ?>
</form>
<hr>
<h2>Totaal aantal betalingen: <?= !empty($results) ? count($results) : 0; ?></h2>
<h2>Totaal betaald: &euro; <?= number_format($totalPaidAmount / 100.0, 2, ",", ""); ?></h2>
<table class="wp-list-table widefat fixed striped sortable">
    <thead>
    <tr>
        <th>Boeking Assumax Id</th>
        <th>Boekingdatum</th>
        <th>Betaling Assumax Id</th>
        <th>Betaald (&euro; EUR)</th>
        <th>Betaaldatum</th>
    </tr>
    </thead>
    <tbody>
    <?php if(!empty($results)): ?>
    <?php foreach($results as $result): ?>
        <tr>
            <td><?= isset($result->BookingReport) ? $result->BookingReport->NosunId : -1;?></td>
            <td><?= isset($result->BookingReport) ? date("d-m-Y", strtotime($result->BookingReport->BookingDate)) : '';?></td>
            <td><?= $result->NosunId; ?></td>
            <td><?= number_format($result->Amount / 100.0, 2, ",", "");?></td>
            <td><?= date("d-m-Y", strtotime($result->DateTime))?></td>
        </tr>
    <?php endforeach;?>
    <?php endif;?>
    </tbody>
</table>