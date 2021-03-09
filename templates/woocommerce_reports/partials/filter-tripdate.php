<?php
    global $tripDateBegin;
    global $tripDateEnd;
?>
<div>
    <h2>Reis periode</h2>
    <label for="trip-date-filter-begin">Begindatum</label>
    <input type="text" name="trip-date-filter-begin" id="trip-date-filter-begin" value="<?= isset($tripDateBegin) ? $tripDateBegin : '' ?>">
    <label for="trip-date-filter-end">Einddatum</label>
    <input type="text" name="trip-date-filter-end" id="trip-date-filter-end" value="<?= isset($tripDateEnd) ? $tripDateEnd : '' ?>">
</div>