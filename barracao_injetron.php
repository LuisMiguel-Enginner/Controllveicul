<?php
require_once 'verificar_sessao.php';
require_once 'config.php';

// Dados do usuário logado
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_email = $_SESSION['usuario_email'] ?? '';
$usuario_setor = $_SESSION['usuario_setor'] ?? '';
$usuario_foto = getAvatarUsuario();
$iniciais = getIniciaisNome($usuario_nome);

$empresaId = 1;
$data_inicial = $_GET['data_inicial'] ?? '';
$data_final = $_GET['data_final'] ?? '';
$placa_filtro = $_GET['placa'] ?? '';

function parseValidBRDate($d) {
    if (!is_string($d)) return null;
    if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $d)) return null;
    $parts = explode('/', $d);
    $dia = (int)$parts[0];
    $mes = (int)$parts[1];
    $ano = (int)$parts[2];
    if (!checkdate($mes, $dia, $ano)) return null;
    return sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
}

try {
    $placas_stmt = $pdo->prepare("SELECT DISTINCT placa FROM veiculos WHERE empresa_id = :empresa ORDER BY placa ASC");
    $placas_stmt->execute([':empresa' => $empresaId]);
    $placas = $placas_stmt->fetchAll(PDO::FETCH_COLUMN);

    $where = ["v.empresa_id = :empresa"];
    $params = [':empresa' => $empresaId];

    $ini = parseValidBRDate($data_inicial);
    $fim = parseValidBRDate($data_final);
    if ($ini && $fim) {
        $where[] = "DATE(v.data_chegada) BETWEEN :ini AND :fim";
        $params[':ini'] = $ini;
        $params[':fim'] = $fim;
    } elseif (($data_inicial !== '' || $data_final !== '') && (!$ini || !$fim)) {
        $_SESSION['erro'] = 'Informe datas válidas no formato DD/MM/AAAA.';
    }

    if ($placa_filtro) {
        $where[] = "v.placa = :placa";
        $params[':placa'] = $placa_filtro;
    }

    $sql = "
        SELECT 
            v.id,
            v.segmento,
            v.placa,
            v.modelo,
            v.montadora,
            v.ano,
            v.combustivel,
            v.data_chegada,
            v.pode_partida,
            v.pode_mexer,
            v.tempo_estimado_dias,
            v.status,
            v.data_entrada,
            v.proprietario_nome
        FROM veiculos v
        WHERE " . implode(" AND ", $where) . "
        ORDER BY v.data_chegada DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $veiculos = $stmt->fetchAll();

    $count_sql = "SELECT COUNT(*) as total FROM veiculos v WHERE " . implode(" AND ", $where);
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_veiculos = $count_stmt->fetch()['total'] ?? 0;
} catch (PDOException $e) {
    error_log('Erro ao carregar veículos do barracão Injetron: ' . $e->getMessage());
    $veiculos = [];
    $total_veiculos = 0;
    $placas = [];
}

