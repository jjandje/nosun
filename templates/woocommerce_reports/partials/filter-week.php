<?php
global $reportWeek;
?>
<div>
    <h2>Rapportage Week</h2>
    <label for="report-week-filter">Nummer</label>
    <input type="text" name="report-week-filter" id="report-week-filter" value="<?= isset($reportWeek) ? $reportWeek : '' ?>">
</div>