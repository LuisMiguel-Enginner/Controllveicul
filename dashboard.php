<?php
require_once 'verificar_sessao.php';
require_once 'config.php';

// Obter informações do usuário
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_email = $_SESSION['usuario_email'] ?? '';
$usuario_setor = $_SESSION['usuario_setor'] ?? '';
$usuario_foto = getAvatarUsuario();
$iniciais = getIniciaisNome($usuario_nome);
$hoje = new DateTime();
$meses = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$data_atual = ($_SESSION['date_format'] ?? 'long') === 'short'
    ? $hoje->format('d/m/Y')
    : ($hoje->format('d') . ' de ' . $meses[(int)$hoje->format('n') - 1] . ', ' . $hoje->format('Y'));

// Notificações (armazenadas em sessão)
if (!isset($_SESSION['notificacoes']) || !is_array($_SESSION['notificacoes'])) {
    $_SESSION['notificacoes'] = [];
}
$notificacoes = $_SESSION['notificacoes'];
$qtd_notificacoes = count($notificacoes);

// Buscar estatísticas do dashboard
try {
    // Total de veículos no barracão
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos WHERE status IN ('Pendente', 'Em andamento')");
    $total_veiculos = $stmt->fetch()['total'] ?? 0;
    
    // Total de reparos concluídos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos WHERE status = 'Concluído'");
    $total_concluidos = $stmt->fetch()['total'] ?? 0;
    
    // Contagem por status para o gráfico
    $status_counts = [
        'Pendente' => 0,
        'Em andamento' => 0,
        'Concluído' => 0,
    ];
    $stmt = $pdo->query("SELECT status, COUNT(*) as total FROM veiculos GROUP BY status");
    while ($row = $stmt->fetch()) {
        if (isset($status_counts[$row['status']])) {
            $status_counts[$row['status']] = (int)$row['total'];
        }
    }
    
    $status_counts_30 = [
        'Pendente' => 0,
        'Em andamento' => 0,
        'Concluído' => 0,
    ];
    $stmt = $pdo->query("SELECT status, COUNT(*) as total FROM veiculos WHERE data_chegada >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY status");
    while ($row = $stmt->fetch()) {
        if (isset($status_counts_30[$row['status']])) {
            $status_counts_30[$row['status']] = (int)$row['total'];
        }
    }
    
    $status_counts_7 = [
        'Pendente' => 0,
        'Em andamento' => 0,
        'Concluído' => 0,
    ];
    $stmt = $pdo->query("SELECT status, COUNT(*) as total FROM veiculos WHERE data_chegada >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY status");
    while ($row = $stmt->fetch()) {
        if (isset($status_counts_7[$row['status']])) {
            $status_counts_7[$row['status']] = (int)$row['total'];
        }
    }
    
    $status_counts_mes = [
        'Pendente' => 0,
        'Em andamento' => 0,
        'Concluído' => 0,
    ];
    $stmt = $pdo->query("SELECT status, COUNT(*) as total FROM veiculos WHERE YEAR(data_chegada) = YEAR(CURDATE()) AND MONTH(data_chegada) = MONTH(CURDATE()) GROUP BY status");
    while ($row = $stmt->fetch()) {
        if (isset($status_counts_mes[$row['status']])) {
            $status_counts_mes[$row['status']] = (int)$row['total'];
        }
    }

    // Veículos recentes (campos principais)
    $stmt = $pdo->query("
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
            v.data_entrada
        FROM veiculos v
        ORDER BY v.data_entrada DESC
        LIMIT 5
    ");
    $veiculos_recentes = $stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("Erro ao buscar dados: " . $e->getMessage());
    $total_veiculos = 0;
    $total_concluidos = 0;
    $veiculos_recentes = [];
    $status_counts = [
        'Pendente' => 0,
        'Em andamento' => 0,
        'Concluído' => 0,
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Chiptronic</title>
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
            <a href="dashboard.php" class="nav-item active">
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
                <h1>Dashboard do Sistema</h1>
                <p class="header-subtitle">Bem-vindo ao controle de veículos Chiptronic</p>
            </div>
            <div class="header-right">
                <div class="header-date">
                    <i class="fas fa-calendar"></i>
                    <span><?php echo htmlspecialchars($data_atual); ?></span>
                </div>
                <?php $notifOn = isset($_SESSION['notifications_enabled']) ? (bool)$_SESSION['notifications_enabled'] : true; ?>
                <?php if ($notifOn): ?>
                <div class="notification-wrapper">
                    <button class="notification-btn" type="button">
                        <i class="fas fa-bell"></i>
                        <?php if ($qtd_notificacoes > 0): ?>
                            <span class="notification-badge"><?php echo (int)$qtd_notificacoes; ?></span>
                        <?php endif; ?>
                    </button>
                    <div id="notification-panel" class="notification-panel">
                        <div class="notification-panel-header">
                            <span>Notificações</span>
                            <?php if ($qtd_notificacoes > 0): ?>
                                <span class="notification-count"><?php echo (int)$qtd_notificacoes; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="notification-panel-body">
                            <?php if (empty($notificacoes)): ?>
                                <p class="notification-empty">Nenhuma notificação recente.</p>
                            <?php else: ?>
                                <ul class="notification-list">
                                    <?php foreach (array_slice($notificacoes, 0, 20) as $n): ?>
                                        <li class="notification-item <?php echo htmlspecialchars($n['tipo'] ?? 'info'); ?>">
                                            <span class="notification-dot"></span>
                                            <div class="notification-item-content">
                                                <span class="notification-message"><?php echo htmlspecialchars($n['mensagem'] ?? ''); ?></span>
                                                <span class="notification-time"><?php echo htmlspecialchars($n['quando'] ?? ''); ?></span>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </header>

        <?php
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

            // Registrar no histórico de notificações (máx. 20)
            array_unshift($_SESSION['notificacoes'], [
                'tipo' => $toast_tipo,
                'mensagem' => (string)$toast_msg,
                'quando' => date('d/m/Y H:i'),
            ]);
            $_SESSION['notificacoes'] = array_slice($_SESSION['notificacoes'], 0, 20);

            unset($_SESSION['sucesso'], $_SESSION['erro']);
        }
        ?>
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

        <!-- Stats Cards -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon vehicles">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Veículos no Barracão</p>
                    <h2 class="stat-value"><?php echo $total_veiculos; ?></h2>
                    <span class="stat-change neutral">Em manutenção</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon repairs">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Reparos Concluídos</p>
                    <h2 class="stat-value"><?php echo $total_concluidos; ?></h2>
                    <span class="stat-change positive">Total geral</span>
                </div>
            </div>

        </section>

        <!-- Charts Section -->
        <section class="charts-section">
            <div class="chart-container large">
                <div class="chart-header">
                    <h3>Veículos por Status</h3>
                    <select class="chart-filter" id="chart-period-filter">
                        <option value="all">Todos os registros</option>
                        <option value="30dias">Últimos 30 dias</option>
                        <option value="7dias">Últimos 7 dias</option>
                        <option value="mes">Este mês</option>
                    </select>
                </div>
                <div class="chart-body">
                    <canvas id="vehicleChart"></canvas>
                </div>
            </div>
        </section>

        <!-- Recent Vehicles Table -->
        <section class="table-section">
            <div class="table-header">
                <h3>Veículos Recentes no Barracão</h3>
                <?php if (temPermissao('adicionar')): ?>
                    <button class="btn-primary" onclick="window.location.href='veiculo_novo.php'">
                        <i class="fas fa-plus"></i>
                        Adicionar Veículo
                    </button>
                <?php endif; ?>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Placa</th>
                            <th>Modelo</th>
                            <th>Montadora</th>
                            <th>Ano</th>
                            <th>Combustível</th>
                            <th>Chegada</th>
                            <th>Partida</th>
                            <th>Mexer?</th>
                            <th>Previsão de saída (dias)</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($veiculos_recentes)): ?>
                            <tr>
                                <td colspan="10" style="text-align: center; padding: 30px; color: var(--text-gray);">
                                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                    Nenhum veículo cadastrado ainda.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($veiculos_recentes as $veiculo): ?>
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
                                    <td><?php echo htmlspecialchars($veiculo['modelo'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($veiculo['montadora'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($veiculo['ano'] ?? ''); ?></td>
                                    <td><span class="fuel-badge <?php echo strtolower($veiculo['combustivel']); ?>"><?php echo htmlspecialchars($veiculo['combustivel']); ?></span></td>
                                    <td>
                                        <?php
                                        $data = $veiculo['data_chegada'] ?? null;
                                        echo $data ? htmlspecialchars(date('d/m/Y', strtotime($data))) : '-';
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($veiculo['pode_partida'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($veiculo['pode_mexer'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($veiculo['tempo_estimado_dias'] ?? '-'); ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch($veiculo['status']) {
                                            case 'Em andamento': $status_class = 'in-progress'; break;
                                            case 'Pendente': $status_class = 'pending'; break;
                                            case 'Concluído': $status_class = 'completed'; break;
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($veiculo['status']); ?></span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 6px;">
                                            <?php if (temPermissao('visualizar')): ?>
                                                <a href="veiculo_ver.php?id=<?php echo (int)$veiculo['id']; ?>" class="action-btn view" title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>

                                            <?php if (temPermissao('editar')): ?>
                                                <a href="veiculo_editar.php?id=<?php echo (int)$veiculo['id']; ?>" class="action-btn edit" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>

                                            <?php if (temPermissao('excluir')): ?>
                                                <form action="excluir_veiculo.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="id" value="<?php echo (int)$veiculo['id']; ?>">
                                                    <button type="submit" class="action-btn delete" title="Excluir" data-placa="<?php echo htmlspecialchars($veiculo['placa']); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <!-- Modal de confirmação de exclusão de veículo -->
    <div id="confirm-delete-modal" class="confirm-modal">
        <div class="confirm-modal-content">
            <h3>Confirmar exclusão</h3>
            <p id="confirm-delete-text">Tem certeza que deseja excluir este veículo?</p>
            <div class="confirm-modal-actions">
                <button type="button" class="btn-secondary" id="confirm-delete-cancel">Cancelar</button>
                <button type="button" class="btn-primary" id="confirm-delete-ok">Excluir</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        window.dashboardStatusData = <?php echo json_encode($status_counts ?? [
            'Pendente' => 0,
            'Em andamento' => 0,
            'Concluído' => 0,
        ]); ?>;
        window.dashboardStatusRanges = {
            '30dias': <?php echo json_encode($status_counts_30 ?? ['Pendente'=>0,'Em andamento'=>0,'Concluído'=>0]); ?>,
            '7dias': <?php echo json_encode($status_counts_7 ?? ['Pendente'=>0,'Em andamento'=>0,'Concluído'=>0]); ?>,
            'mes': <?php echo json_encode($status_counts_mes ?? ['Pendente'=>0,'Em andamento'=>0,'Concluído'=>0]); ?>,
            'all': <?php echo json_encode($status_counts ?? ['Pendente'=>0,'Em andamento'=>0,'Concluído'=>0]); ?>
        };
    </script>
    <script src="dashboard.js"></script>
</body>
</html>
