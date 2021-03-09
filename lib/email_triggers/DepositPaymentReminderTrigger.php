<?php

namespace lib\email_triggers;

/**
 * Triggers when a deposit payment reminder needs to be send.
 *
 * Class DepositPaymentReminderTrigger
 * @package lib\email_triggers
 */
class DepositPaymentReminderTrigger extends Trigger
{
    /**
     * @inheritDoc
     */
    function parse(array $data): array {
        if (empty($data['assumax_id']) || empty($data['nick_name']) || empty($data['days'])) {
            return [];
        }
        return [
            self::create_key('assumax_id') => $data['assumax_id'],
            self::create_key('nick_name') => $data['nick_name'],
            self::create_key('days') => $data['days']
        ];
    }

    /**
     * @inheritDoc
     */
    static function add_available_email_triggers(array $triggers): array {
        $triggers['deposit_payment_reminder'] = new TriggerInformation(
            _x('Aanbetalingreminder', 'email_triggers', 'vazquez'),
            _x('Triggert wanneer er een aanbetalingreminder verstuurt moet worden. Eenmalig betekent hier dat er maar 1 e-mail gestuurd wordt per boeking id en voor 3 en 7 dagen.', 'email_triggers', 'vazquez'),
            [
                self::create_key('assumax_id') => _x('De id van de boeking in Assumax', 'email_triggers', 'vazquez'),
                self::create_key('nick_name') => _x('De roepnaam van de gebruiker', 'email_triggers', 'vazquez'),
                self::create_key('days') => _x('Het aantal dagen dat nog over is voor een aanbetaling', 'email_triggers', 'vazquez')
            ],
            false
        );
        return $triggers;
    }
}

add_filter('vazquez_email_available_triggers', [DepositPaymentReminderTrigger::class, 'add_available_email_triggers']);
