<h1>Gemiddelde Leeftijd</h1>
<?php get_template_part('templates/woocommerce_reports/partials/errors'); ?>
<div class="woocommerce-reports-wide">
    <form method="post">
        <?php get_template_part('templates/woocommerce_reports/partials/filter', 'bookingdate'); ?>
        <?php get_template_part('templates/woocommerce_reports/partials/filter', 'tripdate'); ?>
        <?php get_template_part('templates/woocommerce_reports/partials/filter', 'tripid'); ?>
        <input class="button btn" type="submit" value="Filteren">
        <?php wp_nonce_field( 'bookings-average-age', 'report-nonce' ); ?>
    </form>
    <div>
        <h2>Gemiddelde leeftijd: <?= number_format($averageAge, 0); ?></h2>
        <h2>Aantal reizigers: <?= $numAges; ?></h2>
        <canvas id="chart" width="800" height="500"></canvas>
    </div>
</div>
<script>
    var dataLabels = <?= $ageLabels; ?>;
    var dataValues = <?= $ageValues; ?>;
    var myChart = {
        type: 'bar',
        data: {
            labels: dataLabels,
            datasets: [{
                label: 'Leeftijden',
                data: dataValues,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
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