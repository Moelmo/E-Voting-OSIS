<?php

declare(strict_types=1);

session_start();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
	$cookie = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
}

session_destroy();
header('Location: index.php?success=' . rawurlencode('Kamu sudah keluar dari bilik suara.'));
exit;
