<h1>Update Log</h1>
<?php get_template_part('templates/woocommerce_reports/partials/errors');
if(!empty($updateLogs)): ?>
    <table class="wp-list-table widefat fixed striped sortable">
        <thead>
        <tr>
            <th>Datum</th>
            <th>Type</th>
            <th>Actie</th>
            <th>Assumax Id</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($updateLogs as $updateLog): ?>
            <tr>
                <td><?= date("d-m-Y H:i:s", strtotime($updateLog->CreatedAt)); ?></td>
                <td><?= $updateLog->Type; ?></td>
                <td><?= $updateLog->Action; ?></td>
                <td><?= isset($updateLog->NosunId) ? $updateLog->NosunId : "n.v.t."; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
