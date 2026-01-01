<?php
if (php_sapi_name() !== 'cli-server') {
    die('Użyj: php -S localhost:8080 www.php');
}

$uri = $_SERVER['REQUEST_URI'];

// Przekieruj root na /wiki/
if ($uri === '/') {
    header('Location: /wiki/');
    exit;
}

// Obsługa wszystkich ścieżek zaczynających się od /wiki/
if (strpos($uri, '/wiki/') === 0) {
    // Usuń /wiki z początku URI dla DokuWiki
    $dokuwiki_uri = substr($uri, 5); // usuwa '/wiki'
    if (empty($dokuwiki_uri)) {
        $dokuwiki_uri = '/';
    }
    
    // Ustaw zmienne środowiskowe dla DokuWiki
    $_SERVER['REQUEST_URI'] = $dokuwiki_uri;
    $_SERVER['SCRIPT_NAME'] = '/doku.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/doku.php';
    $_SERVER['PHP_SELF'] = '/doku.php';
    
    // Debug: uncomment to see what's happening
    // error_log("Original URI: $uri, DokuWiki URI: $dokuwiki_uri");
    
    // Sprawdź czy to żądanie do plików w lib/exe/
    if (preg_match('@^/lib/exe/(.+\.php)@', $dokuwiki_uri, $matches)) {
        $target_file = __DIR__ . '/lib/exe/' . $matches[1];
        // Debug: uncomment to see what's happening
        // error_log("Trying to load: $target_file for URI: $dokuwiki_uri");
        if (file_exists($target_file)) {
            chdir(__DIR__);
            require $target_file;
            exit;
        }
    }
    
    // Sprawdź czy to żądanie do głównych plików PHP
    if (preg_match('@^/(doku|feed|install)\.php@', $dokuwiki_uri)) {
        $target_file = __DIR__ . $dokuwiki_uri;
        if (file_exists($target_file)) {
            chdir(__DIR__);
            require $target_file;
            exit;
        }
    }
    
    // Sprawdź czy to plik statyczny
    if (preg_match('/\.(css|js|png|jpg|gif|ico|svg|woff|woff2|ttf)$/', $dokuwiki_uri)) {
        $file = __DIR__ . $dokuwiki_uri;
        if (file_exists($file)) {
            return false; // Pozwól serwerowi obsłużyć plik
        }
    }
    
    // Domyślnie przekaż do doku.php
    chdir(__DIR__);
    require 'doku.php';
    exit;
}

// Obsługa bezpośrednich ścieżek DokuWiki (bez prefiksu /wiki/)
if (preg_match('@^/(lib|bin|inc|conf)/.*@', $uri) || 
    preg_match('@/(doku|feed|install)\.php@', $uri)) {
    return false;
}

// 404 dla innych ścieżek
http_response_code(404);
echo "404 - Strona nie znaleziona";
exit;
