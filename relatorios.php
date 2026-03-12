<?php
require_once 'verificar_sessao.php';
require_once 'config.php';

// Bloqueio para perfil Visualizador
if (($_SESSION['usuario_perfil_id'] ?? 0) == 3) {
    header('Location: dashboard.php');
    exit;
}

// Buscar todos os veículos
$veiculos = [];
$totais = [
    'total' => 0,
    'pendentes' => 0,
    'andamento' => 0,
    'concluidos' => 0,
];

try {
    $stmt = $pdo->query("
        SELECT 
            v.id,
            v.segmento,
            TRIM(v.proprietario_nome) AS empresa_nome,
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

    $totais['total'] = count($veiculos);
    foreach ($veiculos as $v) {
        if (($v['status'] ?? '') === 'Pendente') $totais['pendentes']++;
        elseif (($v['status'] ?? '') === 'Em andamento') $totais['andamento']++;
        elseif (($v['status'] ?? '') === 'Concluído') $totais['concluidos']++;
    }
} catch (PDOException $e) {
    error_log('Erro ao carregar veículos para relatório: ' . $e->getMessage());
    $veiculos = [];
}


// Dados do usuário logado
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_email = $_SESSION['usuario_email'] ?? '';
$usuario_perfil = $_SESSION['usuario_perfil'] ?? '';
$usuario_foto = getAvatarUsuario();
$iniciais = getIniciaisNome($usuario_nome);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Chiptronic</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .sidebar, .dashboard-header .header-right, .table-header .actions, .sidebar-footer, .toast-notification { display: none !important; }
            body { background: #fff; }
            .main-content { margin-left: 0; }
        }
        .pdf-container {
            background: #ffffff;
            padding: 16px;
        }
        .pdf-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .pdf-header h2 {
            margin: 0;
            font-size: 18px;
            color: #222;
        }
        .pdf-header .date {
            font-size: 12px;
            color: #555;
        }
        .pdf-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            background: #ffffff;
        }
        .pdf-table th, .pdf-table td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            font-size: 11px;
            color: #222;
            word-break: break-word;
        }
        .pdf-table th {
            background: #f3f3f3;
            font-weight: 600;
        }
        .pdf-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 12px;
        }
        .pdf-stat {
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 6px;
            background: #fafafa;
            text-align: center;
        }
        .pdf-stat .label {
            font-size: 11px;
            color: #555;
            margin-bottom: 4px;
        }
        .pdf-stat .value {
            font-size: 16px;
            color: #222;
            font-weight: 600;
        }
    </style>
    </head>
