<?php

namespace lib\email_triggers;

/**
 * Triggers when a new insurance is added to a Booking.
 *
 * Class NewInsuranceTrigger
 * @package lib\email_triggers
 */
class NewInsuranceTrigger extends Trigger {
    /**
     * @inheritDoc
     */
    function parse(array $data): array {
        if (empty($data['customer']) || empty($data['assumax_id']) || empty($data['insurance_type'])) {
            return [];
        }
        $replacements = [
            self::create_key('assumax_id') => $data['assumax_id']
        ];
        $replacements[self::create_key('nickname')] = get_field('customer_nick_name', $data['customer']->ID);
        $insuranceType = '';
        if ($data['insurance_type'] === 'Cancellation') {
            $insuranceType = _x('Annuleringsverzekering', 'email_triggers', 'vazquez');
        } elseif ($data['insurance_type'] === 'Trip') {
            $insuranceType = _x('Reisverzekering', 'email_triggers', 'vazquez');
        } elseif ($data['insurance_type'] === 'SnowmobilePayOff') {
            $insuranceType = _x('Sneeuwscooterafbetaling', 'email_triggers', 'vazquez');
        }
        $replacements[self::create_key('insurance_type')] = $insuranceType;
        $answerOne = _x('Onbekend', 'email_triggers', 'vazquez');
        $answerTwo = _x('Onbekend', 'email_triggers', 'vazquez');
        if (!empty($data['answers'])) {
            if (isset($data['answers'][0])) {
                $answerOne = boolval($data['answers'][0]) ? _x('Ja', 'email_triggers', 'vazquez') : _x('Nee', 'email_triggers', 'vazquez');
            }
            if (isset($data['answers'][1])) {
                $answerTwo = boolval($data['answers'][1]) ? _x('Ja', 'email_triggers', 'vazquez') : _x('Nee', 'email_triggers', 'vazquez');
            }
        }
        $replacements[self::create_key('answer_1')] = $answerOne;
        $replacements[self::create_key('answer_2')] = $answerTwo;
        return $replacements;
    }

    /**
     * @inheritDoc
     */
    static function add_available_email_triggers(array $triggers): array {
        $triggers['new_insurance'] = new TriggerInformation(
            _x('Nieuwe verzekering', 'email_triggers', 'vazquez'),
            _x('Triggert wanneer er een nieuwe verzekering wordt afgesloten. Eenmalig betekent hier dat er maar 1 e-mail gestuurd wordt per boeking id en type verzekering.', 'email_triggers', 'vazquez'),
            [
                self::create_key('assumax_id') => _x('De id van de boeking in Assumax', 'email_triggers', 'vazquez'),
                self::create_key('nickname') => _x('De roepnaam van de gebruiker', 'email_triggers', 'vazquez'),
                self::create_key('insurance_type') => _x('Het type verzekering wat wordt afgesloten', 'email_triggers', 'vazquez'),
                self::create_key('answer_1') => _x('Het antwoord op de eerste vraag', 'email_triggers', 'vazquez'),
                self::create_key('answer_2') => _x('Het antwoord op de tweede vraag', 'email_triggers', 'vazquez')
            ],
            false
        );
        return $triggers;
    }
}

add_filter('vazquez_email_available_triggers', [NewInsuranceTrigger::class, 'add_available_email_triggers']);
