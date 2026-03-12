<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$token = $_POST['token'] ?? '';
$senha = $_POST['senha'] ?? '';
$confirmar = $_POST['confirmar_senha'] ?? '';

if (!$token || strlen($senha) < 6 || $senha !== $confirmar) {
    $_SESSION['erro'] = 'Verifique os campos informados.';
    header('Location: redefinir_senha.php?token=' . urlencode($token));
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT pr.id, pr.user_id, pr.expires_at, pr.used
        FROM password_resets pr
        WHERE pr.token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();
    if (!$reset) {
        $_SESSION['erro'] = 'Token inválido.';
        header('Location: redefinir_senha.php?token=' . urlencode($token));
        exit;
    }
    if ((int)$reset['used'] === 1 || strtotime($reset['expires_at']) <= time()) {
        $_SESSION['erro'] = 'Token expirado.';
        header('Location: redefinir_senha.php?token=' . urlencode($token));
        exit;
    }

    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
    $stmt->execute([$hash, (int)$reset['user_id']]);

    $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
    $stmt->execute([(int)$reset['id']]);
    $pdo->commit();

    $_SESSION['sucesso'] = 'Senha redefinida com sucesso. Faça login com sua nova senha.';
    header('Location: login.php');
    exit;
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Erro ao aplicar reset: ' . $e->getMessage());
    $_SESSION['erro'] = 'Não foi possível redefinir a senha.';
    header('Location: redefinir_senha.php?token=' . urlencode($token));
    exit;
}
