jQuery(document).ready(function ($) {
    // EmailTemplate editor section.
    $('#email_shortcodes_select').change(function () {
        $(".email_shortcodes_display").hide();
        var trigger = $(this).find("option:selected").val();
        if (trigger) {
            $('#'+trigger).show();
        }
    });

    // EmailModule page section.
    $('#change-trigger').change(function () {
        $(".trigger").hide();
        var trigger = $(this).find("option:selected").val();
        if (trigger) {
            $('#'+trigger).show();
        }
    });

    $('button.new').click(function () {
        var $parent = $(this).parent();
        if (!$parent.length) {
            return;
        }
        var $template = $parent.find('.event-template');
        if (!$template.length) {
            return;
        }
        var $clone = $template.clone();
        if (!$clone.length) {
            return;
        }
        var $events = $parent.find('.events tbody');
        if (!$events.length) {
            return;
        }
        $clone.removeClass('event-template');
        $events.append($clone);
    });

    var $triggerContainer = $('#trigger-container');
    var updateNonce = $triggerContainer.data('update-nonce');
    var deleteNonce = $triggerContainer.data('delete-nonce');

    $triggerContainer.on('click', 'button.save', function () {
        if (!updateNonce) {
            alert('Kon de event niet opslaan!');
            return;
        }
        var $event = $(this).parents('.event');
        if (!$event.length) {
            alert('Kon de event niet opslaan!');
            return;
        }
        var $selectedTemplate = $event.find('.email-template option:selected');
        if (!$selectedTemplate.length || parseInt($selectedTemplate.val()) === 0) {
            alert('Er is geen e-mail template geselecteerd.');
            return;
        }
        var templateId = parseInt($selectedTemplate.val());
        var eventId = $event.data('id') ? $event.data('id') : 0;
        var $recipients = $event.find('.email-recipients');
        var $bcc = $event.find('.email-bcc');
        var $singleton = $event.find('.email-singleton');
        var trigger = $event.data('trigger');
        if (!$recipients.length || !$bcc.length || !$singleton.length || !trigger) {
            alert('Kon de event niet opslaan!');
            return;
        }
        var recipients = '';
        var i;
        if ($recipients.val()) {
            recipients = $recipients.val();
            var recipientArray = recipients.split(';');
            if (!recipientArray.length) {
                alert('De waarde in het ontvangers veld is niet geldig.');
                return;
            }
            for (i = 0; i < recipientArray.length; i++) {
                if (!recipientArray[i].match(/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/)) {
                    alert('De waarde in het ontvangers veld is niet geldig.');
                    return;
                }
            }
        }
        var bcc = '';
        if ($bcc.val()) {
            bcc = $bcc.val();
            var bccArray = bcc.split(';');
            if (!bccArray.length) {
                alert('De waarde in het bcc veld is niet geldig.');
                return;
            }
            for (i = 0; i < bccArray.length; i++) {
                if (!bccArray[i].match(/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/)) {
                    alert('De waarde in het bcc veld is niet geldig.');
                    return;
                }
            }
        }
        var singletonState = $singleton.is(':checked');
        // Send the update request.
        jQuery.ajax({
            url: ajax_object.ajaxurl,
            type: 'POST',
            data: {
                action: 'email_upsert_event',
                security: updateNonce,
                trigger: trigger,
                event_id: eventId,
                template_id: templateId,
                recipients: recipients,
                bcc: bcc,
                singleton: singletonState
            },
            beforeSend: function () {
                $(this).prop('disabled', true);
            },
            success: function (response) {
                $event.data('id', response);
                $(this).prop('disabled', false);
            },
            fail: function (response) {
                $(this).prop('disabled', false);
                alert('Er ging iets fout tijdens het opslaan van het event.');
            }
        });
    });

    $triggerContainer.on('click', 'button.delete', function () {
        if (!updateNonce) {
            alert('Kon de event niet verwijderen!');
            return;
        }
        var $event = $(this).parents('.event');
        if (!$event.length) {
            alert('Kon de event niet verwijderen!');
            return;
        }
        var eventId = $event.data('id');
        if (!eventId) {
            $event.remove();
            return;
        }
        // Send the delete request.
        jQuery.ajax({
            url: ajax_object.ajaxurl,
            type: 'POST',
            data: {
                action: 'email_delete_event',
                security: deleteNonce,
                event_id: eventId
            },
            beforeSend: function () {
                $(this).prop('disabled', true);
            },
            success: function (response) {
                $event.data('id', response);
                $event.remove();
            },
            fail: function (response) {
                $(this).prop('disabled', false);
                alert('Er ging iets fout tijdens het verwijderen van het event.');
            }
        });
    });
});
