<?php

namespace lib\email_triggers;

use lib\controllers\Template;
use Roots\Sage\Helpers;
use Vazquez\NosunAssumaxConnector\Api\Customers;

/**
 * Trigger for new booking emails.
 *
 * Class NewBookingTrigger
 * @package lib\email_triggers
 */
class NewBookingTrigger extends Trigger
{
    /**
     * @inheritDoc
     */
    function parse(array $data): array
    {
        if (empty($data['id']) || empty($data['booking']) || empty($data['trip']) || empty($data['customer'])) {
            return [];
        }
        $replacements = [
            self::create_key('assumax_id') => $data['id'],
            self::create_key('trip') => $data['trip']->post_title,
            self::create_key('booking_type') => $data['booking']->Option ? 'optie' : 'boeking',
        ];
        $triggerCustomer = Customers::get_by_assumax_id($data['customer']->Id);
        if (!empty($triggerCustomer)) {
            $nickName = get_field('customer_nick_name', $triggerCustomer->ID);
        }
        if (empty($nickName)) {
            $replacements[self::create_key('nickname')] = __('reiziger', 'email_triggers', 'vazquez');
        } else {
            $replacements[self::create_key('nickname')] = $nickName;
        }
        // Create a table of customers.
        global $customers;
        $customers = [];
        foreach ($data['booking']->Customers as $bookingCustomer) {
            $customerObject = Customers::get_by_assumax_id($bookingCustomer->Id);
            if (empty($customerObject)) {
                continue;
            }
            $customers[] = get_fields($customerObject->ID);
        }
        ob_start();
        get_template_part('templates/email_filters/partials/customer_table');
        $replacements[self::create_key('customers')] = ob_get_contents();
        ob_end_clean();
        // Trip data
        $replacements[self::create_key('trip')] = $data['trip']->post_title;
        $templatePostId = get_field('trip_template', $data['trip']->ID);
        $primaryDestination = Template::get_primary_destination($templatePostId);
        $replacements[self::create_key('trip_destination')] = empty($primaryDestination) ? '' : $primaryDestination->name;
        $replacements[self::create_key('trip_days')] = get_field('trip_num_days', $data['trip']->ID);
        $startDate = Helpers::create_local_datetime(get_field('trip_start_date', $data['trip']->ID));
        $replacements[self::create_key('trip_start')] = empty($startDate) ? '' : $startDate->format('d-m-Y');
        $endDate = Helpers::create_local_datetime(get_field('trip_end_date', $data['trip']->ID));
        $replacements[self::create_key('trip_end')] = empty($startDate) ? '' : $endDate->format('d-m-Y');
        return $replacements;
    }

    /**
     * @inheritDoc
     */
    public static function add_available_email_triggers(array $triggers) : array
    {
        $triggers['new_booking'] = new TriggerInformation(
            _x('Nieuwe boeking', 'email_triggers', 'vazquez'),
            _x('Triggert bij een nieuwe boeking. Eenmalig betekent hier dat er maar 1 e-mail gestuurd wordt per klant per boeking id.', 'email_triggers', 'vazquez'),
            [
                self::create_key('assumax_id') => _x('Id van de boeking in het ERP', 'email_triggers', 'vazquez'),
                self::create_key('customers') => _x('Tabel van reizigers', 'email_triggers', 'vazquez'),
                self::create_key('nickname') => _x('Roepnaam van de hoofdboeker', 'email_triggers', 'vazquez'),
                self::create_key('trip') => _x('De titel van de geboekte reis', 'email_triggers', 'vazquez'),
                self::create_key('trip_destination') => _x('Hoofdbestemming van de reis', 'email_triggers', 'vazquez'),
                self::create_key('trip_days') => _x('Aantal dagen in de reis', 'email_triggers', 'vazquez'),
                self::create_key('trip_start') => _x('Startdatum van de reis', 'email_triggers', 'vazquez'),
                self::create_key('trip_end') => _x('Einddatum van de reis', 'email_triggers', 'vazquez'),
                self::create_key('booking_type') => _x('Of het een boeking of een optie betreft', 'email_triggers', 'vazquez'),
            ],
            false
        );
        return $triggers;
    }
}

add_filter('vazquez_email_available_triggers', [NewBookingTrigger::class, 'add_available_email_triggers']);
