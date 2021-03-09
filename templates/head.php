<?php use lib\controllers\Booking; ?>
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= get_stylesheet_directory_uri(); ?>/dist/images/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= get_stylesheet_directory_uri(); ?>/dist/images/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= get_stylesheet_directory_uri(); ?>/dist/images/favicons/favicon-16x16.png">
    <link rel="manifest" href="<?= get_stylesheet_directory_uri(); ?>/dist/images/favicons/manifest.json">
    <link rel="mask-icon" href="<?= get_stylesheet_directory_uri(); ?>/dist/images/favicons/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="theme-color" content="#ffffff">

    <!-- Google Tag Manager -->
    <script>(function ( w, d, s, l, i ) {
            w[l] = w[l] || [];
            w[l].push(
                {'gtm.start': new Date().getTime(), event: 'gtm.js'}
            );
            var f = d.getElementsByTagName( s )[0],
                j = d.createElement( s ), dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src =
                'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore( j, f );
        })( window, document, 'script', 'dataLayer', 'GTM-WBD7JT' );</script>
    <!-- End Google Tag Manager -->

    <!-- Start VWO Asynchronous Code -->
    <script type='text/javascript'>
        var _vwo_code = (function () {
            var account_id = 268356,
                settings_tolerance = 2000,
                library_tolerance = 2500,
                use_existing_jquery = false,
                /* DO NOT EDIT BELOW THIS LINE */
                f = false, d = document;
            return {
                use_existing_jquery: function () {
                    return use_existing_jquery;
                }, library_tolerance: function () {
                    return library_tolerance;
                }, finish: function () {
                    if ( !f ) {
                        f = true;
                        var a = d.getElementById( '_vis_opt_path_hides' );
                        if ( a ) a.parentNode.removeChild( a );
                    }
                }, finished: function () {
                    return f;
                }, load: function ( a ) {
                    var b = d.createElement( 'script' );
                    b.src = a;
                    b.type = 'text/javascript';
                    b.innerText;
                    b.onerror = function () {
                        _vwo_code.finish();
                    };
                    d.getElementsByTagName( 'head' )[0].appendChild( b );
                }, init: function () {
                    settings_timer = setTimeout( '_vwo_code.finish()', settings_tolerance );
                    var a = d.createElement( 'style' ),
                        b = 'body{opacity:0 !important;filter:alpha(opacity=0) !important;background:none !important;}',
                        h = d.getElementsByTagName( 'head' )[0];
                    a.setAttribute( 'id', '_vis_opt_path_hides' );
                    a.setAttribute( 'type', 'text/css' );
                    if ( a.styleSheet ) a.styleSheet.cssText = b; else a.appendChild( d.createTextNode( b ) );
                    h.appendChild( a );
                    this.load( '//dev.visualwebsiteoptimizer.com/j.php?a=' + account_id + '&u=' + encodeURIComponent( d.URL ) + '&r=' + Math.random() );
                    return settings_timer;
                }
            };
        }());
        _vwo_settings_timer = _vwo_code.init();
    </script>
    <!-- End VWO Asynchronous Code -->

    <!-- Facebook Helper -->
    <script type="text/javascript">
        function waitForFbq( callback ) {
            if ( typeof fbq !== 'undefined' ) {
                callback()
            } else {
                setTimeout( function () {
                    waitForFbq( callback )
                }, 100 )
            }
        }
    </script>
    <!-- End Facebook Helper -->

    <script type="text/javascript">
        <?php global $wp; ?>
        var templateUrl = '<?= home_url( $wp->request ); ?>';
    </script>

    <?php wp_head(); ?>
</head>
