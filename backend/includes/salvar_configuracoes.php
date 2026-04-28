<?php
require_once 'verificar_sessao.php';
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: configuracoes.php');
    exit;
}

$timezone = $_POST['timezone'] ?? 'America/Sao_Paulo';
$date_format = $_POST['date_format'] ?? 'long';
$timeout_minutes = isset($_POST['timeout_minutes']) ? (int)$_POST['timeout_minutes'] : 30;
$notifications_enabled = isset($_POST['notifications_enabled']) ? (int)$_POST['notifications_enabled'] : 1;

$valid_tz = ['America/Sao_Paulo','America/Manaus','America/Bahia','America/Fortaleza','America/Recife'];
if (!in_array($timezone, $valid_tz, true)) {
    $timezone = 'America/Sao_Paulo';
}
if (!in_array($date_format, ['long','short'], true)) {
    $date_format = 'long';
}
if ($timeout_minutes < 5) $timeout_minutes = 5;
if ($timeout_minutes > 240) $timeout_minutes = 240;
$notifications_enabled = $notifications_enabled ? 1 : 0;

$_SESSION['timezone'] = $timezone;
$_SESSION['date_format'] = $date_format;
$_SESSION['timeout'] = $timeout_minutes * 60;
$_SESSION['notifications_enabled'] = (bool)$notifications_enabled;

@date_default_timezone_set($timezone);

$_SESSION['sucesso'] = 'Configurações salvas com sucesso.';
header('Location: configuracoes.php');
exit;
