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
    // Some hosts (e.g. cPanel with open_basedir) block PHP's default session save path,
    // which silently starts a fresh session on every request and breaks CSRF/login.
    // Use a writable folder inside the project instead.
    $sessionPath = dirname(__DIR__) . '/storage/sessions';
    if (!is_dir($sessionPath)) {
        @mkdir($sessionPath, 0755, true);
    }
    if (is_dir($sessionPath) && is_writable($sessionPath)) {
        @session_save_path($sessionPath);
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    // Suppressed: a failed session start (e.g. open_basedir restriction) must never leak
    // a warning into the response body — that alone breaks JSON API responses like the POS integration.
    @session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_DEBUG') === '1' ? '1' : '0');
ini_set('log_errors', '1');

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
