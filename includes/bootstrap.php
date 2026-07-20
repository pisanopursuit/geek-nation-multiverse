<?php
declare(strict_types=1);

const GNM_ROOT = __DIR__ . '/..';
$configFile = GNM_ROOT . '/config/config.php';
if (!file_exists($configFile)) {
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'install.php') {
        header('Location: /install.php');
        exit;
    }
    return;
}
$config = require $configFile;
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($config['app']['session_name'] ?? 'gnm_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
require_once GNM_ROOT . '/includes/functions.php';
require_once GNM_ROOT . '/includes/mailer.php';
