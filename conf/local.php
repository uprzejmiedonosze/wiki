<?php

$conf['title'] = 'Uprzejme Wiki';
$conf['lang'] = 'pl';
$conf['license'] = 'cc-by-sa';
$conf['disableactions'] = 'register';
$conf['template']    = 'uprzejmiedonosze';

if ($_SERVER['HTTP_HOST'] === 'localhost:8080') {
    $conf['basedir']    = '/';
    $conf['baseurl']    = 'http://localhost:8080/';
    $conf['cookiedir']  = '/';
} elseif ($_SERVER['HTTP_HOST'] === 'ud-dev.x93.org') {
    $conf['basedir']    = '/wiki/';
    $conf['baseurl']    = 'https://ud-dev.x93.org/';
    $conf['cookiedir']  = '/wiki/';
} else {
    $conf['basedir']    = '/';
    $conf['baseurl']    = '';
    $conf['cookiedir']  = '/';
}

$conf['userewrite'] = 1; 
$conf['useslash']   = 1;
$conf['sepchar']    = '_';

$conf['breadcrumbs'] = 0;
