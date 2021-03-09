<?php

namespace lib\email_triggers;

/**
 * Trigger for new user e-mails.
 *
 * Class NewUserTrigger
 * @package lib\email_triggers
 */
class NewUserTrigger extends Trigger {
    /**
     * @inheritDoc
     */
    function parse(array $data): array
    {
        if (empty($data['user_login']) || empty($data['nickname']) || empty($data['user_pass']) || empty($data['user_email'])) {
            return [];
        }
        return [
            self::create_key('user_name') => $data['user_login'],
            self::create_key('nick_name') => $data['nickname'],
            self::create_key('password') => $data['user_pass'],
            self::create_key('email_address') => $data['user_email'],
            self::create_key('first_name') => empty($data['first_name']) ? '' : $data['first_name'],
            self::create_key('last_name') => empty($data['last_name']) ? '' : $data['last_name']
        ];
    }

    /**
     * @inheritDoc
     */
    static function add_available_email_triggers(array $triggers): array
    {
        $triggers['new_user'] = new TriggerInformation(
            _x('Nieuwe gebruiker', 'email_triggers', 'vazquez'),
            _x('Triggert wanneer er een nieuw gebruikersaccount wordt aangemaakt. Eenmalig betekent hier dat er maar 1 e-mail gestuurd wordt per gebruikers id.', 'email_triggers', 'vazquez'),
            [
                self::create_key('user_name') => _x('De gebruikersnaam van de gebruiker', 'email_triggers', 'vazquez'),
                self::create_key('password') => _x('Het wachtwoord van de gebruiker', 'email_triggers', 'vazquez'),
                self::create_key('email_address') => _x('Het e-mail adres van de gebruiker', 'email_triggers', 'vazquez'),
                self::create_key('nick_name') => _x('De roepnaam van de gebruiker', 'email_triggers', 'vazquez'),
                self::create_key('first_name') => _x('De voornaam van de gebruiker', 'email_triggers', 'vazquez'),
                self::create_key('last_name') => _x('De achternaam van de gebruiker', 'email_triggers', 'vazquez'),
            ],
            false
        );
        return $triggers;
    }
}

add_filter('vazquez_email_available_triggers', [NewUserTrigger::class, 'add_available_email_triggers']);