<body>
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
                    <span class="user-role"><?php echo htmlspecialchars($usuario_perfil); ?></span>
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
            <?php if (!$isVisualizador): ?>
                <a href="empresas.php" class="nav-item">
                    <i class="fas fa-building"></i>
                    <span>Empresas</span>
                </a>
            <?php endif; ?>
            <a href="relatorios.php" class="nav-item active">
                <i class="fas fa-chart-line"></i>
                <span>Relatórios</span>
            </a>
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

    <main class="main-content">
        <header class="dashboard-header">
            <div class="header-left">
                <h1>Relatórios</h1>
                <p class="header-subtitle">Gerar relatório com todos os dados de veículos</p>
            </div>
            <div class="header-right">
                <div style="display:flex; gap:10px;">
                    <a class="btn-primary" href="#" id="export-pdf-top">
                        <i class="fas fa-file-pdf"></i>
                        Exportar PDF
                    </a>
                    <button class="btn-secondary" onclick="window.print()">
                        <i class="fas fa-print"></i>
                        Imprimir
                    </button>
                </div>
            </div>
        </header>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon vehicles">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Total de Veículos</p>
                    <h2 class="stat-value"><?php echo $totais['total']; ?></h2>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon repairs">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Pendentes</p>
                    <h2 class="stat-value"><?php echo $totais['pendentes']; ?></h2>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon earning">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Em Andamento</p>
                    <h2 class="stat-value"><?php echo $totais['andamento']; ?></h2>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon rating">
                    <i class="fas fa-check"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Concluídos</p>
                    <h2 class="stat-value"><?php echo $totais['concluidos']; ?></h2>
                </div>
            </div>
        </section>

        <section class="table-section" id="pdf-container" class="pdf-container">
            <div class="pdf-header">
                <h2>Relatório de Veículos</h2>
                <div class="date"><?php echo date('d/m/Y H:i'); ?></div>
            </div>
            <div class="pdf-stats">
                <div class="pdf-stat">
                    <div class="label">Total</div>
                    <div class="value"><?php echo $totais['total']; ?></div>
                </div>
                <div class="pdf-stat">
                    <div class="label">Pendentes</div>
                    <div class="value"><?php echo $totais['pendentes']; ?></div>
                </div>
                <div class="pdf-stat">
                    <div class="label">Em andamento</div>
                    <div class="value"><?php echo $totais['andamento']; ?></div>
                </div>
                <div class="pdf-stat">
                    <div class="label">Concluídos</div>
                    <div class="value"><?php echo $totais['concluidos']; ?></div>
                </div>
            </div>
            <div class="table-header">
                <h3>Relatório de Veículos</h3>
                <div class="actions" style="display:flex; gap:10px;">
                    <a class="btn-primary" href="#" id="export-pdf">
                        <i class="fas fa-file-pdf"></i>
                        Exportar PDF
                    </a>
                    <button class="btn-secondary" onclick="window.print()">
                        <i class="fas fa-print"></i>
                        Imprimir
                    </button>
                </div>
            </div>
            <div class="table-container">
                <table class="pdf-table">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>Placa</th>
                            <th>Modelo</th>
                            <th>Montadora</th>
                            <th>Ano</th>
                            <th>Combustível</th>
                            <th>Data Chegada</th>
                            <th>Pode Dar Partida</th>
                            <th>Pode Mexer</th>
                            <th>Tempo Estimado (dias)</th>
                            <th>Status</th>
                            <th>Data Entrada</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($veiculos)): ?>
                            <tr>
                                <td colspan="12" style="text-align:center; padding: 30px; color: var(--text-gray);">
                                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; display:block;"></i>
                                    Nenhum veículo encontrado.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($veiculos as $v): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($v['empresa_nome'] ?? ''); ?></td>
                                    <td>
                                        <?php
                                        $seg = strtolower(trim($v['segmento'] ?? ''));
                                        $placaTxt = $v['placa'] ?? '';
                                        if (in_array($seg, ['construção','construcao','agrícola','agricola','florestal'])) {
                                            $placaTxt = 'Placa inválida para este veículo';
                                        }
                                        ?>
                                        <strong><?php echo htmlspecialchars($placaTxt ?: '-'); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($v['modelo'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($v['montadora'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($v['ano'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($v['combustivel'] ?? ''); ?></td>
                                    <td>
                                        <?php
                                        $data = $v['data_chegada'] ?? null;
                                        echo $data ? htmlspecialchars(date('d/m/Y', strtotime($data))) : '-';
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($v['pode_partida'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($v['pode_mexer'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($v['tempo_estimado_dias'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($v['status'] ?? ''); ?></td>
                                    <td>
                                        <?php
                                        $entrada = $v['data_entrada'] ?? null;
                                        echo $entrada ? htmlspecialchars(date('d/m/Y H:i', strtotime($entrada))) : '-';
                                        ?>
                                    </td>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        (function(){
            function exportPDF() {
                var el = document.getElementById('pdf-container');
                if (!el) return;
                var opt = {
                    margin:       10,
                    filename:     'relatorio_veiculos_' + new Date().toISOString().replace(/[:\-T\.Z]/g,'').slice(0,15) + '.pdf',
                    image:        { type: 'jpeg', quality: 0.95 },
                    html2canvas:  { scale: 2, useCORS: true, background: '#ffffff', scrollY: 0 },
                    jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
                    pagebreak:    { mode: ['css', 'legacy'] }
                };
                html2pdf().set(opt).from(el).save();
            }
            var b1 = document.getElementById('export-pdf');
            var b2 = document.getElementById('export-pdf-top');
            if (b1) b1.addEventListener('click', function(e){ e.preventDefault(); exportPDF(); });
            if (b2) b2.addEventListener('click', function(e){ e.preventDefault(); exportPDF(); });
        })();
    </script>
</body>
</html>
