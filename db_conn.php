<?php
/**
 * FILE: db_conn.php
 * PURPOSE: Loads private settings (config.php), sets the timezone,
 *          and opens the MySQL connection used by every page.
 */

/* --- 1. LOAD PRIVATE CONFIG --- */
if (!isset($CONFIG)) {
    $configFile = __DIR__ . '/config.php';
    if (!file_exists($configFile)) {
        http_response_code(500);
        die('Setup needed: copy config.example.php to config.php and fill in your database settings.');
    }
    $CONFIG = require $configFile;
}

/* --- 2. ERROR DISPLAY & TIMEZONE --- */
$APP_DEBUG = !empty($CONFIG['app']['debug']);
ini_set('display_errors', $APP_DEBUG ? '1' : '0');
error_reporting(E_ALL);
date_default_timezone_set($CONFIG['app']['timezone'] ?? 'Asia/Manila');

/* --- 3. ESTABLISH CONNECTION --- */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // throw exceptions so transactions roll back properly
try {
    $conn = new mysqli(
        $CONFIG['db']['host'],
        $CONFIG['db']['user'],
        $CONFIG['db']['pass'],
        $CONFIG['db']['name']
    );
    $conn->set_charset('utf8mb4');
    // Keep MySQL NOW() in the same timezone as PHP
    $conn->query("SET time_zone = '" . date('P') . "'");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die($APP_DEBUG ? 'Database connection failed: ' . $e->getMessage() : 'Database connection failed. Please try again later.');
}

/* --- 4. SMALL HELPERS --- */
function cfg($path, $default = null) {
    global $CONFIG;
    $val = $CONFIG;
    foreach (explode('.', $path) as $key) {
        if (!is_array($val) || !array_key_exists($key, $val)) return $default;
        $val = $val[$key];
    }
    return $val;
}

/** Base URL of the app, e.g. https://neulibproject.page.gd */
function app_url() {
    $url = rtrim((string) cfg('app.url', ''), '/');
    if ($url !== '') return $url;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir;
}

/** HTML-escape shortcut */
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
