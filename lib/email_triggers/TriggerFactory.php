<?php

namespace lib\email_triggers;

/**
 * Handles the creation of the correct type of Trigger for the provided context.
 *
 * Class TriggerFactory
 * @package lib\email_triggers
 */
class TriggerFactory
{
    public static $triggerClasses = [
        'new_booking' => NewBookingTrigger::class,
        'new_option' => NewOptionTrigger::class,
        'new_user' => NewUserTrigger::class,
        'birthday' => BirthdayTrigger::class,
        'booking_confirmed' => BookingConfirmedTrigger::class,
        'booking_cancelled' => BookingCancelledTrigger::class,
        'booking_done' => BookingDoneTrigger::class,
        'new_insurance' => NewInsuranceTrigger::class,
        'total_payment_received' => TotalPaymentReceivedTrigger::class,
        'deposit_payment_received' => DepositPaymentReceivedTrigger::class,
        'rest_payment_received' => RestPaymentReceivedTrigger::class,
        'total_payment_reminder' => TotalPaymentReminderTrigger::class,
        'rest_payment_reminder' => RestPaymentReminderTrigger::class,
        'deposit_payment_reminder' => DepositPaymentReminderTrigger::class,
        'new_extra_product' => NewExtraProductTrigger::class,
        'active_travelgroup' => ActiveTravelGroupTrigger::class,
        'option_reminder' => OptionReminderTrigger::class
    ];

    /**
     * Obtains the instance of a Trigger to which data can be applied and through which e-mails can be send.
     *
     * @param string $trigger The trigger key used to specify the type of trigger.
     * @param int $emailPostId The post id of the e-mail object associated with the trigger.
     * @param array $data The data that will be passed to the trigger.
     * @return Trigger|null Either an instance of a class implementing the Trigger abstract class, or null when none
     * can be created for the provided trigger parameter.
     */
    public static function get(string $trigger, int $emailPostId, array $data)
    {
        if (key_exists($trigger, self::$triggerClasses)) {
            return new self::$triggerClasses[$trigger]($emailPostId, $data);
        }
        return null;
    }
}
