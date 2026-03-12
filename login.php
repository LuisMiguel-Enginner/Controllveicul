<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Chiptronic</title>
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
        <div class="tech-detail top-right">SECURE_LOGIN</div>
        <div class="tech-detail bottom-left">CHIPTRONIC©</div>
        <div class="tech-detail bottom-right">ID: #A7F2</div>
        <div class="logo-container">
            <img src="chip2.png" alt="Chiptronic Logo">
        </div>

        <h2>Controll Veicul</h2>

        <?php if (isset($_SESSION['erro'])): ?>
            <div class="message error" style="padding: 15px; border-radius: 8px; margin-bottom: 20px; background: rgba(255, 0, 0, 0.1); border: 2px solid rgba(255, 0, 0, 0.5); color: #ff0000; text-align: center;">
                <?php 
                echo htmlspecialchars($_SESSION['erro']); 
                unset($_SESSION['erro']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['sucesso'])): ?>
            <div class="message success" style="padding: 15px; border-radius: 8px; margin-bottom: 20px; background: rgba(0, 255, 0, 0.08); border: 2px solid rgba(0, 200, 0, 0.5); color: #00aa00; text-align: center;">
                <?php 
                echo htmlspecialchars($_SESSION['sucesso']); 
                unset($_SESSION['sucesso']);
                ?>
            </div>
        <?php endif; ?>

        <form action="auth.php" method="POST">
            <div class="input-group">
                <input type="email" name="email" placeholder="📧 Email" required>
            </div>

            <div class="input-group">
                <input type="password" name="senha" placeholder="🔒 Senha" required>
            </div>

            <button type="submit">Entrar</button>
        </form>

        <div class="link-container" style="margin-top: 10px;">
            <p>
                Esqueceu a senha?
                <a href="esqueci_senha.php">Recuperar acesso</a>
            </p>
        </div>

        <div class="link-container">
            <p>
                Não tem conta?
                <a href="salvar_usuario.php">Cadastrar usuário</a>
            </p>
        </div>
    </div>
</body>
</html>

