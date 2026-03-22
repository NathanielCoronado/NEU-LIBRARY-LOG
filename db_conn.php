<?php
/* --- 1. DATABASE CONFIGURATION --- */
define('DB_SERVER', 'sql305.infinityfree.com');
define('DB_USERNAME', 'if0_41450029');
define('DB_PASSWORD', 'AbpaBt2909sgmC');
define('DB_NAME', 'if0_41450029_nath');

/* --- 2. ESTABLISH CONNECTION --- */
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

/* --- 3. ERROR HANDLING --- */
if (!$conn) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

/* --- 4. CHARACTER SET --- */
mysqli_set_charset($conn, "utf8mb4");

/* --- Note: No closing tag to prevent whitespace issues --- */