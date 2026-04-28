<?php
require_once 'verificar_sessao.php';
require_once 'config.php';

// Obter informações do usuário logado
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_email = $_SESSION['usuario_email'] ?? '';
$usuario_setor = $_SESSION['usuario_setor'] ?? '';
$usuario_foto = getAvatarUsuario();
$iniciais = getIniciaisNome($usuario_nome);

// Buscar lista de veículos e estatísticas básicas
try {
    // Lista completa de veículos (somente campos necessários)
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
    ");
    $veiculos = $stmt->fetchAll();

    // Estatísticas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos");
    $total_veiculos = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos WHERE status = 'Pendente'");
    $total_pendentes = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos WHERE status = 'Em andamento'");
    $total_andamento = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos WHERE status = 'Concluído'");
    $total_concluidos = $stmt->fetch()['total'] ?? 0;

} catch (PDOException $e) {
    error_log('Erro ao carregar veículos: ' . $e->getMessage());
    $veiculos = [];
    $total_veiculos = 0;
    $total_pendentes = 0;
    $total_andamento = 0;
    $total_concluidos = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veículos - Chiptronic</title>
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
            <a href="veiculos.php" class="nav-item active">
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
                <h1>Veículos</h1>
                <p class="header-subtitle">Gerencie os veículos no barracão</p>
            </div>
            <div class="header-right">
                <?php if (temPermissao('adicionar')): ?>
                    <button class="btn-primary" onclick="window.location.href='veiculo_novo.php'">
                        <i class="fas fa-plus"></i>
                        Novo Veículo
                    </button>
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

        <!-- Resumo rápido -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon vehicles">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Total de Veículos</p>
                    <h2 class="stat-value"><?php echo $total_veiculos; ?></h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon repairs">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Pendentes</p>
                    <h2 class="stat-value"><?php echo $total_pendentes; ?></h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon earning">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Em Andamento</p>
                    <h2 class="stat-value"><?php echo $total_andamento; ?></h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon rating">
                    <i class="fas fa-check"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Concluídos</p>
                    <h2 class="stat-value"><?php echo $total_concluidos; ?></h2>
                </div>
            </div>
        </section>

        <!-- Tabela de Veículos -->
        <section class="table-section">
            <div class="table-header">
                <h3>Lista de Veículos</h3>
                <div style="display: flex; gap: 10px;">
                    <select class="chart-filter" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                        <option value="">Todos os status</option>
                        <option value="Pendente">Pendentes</option>
                        <option value="Em andamento">Em andamento</option>
                        <option value="Concluído">Concluídos</option>
                    </select>
                    <input type="text" placeholder="Buscar por placa, modelo ou montadora..." 
                           style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light); min-width: 280px;">
                </div>
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
                        <?php if (empty($veiculos)): ?>
                            <tr>
                                <td colspan="10" style="text-align: center; padding: 30px; color: var(--text-gray);">
                                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                    Nenhum veículo cadastrado ainda.
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
                                    <td><?php echo htmlspecialchars($veiculo['modelo'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($veiculo['montadora'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($veiculo['ano'] ?? ''); ?></td>
                                    <td>
                                        <span class="fuel-badge <?php echo strtolower($veiculo['combustivel']); ?>">
                                            <?php echo htmlspecialchars($veiculo['combustivel']); ?>
                                        </span>
                                    </td>
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
                                        switch ($veiculo['status']) {
                                            case 'Em andamento': $status_class = 'in-progress'; break;
                                            case 'Pendente': $status_class = 'pending'; break;
                                            case 'Concluído': $status_class = 'completed'; break;
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($veiculo['status']); ?>
                                        </span>
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
    <script src="dashboard.js"></script>
</body>
</html>
