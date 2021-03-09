<?php
global $reportSeason;
?>
<div>
    <h2>Rapportage Seizoen</h2>
    <label for="report-season-filter">Seizoen</label>
    <select type="text" name="report-season-filter" id="report-season-filter">
        <option value="voorjaar" <?= $reportSeason === "voorjaar" ? "selected" : ""; ?>>Voorjaar</option>
        <option value="zomer" <?= $reportSeason === "zomer" ? "selected" : ""; ?>>Zomer</option>
        <option value="najaar" <?= $reportSeason === "najaar" ? "selected" : ""; ?>>Najaar</option>
        <option value="winter" <?= $reportSeason === "winter" ? "selected" : ""; ?>>Winter</option>
    </select>
</div>