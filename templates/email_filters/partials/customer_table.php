<?php
global $customers;
if (!empty($customers)):
    foreach ($customers as $customer): ?>
        <table class="customer">
            <tr>
                <th><?= _x('Voornamen', 'email_triggers', 'vazquez') ?></th>
                <td><?= $customer['customer_first_name']; ?></td>
            </tr>
            <tr>
                <th><?= _x('Roepnaam', 'email_triggers', 'vazquez') ?></th>
                <td><?= $customer['customer_nick_name']; ?></td>
            </tr>
            <tr>
                <th><?= _x('Achternaam', 'email_triggers', 'vazquez') ?></th>
                <td><?= $customer['customer_last_name']; ?></td>
            </tr>
            <tr>
                <th><?= _x('Geboortedatum', 'email_triggers', 'vazquez') ?></th>
                <td><?= date('d-m-Y', strtotime($customer['customer_date_of_birth'])) ?></td>
            </tr>
            <tr>
                <th><?= _x('Telefoonnummer', 'email_triggers', 'vazquez') ?></th>
                <td><?= !empty($customer['customer_phone_number']) ? $customer['customer_phone_number'] : '-' ?></td>
            </tr>
        </table>
<?php endforeach;
endif; ?>