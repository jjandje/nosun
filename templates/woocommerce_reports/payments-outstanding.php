<h1>Openstaande Betalingen</h1>
<?php get_template_part('templates/woocommerce_reports/partials/errors'); ?>
<form method="post">
    <?php get_template_part('templates/woocommerce_reports/partials/filter', 'bookingdate'); ?>
    <input class="button btn" type="submit" value="Filteren">
    <?php wp_nonce_field( 'payments-outstanding', 'report-nonce' ); ?>
</form>
<hr>
<h2>Totaal factuurwaardes: &euro; <?= number_format($totalInvoiceAmount, 2, ",", ""); ?></h2>
<h2>Totaal betaald: &euro; <?= number_format($totalPaidAmount, 2, ",", ""); ?></h2>
<h2>Totaal openstaand: &euro; <?= number_format($totalOutstandingAmount, 2, ",", ""); ?></h2>
<table class="wp-list-table widefat fixed striped sortable">
    <thead>
        <tr>
            <th>Boeking Assumax Id</th>
            <th>Reis</th>
            <th>Klant Assumax Id</th>
            <th>Klant Naam</th>
            <th>Factuurwaarde (&euro; EUR)</th>
            <th>Betaald (&euro; EUR)</th>
            <th>Openstaand (&euro; EUR)</th>
            <th>Uiterlijke betaaldatum</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($outstandingBookings as $outstandingBooking): ?>
        <tr>
            <td><?= $outstandingBooking->NosunId;?></td>
            <td><?= $outstandingBooking->Trip?></td>
            <?php if(isset($outstandingBooking->Customer)): ?>
                <td><?= $outstandingBooking->Customer->NosunId;?></td>
                <td><?= $outstandingBooking->Customer->FirstName . " " . $outstandingBooking->Customer->LastName;?></td>
            <?php endif;?>
            <td><?= number_format($outstandingBooking->InvoiceAmount, 2, ",", "");?></td>
            <td><?= number_format($outstandingBooking->PaymentAmount, 2, ",", "");?></td>
            <td><?= number_format($outstandingBooking->InvoiceAmount - $outstandingBooking->PaymentAmount, 2, ",", "");?></td>
            <td><?= date("d-m-Y", $outstandingBooking->PaymentDeadline)?></td>
        </tr>
    <?php endforeach;?>
    </tbody>
</table>