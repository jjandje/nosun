<?php

namespace lib\email_triggers;

/**
 * Holds information about the Trigger and is used to enforce required fields to exist.
 *
 * Class TriggerInformation
 * @package lib\email_triggers
 */
class TriggerInformation
{
    /**
     * @var string The title of the Trigger.
     */
    public $Title;
    /**
     * @var string The description of the Trigger.
     */
    public $Description;
    /**
     * @var array Map of replacements available in the Trigger.
     */
    public $Replacements;
    /**
     * @var bool Whether or not the Trigger needs to supply its own recipients.
     */
    public $RequiresRecipients;

    /**
     * TriggerInformation constructor.
     *
     * @param string $title The title of the Trigger.
     * @param string $description The description of the Trigger.
     * @param array $replacements Map of replacements available in the Trigger.
     * @param bool $requiresRecipients Whether or not the Trigger needs to supply its own recipients.
     */
    public function __construct(string $title, string $description, array $replacements, bool $requiresRecipients)
    {
        $this->Title = $title;
        $this->Description = $description;
        $this->Replacements = $replacements;
        $this->RequiresRecipients = $requiresRecipients;
    }
}