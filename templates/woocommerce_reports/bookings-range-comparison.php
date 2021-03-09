<h1>Periode Vergelijk</h1>
<?php get_template_part('templates/woocommerce_reports/partials/errors'); ?>
<div class="woocommerce-reports-wide" style="display: flex; justify-content: space-between;">
    <form method="post" style="min-width: 20%;">
        <?php get_template_part('templates/woocommerce_reports/partials/filter', 'week'); ?>
        <br>
        <input class="button btn" type="submit" value="Filteren">
        <?php wp_nonce_field( 'bookings-range-comparison', 'report-nonce' ); ?>
    </form>
    <div>
        <h2><?= $year; ?></h2>
        <table class="wp-list-table widefat fixed striped" style="max-width: 50%;">
            <thead>
            <tr>
                <th style="width: 10%;">Week</th>
                <th>Aantal boekingen</th>
                <th>Omzet</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><?= $previousReportWeek; ?></td>
                <td><?= number_format($numPreviousWeek, 0); ?></td>
                <td>&euro;<?= number_format($revenuePreviousWeek / 100, 2); ?></td>
            </tr>
            <tr>
                <td><?= $reportWeek; ?></td>
                <td><?= number_format($numThisWeek, 0); ?></td>
                <td>&euro;<?= number_format($revenueThisWeek / 100, 2); ?></td>
            </tr>
            </tbody>
        </table>
        <br>
        <h2><?= $year-1; ?></h2>
        <table class="wp-list-table widefat fixed striped" style="max-width: 50%;">
            <thead>
            <tr>
                <th style="width: 10%;">Week</th>
                <th>Aantal boekingen</th>
                <th>Omzet</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><?= $previousReportWeek; ?></td>
                <td><?= number_format($numPreviousWeekPreviousYear, 0); ?></td>
                <td>&euro;<?= number_format($revenuePreviousWeekPreviousYear / 100, 2); ?></td>
            </tr>
            <tr>
                <td><?= $reportWeek; ?></td>
                <td><?= number_format($numThisWeekPreviousYear, 0); ?></td>
                <td>&euro;<?= number_format($revenueThisWeekPreviousYear / 100, 2); ?></td>
            </tr>
            </tbody>
        </table>
        <br>
        <canvas id="chart" width="600" height="500" style="max-width: 50%;"></canvas>
    </div>
</div>
<script>
    var dataLabels = <?= $rangeLabels; ?>;
    var dataValues = <?= $rangeValues; ?>;
    var dataValuesPreviousYear = <?= $rangeValuesPreviousYear; ?>;
    var myChart = {
        type: 'bar',
        data: {
            labels: dataLabels,
            datasets: [
                {
                    label: <?= sprintf("'%d'", $year-1); ?>,
                    data: dataValuesPreviousYear,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(54, 162, 235, 0.2)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(54, 162, 235, 1)'
                    ],
                    borderWidth: 1
                },
                {
                    label: <?= sprintf("'%d'", $year); ?>,
                    data: dataValues,
                    backgroundColor: [
                        'rgba(221, 122, 15, 0.2)',
                        'rgba(221, 122, 15, 0.2)'
                    ],
                    borderColor: [
                        'rgba(221, 122, 15, 1)',
                        'rgba(221, 122, 15, 1)'
                    ],
                    borderWidth: 1
                }
            ]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero:true
                    }
                }]
            }
        }
    };
</script>