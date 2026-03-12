<?php
require_once 'verificar_sessao.php';
require_once 'config.php';

if (!temPermissao('editar')) {
    $_SESSION['erro'] = 'Você não tem permissão para alterar a foto do veículo.';
    header('Location: veiculos.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    $_SESSION['erro'] = 'Veículo inválido.';
    header('Location: veiculos.php');
    exit;
}

if (!isset($_FILES['foto_veiculo']) || $_FILES['foto_veiculo']['error'] === UPLOAD_ERR_NO_FILE) {
    $_SESSION['erro'] = 'Nenhuma foto foi selecionada.';
    header('Location: veiculo_ver.php?id=' . $id);
    exit;
}

$file = $_FILES['foto_veiculo'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['erro'] = 'Erro ao enviar o arquivo. Código: ' . $file['error'];
    header('Location: veiculo_ver.php?id=' . $id);
    exit;
}

// Validações básicas
$maxSize = 2 * 1024 * 1024; // 2MB
if ($file['size'] > $maxSize) {
    $_SESSION['erro'] = 'A foto deve ter no máximo 2MB.';
    header('Location: veiculo_ver.php?id=' . $id);
    exit;
}

$validExtensions = ['jpg','jpeg','png','webp'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $validExtensions, true)) {
    $_SESSION['erro'] = 'Formato de imagem inválido. Use JPG, PNG ou WEBP.';
    header('Location: veiculo_ver.php?id=' . $id);
    exit;
}

$uploadDir = __DIR__ . '/uploads/veiculos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

// Remove arquivos antigos do mesmo veículo
foreach (glob($uploadDir . 'veiculo_' . $id . '.*') as $oldFile) {
    @unlink($oldFile);
}

$targetPath = $uploadDir . 'veiculo_' . $id . '.' . $ext;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    $_SESSION['erro'] = 'Não foi possível salvar a foto do veículo.';
    header('Location: veiculo_ver.php?id=' . $id);
    exit;
}

// Caminho relativo para uso no HTML (a partir da raiz do projeto)
$relativePath = 'uploads/veiculos/' . 'veiculo_' . $id . '.' . $ext;

try {
    // Se existir coluna foto na tabela, atualiza (ignora erro se não existir)
    $pdo->exec("ALTER TABLE veiculos ADD COLUMN IF NOT EXISTS foto VARCHAR(255) NULL");
} catch (Throwable $e) {
    // Alguns MySQL não suportam IF NOT EXISTS em ADD COLUMN; ignoramos erro.
}

try {
    // Tenta salvar o caminho da foto, se a coluna existir
    $stmt = $pdo->prepare("UPDATE veiculos SET foto = :foto WHERE id = :id");
    $stmt->execute([':foto' => $relativePath, ':id' => $id]);
} catch (Throwable $e) {
    // Se a coluna não existir, só registra no log e segue
    error_log('Não foi possível atualizar coluna foto em veiculos: ' . $e->getMessage());
}

$_SESSION['sucesso'] = 'Foto do veículo atualizada com sucesso.';
header('Location: veiculo_ver.php?id=' . $id);
exit;

