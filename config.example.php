<?php
/**
 * FILE: config.example.php
 * PURPOSE: Template for your private settings.
 *
 * HOW TO USE:
 *   1. Copy this file and rename the copy to  config.php
 *   2. Fill in your real values below.
 *   3. NEVER commit config.php to GitHub (it is already listed in .gitignore).
 */

return [
    /* --- DATABASE (MySQL / MariaDB) --- */
    'db' => [
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'name' => 'neu_library',
    ],

    /* --- MASTER ADMIN ACCOUNT (works even on Sundays) --- */
    'admin' => [
        'email'    => 'admin@neu.edu.ph',
        'password' => 'change-me',
    ],

    /* --- GMAIL SMTP (use a Google "App Password", not your real password) --- */
    'mail' => [
        'host'      => 'smtp.gmail.com',
        'port'      => 587,
        'username'  => 'your.name@neu.edu.ph',
        'password'  => 'xxxx xxxx xxxx xxxx',
        'from'      => 'noreply@neu.edu.ph',
        'from_name' => 'NEU Library',
    ],

    /* --- GOOGLE SIGN-IN --- */
    'google_client_id' => 'YOUR_CLIENT_ID.apps.googleusercontent.com',

    /* --- APP SETTINGS --- */
    'app' => [
        // Public URL of the site, no trailing slash. Leave '' to auto-detect.
        'url'      => '',
        'timezone' => 'Asia/Manila',
        // Long random string used to sign e-mail verification links.
        // Generate one with:  php -r "echo bin2hex(random_bytes(32));"
        'secret'   => 'replace-with-a-long-random-string',
        // Minutes before a password-reset link expires.
        'reset_minutes' => 10,
        // Set to true only while debugging on your own computer.
        'debug'    => false,
    ],
];
