<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: esqueci_senha.php');
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
if (!$email) {
    $_SESSION['erro'] = 'Informe um email válido.';
    header('Location: esqueci_senha.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, nome, ativo FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        $_SESSION['erro'] = 'Email não encontrado.';
        header('Location: esqueci_senha.php');
        exit;
    }
    if ((int)$usuario['ativo'] !== 1) {
        $_SESSION['erro'] = 'Usuário inativo.';
        header('Location: esqueci_senha.php');
        exit;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            INDEX (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $token = bin2hex(random_bytes(32));
    $expiresAt = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

    $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at, used) VALUES (?, ?, ?, 0)");
    $stmt->execute([(int)$usuario['id'], $token, $expiresAt]);

    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $resetLink = $baseUrl . $path . '/redefinir_senha.php?token=' . urlencode($token);

    $subject = 'Redefinição de senha - Chiptronic';
    $message = "Olá, {$usuario['nome']}.\r\n\r\n"
             . "Recebemos uma solicitação para redefinir sua senha.\r\n"
             . "Use o link abaixo (válido por 1 hora):\r\n\r\n"
             . $resetLink . "\r\n\r\n"
             . "Se você não solicitou, ignore este email.";
    $ok = @send_smtp_mail($email, $subject, $message, 'no-reply@chiptronic.com.br', 'no-reply@chiptronic.com.br');
    if (!$ok) {
        $headers = "From: no-reply@chiptronic.com.br\r\n"
                 . "Reply-To: no-reply@chiptronic.com.br\r\n"
                 . "Content-Type: text/plain; charset=UTF-8\r\n";
        $ok = @mail($email, $subject, $message, $headers);
    }
    $_SESSION['sucesso'] = $ok ? 'Enviamos o link de redefinição.' : 'Não foi possível enviar o email, mas o link foi gerado abaixo.';

    $_SESSION['sucesso'] = 'Enviamos o link de redefinição. Caso não receba, use o link mostrado abaixo.';
    $_SESSION['reset_link'] = $resetLink;
    header('Location: esqueci_senha.php');
    exit;
} catch (Exception $e) {
    error_log('Erro ao solicitar reset: ' . $e->getMessage());
    $_SESSION['erro'] = 'Não foi possível processar sua solicitação.';
    header('Location: esqueci_senha.php');
    exit;
}
