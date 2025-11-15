<?php
require __DIR__ . '/../vendor/autoload.php';
// Preparar entorno de BD de pruebas (silencioso si falla)
@require __DIR__ . '/db_setup.php';
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$_SESSION = [];
date_default_timezone_set('UTC');