<?php

$baseDir = dirname( __FILE__ );
$cacheDir = $baseDir . '/var/cache/sfc';

$origIncludePath = get_include_path();
set_include_path( implode( ':', array() ) );
include_once readlink( sprintf( '%s/SFC_%s.php', $cacheDir,
    str_replace( '/', '--slash--', $_SERVER['SCRIPT_URL'] ) ) );
set_include_path( $origIncludePath );
