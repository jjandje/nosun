<?php
    global $nosunTripId;
?>
<div>
    <h2>Specifieke Reis</h2>
    <label for="trip-id-filter">Assumax Id</label>
    <input type="text" name="trip-id-filter" id="trip-id-filter" value="<?= isset($nosunTripId) ? $nosunTripId : '' ?>">
</div>
