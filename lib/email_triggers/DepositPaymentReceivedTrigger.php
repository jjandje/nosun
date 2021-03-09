<?php

namespace lib\email_triggers;

/**
 * Triggers when a deposit payment has been received.
 *
 * Class DepositPaymentReceivedTrigger
 * @package lib\email_triggers
 */
class DepositPaymentReceivedTrigger extends Trigger
{
    /**
     * @inheritDoc
     */
    function parse(array $data): array {
        if (empty($data['customer']) || empty($data['booking_assumax_id']) || empty($data['paid_amount'])) {
            return [];
        }
        $nickName = get_field('customer_nick_name', $data['customer']->ID);
        if (empty($nickName)) {
            return [];
        }
        return [
            self::create_key('assumax_id') => $data['booking_assumax_id'],
            self::create_key('nick_name') => $nickName,
            self::create_key('paid_amount') => number_format($data['paid_amount'], 2, ',', ''),
        ];
    }

    /**
     * @inheritDoc
     */
    static function add_available_email_triggers(array $triggers): array {
        $triggers['deposit_payment_received'] = new TriggerInformation(
            _x('Aanbetaling ontvangen', 'email_triggers', 'vazquez'),
            _x('Triggert wanneer er een aanbetaling is ontvangen. Eenmalig betekent hier dat er maar 1 e-mail gestuurd wordt per boeking id en type betaling.', 'email_triggers', 'vazquez'),
            [
                self::create_key('assumax_id') => _x('De id van de boeking in Assumax', 'email_triggers', 'vazquez'),
                self::create_key('nick_name') => _x('De roepnaam van de gebruiker', 'email_triggers', 'vazquez'),
                self::create_key('paid_amount') => _x('Het betaalde bedrag met komma als decimaal teken: 1234,56.', 'email_triggers', 'vazquez'),
            ],
            false
        );
        return $triggers;
    }
}

add_filter('vazquez_email_available_triggers', [DepositPaymentReceivedTrigger::class, 'add_available_email_triggers']);
