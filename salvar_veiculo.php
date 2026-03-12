<?php
require_once 'verificar_sessao.php';
require_once 'config.php';

// Verificar permissão
if (!temPermissao('adicionar')) {
    $_SESSION['erro'] = 'Você não tem permissão para adicionar veículos.';
    header('Location: veiculos.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placa = strtoupper(trim($_POST['placa'] ?? ''));
    $segmento = trim($_POST['segmento'] ?? '');
    $empresa_id = !empty($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : null;
    $empresa_nome = trim($_POST['empresa_nome'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $montadora = trim($_POST['montadora'] ?? '');
    $ano = isset($_POST['ano']) && $_POST['ano'] !== '' ? (int)$_POST['ano'] : null;
    $combustivel = trim($_POST['combustivel'] ?? '');
    $data_chegada = !empty($_POST['data_chegada']) ? $_POST['data_chegada'] : null;
    $pode_partida = trim($_POST['pode_partida'] ?? '');
    $tempo_estimado_dias = isset($_POST['tempo_estimado_dias']) && $_POST['tempo_estimado_dias'] !== '' ? (int)$_POST['tempo_estimado_dias'] : null;
    $status = trim($_POST['status'] ?? 'Pendente');
    $pode_mexer = trim($_POST['pode_mexer'] ?? '');

    // Validações básicas
    $segmentosPermitidos = ['Construção','Agrícola','Florestal','Leves','Pesados'];
    if (!in_array($segmento, $segmentosPermitidos, true)) {
        $_SESSION['erro'] = 'Segmento inválido.';
        header('Location: veiculo_novo.php');
        exit;
    }
    $placaObrigatoria = in_array($segmento, ['Leves','Pesados'], true);
    if (empty($combustivel) || ($placaObrigatoria && empty($placa))) {
        $_SESSION['erro'] = $placaObrigatoria ? 'Placa e combustível são obrigatórios para Leves/Pesados.' : 'Combustível é obrigatório.';
        header('Location: veiculo_novo.php');
        exit;
    }

    // Validar formato da placa (padrão antigo ABC1234 / ABC-1234 e Mercosul ABC1D23 / ABC-1D23)
    if (!empty($placa)) {
        $placaRegex = '/^[A-Z]{3}-?[0-9][A-Z0-9][0-9]{2}$/';
        if (!preg_match($placaRegex, $placa)) {
            $_SESSION['erro'] = 'Placa inválida. Use um formato válido, por exemplo ABC1234, ABC-1234 ou ABC1D23.';
            header('Location: veiculo_novo.php');
            exit;
        }
    }

    // Validar status
    $statusPermitidos = ['Pendente', 'Em andamento', 'Concluído'];
    if (!in_array($status, $statusPermitidos, true)) {
        $_SESSION['erro'] = 'Status inválido.';
        header('Location: veiculo_novo.php');
        exit;
    }

    try {
        if (!empty($placa)) {
            $dupChk = $pdo->prepare("SELECT 1 FROM veiculos WHERE UPPER(REPLACE(placa,'-','')) = UPPER(REPLACE(:placa,'-','')) LIMIT 1");
            $dupChk->execute([':placa' => $placa]);
            if ($dupChk->fetch()) {
                $_SESSION['erro'] = 'Já existe um veículo com esta placa cadastrado.';
                header('Location: veiculo_novo.php');
                exit;
            }
        }
        // Garantir que a coluna pode_mexer exista (ignorar erro se já existir)
        try {
            $pdo->exec("ALTER TABLE veiculos ADD COLUMN pode_mexer VARCHAR(10) NULL");
        } catch (PDOException $e) {
            // Se der erro por já existir, ignoramos
        }
        // Garantir coluna segmento
        try {
            $pdo->exec("ALTER TABLE veiculos ADD COLUMN segmento VARCHAR(20) NULL");
        } catch (PDOException $e) {
        }

        $stmt = $pdo->prepare("
            INSERT INTO veiculos (
                placa,
                segmento,
                empresa_id,
                proprietario_nome,
                modelo,
                montadora,
                ano,
                combustivel,
                data_chegada,
                pode_partida,
                tempo_estimado_dias,
                pode_mexer,
                status,
                data_entrada
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )
        ");

        $stmt->execute([
            $placa,
            $segmento ?: null,
            $empresa_id,
            $empresa_nome ?: null,
            $modelo ?: null,
            $montadora ?: null,
            $ano,
            $combustivel,
            $data_chegada,
            $pode_partida ?: null,
            $tempo_estimado_dias,
            $pode_mexer ?: null,
            $status,
        ]);

        $novoId = (int)$pdo->lastInsertId();
        if ($novoId > 0 && isset($_FILES['foto_veiculo']) && is_array($_FILES['foto_veiculo'])) {
            $file = $_FILES['foto_veiculo'];
            if ($file['error'] === UPLOAD_ERR_OK && $file['size'] > 0) {
                $allowed = ['jpg','jpeg','png','webp'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed, true)) {
                    $uploadDir = 'uploads/veiculos/';
                    if (!is_dir($uploadDir)) {
                        @mkdir($uploadDir, 0775, true);
                    }
                    foreach (glob($uploadDir . 'veiculo_' . $novoId . '.*') as $oldFile) {
                        @unlink($oldFile);
                    }
                    $targetPath = $uploadDir . 'veiculo_' . $novoId . '.' . $ext;
                    if (@move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $relativePath = 'uploads/veiculos/' . 'veiculo_' . $novoId . '.' . $ext;
                        try {
                            $pdo->exec("ALTER TABLE veiculos ADD COLUMN foto VARCHAR(255) NULL");
                        } catch (PDOException $e) {
                        }
                        try {
                            $stmtFoto = $pdo->prepare("UPDATE veiculos SET foto = :foto WHERE id = :id");
                            $stmtFoto->execute([':foto' => $relativePath, ':id' => $novoId]);
                        } catch (PDOException $e) {
                        }
                    }
                }
            }
        }

        $_SESSION['sucesso'] = 'Veículo cadastrado com sucesso.';
        header('Location: veiculos.php');
        exit;
    } catch (PDOException $e) {
        // Temporário: mostrar erro detalhado para debug
        error_log('Erro ao salvar veículo: ' . $e->getMessage());
        $_SESSION['erro'] = 'Erro ao salvar veículo: ' . $e->getMessage();
        header('Location: veiculo_novo.php');
        exit;
    }
}

header('Location: veiculos.php');
exit;


