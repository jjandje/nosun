<?php

namespace lib\email_triggers;

/**
 * Base class for any Trigger which defines some required functions and provides common functions for all Triggers.
 *
 * @note Remember to add the construction of the implementing Trigger to the TriggerFactory.
 *
 * Abstract Class Trigger
 * @package lib\email_triggers
 */
abstract class Trigger
{
    /**
     * @var string The body of th e-mail.
     */
    private $body;
    /**
     * @var string The message contents of the e-mail.
     */
    private $content;
    /**
     * @var string The e-mail subject.
     */
    private $subject;
    /**
     * @var array A set of attachments.
     */
    private $attachments;
    /**
     * @var string A link for the button.
     */
    private $link;

    /**
     * Trigger constructor.
     *
     * @param int $emailPostId Post id of the e-mail object that holds the content and template information.
     * @param array $data The set of data used to fill in the e-mail.
     */
    public function __construct(int $emailPostId, array $data)
    {
        $emailPost = get_post($emailPostId);
        if (empty($emailPost)) {
            return;
        }
        $replacementPairs = $this->parse($data);
        if (empty($replacementPairs)) {
            return;
        }

	    $this->subject = strtr(get_field('email_subject', $emailPostId), $replacementPairs);

	    $this->content = strtr(wpautop($emailPost->post_content), $replacementPairs);

//        $this->content = strtr(apply_filters('the_content', $emailPost->post_content), $replacementPairs);


        $this->link = get_field('email_link', $emailPostId);
        $attachments = get_field('email_attachments', $emailPostId);
        $this->attachments = [];
        if (!empty($attachments) && is_array($attachments)) {
            foreach ($attachments as $attachment) {
                $filePath = get_attached_file($attachment['attachment']);
                if (empty($filePath)) {
                    error_log("[Trigger->__construct]: The attached file for EmailTemplate with id: {$emailPostId} and attachment id: {$attachment['attachment']} doesn't exist.");
                } else {
                    $this->attachments[] = $filePath;
                }
            }
        }
        $templateSlug = get_page_template_slug($emailPost);
        if (empty($templateSlug)) {
            return;
        }
        global $emailContent;
        $emailContent = $this->content;
        global $emailLink;
        $emailLink = $this->link;

        ob_start();
        get_template_part(substr($templateSlug, 0, -4));
        $this->body = ob_get_contents();
        ob_end_clean();
    }

    /**
     * Creates a set of replacement pairs which can be used to replace substrings inside a string with something else.
     *
     * @param array $data The set of data used to fill in the e-mail.
     * @return array Array of string replacement pairs.
     */
    abstract function parse(array $data) : array;

    /**
     * Appends new triggers to the available triggers.
     *
     * @param array $triggers The currently available triggers.
     * @return array The triggers modified to contain new ones for this class.
     */
    abstract static function add_available_email_triggers(array $triggers) : array;

    /**
     * Sends the e-mail to the list of addresses supplied. Uses the e-mail content set up using the parse method.
     *
     * @param array $recipients A list of e-mail addresses for the recipients.
     * @param array $bcc A list of bcc e-mail addresses.
     * @param array $attachments Optional list of attachments filepaths.
     * @return bool Whether or not the e-mails have been sent successfully.
     */
    public function send(array $recipients, array $bcc = [], array $attachments = []) : bool
    {
        if (!$this->isValid()) {
            return false;
        }
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
        ];
        if (!empty($bcc)) {
            $headers[] = 'Bcc: ' . implode(";", $bcc);
        }
        if($this->subject === 'Reiziger actief in reisgroep') {
        	// TODO: DEBUG REMOVE THIS
	        error_log('###################### START ######################');
	        error_log('Trying to send the email: Reiziger actief in reisgroep');
	        error_log(var_export($recipients, true));
	        error_log(var_export($this->subject, true));
        	error_log(var_export($this->body, true));
	        error_log('###################### END ######################');
        }
        return wp_mail($recipients, $this->subject, $this->body, $headers, array_merge($this->attachments, $attachments));
    }

    /**
     * Checks whether or not the e-mail content has been set.
     *
     * @return bool
     */
    public function isValid() : bool
    {
        return !empty($this->body) && !empty($this->subject);
    }

    /**
     * Adds % symbols on either side of the key and returns the result.
     *
     * @param string $key The key to modify.
     * @return string The key modified to act as replacement key.
     */
    protected static function create_key(string $key) : string
    {
        return sprintf("%%%s%%", $key);
    }
}
