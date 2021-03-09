<?php
    global $bookingDateBegin;
    global $bookingDateEnd;
?>
<div>
    <h2>Boeking periode</h2>
    <label for="booking-date-filter-begin">Begindatum</label>
    <input type="text" name="booking-date-filter-begin" id="booking-date-filter-begin" value="<?= isset($bookingDateBegin) ? $bookingDateBegin : '' ?>">
    <label for="booking-date-filter-end">Einddatum</label>
    <input type="text" name="booking-date-filter-end" id="booking-date-filter-end" value="<?= isset($bookingDateEnd) ? $bookingDateEnd : '' ?>">
</div>
