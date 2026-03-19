<?php
// 1. Simulan ang session para ma-access ang data nito
session_start();

// 2. Burahin ang lahat ng session variables
$_SESSION = array();

// 3. Kung may session cookie, burahin din ito para sa extra security
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Tuluyang sirain ang session sa server
session_destroy();

// 5. I-redirect ang user pabalik sa Login Page
// Siguraduhin na tama ang filename ng login page mo rito
header("Location: NEU LIBRARY - LOGIN.HTML");
exit();
?>