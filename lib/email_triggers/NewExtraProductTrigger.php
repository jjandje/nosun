<?php

namespace lib\email_triggers;

/**
 * Triggers when a new extra product has been added to a booking.
 *
 * Class NewExtraProductTrigger
 * @package lib\email_triggers
 */
class NewExtraProductTrigger extends Trigger
{
    /**
     * @inheritDoc
     */
    function parse(array $data): array {
        if (empty($data['nick_name']) || empty($data['assumax_id']) || empty($data['product']) || empty($data['booking_url'])) {
            return [];
        }
        return [
            self::create_key('assumax_id') => $data['assumax_id'],
            self::create_key('nick_name') => $data['nick_name'],
            self::create_key('product') => $data['product'],
            self::create_key('booking_url') => $data['booking_url'],
        ];
    }

    /**
     * @inheritDoc
     */
    static function add_available_email_triggers(array $triggers): array {
        $triggers['new_extra_product'] = new TriggerInformation(
            _x('Nieuw extra product', 'email_triggers', 'vazquez'),
            _x('Triggert wanneer er een nieuw extra product wordt toegevoegd. Eenmalig betekent hier dat er maar 1 e-mail gestuurd wordt per boeking id en type extra product. Dit is af te raden gezien er meer dan een kan worden besteld.', 'email_triggers', 'vazquez'),
            [
                self::create_key('assumax_id') => _x('De id van de boeking in Assumax', 'email_triggers', 'vazquez'),
                self::create_key('nick_name') => _x('De roepnaam van de gebruiker', 'email_triggers', 'vazquez'),
                self::create_key('product') => _x('De titel van het product', 'email_triggers', 'vazquez'),
                self::create_key('booking_url') => _x('De url naar de boeking', 'email_triggers', 'vazquez'),
            ],
            false
        );
        return $triggers;
    }
}

add_filter('vazquez_email_available_triggers', [NewExtraProductTrigger::class, 'add_available_email_triggers']);
