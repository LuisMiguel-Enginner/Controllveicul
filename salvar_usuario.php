<?php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $setor_nome = trim($_POST['setor_nome'] ?? '');
    $perfil_id = filter_var($_POST['perfil_id'] ?? 3, FILTER_VALIDATE_INT, ['options' => ['default' => 3]]);
    if (!in_array($perfil_id, [2, 3], true)) {
        $perfil_id = 3;
    }
    $setor_id = 0;
    
    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha) || empty($setor_nome)) {
        $_SESSION['erro'] = 'Todos os campos são obrigatórios.';
        header('Location: salvar_usuario.php');
        exit;
    }
    
    if (strlen($senha) < 6) {
        $_SESSION['erro'] = 'A senha deve ter no mínimo 6 caracteres.';
        header('Location: salvar_usuario.php');
        exit;
    }
    
    if ($senha !== $confirmar_senha) {
        $_SESSION['erro'] = 'As senhas não conferem.';
        header('Location: salvar_usuario.php');
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['erro'] = 'Este email já está cadastrado.';
            header('Location: salvar_usuario.php');
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT id FROM setores WHERE nome = ?");
        $stmt->execute([$setor_nome]);
        $row = $stmt->fetch();
        if ($row && isset($row['id'])) {
            $setor_id = (int)$row['id'];
        } else {
            $stmtIns = $pdo->prepare("INSERT INTO setores (nome) VALUES (?)");
            $stmtIns->execute([$setor_nome]);
            $setor_id = (int)$pdo->lastInsertId();
        }
        
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome, email, senha, perfil_id, setor_id, ativo) 
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$nome, $email, $senha_hash, $perfil_id, $setor_id]);
        
        $_SESSION['sucesso'] = 'Cadastro realizado com sucesso! Faça login.';
        header('Location: login.php');
        exit;
        
    } catch(PDOException $e) {
        $_SESSION['erro'] = 'Erro ao cadastrar usuário. Tente novamente.';
        error_log('Erro no cadastro: ' . $e->getMessage());
        header('Location: salvar_usuario.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Chiptronic</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="scan-line"></div>
    <div class="scan-line"></div>
    <div class="scan-line"></div>
    <div class="tech-circle"></div>
    <div class="tech-circle"></div>

    <div class="cadastro-container">
        <div class="tech-detail top-left">SYS_v2.4.1</div>
        <div class="tech-detail top-right">NEW_USER</div>
        <div class="tech-detail bottom-left">CHIPTRONIC©</div>
        <div class="tech-detail bottom-right">ID: #B9K3</div>

        <div class="logo-container">
            <img src="chip2.png" alt="Chiptronic Logo">
        </div>

        <h2>Novo Cadastro</h2>

        <?php if (isset($_SESSION['erro'])): ?>
            <div class="message error" style="padding: 15px; border-radius: 8px; margin-bottom: 20px; background: rgba(255, 0, 0, 0.1); border: 2px solid rgba(255, 0, 0, 0.5); color: #ff0000; text-align: center;">
                <?php 
                echo htmlspecialchars($_SESSION['erro']); 
                unset($_SESSION['erro']);
                ?>
            </div>
        <?php endif; ?>

        <form action="salvar_usuario.php" method="POST">
            <div class="input-group">
                <input type="text" name="nome" placeholder="👤 Nome completo" required>
            </div>

            <div class="input-group">
                <input type="email" name="email" placeholder="📧 Email" required>
            </div>

            <div class="input-group">
                <input type="password" name="senha" placeholder="🔒 Senha (mínimo 6 caracteres)" required minlength="6">
            </div>
            <div class="input-group">
                <input type="password" name="confirmar_senha" placeholder="🔒 Confirmar senha" required minlength="6">
            </div>
            <div class="input-group">
                <label class="select-label">Perfil</label>
                <select name="perfil_id" required>
                    <option value="2">Barracão</option>
                    <option value="3" selected>Visualizador</option>
                </select>
            </div>
            <div class="input-group">
                <input type="text" name="setor_nome" placeholder="🏢 Qual seu setor?" required>
            </div>

            <button type="submit">Cadastrar</button>
        </form>

        <div class="link-container">
            <p>
                Já tem conta?
                <a href="login.php">Voltar ao login</a>
            </p>
        </div>
    </div>
</body>
</html>
