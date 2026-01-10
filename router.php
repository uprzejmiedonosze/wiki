<?php
/**
 * Simple router for PHP built-in server
 * Handles /wiki/lib/tpl/uprzejmiedonosze -> /lib/tpl/uprzejmiedonosze mapping
 */

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// If path starts with /wiki/lib/tpl/uprzejmiedonosze, remove /wiki prefix
if (strpos($path, '/wiki/lib/tpl/uprzejmiedonosze') === 0) {
    $newPath = substr($path, 5); // Remove '/wiki' (5 characters)
    $filePath = __DIR__ . $newPath;
    
    if (is_file($filePath)) {
        return false; // Let PHP serve the file from the correct path
    }
}

// For all other requests, let PHP handle normally
return false;
?>