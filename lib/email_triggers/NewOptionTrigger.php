<?php

namespace lib\email_triggers;

/**
 * Trigger for new option e-mails. Extends the NewBookingTrigger as it is very similar.
 *
 * Class NewOptionTrigger
 * @package lib\email_triggers
 */
class NewOptionTrigger extends NewBookingTrigger {
    /**
     * @inheritDoc
     */
    public static function add_available_email_triggers(array $triggers) : array
    {
        $triggers['new_option'] = new TriggerInformation(
            _x('Nieuwe optie', 'email_triggers', 'vazquez'),
            _x('Triggert bij een nieuwe optie. Eenmalig betekent hier dat er maar 1 e-mail gestuurd wordt per klant per boeking id.', 'email_triggers', 'vazquez'),
            [
                self::create_key('assumax_id') => _x('Id van de optie in het ERP', 'email_triggers', 'vazquez'),
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

add_filter('vazquez_email_available_triggers', [NewOptionTrigger::class, 'add_available_email_triggers']);
