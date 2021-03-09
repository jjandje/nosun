<h1>E-mail module</h1>
<p><?= _x('Hier kunnen voor alle beschikbare triggers \'events\' worden aangemaakt.

Klik voor de betreffende trigger op de "Nieuw" knop en kies de Email Template die gebruikt moet worden.

Verder is er de mogelijkheid om e-mail adressen in te voegen bij de ontvanger welke de standaard ontvangers <bold>overschrijven</bold>. Doe dit door een ; gescheiden lijst van e-mail adressen in te voegen.
De bcc werkt ook zoals de ontvangers, maar voegt extra bcc e-mail adressen toe aan wie de e-mail ook verstuurd wordt. Ook dit is een door ; gescheiden lijst.

Als de event maar eenmalig uitgevoerd moet worden per e-mail adres, vink dan eenmalig aan.

Als laatste moet er op "opslaan" geklikt worden en daarmee worden de wijzigingen van kracht.', 'email_triggers', 'vazquez') ?></p>
<h1>Triggers</h1>
<?php
$triggers = get_query_var('triggers');
$events = get_query_var('events');
$emailTemplates = get_query_var('email_templates');
if (empty($triggers)): ?>
    <p><?= _x('Er zijn op dit moment geen e-mail triggers beschikbaar.', 'email_triggers', 'vazquez') ?></p>
<?php else: ?>
<div id="trigger-container"
     data-update-nonce="<?= wp_create_nonce('upsert_event_nonce') ?>"
     data-delete-nonce="<?= wp_create_nonce('delete_event_nonce') ?>">

    <div>
        <label>
            <select id="change-trigger">
                <option value="0">-- Selecteer een trigger --</option>
                <?php foreach ($triggers as $trigger => $information): ?>
                    <option value="<?= $trigger ?>"><?= $information->Title ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <?php foreach ($triggers as $trigger => $information): ?>
        <div id="<?= $trigger ?>" class="trigger">
            <h2><?= $information->Title ?></h2>
            <p class="trigger-description"><?= $information->Description ?></p>
            <table class="events">
                <thead>
                <tr>
                    <th style="width: 25%;"><?= _x('E-mail template', 'email_triggers', 'vazquez') ?></th>
                    <th style="width: 25%;"><?= _x('Ontvangers', 'email_triggers', 'vazquez') ?></th>
                    <th style="width: 25%;"><?= _x('BCC', 'email_triggers', 'vazquez') ?></th>
                    <th><?= _x('Eenmalig', 'email_triggers', 'vazquez') ?></th>
                    <th><?= _x('Maal uitgevoerd', 'email_triggers', 'vazquez') ?></th>
                    <th><?= _x('Acties', 'email_triggers', 'vazquez') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (isset($events[$trigger])):
                    foreach ($events[$trigger] as $event): ?>
                        <tr class="event" id="<?= $event->id ?>" data-trigger="<?= $trigger ?>" data-id="<?= $event->id ?>">
                            <td>
                                <label>
                                    <select class="email-template" name="email_template" required>
                                        <option value="0">-- Selecteer een e-mail template --</option>
                                        <?php foreach ($emailTemplates as $postId => $title): ?>
                                            <option value="<?= $postId ?>" <?= intval($event->email_template) === $postId ? 'selected' : '' ?>> <?= $title ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="required">*</span>
                                </label>
                            </td>
                            <td>
                                <label>
                                    <input class="email-recipients" name="recipients" type="text" value="<?= $event->recipients ?? '' ?>" <?= $information->RequiresRecipients ? 'required' : '' ?>>
                                </label>
                                <?php if ($information->RequiresRecipients): ?><span class="required">*</span><?php endif; ?>
                            </td>
                            <td>
                                <label>
                                    <input class="email-bcc" name="bcc" type="text" value="<?= $event->bcc ?? '' ?>">
                                </label>
                            </td>
                            <td>
                                <label>
                                    <input class="email-singleton" name="singleton" type="checkbox" <?= $event->singleton === '1' ? 'checked' : '' ?>>
                                </label>
                            </td>
                            <td><?= $event->fired ?></td>
                            <td>
                                <button class="save"><?= _x('Opslaan', 'email_triggers', 'vazquez') ?></button>
                                <button class="delete"><?= _x('Verwijderen', 'email_triggers', 'vazquez') ?></button>
                            </td>
                        </tr>
                    <?php endforeach;
                endif; ?>
                <tr class="event event-template" data-trigger="<?= $trigger ?>">
                    <td>
                        <label>
                            <select class="email-template" name="email_template" required>
                                <option value="0">-- Selecteer een e-mail template --</option>
                                <?php foreach ($emailTemplates as $postId => $title): ?>
                                    <option value="<?= $postId ?>"> <?= $title ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="required">*</span>
                        </label>
                    </td>
                    <td>
                        <label>
                            <input class="email-recipients" name="recipients" type="text" <?= $information->RequiresRecipients ? 'required' : '' ?>>
                        </label>
                        <?php if ($information->RequiresRecipients): ?><span class="required">*</span><?php endif; ?>
                    </td>
                    <td>
                        <label>
                            <input class="email-bcc" name="bcc" type="text">
                        </label>
                    </td>
                    <td>
                        <label>
                            <input class="email-singleton" name="singleton" type="checkbox">
                        </label>
                    </td>
                    <td>0</td>
                    <td>
                        <button class="save"><?= _x('Opslaan', 'email_triggers', 'vazquez') ?></button>
                        <button class="delete"><?= _x('Verwijderen', 'email_triggers', 'vazquez') ?></button>
                    </td>
                </tr>
                </tbody>
            </table>
            <button class="new"><?= __('Nieuw', 'email_triggers', 'vazquez') ?></button>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
