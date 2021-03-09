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
    $('input').iCheck({
        checkboxClass: 'icheckbox_square-yellow',
        radioClass: 'iradio_square-yellow'
    });

    $('.resetfilters').click(function () {
        $('input').iCheck('uncheck');
        return false;
    });

    function setPushyHeight() {
        var headerHeight = $("header.main_header").innerHeight();
        var pushyTop = headerHeight;
        var width = window.innerWidth;

        $(".pushy").css('top', pushyTop);

        if (width < 991) {
            $("body").css('padding-top', pushyTop);
        }
    }

    window.FontAwesomeConfig = {
        searchPseudoElements: true
    };

    lightbox.option({
        'resizeDuration': 200,
        'wrapAround': true,
        'albumLabel': "Foto %1 / %2",
        'positionFromTop': 100
    });

    $.extend($.validator.messages, {
        required: "Dit is een verplicht veld.",
        remote: "Controleer dit veld.",
        email: "Vul hier een geldig e-mailadres in.",
        url: "Vul hier een geldige URL in.",
        date: "Vul hier een geldige datum in.",
        dateISO: "Vul hier een geldige datum in (ISO-formaat).",
        number: "Vul hier een geldig getal in.",
        digits: "Vul hier alleen getallen in.",
        creditcard: "Vul hier een geldig creditcardnummer in.",
        equalTo: "Vul hier dezelfde waarde in.",
        extension: "Vul hier een waarde in met een geldige extensie.",
        maxlength: $.validator.format("Vul hier maximaal {0} tekens in."),
        minlength: $.validator.format("Vul hier minimaal {0} tekens in."),
        rangelength: $.validator.format("Vul hier een waarde in van minimaal {0} en maximaal {1} tekens."),
        range: $.validator.format("Vul hier een waarde in van minimaal {0} en maximaal {1}."),
        max: $.validator.format("Vul hier een waarde in kleiner dan of gelijk aan {0}."),
        min: $.validator.format("Vul hier een waarde in groter dan of gelijk aan {0}."),
        step: $.validator.format("Vul hier een veelvoud van {0} in."),

        // For validations in additional-methods.js
        iban: "Vul hier een geldig IBAN in.",
        dateNL: "Vul hier een geldige datum in.",
        phoneNumbers: "Een telefoonnummer heeft 10 cijfers en begint<br /> met een '0' of '+31'",
        mobileNL: "Vul hier een geldig Nederlands mobiel telefoonnummer in.",
        postalcodeNL: "Vul hier een geldige postcode in.",
        bankaccountNL: "Vul hier een geldig bankrekeningnummer in.",
        giroaccountNL: "Vul hier een geldig gironummer in.",
        bankorgiroaccountNL: "Vul hier een geldig bank- of gironummer in."
    });

    $.validator.addMethod("dateFormat",
        function (value, element) {
            var check = false;
            var re = /^(\d{1,2})-(\d{1,2})-(\d{4})$/;
            if (re.test(value)) {
                var adata = value.split('-');
                var dd = parseInt(adata[0], 10);
                var mm = parseInt(adata[1], 10);
                var yyyy = parseInt(adata[2], 10);
                var xdata = new Date(yyyy, mm - 1, dd);
                if ((xdata.getFullYear() === yyyy) && (xdata.getMonth() === mm - 1) && (xdata.getDate() === dd)) {
                    check = true;
                } else {
                    check = false;
                }
            } else {
                check = false;
            }
            return this.optional(element) || check;
        },
        "Vul hier een geldige datum in (bijv. 27-06-1989)"
    );

    $.validator.addMethod("equalToIgnoreCase", function (value, element, param) {
        return this.optional(element) ||
            (value.toLowerCase() === $(param).val().toLowerCase());
    });

    $.validator.addMethod("ifEmailExists", function (value, element, param) {
        return this.optional(element) || !$(param).hasClass('email-exists');
    });

    $.validator.addMethod(
        "regex",
        function (value, element, regexp) {
            var re = new RegExp(regexp);
            return this.optional(element) || re.test(value);
        },
        "Please check your input!!."
    );

    $.validator.addMethod('phoneNumbers', function (value) {
        return /([0-9\s\-]{7,})(?:\s*(?:#|x\.?|ext\.?|extension)\s*(\d+))?$/.test(value);
    }, 'Vul hier een geldig telefoonnummer in.');

    /* jshint ignore:start */
    $.each($.validator.methods, function (key, value) {
        $.validator.methods[key] = function () {
            if (arguments.length > 0) {
                arguments[0] = $.trim(arguments[0]);
            }
            return value.apply(this, arguments);
        };
    });
    /* jshint ignore:end */

    var getUrlParameter = function getUrlParameter(sParam) {
        var sPageURL = decodeURIComponent(window.location.search.substring(1)),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;

        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');

            if (sParameterName[0] === sParam) {
                return sParameterName[1] === undefined ? true : sParameterName[1];
            }
        }
    };

    var filterTimeout = null;

    function filterResults() {
        if (filterTimeout) {
            clearTimeout(filterTimeout);
        }

        filterTimeout = setTimeout(function () {
            var destinations = [];
            var types = [];

            $('.checkDestination:checked').each(function () {
                destinations.push(this.value);
            });

            $('.checkType:checked').each(function () {
                types.push(this.value);
            });

            var nonce = $('#searchform').data('nonce');
            var $dateRangeFilter = $('input[name="datum"]');
            var dateRange = {
                start: $dateRangeFilter.data('start'),
                end: $dateRangeFilter.data('end')
            };

            jQuery.ajax({
                url: ajax_object.ajax_url,
                type: 'GET',
                data: {
                    action: 'filter_results',
                    bestemming: destinations,
                    type: types,
                    zoekterm: $('input[name="zoekterm"]').val(),
                    datum: dateRange,
                    security: nonce
                },
                beforeSend: function () {
                    $('i.loading').addClass('show');
                    $('#searchsubmit').addClass('loading');
                    $('span.amount').removeClass('show');
                    $('#searchsubmit span.amount').text('');
                    $('#searchsubmit span.text').text('');
                    $('#searchsubmit span.text-2').text('');
                },
                success: function (response) {
                    if (response !== 0) {
                        $('span.amount').text(response);
                        if (response === 1) {
                            $('#searchsubmit span.text-2').text('reis');
                        } else {
                            $('#searchsubmit span.text-2').text('reizen');
                        }
                        $('i.loading').removeClass('show');
                        $('#searchsubmit span.text').text('Toon');
                        $('span.amount').addClass('show');
                        $('#searchsubmit').removeClass('loading');
                    } else {
                        $('#searchsubmit span.text').text('Geen');
                        $('span.amount').text('reizen');
                        $('#searchsubmit span.text-2').text('gevonden');
                        $('i.loading').removeClass('show');
                        $('span.amount').addClass('show');
                        $('#searchsubmit').removeClass('loading');
                    }
                }
            });

            // Set filter counts
            if (destinations.length !== 0) {
                $('#countDestinations').addClass('active').text(destinations.length);
            } else {
                $('#countDestinations').removeClass('active');
            }

            if (types.length !== 0) {
                $('#countTypes').addClass('active').text(types.length);
            } else {
                $('#countTypes').removeClass('active');
            }
        }, 1000);
    }

    // Use this variable to set up the common and page specific functions. If you
    // rename this variable, you will also need to rename the namespace below.
    var Sage = {
        // All pages
        'common': {
            init: function () {
                $(document).ready(function () {
                    setTimeout(function () {
                        $('.left-popup').addClass('show');
                        Cookies.set('popupShown', 'shown');
                    }, 1500);
                    $('.left-popup a.go-to-link').on('click', function () {
                        Cookies.set('popup', 'no');
                    });
                    $('.left-popup a.close').on('click', function () {
                        Cookies.set('popup', 'no');
                        $('.left-popup').removeClass('show');
                        return false;
                    });

                    var $datePicker = $('input[name="datum"]');
                    if ($datePicker.length) {
                        $datePicker.daterangepicker({
                            "autoUpdateInput": false,
                            "showWeekNumbers": true,
                            "locale": {
                                "format": "DD-MM-YYYY",
                                "separator": " / ",
                                "applyLabel": "Toepassen",
                                "cancelLabel": "Annuleren",
                                "fromLabel": "Van",
                                "toLabel": "Tot",
                                "customRangeLabel": "Custom",
                                "weekLabel": "W",
                                "daysOfWeek": [
                                    "Zo",
                                    "Ma",
                                    "Di",
                                    "Wo",
                                    "Do",
                                    "Vr",
                                    "Za"
                                ],
                                "monthNames": [
                                    "Januari",
                                    "Februari",
                                    "Maart",
                                    "April",
                                    "Mei",
                                    "Juni",
                                    "Juli",
                                    "Augustus",
                                    "September",
                                    "Oktober",
                                    "November",
                                    "December"
                                ],
                                "firstDay": 1
                            },
                            "buttonClasses": "btn",
                            "applyButtonClasses": "btn--pink",
                            "cancelButtonClasses": "btn--transparant"
                        });
                    }
                    $datePicker.on('apply.daterangepicker', function(ev, picker) {
                        $(this).val(picker.startDate.format('DD-MM-YYYY') + ' - ' + picker.endDate.format('DD-MM-YYYY'));
                        $(this).data('start', picker.startDate.format('DD-MM-YYYY'));
                        $(this).data('end', picker.endDate.format('DD-MM-YYYY'));
                        filterResults();
                    });
                    $datePicker.on('cancel.daterangepicker', function(ev, picker) {
                        $(this).val('');
                    });
                });

                $('a.booking-costs').webuiPopover({
                    title: 'Betreft vanafprijs per persoon',
                    content: 'Excl. boekingskosten (€30,-) en bijdrage calamiteitenfonds (€2,50) per boeking.',
                    placement: 'top',
                    width: 200
                });

                $('.open-filters-btn').on('click', function () {
                    $('#available_filters').slideToggle();
                    $(this).toggleClass('filter-is-open');
                });

                $('#mobileNav > li.menu-item-has-children > a').click(function (e) {
                    e.preventDefault();
                });

                $('#mobileNav > li.menu-item-has-children > a').each(function () {
                    var closest_li = $(this).parent('li');
                    var a_text = $(this).text();
                    var href = $(this).attr('href');
                    closest_li.find('.sub-menu').prepend('<li><a href="' + href + '">' + a_text + '</a></li>');
                });

                $('.close-notice').click(function () {
                    $(this).closest('.nosun-notice').hide();
                    $('header').removeClass('show-notice');
                    Cookies.set('shownotice', false, {expires: 7});
                });

                $('#remove-notice-account').click(function () {
                    $(this).closest('.nosun-notice').hide();
                    Cookies.set('showaccountnotice', false, {expires: 7});
                    return false;
                });

                if ($('.filters-search').length) {
                    $(document).mouseup(function (e) {
                        var container = $(".filter-selects");
                        if (!container.is(e.target) && container.has(e.target).length === 0) {
                            $('.filter li .inner').removeClass('active');
                            $(".filter-title").removeClass('active');
                        }
                    });
                }

                $(window).scroll(function () {
                    var quote = $('.mobile-quote').closest('.sub-header'),
                        scroll = $(window).scrollTop();
                    if (scroll >= 100) {
                        quote.addClass('hide');
                    } else {
                        quote.removeClass('hide');
                    }
                });

                $(document).bind('gform_post_render', function () {
                    $('input').iCheck({
                        checkboxClass: 'icheckbox_square-yellow',
                        radioClass: 'iradio_square-yellow'
                    });
                });

                var allPanels = $('.accordion > .content').hide();

                $('.accordion > .header').click(function () {
                    var $this = $(this);
                    var $target = $this.next();
                    if (!$target.hasClass('active')) {
                        //allPanels.removeClass( 'active' ).slideUp();
                        $target.addClass('active').slideDown();
                    }
                    return false;
                });

                $('.rating-slider').slick({
                    dots: false,
                    infinite: true,
                    speed: 500,
                    autoplay: false,
                    autoplaySpeed: 4000,
                    arrows: true,
                    slide: '.rating-slide'
                });

                var delay;
                $('.menu-button').click(function () {
                    var elem = $(this);
                    elem.find(".hambergerIcon").toggleClass("open");
                    if (elem.hasClass('open') === true) {
                        delay = 0;
                        $('#mobileNav > li').each(function () {
                            $(this).delay(delay).animate({
                                opacity: 1,
                                marginTop: '-20px'
                            }, 100);
                            $(this).addClass('animate-it');
                            delay += 70;
                        });
                        $(this).removeClass('open');
                        $('body').removeClass('pushy-open-left');
                    } else {
                        $(this).addClass('open');
                        delay = 500;
                        $('#mobileNav > li').each(function () {
                            $(this).delay(delay).animate({
                                opacity: 1,
                                marginTop: '0px'
                            }, 200);
                            $(this).addClass('animate-it');
                            delay += 60;
                        });
                        $('body').addClass('pushy-open-left');
                    }
                });

                var mobile_li_with_children = $('#mobileNav > li.menu-item-has-children');
                mobile_li_with_children.prepend('<span class="arrow"></span>');

                mobile_li_with_children.on('click', function () {
                    if (!$(this).hasClass('open')) {
                        $('#mobileNav > li.menu-item-has-children.open > .sub-menu').slideToggle(200);
                        $('#mobileNav > li.menu-item-has-children.open').removeClass('open');
                    }
                    $(this).toggleClass('open');
                    $(this).find('.sub-menu').first().slideToggle(200);
                });

                var mobile_li_subs_with_children = $('#mobileNav .sub-menu > li.menu-item-has-children');
                mobile_li_subs_with_children.prepend('<span class="arrow"></span>');

                mobile_li_subs_with_children.find('.arrow').on('click', function () {
                    if ($(this).parent().hasClass('open')) {
                    } else {
                        $('#mobileNav .sub-menu > li.menu-item-has-children.open > .sub-menu').slideToggle(200);
                        $('#mobileNav .sub-menu > li.menu-item-has-children.open').removeClass('open');
                    }
                    $(this).parent().toggleClass('open');
                    $(this).parent().find('.sub-menu').slideToggle(200);
                });

                $(window).resize(function () {
                    setPushyHeight();
                });

                $('a.live-chat').click(function () {
                    return false;
                });

                var isMobile = false; //initiate as false
                // device detection
                if (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|ipad|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(navigator.userAgent) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(navigator.userAgent.substr(0, 4))) {
                    isMobile = true;
                }

                if (isMobile === true) {
                    var hoofdMenuLis = $('ul#menu-hoofdmenu > li.menu-item');
                    hoofdMenuLis.each(function (index, element) {
                        if ($(element).hasClass('menu-item-has-children')) {
                            var $this = $(this);
                            var thisClasses = $this.attr('class');
                            var aTag = $this.children('a');
                            var aTagHref = aTag.attr('href');
                            var aTagTitle = aTag.text();
                            if (!$this.hasClass('menu-item-17343')) {
                                var thisSubMenu = $this.children('ul.sub-menu');
                                var extraSubMenuItem = '<li class="' + thisClasses + '"><a href="' + aTagHref + '">' + aTagTitle + '</a></li>';
                                if (thisSubMenu) {
                                    thisSubMenu.prepend(extraSubMenuItem);
                                }
                            }
                            $(aTag).on('click', function (e) {
                                e.preventDefault();
                                return false;
                            });
                        }
                    });
                }

                /**
                 * FILTER SECTION (START)
                 */
                var $toggleFilter = $('.toggle-filter');
                $toggleFilter.click(function () {
                    $(this).toggleClass('active');
                    $('.filter').slideToggle(200);
                    $('.filters button[type="reset"]').toggleClass('active');
                    $('.home-hero__arrow').toggleClass('hide');
                });

                $('.filter li .inner .filter-title').click(function (e) {
                    e.stopPropagation();
                    var $this = $(this).parent().find('.inner');
                    var $title = $(this);
                    $(".filter li .inner").removeClass('active');
                    if ($title.hasClass('active')) {
                        $(this).removeClass('active');
                        $(".filter li .inner").removeClass('active');
                        $(".filter-title").removeClass('active');

                    } else {
                        $(".filter-title").removeClass('active');
                        $(this).closest('.inner').toggleClass('active');
                        $(this).addClass('active');
                    }
                });

                $('.filter li .inner .filter-title svg').click(function (e) {
                    e.stopPropagation();
                    $(this).closest('.active').removeClass('active');
                });

                $('.filter-selects').click(function (e) {
                    e.stopPropagation();
                });

                $('.home-hero__container').click(function () {
                    $('.filter li .inner').removeClass('active');
                    $(".filter-title").removeClass('active');
                });

                /* jshint ignore:start */

                $('.filter-selects li input[type="checkbox"], .filter-selects li label, .resetfilters').click(function () {
                    filterResults();
                });

                $('.inputS').on('input', function () {
                    filterResults();
                });

                $toggleFilter.click(function () {
                    $(this).toggleClass('active');

                    if ($(this).hasClass('active')) {
                        $(this).find("span").html('<i class="fas fa-minus"></i> sluit');
                    } else {
                        $(this).find("span").html('<i class="fas fa-plus"></i> open');
                    }

                    $('.filters-search').slideToggle();
                    return false;
                });
                /* jshint ignore:end */

                /**
                 * FILTER SECTION (END)
                 */
            },
            finalize: function () {
                // JavaScript to be fired on all pages, after page specific JS is fired
            }
        },
        'home': {
            init: function () {
                $('.home-hero__arrow').click(function () {
                    $('html, body').animate({
                        scrollTop: $(".usps").offset().top
                    }, 1000);
                    return false;
                });
            },
            finalize: function () {
            }
        },
        'page_template_template_account_booking': {
            init: function () {
                var busy = false;
                var questions = [];
                var correct_answers = false;
                $('.add-insurance-form').submit(function (e) {
                    e.preventDefault();
                    var $_form = $(this);
                    var $_submit_btn = $_form.find('button.submit-btn');
                    questions = $_form.serializeArray();
                    if (busy === true) {
                        return false;
                    }
                    var bookingId = $_submit_btn.data('booking-id');
                    var insuranceType = $_submit_btn.data('insurance-type');
                    var loader = $_form.find('.loading');

                    jQuery.ajax({
                        url: ajax_object.ajax_url,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'add_insurance',
                            booking_id: bookingId,
                            insurance_type: insuranceType,
                            questions: questions
                        },
                        beforeSend: function (response) {
                            loader.show();
                            busy = true;
                            $_submit_btn.prop('disabled', true);
                        },
                        complete: function () {
                            busy = false;
                            loader.hide();
                            window.location.href = templateUrl;
                        }
                    });
                });

                $('input').on('ifChanged', function (event) {
                    var $_form = $(this).closest('form');
                    questions = $_form.serializeArray();
                    correct_answers = true;
                    $.each(questions, function (index, item) {
                        if (item.value === '1') {
                            correct_answers = false;
                        }
                    });
                    if (correct_answers === false && questions.length === 2) {
                        $_form.find('.need-extra-info').show();
                    } else if (correct_answers === true && questions.length === 2) {
                        $_form.find('.need-extra-info').hide();
                    }
                });

                $('.add-extra-product-form').submit(function (e) {
                    e.preventDefault();
                    var $_form = $(this);
                    var $_submit_btn = $_form.find('button.submit-btn');
                    if (busy === true) {
                        return false;
                    }
                    var bookingId = $_submit_btn.data('booking-id');
                    var productId = $_submit_btn.data('product-id');
                    var loader = $_form.find('.loading');
                    jQuery.ajax({
                        url: ajax_object.ajax_url,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'add_extra_product',
                            booking_id: bookingId,
                            product_id: productId
                        },
                        beforeSend: function (response) {
                            loader.show();
                            busy = true;
                            $_submit_btn.prop('disabled', true);
                        },
                        complete: function () {
                            busy = false;
                            loader.hide();
                            window.location.href = templateUrl;
                        }
                    });
                });
            },
            finalize: function () {

            }
        },
        'single_template': {
            init: function () {
                if (window.location.hash) {
                    var hash = window.location.hash;
                    if (hash === "#route-map") {

                        $(window).load(function () {
                            $('html, body').animate({
                                scrollTop: $("#route_beschrijving").offset().top - 144
                            }, 600);
                        });
                    }
                    if (hash === "#beoordelingen") {
                        $('html, body').animate({
                            scrollTop: $(".single-trip__ratings").offset().top - 160
                        }, 600);
                    }
                }

                $('.product-slider').slick({
                    dots: true,
                    infinite: true,
                    speed: 500,
                    autoplaySpeed: 4000,
                    arrows: false,
                    appendDots: ".dotscontainer .inner",
                    slide: '.product-slider__slide'
                });

                $('.product-slider-container .custom-slick-prev').click(function () {
                    $(".product-slider").slick('slickPrev');
                });

                $('.product-slider-container  .custom-slick-next').click(function () {
                    $(".product-slider").slick('slickNext');
                });


                $('.trip-tabs a').click(function () {
                    var tab = $(this).data('tab');
                    $('.trip-tabs a').removeClass('active');
                    $(this).addClass('active');
                    $('.trip-tabs-content .tab').removeClass('active');
                    $('.' + tab).addClass('active');
                    return false;
                });

                var map_url = $('.route-map').data('map-url');
                var left_height = $('.left').height();
                $('.iframe-wrap-map').html('<iframe src="' + map_url + '" style="width: 100%;" height="' + left_height + 'px"></iframe>');

                var $grid = $('.data-grid').isotope({
                    itemSelector: '.data-block',
                    layoutMode: 'fitRows',
                    getSortData: {
                        available: '.available',
                        guaranteed: '.guaranteed',
                        price: function (itemElem) {
                            var price = $(itemElem).find('.data-price').text();
                            return parseFloat(price.replace(/,-|€/g, ''));
                        }
                    }
                });

                $('.data-filters li a').click(function () {
                        $('.data-filters li a').removeClass('active');
                        $(this).addClass('active');

                        var sortValue = $(this).attr('data-sort-value');

                        if (sortValue === 'guaranteed') {
                            $grid.isotope({filter: '.guaranteed'});
                        } else {
                            $grid.isotope({filter: '*'});
                            $grid.isotope({sortBy: sortValue});
                        }
                        return false;
                    }
                );

                $('.scroll-to-dates').click(function () {
                    $('html, body').animate({
                        scrollTop: $(".single-trip__data").offset().top - 150
                    }, 600);
                    return false;
                });

                $('.scroll-to-trip').click(function () {
                    $('html, body').animate({
                        scrollTop: $(".single-trip__introduction").offset().top - 150
                    }, 600);
                    return false;
                });

                $('.scroll-to-images').click(function () {
                    $('html, body').animate({
                        scrollTop: $(".single-trip__tabs").offset().top - 100
                    }, 600);
                    return false;
                });

                $('.scroll-to-program').click(function () {
                    $('html, body').animate({
                        scrollTop: $("#anchorProgram").offset().top - 100
                    }, 600);
                    return false;
                });

                $('.single-trip__travel-summary .rating-list-container').click(function () {
                    $('html, body').animate({
                        scrollTop: $(".single-trip__ratings").offset().top - 160
                    }, 800);
                });

                $('.mobileTabTitle').click(function () {
                    var tab = $(this).data('tab');
                    $(this).toggleClass('active');
                    $('.' + tab).toggleClass('active-mobile');
                    $(this).addClass('active');
                    return false;
                });

                $('a.show-more').click(function () {
                    $(this).closest('.column').find('ul').toggleClass('show');
                    if ($(this).closest('.column').find('ul').hasClass('show')) {
                        $(this).html('Toon minder <i class="fas fa-chevron-up"></i>');
                    } else {
                        $(this).html('Toon meer <i class="fas fa-chevron-down"></i>');
                    }
                    return false;
                });
            },
            finalize: function () {
            }
        },
        'single_destination': {
            init: function () {
                $('.destination-information__tabs a').click(function () {

                    var tab = $(this).data('tab');
                    $('.destination-information__tabs a').removeClass('active');
                    $(this).addClass('active');

                    $('.destination-information__data .pane').removeClass('active');
                    $('.tab-' + tab).addClass('active');

                    return false;
                });
            }
        },
        'single_employee': {
            init: function () {
                $('.employee-slider .container').slick({
                    slidesToShow: 4,
                    responsive: [
                        {
                            breakpoint: 768,
                            settings: {
                                arrows: false,
                                slidesToShow: 2
                            }
                        },
                        {
                            breakpoint: 480,
                            settings: {
                                arrows: false,
                                slidesToShow: 1
                            }
                        }
                    ]
                });
            }
        },
        'page_template_template_televisie': {
            init: function () {

            }
        },
        'single_travelgroup': {
            init: function () {
                var $messagePlaceholder = $('#chatMessagePlaceholder');
                var nonce = $messagePlaceholder.data('nonce');
                var $chatContainer = $('.chatContainer');
                var $noProfileImage = $('#noProfileImage > img');
                var latestMessageIds = [];

                function addMessageToChatbox(container, data, subGroup) {
                    if (!data.id || !data.user_type || !data.message || !data.created_at) {
                        return;
                    }
                    if (latestMessageIds[subGroup] >= data.id) {
                        return;
                    } else {
                        latestMessageIds[subGroup] = data.id;
                    }
                    var $newMessage = $('#chatMessagePlaceholder > .message').clone();
                    $newMessage.prop('id', '#message_' + data.id);
                    $newMessage.data('id', data.id);
                    var $profile = $newMessage.find('.message__profile');
                    var $image = null;
                    var $name = null;
                    if (data.user_type === 'customer') {
                        if (data.user_id) {
                            var $customer = $('#customer_' + data.user_id);
                            if ($customer.length > 0) {
                                $image = $customer.find('img');
                                $name = $customer.find('.chatSummary__name').text();
                                if (!$customer.data('is-current-user')) {
                                    $newMessage.addClass('message--other');
                                }
                            }
                        }
                        if (!$name) {
                            $name = 'Reiziger';
                            $newMessage.addClass('message--other');
                        }
                    } else if (data.user_type === 'tourguide') {
                        if (data.user_id) {
                            var $guide = $('#tourguide_'+data.user_id);
                            if ($guide.length > 0) {
                                $image = $guide.find('img');
                                $name = $guide.find('.chatSummary__name').text();
                                if (!$guide.data('is-current-user')) {
                                    $newMessage.addClass('message--nosun');
                                }
                            }
                        }
                        if (!$name) {
                            $name = 'Reisbegeleider';
                            $newMessage.addClass('message--nosun');
                        }
                    } else {
                        $name = 'noSun';
                        $newMessage.addClass('message--nosun');
                    }
                    if ($image !== null) {
                        $profile.prepend($image.clone());
                    } else {
                        $profile.prepend($noProfileImage.clone());
                    }
                    $profile.find('span').text($name);
                    var $inner = $newMessage.find('.inner');
                    $inner.prepend(data.message.replace(/\\/g, '')); // replaces backslashes ( \ ) with a empty string
                    $inner.find('span').html(
                        new Date(data.created_at).toLocaleDateString('nl-NL',
                            { day: 'numeric', month: 'long', year: 'numeric', hour: 'numeric', minute: 'numeric' })
                    );
                    container.find('.chat-container').append($newMessage);
                }

                function getMessages($container, travelGroup, subGroup) {
                    $.ajax({
                        url: ajax_object.ajax_url,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'get_new_travelgroup_messages',
                            travelgroup: travelGroup,
                            subgroup: subGroup,
                            latest_message: latestMessageIds[subGroup],
                            security: nonce
                        },
                        success: function (data) {
                            if (data && data.length) {
                                data.forEach(function (item) {
                                    addMessageToChatbox($container, item, subGroup);
                                });
                            }
                        },
                        failed: function () {
                            alert("Er gaat iets niet goed, neem contact met ons op!");
                        }
                    });
                }

                $chatContainer.each(function () {
                    var $container = $(this);
                    var travelGroup = $container.data('travelgroup');
                    var subGroup = $container.data('subgroup');
                    var $submitButton = $container.find('input[type="submit"]');
                    var $textArea = $container.find('textarea');
                    latestMessageIds[subGroup] = 0;

                    window.setInterval(function () { getMessages($container, travelGroup, subGroup); }, 10000);
                    getMessages($container, travelGroup, subGroup);

                    $submitButton.click(function (e) {
                        e.preventDefault();
                        $textArea.removeClass('error');
                        var message = $textArea.val();
                        if (message === '' || message === 'Typ hier je bericht...') {
                            $textArea.addClass('error');
                            return;
                        }
                        jQuery.ajax({
                            url: ajax_object.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'add_new_travelgroup_message',
                                travelgroup: travelGroup,
                                subgroup: subGroup,
                                message: message,
                                security: nonce
                            },
                            beforeSend: function () {
                                $('.form .loading').addClass('active');
                                $textArea.val('');
                                $submitButton.prop('disabled', true);
                            },
                            success: function (data) {
                                $('.form .loading').removeClass('active');
                                $textArea.val('');
                                addMessageToChatbox($container, data, subGroup);
                                $submitButton.prop('disabled', false);
                            },
                            failed: function () {
                                $('.form .loading').removeClass('active');
                                $textArea.val('');
                                $submitButton.prop('disabled', false);
                                alert("Er gaat iets niet goed, neem contact met ons op!");
                            }
                        });
                    });
                });
            }
        },
        'single_product': {
            init: function () {

                $(document).keypress(function (e) {
                    if (e.which === 13) {
                        return false;
                    }
                });

                var bookform = $('#bookform');

                $('.login-btn').click(function () {
                    $('.login-popup').slideToggle();
                    $('#bookform').slideToggle();

                    $(this).toggleClass('show-create');

                    if ($(this).hasClass('show-create')) {
                        $(this).html('<i class="fa fa-user"></i> Nieuw account aanmaken');
                    } else {
                        $(this).html('<i class="fa fa-user"></i> Eerder geboekt bij noSun? Login');
                    }

                    return false;
                });

                $('.booking-form__column').on('click', '.trigger-login-btn', function (e) {
                    $('.login-btn').trigger('click');
                    return false;
                });

                // Form validation
                bookform.validate({
                    submitHandler: function (form) {
                        $('button[name="submit_form"]').hide();
                        $('.loading').toggleClass('show');
                        form.submit();
                    },
                    rules: {
                        "customer[0][email]": {
                            required: true,
                            ifEmailExists: "#email_0"
                        },
                        "customer[0][email_confirm]": {
                            required: true,
                            equalToIgnoreCase: "#email_0"
                        },
                        "customer[0][first_name]": {
                            required: true
                        },
                        "customer[0][last_name]": {
                            required: true
                        },
                        "customer[0][nickname]": {
                            required: true
                        },

                        "customer[0][birthdate]": {
                            required: true,
                            dateFormat: true
                        },
                        "customer[0][phone]": {
                            required: true,
                            phoneNumbers: "#phone_0"
                        }
                    },
                    messages: {
                        "customer[0][email]": {
                            ifEmailExists: 'Dit e-mailadres bestaat al. <a href="#" class="trigger-login-btn"><b>Inloggen ></b></a>'
                        },
                        "customer[0][email_confirm]": {
                            equalToIgnoreCase: 'Vul hier dezelfde waarde in.'
                        }
                    },
                    focusInvalid: false,
                    invalidHandler: function (form, validator) {

                        if (!validator.numberOfInvalids()) {
                            return;
                        }

                        $('html, body').animate({
                            scrollTop: $(validator.errorList[0].element).offset().top - $('header.site-header').height() - 40
                        }, 500);
                    }
                });

                // When option is checked

                $('input#optie', bookform).on('ifChecked', function (event) {
                    $('.submit-btn', bookform).text('Neem een optie');
                    $('span.kind-of', bookform).text('optie');
                });

                $('input#optie', bookform).on('ifUnchecked', function (event) {
                    $('.submit-btn', bookform).text('Definitieve boeking');
                    $('span.kind-of', bookform).text('boeking');
                });

                // Add extra traveler
                var traveler_nr = 1;
                $('#add-extra-traveler').on('click', function (e) {
                        var extra_traveler_data = $("#extra-traveler-data").clone();

                        // Add class for selecting in other functions
                        extra_traveler_data.addClass('extra-traveler-block');

                        // Update the title of the block
                        extra_traveler_data.find('h3 b').text(traveler_nr);

                        // Update for attr of the labels
                        extra_traveler_data.find('label').each(function () {
                            var current_for = $(this).attr('for');
                            $(this).attr('for', current_for.replace('NUMBER', traveler_nr));
                        });

                        // Update id and name attr of the fields
                        extra_traveler_data.find('input').each(function () {
                            var current_id = $(this).attr('id');
                            var current_name = $(this).attr('name');
                            $(this).attr('id', current_id.replace('NUMBER', traveler_nr));
                            $(this).attr('name', current_name.replace('NUMBER', traveler_nr));
                        });

                        // Display the block and delete id attr
                        extra_traveler_data.removeAttr('id');
                        extra_traveler_data.show();

                        // Add the block to de container
                        extra_traveler_data.appendTo(".extra-travelers-container");

                        // Add required validation to all fields
                        extra_traveler_data.find("input").rules('add', {
                            required: true
                        });

                        // Add validation to email field
                        extra_traveler_data.find("input#email_" + traveler_nr).rules('add', {
                            required: true,
                            email: true
                        });

                        // Add validation to confirm email field
                        extra_traveler_data.find("input#email_confirm_" + traveler_nr).rules('add', {
                            required: true,
                            equalToIgnoreCase: "#email_" + traveler_nr,
                            messages: {
                                equalToIgnoreCase: 'Vul hier dezelfde waarde in.'
                            }
                        });

                        // Add validation to date field
                        extra_traveler_data.find("input#birthdate_" + traveler_nr).rules('add', {dateFormat: true});

                        // Add validation to phone field
                        extra_traveler_data.find("input#phone_" + traveler_nr).rules('add', {phoneNumbers: true});

                        // Focus on first input field
                        extra_traveler_data.find('input:first').focus();

                        // Scroll to block
                        $('html, body').animate({
                            scrollTop: extra_traveler_data.offset().top - 10 - $('.site-header').height()
                        }, 600);

                        traveler_nr++;
                        return false;
                    }
                );

                var count = 0;
                $('.delete-extra-traveler').live('click', function (e) {
                    e.preventDefault();

                    var extraTravelerBlock = $(this).closest('.extra-traveler-block');
                    extraTravelerBlock.remove();

                    traveler_nr -= 1;
                    count = 0;
                    $('.extra-traveler-block').each(function () {
                        count++;
                        $(this).find('h3 b').text(count);
                    });

                });

                $('#email_0').on('blur', function () {
                    var $field = $(this);
                    var email = $field.val();
                    var email_exists = false;

                    $field.addClass('loading');

                    $.post(
                        ajax_object.ajax_url,
                        {
                            'action': 'check_if_email_exists',
                            'email': email
                        },
                        function (response) {
                            if (response === 'true') {
                                $field.removeClass('valid loading').addClass('error email-exists');
                                $field.valid();
                            } else {
                                $field.removeClass('loading email-exists');
                                $field.valid();
                            }
                        }
                    );
                });

                if (getUrlParameter('email_exists')) {
                    var hash = window.location.hash;

                    if (hash === "") {

                        $(window).load(function () {
                            $('html, body').animate({
                                scrollTop: $(".booking-form").offset().top - 160
                            }, 600);
                        });
                    }
                }


            }
        },
        'page_template_template_booking_my_details': {
            init: function () {

                var bookform = $('#bookform');

                // Form validation
                bookform.validate({
                    submitHandler: function (form) {
                        form.submit();
                    }
                });

                // Add validation to phone field
                bookform.find(".phonenumber").each(function () {
                    $(this).rules('add', {phoneNumbers: true});
                });

                // Add validation to birth date field
                bookform.find(".birthdate").each(function () {
                    $(this).rules('add', {required: true, dateFormat: true});
                });

                // Add validation to birth postcode field
                bookform.find(".postcode").each(function () {
                    //$( this ).rules( 'add', {required: true, postalcodeNL: true} );
                });

                $('.accordion > .header:first').click();
            }
        },
        'page_template_template_contact': {
            init: function () {

                var url = WPURLS.siteurl;
                var nosunHQ = {lat: 52.203907, lng: 6.799550};
                var map = new google.maps.Map(document.getElementById('nosun-map'), {
                    zoom: 14,
                    center: nosunHQ
                });

                var contentString = '<div id="content">' +
                    '<div id="siteNotice">' +
                    '</div>' +
                    '<h4 id="firstHeading" class="firstHeading">noSun Reizen BV<br/> <a target="_blank" href="' + nosunRoute.nosun_route + '">Routebeschrijving' +
                    '</a></h4>' +
                    '</div>';

                var infowindow = new google.maps.InfoWindow({
                    content: contentString
                });

                var marker = new google.maps.Marker({
                    position: nosunHQ,
                    map: map,
                    title: 'noSun Reizen BV',
                    icon: url + '/wp-content/themes/nosun/dist/images/pointer.png'
                });
                marker.addListener('click', function () {
                    infowindow.open(map, marker);
                });

            }
        },
        'search': {
            init: function () {
                $('.filterInput').click(function () {
                    $('#searchform input[type="text"]').val('');
                    $("#searchform").submit();
                });

                $('.filterDestination').click(function () {
                    var dataTerm = $(this).data('destinationterm');
                    $("#" + dataTerm).prop("checked", false);
                    $("#searchform").submit();
                });

                $('.filterTerm').click(function () {
                    var dataTerm = $(this).data('typeterm');
                    $("#" + dataTerm).prop("checked", false);
                    $("#searchform").submit();
                });

                $('.filterDateRange').click(function () {
                    $('#searchform input[name="datum"]').val('');
                    $("#searchform").submit();
                });
            }
        },
        'woocommerce_edit_account': {
            init: function () {

                /**
                 * Validate user profile form
                 */
                var $EditAccountForm = $('.woocommerce-EditAccountForm');

                $EditAccountForm.validate({
                    submitHandler: function (form) {
                        $('button[name="save_account_details"]').attr('disabled', 'disabled');
                        $EditAccountForm.find('.loading').toggleClass('show');
                        form.submit();
                    },
                    rules: {
                        "account_email": {
                            required: true,
                            email: true
                        },
                        "dateofbirth": {
                            required: true,
                            dateFormat: true
                        },
                        "document-valid": {
                            required: false,
                            dateFormat: true
                        }
                    }
                });

                $EditAccountForm.find(".phonenumber").each(function () {
                    $(this).rules('add', {phoneNumbers: true});
                });

                /**
                 * Profile Image
                 */
                var profile_image_button = $('#upload_profile_image');
                var profile_image_input = $('input#profile_image');
                var profile_image_loader = $('.profile_image_loader');

                profile_image_button.on('click', function () {

                    var file_data = profile_image_input.prop('files')[0];
                    var form_data = new FormData();

                    form_data.append('profile_image', file_data);
                    form_data.append('action', 'profile_image');
                    form_data.append('profile_image_nonce', $('#profile_image_nonce').val());

                    $.ajax({
                        url: ajax_object.ajax_url,
                        type: 'POST',
                        dataType: 'json',
                        data: form_data,
                        cache: false,
                        contentType: false,
                        processData: false,
                        beforeSend: function () {
                            profile_image_loader.show();
                        },
                        success: function (response) {
                            profile_image_loader.hide();
                            if (response.redirect_url !== '') {
                                window.location.replace(response.redirect_url);
                            }
                        },
                        error: function (response) {
                        }
                    });

                });

                function readURL(input) {
                    if (profile_image_input.prop('files') && profile_image_input.prop('files')[0]) {
                        var reader = new FileReader();

                        reader.onload = function (e) {
                        };

                        reader.readAsDataURL(profile_image_input.prop('files')[0]);
                    }
                }

                profile_image_input.change(function () {
                    if (this.files[0].size > 1000000) {
                        alert("De maximale uploadgrootte is 1MB");
                        this.value = "";
                    } else {
                        profile_image_button.show();
                    }
                });
            }
        },
        'page_template_template_all_trips': {
            init: function () {
            }
        }
    };

// The routing fires all common scripts, followed by the page specific scripts.
// Add additional events for more control over timing e.g. a finalize event
    var UTIL = {
        fire: function (func, funcname, args) {
            var fire;
            var namespace = Sage;
            funcname = (funcname === undefined) ? 'init' : funcname;
            fire = func !== '';
            fire = fire && namespace[func];
            fire = fire && typeof namespace[func][funcname] === 'function';

            if (fire) {
                namespace[func][funcname](args);
            }
        },
        loadEvents: function () {
            // Fire common init JS
            UTIL.fire('common');

            // Fire page-specific init JS, and then finalize JS
            $.each(document.body.className.replace(/-/g, '_').split(/\s+/), function (i, classnm) {
                UTIL.fire(classnm);
                UTIL.fire(classnm, 'finalize');
            });

            // Fire common finalize JS
            UTIL.fire('common', 'finalize');
        }
    };

// Load Events
    $(document).ready(UTIL.loadEvents);

})
(jQuery); // Fully reference jQuery after this point.
