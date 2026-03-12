<?php
require_once 'verificar_sessao.php';
require_once 'config.php';

// Bloqueio para perfil Visualizador
if (($_SESSION['usuario_perfil_id'] ?? 0) == 3) {
    header('Location: dashboard.php');
    exit;
}

// Dados do usuário logado
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_email = $_SESSION['usuario_email'] ?? '';
$usuario_setor = $_SESSION['usuario_setor'] ?? '';
$usuario_foto = getAvatarUsuario();
$iniciais = getIniciaisNome($usuario_nome);

$empresaFiltro = isset($_GET['empresa']) ? trim($_GET['empresa']) : '';

// Buscar empresas (clientes) a partir dos veículos
try {
    $empresas_nomes_stmt = $pdo->query("
        SELECT DISTINCT TRIM(v.proprietario_nome) AS empresa_nome
        FROM veiculos v
        WHERE v.proprietario_nome IS NOT NULL AND TRIM(v.proprietario_nome) <> ''
        ORDER BY empresa_nome
    ");
    $empresas_nomes = $empresas_nomes_stmt->fetchAll(PDO::FETCH_COLUMN);

    $whereBase = "v.proprietario_nome IS NOT NULL AND TRIM(v.proprietario_nome) <> ''";
    $params = [];
    $whereResumo = $whereBase;
    $whereVeiculos = $whereBase;

    if ($empresaFiltro !== '') {
        $whereResumo .= " AND TRIM(v.proprietario_nome) = :empresa";
        $whereVeiculos .= " AND TRIM(v.proprietario_nome) = :empresa";
        $params[':empresa'] = $empresaFiltro;
    }

    $sqlResumo = "
        SELECT 
            TRIM(v.proprietario_nome) AS empresa_nome,
            COUNT(*) AS total_veiculos,
            SUM(CASE WHEN v.status = 'Pendente' THEN 1 ELSE 0 END) AS pendentes,
            SUM(CASE WHEN v.status = 'Em andamento' THEN 1 ELSE 0 END) AS em_andamento,
            SUM(CASE WHEN v.status = 'Concluído' THEN 1 ELSE 0 END) AS concluidos
        FROM veiculos v
        WHERE $whereResumo
        GROUP BY TRIM(v.proprietario_nome)
        ORDER BY empresa_nome
    ";
    $stmt = $pdo->prepare($sqlResumo);
    $stmt->execute($params);
    $empresas = $stmt->fetchAll();

    $sqlVeiculos = "
        SELECT 
            v.id,
            v.placa,
            v.modelo,
            v.montadora,
            v.ano,
            v.status,
            TRIM(v.proprietario_nome) AS empresa_nome
        FROM veiculos v
        WHERE $whereVeiculos
        ORDER BY empresa_nome, v.data_entrada DESC
    ";
    $stmt = $pdo->prepare($sqlVeiculos);
    $stmt->execute($params);
    $veiculos_por_empresa = [];
    while ($row = $stmt->fetch()) {
        $nome = $row['empresa_nome'];
        if (!isset($veiculos_por_empresa[$nome])) {
            $veiculos_por_empresa[$nome] = [];
        }
        $veiculos_por_empresa[$nome][] = $row;
    }

    $total_empresas = count($empresas);
    $total_veiculos = array_sum(array_column($empresas, 'total_veiculos'));

} catch (PDOException $e) {
    error_log('Erro ao carregar empresas: ' . $e->getMessage());
    $empresas = [];
    $veiculos_por_empresa = [];
    $total_empresas = 0;
    $total_veiculos = 0;
    $empresas_nomes = [];
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
    <title>Empresas - Chiptronic</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="chip2.png" alt="Chiptronic Logo" class="sidebar-logo">
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

            <div class="nav-item nav-item-with-children" data-submenu="barracao-submenu">
                <div class="nav-item-left">
                    <i class="fas fa-warehouse"></i>
                    <span>Barracão</span>
                </div>
                <i class="fas fa-chevron-down submenu-arrow"></i>
            </div>
            <div id="barracao-submenu" class="nav-submenu">
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
            <a href="empresas.php" class="nav-item active">
                <i class="fas fa-building"></i>
                <span>Empresas</span>
            </a>
            <?php if (!$isVisualizador): ?>
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
                <h1>Empresas</h1>
                <p class="header-subtitle">Empresas que já passaram pelas oficinas e seus veículos</p>
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
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Total de Empresas</p>
                    <h2 class="stat-value"><?php echo $total_empresas; ?></h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon repairs">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Veículos Cadastrados</p>
                    <h2 class="stat-value"><?php echo $total_veiculos; ?></h2>
                </div>
            </div>
        </section>

        <!-- Tabela de empresas -->
        <section class="table-section">
            <div class="table-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h3>Empresas que já passaram pelas oficinas</h3>
                <div style="display:flex; align-items:center; gap:10px;">
                    <input type="text" id="empresa-search" placeholder="Pesquisar empresa..." 
                           style="padding:8px 12px; border-radius:8px; border:1px solid var(--border-color); background: transparent; color: var(--text-light); min-width:240px;">
                    <i class="fas fa-search" style="color: var(--text-gray);"></i>
                </div>
            </div>
            <div style="display:flex; gap:10px; align-items:center; padding: 0 24px 12px;">
                <form method="GET" style="display:flex; gap:10px; align-items:center; width:100%;">
                    <select name="empresa" class="chart-filter" style="padding:8px 12px; border-radius:8px; border:1px solid var(--border-color); background:transparent; color:var(--text-light); min-width:260px;">
                        <option value="">Todas as empresas</option>
                        <?php foreach ($empresas_nomes as $nome): ?>
                            <option value="<?php echo htmlspecialchars($nome); ?>" <?php echo $empresaFiltro === $nome ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($nome); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-primary" style="display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                    <?php if ($empresaFiltro !== ''): ?>
                        <a href="empresas.php" class="btn-secondary" style="margin-left:auto;">Limpar</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>Total de Veículos</th>
                            <th>Pendentes</th>
                            <th>Em Andamento</th>
                            <th>Concluídos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($empresas)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px; color: var(--text-gray);">
                                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                    Nenhuma empresa registrada ainda.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($empresas as $empresa): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($empresa['empresa_nome']); ?></strong></td>
                                    <td><?php echo (int)$empresa['total_veiculos']; ?></td>
                                    <td><?php echo (int)$empresa['pendentes']; ?></td>
                                    <td><?php echo (int)$empresa['em_andamento']; ?></td>
                                    <td><?php echo (int)$empresa['concluidos']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Tabela de veículos por empresa -->
        <section class="table-section">
            <div class="table-header">
                <h3>Veículos por Empresa</h3>
            </div>
            <?php if ($empresaFiltro !== ''): ?>
                <div style="padding: 0 24px 12px; color: var(--text-gray);">
                    Mostrando veículos de: <strong><?php echo htmlspecialchars($empresaFiltro); ?></strong>
                </div>
            <?php endif; ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>Placa</th>
                            <th>Modelo</th>
                            <th>Montadora</th>
                            <th>Ano</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($veiculos_por_empresa)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-gray);">
                                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                    Nenhum veículo cadastrado ainda.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($veiculos_por_empresa as $nome_empresa => $listaVeiculos): ?>
                                <?php foreach ($listaVeiculos as $veiculo): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($nome_empresa); ?></td>
                                        <td><strong><?php echo htmlspecialchars($veiculo['placa']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($veiculo['modelo'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($veiculo['montadora'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($veiculo['ano'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($veiculo['status'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
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
