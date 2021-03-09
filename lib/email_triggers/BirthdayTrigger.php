<?php

namespace lib\email_triggers;

/**
 * Trigger for Birthday E-mails.
 *
 * Class BirthdayTrigger
 * @package lib\email_triggers
 */
class BirthdayTrigger extends Trigger {
    /**
     * @inheritDoc
     */
    function parse(array $data): array {
        if (empty($data['nick_name']) || empty($data['first_name'] || empty($data['last_name']))) {
            return [];
        }
        return [
            self::create_key('nick_name') => $data['nick_name'],
            self::create_key('first_name') => $data['first_name'],
            self::create_key('last_name') => $data['last_name']
        ];
    }

    /**
     * @inheritDoc
     */
    public static function add_available_email_triggers(array $triggers) : array
    {
        $triggers['birthday'] = new TriggerInformation(
            _x('Verjaardag', 'email_triggers', 'vazquez'),
            _x('Triggert op de verjaardag van een reiziger. Eenmalig betekent hier dat er maar 1 e-mail gestuurd wordt per klant per jaar.', 'email_triggers', 'vazquez'),
            [
                self::create_key('nick_name') => _x('De roepnaam van de reiziger', 'email_triggers', 'vazquez'),
                self::create_key('first_name') => _x('De voornaam van de reiziger', 'email_triggers', 'vazquez'),
                self::create_key('last_name') => _x('De achternaam van de reiziger', 'email_triggers', 'vazquez'),
            ],
            false
        );
        return $triggers;
    }
}

add_filter('vazquez_email_available_triggers', [BirthdayTrigger::class, 'add_available_email_triggers']);
