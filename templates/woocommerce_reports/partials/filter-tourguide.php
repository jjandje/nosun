<?php
global $tourGuideName;
?>
<div>
    <h2>Reisbegeleider</h2>
    <label for="tourguide-name-filter">Naam</label>
    <input type="text" name="tourguide-name-filter" id="tourguide-name-filter" value="<?= isset($tourGuideName) ? $tourGuideName : '' ?>">
</div>