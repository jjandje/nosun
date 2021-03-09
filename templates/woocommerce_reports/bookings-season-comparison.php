<h1>Seizoen Vergelijk</h1>
<?php get_template_part('templates/woocommerce_reports/partials/errors'); ?>
<div class="woocommerce-reports-wide">
    <form method="post">
        <?php get_template_part('templates/woocommerce_reports/partials/filter', 'season'); ?>
        <input class="button btn" type="submit" value="Filteren">
        <?php wp_nonce_field( 'bookings-season-comparison', 'report-nonce' ); ?>
    </form>
    <div>
        <h2>Aantal Boekingen: <?= number_format($totalBookings, 0); ?></h2>
        <canvas id="chart" width="800" height="500"></canvas>
    </div>
</div>
<script>
    var dataLabels = <?= $seasonLabels; ?>;
    var dataValues = <?= $seasonValues; ?>;
    var backgroundColors = <?= $seasonColors; ?>;
    var borderColors = <?= $seasonBorders; ?>;
    var myChart = {
        type: 'bar',
        data: {
            labels: dataLabels,
            datasets: [{
                label: 'Boekingen',
                data: dataValues,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 1
            }]
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