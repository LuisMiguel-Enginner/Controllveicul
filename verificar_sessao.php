<?php
// Verificar se a sessão existe
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    // Redirecionar para login se não estiver logado
    header('Location: login.php');
    exit;
}

// Verificar timeout da sessão
$timeout = isset($_SESSION['timeout']) ? (int)$_SESSION['timeout'] : 1800;
if ($timeout < 300) { // mínimo 5 minutos
    $timeout = 300;
}

if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso']) > $timeout) {
    // Sessão expirada
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}

// Atualizar último acesso
$_SESSION['ultimo_acesso'] = time();

// Função para verificar permissão
function temPermissao($permissao_necessaria) {
    $perfil_id = $_SESSION['usuario_perfil_id'] ?? 0;
    
    // Administrador tem acesso total
    if ($perfil_id == 1) {
        return true;
    }
    
    // Barracão pode adicionar, editar e excluir
    if ($perfil_id == 2 && in_array($permissao_necessaria, ['adicionar', 'editar', 'excluir', 'visualizar'])) {
        return true;
    }
    
    // Visualizador só pode visualizar
    if ($perfil_id == 3 && $permissao_necessaria == 'visualizar') {
        return true;
    }
    
    return false;
}

// Função para obter avatar do usuário
function getAvatarUsuario() {
    if (!empty($_SESSION['usuario_foto']) && file_exists($_SESSION['usuario_foto'])) {
        return $_SESSION['usuario_foto'];
    }
    
    // Retornar avatar padrão se não houver foto
    return null;
}

// Função para obter iniciais do nome
function getIniciaisNome($nome) {
    $palavras = explode(' ', $nome);
    $iniciais = '';
    
    foreach ($palavras as $palavra) {
        if (!empty($palavra)) {
            $iniciais .= strtoupper(substr($palavra, 0, 1));
            if (strlen($iniciais) >= 2) break;
        }
    }
    
    return $iniciais;
}
?>
