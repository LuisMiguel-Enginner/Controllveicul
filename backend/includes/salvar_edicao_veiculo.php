<?php
require_once 'verificar_sessao.php';
require_once 'config.php';

if (!temPermissao('editar')) {
    $_SESSION['erro'] = 'Você não tem permissão para editar veículos.';
    header('Location: veiculos.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $placa = strtoupper(trim($_POST['placa'] ?? ''));
    $segmento = trim($_POST['segmento'] ?? '');
    $empresa_id = !empty($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : null;
    $modelo = trim($_POST['modelo'] ?? '');
    $montadora = trim($_POST['montadora'] ?? '');
    $ano = isset($_POST['ano']) && $_POST['ano'] !== '' ? (int)$_POST['ano'] : null;
    $combustivel = trim($_POST['combustivel'] ?? '');
    $data_chegada = !empty($_POST['data_chegada']) ? $_POST['data_chegada'] : null;
    $pode_partida = trim($_POST['pode_partida'] ?? '');
    $pode_mexer = trim($_POST['pode_mexer'] ?? '');
    $tempo_estimado_dias = isset($_POST['tempo_estimado_dias']) && $_POST['tempo_estimado_dias'] !== '' ? (int)$_POST['tempo_estimado_dias'] : null;
    $status = trim($_POST['status'] ?? 'Pendente');

    if ($id <= 0) {
        $_SESSION['erro'] = 'ID inválido.';
        header('Location: veiculos.php');
        exit;
    }

    $segmentosPermitidos = ['Construção','Agrícola','Florestal','Leves','Pesados'];
    if ($segmento !== '' && !in_array($segmento, $segmentosPermitidos, true)) {
        $_SESSION['erro'] = 'Segmento inválido.';
        header("Location: veiculo_editar.php?id=".$id);
        exit;
    }
    if (empty($combustivel)) {
        $_SESSION['erro'] = 'Combustível é obrigatório.';
        header("Location: veiculo_editar.php?id=".$id);
        exit;
    }
    $placaObrigatoria = in_array($segmento, ['Leves','Pesados'], true) || $segmento === '';
    if ($placaObrigatoria && empty($placa)) {
        $_SESSION['erro'] = 'Placa é obrigatória para Leves/Pesados.';
        header("Location: veiculo_editar.php?id=".$id);
        exit;
    }

    if (!empty($placa)) {
        $placaRegex = '/^[A-Z]{3}-?[0-9][A-Z0-9][0-9]{2}$/';
        if (!preg_match($placaRegex, $placa)) {
            $_SESSION['erro'] = 'Placa inválida. Use um formato válido, por exemplo ABC1234, ABC-1234 ou ABC1D23.';
            header("Location: veiculo_editar.php?id=".$id);
            exit;
        }
        try {
            $dupChk = $pdo->prepare("SELECT 1 FROM veiculos WHERE UPPER(REPLACE(placa,'-','')) = UPPER(REPLACE(:placa,'-','')) AND id <> :id LIMIT 1");
            $dupChk->execute([':placa' => $placa, ':id' => $id]);
            if ($dupChk->fetch()) {
                $_SESSION['erro'] = 'Já existe um veículo com esta placa cadastrado.';
                header("Location: veiculo_editar.php?id=".$id);
                exit;
            }
        } catch (PDOException $e) {
        }
    }

    $statusPermitidos = ['Pendente','Em andamento','Concluído'];
    if (!in_array($status, $statusPermitidos, true)) {
        $_SESSION['erro'] = 'Status inválido.';
        header("Location: veiculo_editar.php?id=".$id);
        exit;
    }

    try {
        try {
            $pdo->exec("ALTER TABLE veiculos ADD COLUMN pode_mexer VARCHAR(10) NULL");
        } catch (PDOException $e) {
        }
        try {
            $pdo->exec("ALTER TABLE veiculos ADD COLUMN segmento VARCHAR(20) NULL");
        } catch (PDOException $e) {
        }
        $stmt = $pdo->prepare("
            UPDATE veiculos
            SET
                placa = ?,
                segmento = ?,
                empresa_id = ?,
                modelo = ?,
                montadora = ?,
                ano = ?,
                combustivel = ?,
                data_chegada = ?,
                pode_partida = ?,
                pode_mexer = ?,
                tempo_estimado_dias = ?,
                status = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $placa,
            $segmento ?: null,
            $empresa_id,
            $modelo ?: null,
            $montadora ?: null,
            $ano,
            $combustivel,
            $data_chegada,
            $pode_partida ?: null,
            $pode_mexer ?: null,
            $tempo_estimado_dias,
            $status,
            $id,
        ]);

        $_SESSION['sucesso'] = 'Veículo atualizado com sucesso.';
        header('Location: veiculos.php');
        exit;
    } catch (PDOException $e) {
        error_log('Erro ao atualizar veículo: ' . $e->getMessage());
        $_SESSION['erro'] = 'Erro ao atualizar veículo.';
        header("Location: veiculo_editar.php?id=".$id);
        exit;
    }
}

header('Location: veiculos.php');
exit;

