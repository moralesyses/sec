<?php

/*
|--------------------------------------------------------------------------
| Environment loader
|--------------------------------------------------------------------------
| Reads settings from a `.env` file next to this file.
| If `.env` is missing, falls back to safe local XAMPP defaults.
|
|   local      -> errors shown, PLAIN_PASSWORDS allowed
|   production -> errors hidden, PLAIN_PASSWORDS forced OFF
|--------------------------------------------------------------------------
*/

function load_env(string $path): array {
    $vars = [];

    if (!is_file($path)) {
        return $vars;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');

        $vars[trim($key)] = trim($value);
    }

    return $vars;
}

$env = load_env(__DIR__ . '/.env');

define('APP_ENV', $env['APP_ENV'] ?? 'local');

define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
define('DB_NAME', $env['DB_NAME'] ?? 'sec_db');
define('DB_USER', $env['DB_USER'] ?? 'root');
define('DB_PASS', $env['DB_PASS'] ?? '');

/*
|--------------------------------------------------------------------------
| Passwords
|--------------------------------------------------------------------------
| PLAIN_PASSWORDS can only ever be true in local. In production it is
| forced to false no matter what .env says — plaintext passwords must
| never reach a live server.
|--------------------------------------------------------------------------
*/

$plain = strtolower($env['PLAIN_PASSWORDS'] ?? 'false') === 'true';

define('PLAIN_PASSWORDS', APP_ENV === 'production' ? false : $plain);

/*
|--------------------------------------------------------------------------
| Error display
|--------------------------------------------------------------------------
*/

if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

/*
|--------------------------------------------------------------------------
| Database connection
|--------------------------------------------------------------------------
*/

function db(): mysqli {
    static $conn = null;

    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_OFF);

        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            if (APP_ENV === 'production') {
                die('We are experiencing technical difficulties. Please try again later.');
            }

            die('Database connection failed: ' . htmlspecialchars($conn->connect_error));
        }

        $conn->set_charset('utf8mb4');
    }

    return $conn;
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
