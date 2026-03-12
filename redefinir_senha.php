<?php
session_start();
require_once 'config.php';

$token = $_GET['token'] ?? '';
$tokenValido = false;
$usuarioId = null;

if ($token) {
    try {
        $stmt = $pdo->prepare("
            SELECT pr.user_id, pr.expires_at, pr.used
            FROM password_resets pr
            WHERE pr.token = ?
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();
        if ($reset) {
            $exp = strtotime($reset['expires_at']);
            if ((int)$reset['used'] === 0 && $exp > time()) {
                $tokenValido = true;
                $usuarioId = (int)$reset['user_id'];
            }
        }
    } catch (Exception $e) {
        $tokenValido = false;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Chiptronic</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="scan-line"></div>
    <div class="scan-line"></div>
    <div class="scan-line"></div>
    <div class="tech-circle"></div>
    <div class="tech-circle"></div>

    <div class="login-container">
        <div class="tech-detail top-left">SYS_v2.4.1</div>
        <div class="tech-detail top-right">RESET_PASS</div>
        <div class="tech-detail bottom-left">CHIPTRONIC©</div>
        <div class="tech-detail bottom-right">ID: #R3S3</div>
        <div class="logo-container">
            <img src="chip2.png" alt="Chiptronic Logo">
        </div>

        <h2>Redefinir senha</h2>

        <?php if (isset($_SESSION['erro'])): ?>
            <div class="message error" style="padding: 15px; border-radius: 8px; margin-bottom: 20px; background: rgba(255, 0, 0, 0.1); border: 2px solid rgba(255, 0, 0, 0.5); color: #ff0000; text-align: center;">
                <?php 
                echo htmlspecialchars($_SESSION['erro']); 
                unset($_SESSION['erro']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (!$tokenValido): ?>
            <div class="message error" style="padding: 15px; border-radius: 8px; margin-bottom: 20px; background: rgba(255, 0, 0, 0.1); border: 2px solid rgba(255, 0, 0, 0.5); color: #ff0000; text-align: center;">
                Link inválido ou expirado.
            </div>
            <div class="link-container" style="margin-top: 10px;">
                <p>
                    Solicite um novo link:
                    <a href="esqueci_senha.php">Recuperar acesso</a>
                </p>
            </div>
        <?php else: ?>
            <form action="aplicar_reset.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="input-group">
                    <input type="password" name="senha" placeholder="🔒 Nova senha (mínimo 6 caracteres)" required minlength="6">
                </div>
                <div class="input-group">
                    <input type="password" name="confirmar_senha" placeholder="🔒 Confirmar nova senha" required minlength="6">
                </div>
                <button type="submit">Redefinir senha</button>
            </form>
        <?php endif; ?>

        <div class="link-container" style="margin-top: 10px;">
            <p>
                Voltar ao login
                <a href="login.php">Login</a>
            </p>
        </div>
    </div>
</body>
</html>
