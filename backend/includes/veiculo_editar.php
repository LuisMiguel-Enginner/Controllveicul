<?php
require_once 'verificar_sessao.php';
require_once 'config.php';

if (!temPermissao('editar')) {
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
        SELECT *
        FROM veiculos
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $veiculo = $stmt->fetch();

    if (!$veiculo) {
        $_SESSION['erro'] = 'Veículo não encontrado.';
        header('Location: veiculos.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Erro ao carregar veículo para edição: ' . $e->getMessage());
    $_SESSION['erro'] = 'Erro ao carregar veículo.';
    header('Location: veiculos.php');
    exit;
}

$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_email = $_SESSION['usuario_email'] ?? '';
$usuario_perfil = $_SESSION['usuario_perfil'] ?? '';
$usuario_foto = getAvatarUsuario();
$iniciais = getIniciaisNome($usuario_nome);

$mensagem_erro = $_SESSION['erro'] ?? null;
unset($_SESSION['erro']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Veículo - Chiptronic</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <main class="main-content" style="margin-left: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;">
        <section class="table-section" style="max-width: 700px; width: 100%;">
            <div class="table-header">
                <h3>Editar Veículo - <?php echo htmlspecialchars($veiculo['placa']); ?></h3>
                <button class="btn-primary" style="background: transparent; border: 1px solid var(--border-color);" onclick="window.location.href='veiculos.php'">
                    <i class="fas fa-arrow-left"></i>
                    Voltar
                </button>
            </div>

            <?php if ($mensagem_erro): ?>
                <div class="message error" style="padding: 15px; border-radius: 8px; margin-bottom: 20px; background: rgba(255, 0, 0, 0.1); border: 2px solid rgba(255, 0, 0, 0.5); color: #ff0000; text-align: center;">
                    <?php echo htmlspecialchars($mensagem_erro); ?>
                </div>
            <?php endif; ?>

            <form action="salvar_edicao_veiculo.php" method="POST" style="display: flex; flex-direction: column; gap: 20px;">
                <input type="hidden" name="id" value="<?php echo (int)$veiculo['id']; ?>">

                <div class="form-row" style="display: flex; gap: 20px;">
                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Segmento do veículo</label>
                        <select name="segmento" id="segmentoEdit"
                                style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                            <?php
                            $segmentos = ['Construção','Agrícola','Florestal','Leves','Pesados'];
                            $segAtual = $veiculo['segmento'] ?? '';
                            echo '<option value="">Selecione o segmento</option>';
                            foreach ($segmentos as $seg) {
                                $sel = ($segAtual === $seg) ? 'selected' : '';
                                echo '<option value="'.htmlspecialchars($seg).'" '.$sel.'>'.htmlspecialchars($seg).'</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-row" style="display: flex; gap: 20px;">
                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Placa</label>
                        <input type="text" name="placa" id="placaEdit"
                               value="<?php echo htmlspecialchars($veiculo['placa']); ?>"
                               style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                    </div>

                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Empresa</label>
                        <select name="empresa_id"
                                style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                            <option value="">Selecione</option>
                            <option value="1" <?php echo ($veiculo['empresa_id'] ?? null) == 1 ? 'selected' : ''; ?>>Injetron</option>
                            <option value="2" <?php echo ($veiculo['empresa_id'] ?? null) == 2 ? 'selected' : ''; ?>>Barracão Pesados</option>
                        </select>
                    </div>
                </div>

                <div class="form-row" style="display: flex; gap: 20px;">
                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Modelo</label>
                        <input type="text" name="modelo"
                               value="<?php echo htmlspecialchars($veiculo['modelo'] ?? ''); ?>"
                               style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                    </div>

                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Montadora</label>
                        <input type="text" name="montadora"
                               value="<?php echo htmlspecialchars($veiculo['montadora'] ?? ''); ?>"
                               style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                    </div>
                </div>

                <div class="form-row" style="display: flex; gap: 20px;">
                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Ano</label>
                        <input type="number" name="ano" min="1980" max="2100"
                               value="<?php echo htmlspecialchars($veiculo['ano'] ?? ''); ?>"
                               style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                    </div>

                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Combustível</label>
                        <select name="combustivel" required
                                style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                            <?php
                            $combustiveis = ['Gasolina','Etanol','Diesel','Flex','GNV','Elétrico'];
                            foreach ($combustiveis as $c) {
                                $sel = ($veiculo['combustivel'] ?? '') === $c ? 'selected' : '';
                                echo '<option value="'.htmlspecialchars($c).'" '.$sel.'>'.htmlspecialchars($c).'</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-row" style="display: flex; gap: 20px;">
                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Data de chegada na mecânica</label>
                        <input type="date" name="data_chegada"
                               value="<?php echo htmlspecialchars($veiculo['data_chegada'] ?? ''); ?>"
                               style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                    </div>

                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Pode dar partida?</label>
                        <select name="pode_partida"
                                style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                            <option value="" <?php echo empty($veiculo['pode_partida']) ? 'selected' : ''; ?>>Selecione</option>
                            <option value="Sim" <?php echo ($veiculo['pode_partida'] ?? '') === 'Sim' ? 'selected' : ''; ?>>Sim</option>
                            <option value="Não" <?php echo ($veiculo['pode_partida'] ?? '') === 'Não' ? 'selected' : ''; ?>>Não</option>
                        </select>
                    </div>
                </div>

                <div class="form-row" style="display: flex; gap: 20px;">
                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Pode mexer no veículo?</label>
                        <select name="pode_mexer"
                                style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                            <option value="" <?php echo empty($veiculo['pode_mexer']) ? 'selected' : ''; ?>>Selecione</option>
                            <option value="Sim" <?php echo ($veiculo['pode_mexer'] ?? '') === 'Sim' ? 'selected' : ''; ?>>Sim</option>
                            <option value="Não" <?php echo ($veiculo['pode_mexer'] ?? '') === 'Não' ? 'selected' : ''; ?>>Não</option>
                        </select>
                    </div>

                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Previsão de saída (dias)</label>
                        <input type="number" name="tempo_estimado_dias" min="1" max="365"
                               value="<?php echo htmlspecialchars($veiculo['tempo_estimado_dias'] ?? ''); ?>"
                               style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                    </div>
                </div>
                <div class="form-row" style="display: flex; gap: 20px;">
                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Status</label>
                        <select name="status" required
                                style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                            <?php
                            $statusAtual = $veiculo['status'] ?? 'Pendente';
                            $statuses = ['Pendente','Em andamento','Concluído'];
                            foreach ($statuses as $s) {
                                $sel = $statusAtual === $s ? 'selected' : '';
                                echo '<option value="'.htmlspecialchars($s).'" '.$sel.'>'.htmlspecialchars($s).'</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div style="display:flex; justify-content: space-between; gap: 10px; margin-top: 10px;">
                    <button type="button" class="btn-primary" style="background: transparent; border: 1px solid var(--border-color);" onclick="window.location.href='veiculos.php'">
                        <i class="fas fa-arrow-left"></i>
                        Voltar
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i>
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </section>
    </main>
</body>
<script>
    (function() {
        const segmentoEl = document.getElementById('segmentoEdit');
        const placaEl = document.getElementById('placaEdit');
        function updatePlacaState() {
            const seg = (segmentoEl.value || '').toLowerCase();
            const desabilitar = ['construção','construcao','agrícola','agricola','florestal'].includes(seg);
            placaEl.disabled = desabilitar;
            placaEl.required = !desabilitar;
            if (desabilitar) {
                placaEl.placeholder = 'Placa desabilitada para este segmento';
            } else {
                placaEl.placeholder = '';
            }
        }
        if (segmentoEl && placaEl) {
            segmentoEl.addEventListener('change', updatePlacaState);
            updatePlacaState();
        }
    })();
</script>
</html>

