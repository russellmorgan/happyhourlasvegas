<?php
// Local dev only: outbound HTTP inside PHP-FPM crashes the worker on this Mac (objc fork-safety SIGABRT → 502).
// GeoDirectory's Font Awesome settings check GitHub for the latest version; answer it locally instead.
// ponytail: pinned version, bump it when you want a newer Font Awesome.
add_filter( 'pre_transient_wp-font-awesome-settings-version', function () { return '6.7.2'; } );
