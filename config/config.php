<?php
/**
 * Global bootstrap: session, error display, path constants, settings cache.
 */

// Buffers all output so redirect()/header() still work even after a page has already echoed HTML
// (several pages require includes/header.php — which prints markup — before checking auth and
// calling redirect()). Without this, header() fails with "headers already sent" whenever the
// server's php.ini doesn't already enable output_buffering (e.g. most production/cPanel hosts,
// unlike XAMPP's default which masked this locally).
if (!ob_get_level()) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_DEBUG') === '1' ? '1' : '1');

define('BASE_PATH', dirname(__DIR__));

/**
 * BASE_URL points at the site root (where /assets, /admin, /api live),
 * regardless of which sub-folder the current script runs from.
 * Assumes the project root is served as the web root.
 */
define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') .
    ($_SERVER['HTTP_HOST'] ?? 'localhost'));

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/loyalty.php';

date_default_timezone_set('Asia/Karachi');
