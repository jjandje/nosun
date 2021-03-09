<?php

namespace lib\email_triggers;

/**
 * Triggers when an option reminder e-mail needs to be send.
 *
 * Class OptionReminderTrigger
 * @package lib\email_triggers
 */
class OptionReminderTrigger extends Trigger
{
    /**
     * @inheritDoc
     */
    function parse(array $data): array {
        if (empty($data['nick_name']) || empty($data['assumax_id']) || empty($data['numdays']) || empty($data['booking_url'])) {
            return [];
        }
        return [
            self::create_key('assumax_id') => $data['assumax_id'],
            self::create_key('nick_name') => $data['nick_name'],
            self::create_key('numdays') => $data['numdays'],
            self::create_key('booking_url') => $data['booking_url'],
        ];
    }

    /**
     * @inheritDoc
     */
    static function add_available_email_triggers(array $triggers): array {
        $triggers['option_reminder'] = new TriggerInformation(
            _x('Optie herinnering', 'email_triggers', 'vazquez'),
            _x('Triggert wanneer er een optie herinnering gestuurd moet worden voor opties die 4 tot en met 6 dagen oud zijn. Eenmalig betekent hier dat er maar 1 e-mail gestuurd wordt per boeking id.', 'email_triggers', 'vazquez'),
            [
                self::create_key('assumax_id') => _x('De id van de optie in Assumax', 'email_triggers', 'vazquez'),
                self::create_key('nick_name') => _x('De roepnaam van de gebruiker', 'email_triggers', 'vazquez'),
                self::create_key('numdays') => _x('Het aantal resterende dagen voor de optie afloopt', 'email_triggers', 'vazquez'),
                self::create_key('booking_url') => _x('De url naar de optie', 'email_triggers', 'vazquez'),
            ],
            false
        );
        return $triggers;
    }
}

add_filter('vazquez_email_available_triggers', [OptionReminderTrigger::class, 'add_available_email_triggers']);
