<h1>Planning</h1>
<?php get_template_part('templates/woocommerce_reports/partials/errors'); ?>
<form method="post">
    <?php get_template_part('templates/woocommerce_reports/partials/filter', 'tripdate'); ?>
    <?php get_template_part('templates/woocommerce_reports/partials/filter', 'tourguide'); ?>
    <input class="button btn" type="submit" value="Filteren">
    <?php wp_nonce_field( 'tourguides-schedule', 'report-nonce' ); ?>
</form>
<hr>
<?php if(!empty($guides)):
    foreach($guides as $guide => $trips): ?>
    <h2><?= $guide ?></h2>
    <table class="wp-list-table widefat fixed striped sortable">
        <thead>
        <tr>
            <th>Reis Assumax Id</th>
            <th>Titel</th>
            <th>Startdatum</th>
            <th>Einddatum</th>
            <th>Aantal dagen</th>
            <th>Samen met</th>
        </tr>
        </thead>
        <tbody>
            <?php if(!empty($trips)): foreach($trips as $trip): ?>
            <tr>
                <td><?= $trip->NosunId ?></td>
                <td><?= $trip->Title ?></td>
                <td><?= date("d-m-Y", strtotime($trip->StartDate))?></td>
                <td><?= date("d-m-Y", strtotime($trip->EndDate))?></td>
                <td><?= $trip->NDays; ?></td>
                <td>
                    <?php                     $jsonGuides = json_decode($trip->Guides);
                    if (!empty($jsonGuides)) {
                        $guideNames = array_column($jsonGuides, 'Name');
                        $extraGuides = array_filter($guideNames, function($value) use ($guide) {
                            return $value !== $guide;
                        });
                        echo(implode(", ", $extraGuides));
                    }
                    ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    <br>
<?php endforeach; endif; ?>