// Toast de notificação
$toast_tipo = null;
$toast_msg = null;
if (!empty($_SESSION['sucesso']) || !empty($_SESSION['erro'])) {
    if (!empty($_SESSION['sucesso'])) {
        $toast_tipo = 'success';
        $toast_msg = $_SESSION['sucesso'];
    } else {
        $toast_tipo = 'error';
        $toast_msg = $_SESSION['erro'];
    }
    unset($_SESSION['sucesso'], $_SESSION['erro']);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barracão - Injetron leves</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <!-- Logo removida -->
            <div class="user-profile">
                <div class="user-avatar">
                    <?php if ($usuario_foto): ?>
                        <img src="<?php echo htmlspecialchars($usuario_foto); ?>" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <?php echo htmlspecialchars($iniciais); ?>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($usuario_nome); ?></h3>
                    <p><?php echo htmlspecialchars($usuario_email); ?></p>
                    <span class="user-role"><?php echo htmlspecialchars($usuario_setor); ?></span>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="veiculos.php" class="nav-item">
                <i class="fas fa-truck"></i>
                <span>Veículos</span>
            </a>

            <div class="nav-item nav-item-with-children open" data-submenu="barracao-submenu">
                <div class="nav-item-left">
                    <i class="fas fa-warehouse"></i>
                    <span>Barracão</span>
                </div>
                <i class="fas fa-chevron-down submenu-arrow"></i>
            </div>
            <div id="barracao-submenu" class="nav-submenu open">
                <a href="barracao_injetron.php" class="nav-subitem">
                    <span class="submenu-dot submenu-dot-light"></span>
                    <span class="nav-subitem-text">Injetron leves</span>
                </a>
                <a href="barracao_pesados.php" class="nav-subitem">
                    <span class="submenu-dot submenu-dot-heavy"></span>
                    <span class="nav-subitem-text">Barracão pesados</span>
                </a>
            </div>

            <?php 
                $perfilId = $_SESSION['usuario_perfil_id'] ?? 0;
                $isVisualizador = $perfilId == 3;
                $isAdmin = $perfilId == 1;
            ?>
            <?php if ($isAdmin): ?>
                <a href="usuarios.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Usuários</span>
                </a>
            <?php endif; ?>
            <?php if (!$isVisualizador): ?>
                <a href="empresas.php" class="nav-item">
                    <i class="fas fa-building"></i>
                    <span>Empresas</span>
                </a>
                <a href="relatorios.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Relatórios</span>
                </a>
            <?php endif; ?>
            <a href="configuracoes.php" class="nav-item">
                <i class="fas fa-cog"></i>
                <span>Configurações</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="logout.php" class="nav-item logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sair</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-left">
                <h1>Barracão - Injetron leves</h1>
                <p class="header-subtitle">Veículos atualmente na oficina Injetron</p>
            </div>
        </header>

        <?php if ($toast_tipo && $toast_msg): ?>
            <div id="toast-notification" class="toast-notification <?php echo $toast_tipo; ?>">
                <div class="toast-icon">
                    <i class="fas <?php echo $toast_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                </div>
                <div class="toast-content">
                    <strong><?php echo $toast_tipo === 'success' ? 'Sucesso' : 'Atenção'; ?></strong>
                    <span><?php echo htmlspecialchars($toast_msg); ?></span>
                </div>
                <button type="button" class="toast-close" aria-label="Fechar">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Resumo rápido -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon vehicles">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Veículos na Injetron</p>
                    <h2 class="stat-value"><?php echo $total_veiculos; ?></h2>
                </div>
            </div>
        </section>

        <!-- Tabela de Veículos -->
        <section class="table-section">
            <div class="table-header">
                <h3>Veículos da Injetron</h3>
            </div>
            <div style="display:flex; gap:10px; align-items:center; padding: 0 24px 12px;">
                <form method="GET" style="display:flex; gap:10px; align-items:center; width:100%;">
                    <input type="text" name="data_inicial" value="<?php echo htmlspecialchars($data_inicial); ?>" placeholder="DD/MM/AAAA" pattern="\d{2}/\d{2}/\d{4}" inputmode="numeric" maxlength="10" oninput="mascaraData(this)" class="chart-filter" style="padding:8px 12px; border-radius:8px; border:1px solid var(--border-color); background:transparent; color:var(--text-light);">
                    <span style="color:var(--text-gray);">até</span>
                    <input type="text" name="data_final" value="<?php echo htmlspecialchars($data_final); ?>" placeholder="DD/MM/AAAA" pattern="\d{2}/\d{2}/\d{4}" inputmode="numeric" maxlength="10" oninput="mascaraData(this)" class="chart-filter" style="padding:8px 12px; border-radius:8px; border:1px solid var(--border-color); background:transparent; color:var(--text-light);">
                    <select name="placa" class="chart-filter" style="padding:8px 12px; border-radius:8px; border:1px solid var(--border-color); background:transparent; color:var(--text-light); min-width:220px;">
                        <option value="">Todos os veículos</option>
                        <?php foreach ($placas as $p): ?>
                            <option value="<?php echo htmlspecialchars($p); ?>" <?php echo $placa_filtro === $p ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-primary" style="display:flex; align-items:center; gap:8px;" onclick="return validarDatasFiltro(this);">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                    <?php if ($data_inicial || $data_final || $placa_filtro): ?>
                        <a href="barracao_injetron.php" class="btn-secondary" style="margin-left:auto;">Limpar</a>
                    <?php endif; ?>
                </form>
            </div>
            <script>
                function mascaraData(inp) {
                    var v = (inp.value || '').replace(/\D/g,'').slice(0,8);
                    if (v.length >= 5) {
                        inp.value = v.slice(0,2) + '/' + v.slice(2,4) + '/' + v.slice(4);
                    } else if (v.length >= 3) {
                        inp.value = v.slice(0,2) + '/' + v.slice(2);
                    } else {
                        inp.value = v;
                    }
                }
                function validarDatasFiltro(btn) {
                    var form = btn.closest('form');
                    var ini = form.querySelector('input[name="data_inicial"]');
                    var fim = form.querySelector('input[name="data_final"]');
                    var iniVal = ini.value || '';
                    var fimVal = fim.value || '';
                    if ((iniVal && !fimVal) || (!iniVal && fimVal)) {
                        showToast('Selecione as duas datas para filtrar.', 'error');
                        return false;
                    }
                    var re = /^\d{2}\/\d{2}\/\d{4}$/;
                    if (iniVal && (!re.test(iniVal) || iniVal.length !== 10)) {
                        showToast('Use datas no formato DD/MM/AAAA.', 'error');
                        return false;
                    }
                    if (fimVal && (!re.test(fimVal) || fimVal.length !== 10)) {
                        showToast('Use datas no formato DD/MM/AAAA.', 'error');
                        return false;
                    }
                    function isValidDMY(s) {
                        var m = s.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
                        if (!m) return false;
                        var d = parseInt(m[1], 10);
                        var mo = parseInt(m[2], 10);
                        var y = parseInt(m[3], 10);
                        if (mo < 1 || mo > 12) return false;
                        var max = new Date(y, mo, 0).getDate();
                        return d >= 1 && d <= max;
                    }
                    if (iniVal && !isValidDMY(iniVal)) {
                        showToast('Informe datas válidas.', 'error');
                        return false;
                    }
                    if (fimVal && !isValidDMY(fimVal)) {
                        showToast('Informe datas válidas.', 'error');
                        return false;
                    }
                    return true;
                }
                function showToast(message, type) {
                    var existing = document.getElementById('toast-notification');
                    if (existing) existing.remove();
                    var div = document.createElement('div');
                    div.id = 'toast-notification';
                    div.className = 'toast-notification ' + (type === 'success' ? 'success' : 'error');
                    div.innerHTML = '<div class="toast-icon">' +
                        '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle') + '"></i>' +
                        '</div>' +
                        '<div class="toast-content">' +
                        '<strong>' + (type === 'success' ? 'Sucesso' : 'Atenção') + '</strong>' +
                        '<span>' + message + '</span>' +
                        '</div>' +
                        '<button type="button" class="toast-close" aria-label="Fechar">&times;</button>';
                    document.body.appendChild(div);
                    setTimeout(function(){ div.classList.add('show'); }, 50);
                    var closeBtn = div.querySelector('.toast-close');
                    if (closeBtn) closeBtn.addEventListener('click', function(){ div.classList.remove('show'); });
                    setTimeout(function(){ div.classList.remove('show'); }, 5000);
                }
            </script>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Placa</th>
                            <th>Empresa</th>
                            <th>Modelo</th>
                            <th>Montadora</th>
                            <th>Ano</th>
                            <th>Combustível</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($veiculos)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-gray);">
                                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                    Nenhum veículo cadastrado para a Injetron.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($veiculos as $veiculo): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $seg = strtolower(trim($veiculo['segmento'] ?? ''));
                                        $placaTxt = $veiculo['placa'] ?? '';
                                        if (in_array($seg, ['construção','construcao','agrícola','agricola','florestal'])) {
                                            $placaTxt = 'Placa inválida para este veículo';
                                        }
                                        ?>
                                        <strong><?php echo htmlspecialchars($placaTxt); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($veiculo['proprietario_nome'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($veiculo['modelo'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($veiculo['montadora'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($veiculo['ano'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($veiculo['combustivel'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($veiculo['status'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="dashboard.js"></script>
</body>
</html>
