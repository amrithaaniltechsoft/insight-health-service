<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Direct Storage file serving: immediately stream files from storage/app/public
// without routing overhead, avoiding any auth redirects or web server junction issues.
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if ($requestUri) {
    // If the browser cached or appended /login to a PDF URL, redirect cleanly to the PDF
    if (preg_match('#^/storage/(.+\.pdf)/login/?$#i', $requestUri, $matches)) {
        header('Location: /storage/' . $matches[1], true, 302);
        exit;
    }

    if (str_starts_with($requestUri, '/storage/')) {
        $relativePath = urldecode(substr($requestUri, strlen('/storage/')));
        // Prevent directory traversal
        $relativePath = str_replace(['..', '\\'], ['', '/'], $relativePath);
        $fullPath = realpath(__DIR__ . '/../storage/app/public/' . $relativePath);
        $baseStorage = realpath(__DIR__ . '/../storage/app/public');

        if ($fullPath && $baseStorage && str_starts_with(strtolower($fullPath), strtolower($baseStorage)) && is_file($fullPath)) {
            $mime = str_ends_with(strtolower($fullPath), '.pdf')
                ? 'application/pdf'
                : (mime_content_type($fullPath) ?: 'application/octet-stream');

            header('Content-Type: ' . $mime);
            header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
            header('Content-Length: ' . filesize($fullPath));
            header('Cache-Control: public, max-age=3600');
            readfile($fullPath);
            exit;
        }
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
