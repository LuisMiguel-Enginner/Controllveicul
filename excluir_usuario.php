<?php
require_once 'verificar_sessao.php';
require_once 'config.php';

// Apenas administradores podem excluir usuários
if (($_SESSION['usuario_perfil_id'] ?? 0) != 1) {
    $_SESSION['erro'] = 'Você não tem permissão para excluir usuários.';
    header('Location: usuarios.php');
    exit;
}

// Apenas via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuarios.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$usuarioLogadoId = $_SESSION['usuario_id'] ?? 0;

if ($id <= 0) {
    $_SESSION['erro'] = 'Usuário inválido.';
    header('Location: usuarios.php');
    exit;
}

// Evitar excluir a si mesmo
if ($id === $usuarioLogadoId) {
    $_SESSION['erro'] = 'Você não pode excluir seu próprio usuário.';
    header('Location: usuarios.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['sucesso'] = 'Usuário excluído com sucesso.';
    } else {
        $_SESSION['erro'] = 'Usuário não encontrado ou já excluído.';
    }
    header('Location: usuarios.php');
    exit;
} catch (PDOException $e) {
    error_log('Erro ao excluir usuário: ' . $e->getMessage());
    $_SESSION['erro'] = 'Erro ao excluir usuário.';
    header('Location: usuarios.php');
    exit;
}

?>
