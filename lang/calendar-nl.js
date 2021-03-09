!function ( e, a ) {
    "object" == typeof exports && "object" == typeof module ? module.exports = a( require( "moment" ), require( "fullcalendar" ) ) : "function" == typeof define && define.amd ? define( ["moment", "fullcalendar"], a ) : "object" == typeof exports ? a( require( "moment" ), require( "fullcalendar" ) ) : a( e.moment, e.FullCalendar )
}( "undefined" != typeof self ? self : this, function ( e, a ) {
    return function ( e ) {
        function a( t ) {
            if ( n[t] ) return n[t].exports;
            var r = n[t] = {i: t, l: !1, exports: {}};
            return e[t].call( r.exports, r, r.exports, a ), r.l = !0, r.exports
        }

        var n = {};
        return a.m = e, a.c = n, a.d = function ( e, n, t ) {
            a.o( e, n ) || Object.defineProperty( e, n, {configurable: !1, enumerable: !0, get: t} )
        }, a.n = function ( e ) {
            var n = e && e.__esModule ? function () {
                return e.default
            } : function () {
                return e
            };
            return a.d( n, "a", n ), n
        }, a.o = function ( e, a ) {
            return Object.prototype.hasOwnProperty.call( e, a )
        }, a.p = "", a( a.s = 169 )
    }( {
        0: function ( a, n ) {
            a.exports = e
        }, 1: function ( e, n ) {
            e.exports = a
        }, 169: function ( e, a, n ) {
            Object.defineProperty( a, "__esModule", {value: !0} ), n( 170 );
            var t = n( 1 );
            t.datepickerLocale( "nl", "nl", {
                closeText: "Sluiten",
                prevText: "←",
                nextText: "→",
                currentText: "Vandaag",
                monthNames: ["januari", "februari", "maart", "april", "mei", "juni", "juli", "augustus", "september", "oktober", "november", "december"],
                monthNamesShort: ["jan", "feb", "mrt", "apr", "mei", "jun", "jul", "aug", "sep", "okt", "nov", "dec"],
                dayNames: ["zondag", "maandag", "dinsdag", "woensdag", "donderdag", "vrijdag", "zaterdag"],
                dayNamesShort: ["zon", "maa", "din", "woe", "don", "vri", "zat"],
                dayNamesMin: ["zo", "ma", "di", "wo", "do", "vr", "za"],
                weekHeader: "Wk",
                dateFormat: "dd-mm-yy",
                firstDay: 1,
                isRTL: !1,
                showMonthAfterYear: !1,
                yearSuffix: ""
            } ), t.locale( "nl", {
                buttonText: {year: "Jaar", month: "Maand", week: "Week", day: "Dag", list: "Agenda"},
                allDayText: "Hele dag",
                eventLimitText: "extra",
                noEventsMessage: "Geen evenementen om te laten zien"
            } )
        }, 170: function ( e, a, n ) {
            !function ( e, a ) {
                a( n( 0 ) )
            }( 0, function ( e ) {
                var a = "jan._feb._mrt._apr._mei_jun._jul._aug._sep._okt._nov._dec.".split( "_" ),
                    n = "jan_feb_mrt_apr_mei_jun_jul_aug_sep_okt_nov_dec".split( "_" ),
                    t = [/^jan/i, /^feb/i, /^maart|mrt.?$/i, /^apr/i, /^mei$/i, /^jun[i.]?$/i, /^jul[i.]?$/i, /^aug/i, /^sep/i, /^okt/i, /^nov/i, /^dec/i],
                    r = /^(januari|februari|maart|april|mei|april|ju[nl]i|augustus|september|oktober|november|december|jan\.?|feb\.?|mrt\.?|apr\.?|ju[nl]\.?|aug\.?|sep\.?|okt\.?|nov\.?|dec\.?)/i;
                return e.defineLocale( "nl", {
                    months: "januari_februari_maart_april_mei_juni_juli_augustus_september_oktober_november_december".split( "_" ),
                    monthsShort: function ( e, t ) {
                        return e ? /-MMM-/.test( t ) ? n[e.month()] : a[e.month()] : a
                    },
                    monthsRegex: r,
                    monthsShortRegex: r,
                    monthsStrictRegex: /^(januari|februari|maart|mei|ju[nl]i|april|augustus|september|oktober|november|december)/i,
                    monthsShortStrictRegex: /^(jan\.?|feb\.?|mrt\.?|apr\.?|mei|ju[nl]\.?|aug\.?|sep\.?|okt\.?|nov\.?|dec\.?)/i,
                    monthsParse: t,
                    longMonthsParse: t,
                    shortMonthsParse: t,
                    weekdays: "zondag_maandag_dinsdag_woensdag_donderdag_vrijdag_zaterdag".split( "_" ),
                    weekdaysShort: "zo._ma._di._wo._do._vr._za.".split( "_" ),
                    weekdaysMin: "zo_ma_di_wo_do_vr_za".split( "_" ),
                    weekdaysParseExact: !0,
                    longDateFormat: {
                        LT: "HH:mm",
                        LTS: "HH:mm:ss",
                        L: "DD-MM-YYYY",
                        LL: "D MMMM YYYY",
                        LLL: "D MMMM YYYY HH:mm",
                        LLLL: "dddd D MMMM YYYY HH:mm"
                    },
                    calendar: {
                        sameDay: "[vandaag om] LT",
                        nextDay: "[morgen om] LT",
                        nextWeek: "dddd [om] LT",
                        lastDay: "[gisteren om] LT",
                        lastWeek: "[afgelopen] dddd [om] LT",
                        sameElse: "L"
                    },
                    relativeTime: {
                        future: "over %s",
                        past: "%s geleden",
                        s: "een paar seconden",
                        ss: "%d seconden",
                        m: "één minuut",
                        mm: "%d minuten",
                        h: "één uur",
                        hh: "%d uur",
                        d: "één dag",
                        dd: "%d dagen",
                        M: "één maand",
                        MM: "%d maanden",
                        y: "één jaar",
                        yy: "%d jaar"
                    },
                    dayOfMonthOrdinalParse: /\d{1,2}(ste|de)/,
                    ordinal: function ( e ) {
                        return e + (1 === e || 8 === e || e >= 20 ? "ste" : "de")
                    },
                    week: {dow: 1, doy: 4}
                } )
            } )
        }
    } )
} );