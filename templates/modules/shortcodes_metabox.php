<?php $triggers = get_query_var('triggers'); ?>
<p><?= __('Hieronder kan een trigger gekozen worden waarvoor alle beschikbare shortcodes worden weergegeven.
Een shortcode bestaat altijd uit een sleutelwoord met %% er omheen.
Deze worden vervolgens vervangen zodra de template wordt omgezet naar een e-mail.
Dit is puur ter informatie en bepaalt verder niets. 
Om de template aan de trigger te koppelen ga je naar het "Email Module" menu item.', 'email_triggers', 'vazquez') ?></p>
<label for="email_shortcodes_select"><?= __('Kies een trigger: ', 'email_triggers', 'vazquez') ?></label>
<select id="email_shortcodes_select">
    <option value=""><?= __('---', 'email_triggers', 'vazquez') ?></option>
    <?php if (!empty($triggers)) :
        foreach ($triggers as $trigger => $information): ?>
            <option value="<?= $trigger ?>"><?= $information->Title ?></option>
        <?php endforeach ?>
    <?php endif; ?>
</select>
<?php if (!empty($triggers)) :
    foreach ($triggers as $trigger => $information): ?>
        <div id="<?= $trigger ?>" class="email_shortcodes_display">
            <table>
                <thead>
                    <tr>
                        <th><?= __('Shortcode', 'email_triggers', 'vazquez') ?></th>
                        <th><?= __('Beschrijving', 'email_triggers', 'vazquez') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($information->Replacements as $shortCode => $description) : ?>
                        <tr>
                            <td><?= $shortCode ?></td>
                            <td><?= $description ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach ?>
<?php endif; ?>
