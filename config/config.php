<?php
/**
 * Global bootstrap: session, error display, path constants, settings cache.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_DEBUG') === '1' ? '1' : '0');

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

date_default_timezone_set('Asia/Karachi');
