<?php
    global $paymentDateBegin;
    global $paymentDateEnd;
?>
<div>
    <h2>Betaling periode</h2>
    <label for="payment-date-filter-begin">Begindatum</label>
    <input type="text" name="payment-date-filter-begin" id="payment-date-filter-begin" value="<?= isset($paymentDateBegin) ? $paymentDateBegin : '' ?>">
    <label for="payment-date-filter-end">Einddatum</label>
    <input type="text" name="payment-date-filter-end" id="payment-date-filter-end" value="<?= isset($paymentDateEnd) ? $paymentDateEnd : '' ?>">
</div>
