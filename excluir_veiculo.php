<?php
require_once 'verificar_sessao.php';
require_once 'config.php';

if (!temPermissao('excluir')) {
    $_SESSION['erro'] = 'Você não tem permissão para excluir veículos.';
    header('Location: veiculos.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id <= 0) {
        $_SESSION['erro'] = 'Veículo inválido.';
        header('Location: veiculos.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM veiculos WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['sucesso'] = 'Veículo excluído com sucesso.';
        header('Location: veiculos.php');
        exit;
    } catch (PDOException $e) {
        error_log('Erro ao excluir veículo: ' . $e->getMessage());
        $_SESSION['erro'] = 'Erro ao excluir veículo.';
        header('Location: veiculos.php');
        exit;
    }
}

header('Location: veiculos.php');
exit;

