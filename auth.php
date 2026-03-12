<?php
session_start();
require_once 'config.php';

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'] ?? '';
    
    // Validar campos
    if (empty($email) || empty($senha)) {
        $_SESSION['erro'] = 'Por favor, preencha todos os campos.';
        header('Location: login.php');
        exit;
    }
    
    try {
        // Buscar usuário no banco de dados
        $stmt = $pdo->prepare("
            SELECT 
                u.id, 
                u.nome, 
                u.email, 
                u.senha, 
                u.ativo,
                p.nome as perfil_nome,
                p.id as perfil_id,
                s.nome as setor_nome,
                s.id as setor_id
            FROM usuarios u
            INNER JOIN perfis p ON u.perfil_id = p.id
            INNER JOIN setores s ON u.setor_id = s.id
            WHERE u.email = ?
        ");
        
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        // Verificar se o usuário existe e a senha está correta
        if ($usuario && (int)$usuario['ativo'] !== 1) {
            $_SESSION['erro'] = 'Usuário inativo.';
            header('Location: login.php');
            exit;
        }
        
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // Regenerar ID da sessão por segurança
            session_regenerate_id(true);
            
            // Armazenar informações do usuário na sessão
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            // Sem coluna de foto no banco, mantém nulo por enquanto
            $_SESSION['usuario_foto'] = null;
            $_SESSION['usuario_perfil'] = $usuario['perfil_nome'];
            $_SESSION['usuario_perfil_id'] = $usuario['perfil_id'];
            $_SESSION['usuario_setor'] = $usuario['setor_nome'];
            $_SESSION['usuario_setor_id'] = $usuario['setor_id'];
            $_SESSION['logado'] = true;
            $_SESSION['ultimo_acesso'] = time();
            
            // Redirecionar para o dashboard
            header('Location: dashboard.php');
            exit;
            
        } else {
            $_SESSION['erro'] = 'Email ou senha incorretos.';
            header('Location: login.php');
            exit;
        }
        
    } catch(PDOException $e) {
        // Mensagem mais detalhada para debug (remova em produção)
        $_SESSION['erro'] = 'Erro ao processar login: ' . $e->getMessage();
        error_log("Erro de autenticação: " . $e->getMessage());
        header('Location: login.php');
        exit;
    }
    
} else {
    // Se não for POST, redirecionar para login
    header('Location: login.php');
    exit;
}
?>
