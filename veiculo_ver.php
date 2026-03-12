<?php
require_once 'verificar_sessao.php';
require_once 'config.php';

if (!temPermissao('visualizar')) {
    header('Location: veiculos.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: veiculos.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            v.*,
            CASE v.empresa_id
                WHEN 1 THEN 'Injetron'
                WHEN 2 THEN 'Barracão Pesados'
                ELSE NULL
            END AS empresa_nome
        FROM veiculos v
        WHERE v.id = ?
    ");
    $stmt->execute([$id]);
    $veiculo = $stmt->fetch();

    if (!$veiculo) {
        $_SESSION['erro'] = 'Veículo não encontrado.';
        header('Location: veiculos.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Erro ao carregar veículo: ' . $e->getMessage());
    $_SESSION['erro'] = 'Erro ao carregar veículo.';
    header('Location: veiculos.php');
    exit;
}

$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_email = $_SESSION['usuario_email'] ?? '';
$usuario_perfil = $_SESSION['usuario_perfil'] ?? '';
$usuario_foto = getAvatarUsuario();
$iniciais = getIniciaisNome($usuario_nome);

// Buscar foto do veículo (se existir em uploads/veiculos/veiculo_ID.ext)
$foto_veiculo = null;
$pasta_fotos = 'uploads/veiculos/';
if (is_dir($pasta_fotos)) {
    foreach (glob($pasta_fotos . 'veiculo_' . $id . '.*') as $arquivo) {
        $foto_veiculo = $arquivo;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Veículo - Chiptronic</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <main class="main-content" style="margin-left: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;">
        <section class="table-section" style="max-width: 900px; width: 100%;">
            <div class="table-header">
                <h3>Detalhes do Veículo - <?php echo htmlspecialchars($veiculo['placa']); ?></h3>
                <button class="btn-primary" style="background: transparent; border: 1px solid var(--border-color);" onclick="window.location.href='veiculos.php'">
                    <i class="fas fa-arrow-left"></i>
                    Voltar
                </button>
            </div>

            <div style="display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap;">
                <!-- Bloco da foto -->
                <div style="flex: 0 0 260px;">
                    <h4 style="margin-bottom: 10px; color: var(--text-light); font-size: 15px;">Foto do veículo</h4>
                    <div style="width: 240px; height: 180px; border-radius: 12px; border: 2px dashed var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; background: rgba(0,0,0,0.2); margin-bottom: 12px;">
                        <?php if ($foto_veiculo && file_exists($foto_veiculo)): ?>
                            <img src="<?php echo htmlspecialchars($foto_veiculo); ?>" alt="Foto do veículo" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <span style="color: var(--text-gray); font-size: 13px; text-align:center; padding: 0 10px;">
                                Nenhuma foto cadastrada.
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (temPermissao('editar')): ?>
                        <form action="upload_foto_veiculo.php" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:8px;">
                            <input type="hidden" name="id" value="<?php echo (int)$veiculo['id']; ?>">
                            <input type="file" name="foto_veiculo" accept="image/*"
                                   style="font-size: 12px; color: var(--text-gray);">
                            <button type="submit" class="btn-primary" style="margin-top:4px; padding: 8px 12px; font-size: 13px;">
                                <i class="fas fa-upload"></i>
                                Atualizar foto
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Bloco de informações -->
                <div class="table-container" style="flex: 1 1 300px;">
                    <table class="data-table">
                        <tbody>
                            <tr>
                                <th>Placa</th>
                                <td><?php echo htmlspecialchars($veiculo['placa']); ?></td>
                            </tr>
                            <tr>
                                <th>Empresa</th>
                                <td><?php echo htmlspecialchars($veiculo['empresa_nome'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Modelo</th>
                                <td><?php echo htmlspecialchars($veiculo['modelo'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Montadora</th>
                                <td><?php echo htmlspecialchars($veiculo['montadora'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Ano</th>
                                <td><?php echo htmlspecialchars($veiculo['ano'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Combustível</th>
                                <td><?php echo htmlspecialchars($veiculo['combustivel'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Data de chegada</th>
                                <td>
                                    <?php
                                    $data = $veiculo['data_chegada'] ?? null;
                                    echo $data ? htmlspecialchars(date('d/m/Y', strtotime($data))) : '-';
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Pode dar partida?</th>
                                <td><?php echo htmlspecialchars($veiculo['pode_partida'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Pode mexer?</th>
                                <td><?php echo htmlspecialchars($veiculo['pode_mexer'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Previsão de saída (dias)</th>
                                <td><?php echo htmlspecialchars($veiculo['tempo_estimado_dias'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td><?php echo htmlspecialchars($veiculo['status'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Data de entrada</th>
                                <td>
                                    <?php
                                    $entrada = $veiculo['data_entrada'] ?? null;
                                    echo $entrada ? htmlspecialchars(date('d/m/Y H:i', strtotime($entrada))) : '-';
                                    ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</body>
</html>

