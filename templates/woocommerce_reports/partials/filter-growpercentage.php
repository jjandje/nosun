<?php
global $targetGrowPercentage;
?>
<div>
    <h2>Doel groeipercentage</h2>
    <label for="grow-percentage-filter">%</label>
    <input type="text" name="grow-percentage-filter" id="grow-percentage-filter" value="<?= isset($targetGrowPercentage) ? $targetGrowPercentage : '' ?>">
</div>