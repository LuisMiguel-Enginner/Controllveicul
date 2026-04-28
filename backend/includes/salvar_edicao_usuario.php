<?php
require_once 'verificar_sessao.php';
require_once 'config.php';

if (($_SESSION['usuario_perfil_id'] ?? 0) != 1) {
    header('Location: usuarios.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuarios.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$nome = trim($_POST['nome'] ?? '');
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$perfil_id = filter_var($_POST['perfil_id'] ?? null, FILTER_VALIDATE_INT);
$setor_id = filter_var($_POST['setor_id'] ?? null, FILTER_VALIDATE_INT);
$ativo = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;

if ($id <= 0 || $nome === '' || !$email || !$perfil_id || !$setor_id) {
    $_SESSION['erro'] = 'Preencha todos os campos obrigatórios.';
    header('Location: editar_usuario.php?id='.$id);
    exit;
}

if (!in_array($perfil_id, [1, 2, 3], true)) {
    $_SESSION['erro'] = 'Perfil inválido.';
    header('Location: editar_usuario.php?id='.$id);
    exit;
}

if ($ativo !== 0 && $ativo !== 1) {
    $ativo = 1;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id <> ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        $_SESSION['erro'] = 'Este email já está em uso por outro usuário.';
        header('Location: editar_usuario.php?id='.$id);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE usuarios
        SET nome = ?, email = ?, perfil_id = ?, setor_id = ?, ativo = ?
        WHERE id = ?
    ");
    $stmt->execute([$nome, $email, $perfil_id, $setor_id, $ativo, $id]);

    if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $id) {
        $perfilNome = null;
        $setorNome = null;
        $pstmt = $pdo->prepare("SELECT nome FROM perfis WHERE id = ?");
        $pstmt->execute([$perfil_id]);
        $row = $pstmt->fetch();
        if ($row) $perfilNome = $row['nome'];
        $sstmt = $pdo->prepare("SELECT nome FROM setores WHERE id = ?");
        $sstmt->execute([$setor_id]);
        $srow = $sstmt->fetch();
        if ($srow) $setorNome = $srow['nome'];
        $_SESSION['usuario_perfil_id'] = $perfil_id;
        $_SESSION['usuario_setor_id'] = $setor_id;
        if ($perfilNome) $_SESSION['usuario_perfil'] = $perfilNome;
        if ($setorNome) $_SESSION['usuario_setor'] = $setorNome;
        $_SESSION['usuario_nome'] = $nome;
        $_SESSION['usuario_email'] = $email;
    }

    $_SESSION['sucesso'] = 'Usuário atualizado com sucesso.';
    header('Location: usuarios.php');
    exit;
} catch (PDOException $e) {
    error_log('Erro ao atualizar usuário: ' . $e->getMessage());
    $_SESSION['erro'] = 'Erro ao atualizar usuário.';
    header('Location: editar_usuario.php?id='.$id);
    exit;
}
