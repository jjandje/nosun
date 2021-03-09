<?php

namespace lib\email_triggers;

/**
 * Triggers when the Booking changes status to done.
 *
 * Class BookingDoneTrigger
 * @package lib\email_triggers
 */
class BookingDoneTrigger extends Trigger {
    /**
     * @inheritDoc
     */
    function parse(array $data): array {
        if (empty($data['nickname'] || empty($data['trip']))) {
            return [];
        }
        return [
            self::create_key('assumax_id') => $data['booking_data']->Id,
            self::create_key('nickname') => $data['nickname'],
            self::create_key('trip') => get_the_title($data['trip']),
            self::create_key('booking_type') => empty($data['booking_data']->IsOption) ? 'optie' : $data['booking_data']->IsOption ? 'optie' : 'boeking'
        ];
    }

    /**
     * @inheritDoc
     */
    static function add_available_email_triggers(array $triggers): array {
        $triggers['booking_done'] = new TriggerInformation(
            _x('Boeking voltooid', 'email_triggers', 'vazquez'),
            _x('Triggert wanneer de status van een boeking naar Done verandert. Eenmalig betekent hier dat er maar 1 e-mail gestuurd wordt per klant per boeking id.', 'email_triggers', 'vazquez'),
            [
                self::create_key('assumax_id') => _x('Id van de optie in het ERP', 'email_triggers', 'vazquez'),
                self::create_key('nickname') => _x('Roepnaam van de hoofdboeker', 'email_triggers', 'vazquez'),
                self::create_key('trip') => _x('De titel van de geboekte reis', 'email_triggers', 'vazquez'),
                self::create_key('booking_type') => _x('Of het een boeking of een optie betreft', 'email_triggers', 'vazquez')
            ],
            false
        );
        return $triggers;
    }
}

add_filter('vazquez_email_available_triggers', [BookingDoneTrigger::class, 'add_available_email_triggers']);
