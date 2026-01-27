<?php

$conf['title'] = 'Uprzejme Wiki';
$conf['lang'] = 'pl';
$conf['license'] = 'cc-by-sa';
$conf['disableactions'] = 'register';
$conf['template']    = 'uprzejmiedonosze';

function isProd(): bool {
    return $_SERVER['HTTP_HOST'] == 'uprzejmiedonosze.net' || $_SERVER['HTTP_HOST'] == 'shadow.uprzejmiedonosze.net';
}

function isStaging(): bool {
    return $_SERVER['HTTP_HOST'] == 'wiki.uprzejmiedonosze.net';
}

function isDev(): bool {
    return !isProd() && !isStaging();
}
  
$conf['useslash']   = 0;
$conf['sepchar']    = '_';
$conf['breadcrumbs'] = 0;

$conf['basedir']    = '/wiki/';
$conf['baseurl']    = '';
$conf['cookiedir']  = '/wiki/';
$conf['userewrite'] = 2;
$conf['useacl']     = 1;
$conf['defaultgroup'] = 'user';

 if ($_SERVER['HTTP_HOST'] === 'ud-dev.x93.org') {
    $conf['basedir']    = '/wiki/';
    $conf['baseurl']    = 'https://ud-dev.x93.org/';
    $conf['cookiedir']  = '/wiki/';
} else if (isDev()) {
    $conf['basedir']    = '/';
    $conf['baseurl']    = '';
    $conf['cookiedir']  = '/';
    $conf['userewrite'] = 0;
    $conf['useacl']     = 0;
} else if (isStaging()) {
    require(dirname(__FILE__) . '/local-staging.php');
} else if (isProd()) {
    require(dirname(__FILE__) . '/local-prod.php');
}
