<h1>Gemiddeld Geslacht</h1>
<?php get_template_part('templates/woocommerce_reports/partials/errors'); ?>
<div class="woocommerce-reports-wide">
    <form method="post">
        <?php get_template_part('templates/woocommerce_reports/partials/filter', 'bookingdate'); ?>
        <?php get_template_part('templates/woocommerce_reports/partials/filter', 'tripdate'); ?>
        <?php get_template_part('templates/woocommerce_reports/partials/filter', 'tripid'); ?>
        <input class="button btn" type="submit" value="Filteren">
        <?php wp_nonce_field( 'bookings-average-gender', 'report-nonce' ); ?>
    </form>
    <div>
        <h2>Percentage Man: <?= number_format($percentageMen, 1); ?></h2>
        <h2>Percentage Vrouw: <?= number_format($percentageWomen, 1); ?></h2>
        <h2>Aantal Reizigers: <?= number_format($numCustomers, 0); ?></h2>
        <canvas id="chart" width="800" height="500"></canvas>
    </div>
</div>
<script>
    var dataLabels = <?= $genderLabels; ?>;
    var dataValues = <?= $genderValues; ?>;
    var myChart = {
        type: 'doughnut',
        data: {
            labels: dataLabels,
            datasets: [{
                label: 'Geslachten',
                data: dataValues,
                backgroundColor: [
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 61, 61, 0.2)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 61, 61, 1)'
                ]
            }]
        }
    };
</script>