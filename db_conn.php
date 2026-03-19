<?php
/* --- 1. DATABASE CONFIGURATION --- */
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'neu_library_signup_db');

/* --- 2. ESTABLISH CONNECTION --- */
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

/* --- 3. ERROR HANDLING --- */
if (!$conn) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

/* --- 4. CHARACTER SET --- */
// Mahalaga para sa special characters at profile URLs
mysqli_set_charset($conn, "utf8mb4");

/* --- Note: No closing tag to prevent whitespace issues --- */