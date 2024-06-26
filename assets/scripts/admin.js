/* ========================================================================
 * DOM-based Routing
 * Based on http://goo.gl/EUTi53 by Paul Irish
 *
 * Only fires on body classes that match. If a body class contains a dash,
 * replace the dash with an underscore when adding it to the object below.
 *
 * .noConflict()
 * The routing is enclosed within an anonymous function so that you can
 * always reference jQuery with $, even when in .noConflict() mode.
 * ======================================================================== */

(function ($) {
    $(document).ready(function () {
        $('.update-assumax').click(function (e) {
            e.preventDefault();
            var currentObject = $(this);
            var assumaxId = currentObject.data('assumax_id');
            var action = currentObject.data('action');
            currentObject.attr("disabled", true);
            if (assumaxId && action) {
                $.post(ajaxurl, {
                    action: action,
                    assumax_id: assumaxId
                }, function () {
                    currentObject.attr("disabled", false);
                });
            }
        });
    });
})(jQuery); // Fully reference jQuery after this point.
