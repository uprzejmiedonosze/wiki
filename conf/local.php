<?php

$conf['title'] = 'Uprzejme Wiki';
$conf['lang'] = 'pl';
$conf['license'] = 'cc-by-sa';
$conf['disableactions'] = 'register';
$conf['template']    = 'uprzejmiedonosze';

// Konfiguracja dla lokalnego developmentu
if ($_SERVER['HTTP_HOST'] === 'localhost:8080') {
    $conf['basedir']    = '/';
    $conf['baseurl']    = 'http://localhost:8080/wiki/';
    $conf['cookiedir']  = '/wiki/';
} else {
    // Konfiguracja produkcyjna
    $conf['basedir']    = '/wiki/';
    $conf['baseurl']    = 'https://uprzejmiedonosze.net/wiki/';
    $conf['cookiedir']  = '/wiki/';
}

$conf['userewrite'] = 2;
$conf['useslash']   = 1;
$conf['sepchar']    = '_';

$conf['breadcrumbs'] = 0;
