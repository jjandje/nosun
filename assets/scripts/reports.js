jQuery(document).ready(function ($) {
    $("#booking-date-filter-begin").datepicker({
        dateFormat:"dd-mm-yy"
    });

    $("#booking-date-filter-end").datepicker({
        dateFormat:"dd-mm-yy"
    });

    $("#payment-date-filter-begin").datepicker({
        dateFormat:"dd-mm-yy"
    });

    $("#payment-date-filter-end").datepicker({
        dateFormat:"dd-mm-yy"
    });

    $("#trip-date-filter-begin").datepicker({
        dateFormat:"dd-mm-yy"
    });

    $("#trip-date-filter-end").datepicker({
        dateFormat:"dd-mm-yy"
    });

    if (typeof(myChart) !== 'undefined') {
        var canvasContext = $("#chart");
        var chart = new Chart(canvasContext, myChart);
    }

    var tables = document.querySelectorAll(".sortable");
    for (index = 0; index < tables.length; ++index) {
        new Tablesort(tables[index]);
    }
});