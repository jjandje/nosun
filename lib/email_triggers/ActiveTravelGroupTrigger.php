<?php

namespace lib\email_triggers;

/**
 * Triggers when a customer becomes active in a travel group.
 *
 * Class ActiveTravelGroupTrigger
 * @package lib\email_triggers
 */
class ActiveTravelGroupTrigger extends Trigger
{
    /**
     * @inheritDoc
     */
    function parse(array $data): array {
        if (empty($data['nick_name']) ||
            empty($data['travelgroup_id']) ||
            empty($data['travelgroup_title']) ||
            empty($data['travelgroup_url']) ||
            empty($data['subgroup'])) {
            return [];
        }
        return [
            self::create_key('nick_name') => $data['nick_name'],
            self::create_key('travelgroup_id') => $data['travelgroup_id'],
            self::create_key('travelgroup_title') => $data['travelgroup_title'],
            self::create_key('travelgroup_url') => $data['travelgroup_url'],
            self::create_key('subgroup') => $data['subgroup']
        ];
    }

    /**
     * @inheritDoc
     */
    static function add_available_email_triggers(array $triggers): array {
        $triggers['active_travelgroup'] = new TriggerInformation(
            _x('Reiziger actief in reisgroep', 'email_triggers', 'vazquez'),
            _x('Triggert wanneer een reiziger actief wordt in een reisgroep. Eenmalig heeft voor deze trigger geen invloed gezien dit verzorgt wordt door de reisgroep.', 'email_triggers', 'vazquez'),
            [
                self::create_key('nick_name') => _x('De roepnaam van de gebruiker', 'email_triggers', 'vazquez'),
                self::create_key('travelgroup_id') => _x('De id van de reisgroep', 'email_triggers', 'vazquez'),
                self::create_key('travelgroup_title') => _x('De titel van de reisgroep', 'email_triggers', 'vazquez'),
                self::create_key('travelgroup_url') => _x('De url naar de reisgroep', 'email_triggers', 'vazquez'),
                self::create_key('subgroup') => _x('De subgroep waar de reiziger in zit', 'email_triggers', 'vazquez')
            ],
            false
        );
        return $triggers;
    }
}

add_filter('vazquez_email_available_triggers', [ActiveTravelGroupTrigger::class, 'add_available_email_triggers']);
