<?php global $errors;
if (!empty($errors)): ?>
    <?php foreach ($errors as $error): ?>
        <div class="error">
            <p><?=$error;?></p>
        </div>
    <?php endforeach;?>
<?php endif;?>