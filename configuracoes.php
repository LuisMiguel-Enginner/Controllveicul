<?php
require_once 'verificar_sessao.php';
require_once 'config.php';

$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_email = $_SESSION['usuario_email'] ?? '';
$usuario_setor = $_SESSION['usuario_setor'] ?? '';
$usuario_foto = getAvatarUsuario();
$iniciais = getIniciaisNome($usuario_nome);

$timezone = $_SESSION['timezone'] ?? 'America/Sao_Paulo';
$date_format = $_SESSION['date_format'] ?? 'long';
$timeout = isset($_SESSION['timeout']) ? (int)$_SESSION['timeout'] : 1800;
$notifications_enabled = isset($_SESSION['notifications_enabled']) ? (bool)$_SESSION['notifications_enabled'] : true;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Chiptronic</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
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
            <a href="configuracoes.php" class="nav-item active">
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
                <h1>Configurações</h1>
                <p class="header-subtitle">Preferências da sua sessão</p>
            </div>
        </header>

        <section class="activity-section">
            <div class="activity-header">
                <h3>Preferências</h3>
            </div>
            <form action="salvar_configuracoes.php" method="POST" style="display:flex; flex-direction: column; gap: 20px;">
                <div style="display:flex; gap:20px;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:8px; color:#00ffff;">Fuso horário</label>
                        <select name="timezone" required style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--border-color); background: transparent; color: var(--text-light);">
                            <?php
                            $timezones = [
                                'America/Sao_Paulo' => 'América/São Paulo',
                                'America/Manaus' => 'América/Manaus',
                                'America/Bahia' => 'América/Bahia',
                                'America/Fortaleza' => 'América/Fortaleza',
                                'America/Recife' => 'América/Recife'
                            ];
                            foreach ($timezones as $tz => $label) {
                                $sel = ($timezone === $tz) ? 'selected' : '';
                                echo "<option value=\"{$tz}\" {$sel}>{$label}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:8px; color:#00ffff;">Formato de data</label>
                        <select name="date_format" required style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--border-color); background: transparent; color: var(--text-light);">
                            <option value="long" <?php echo $date_format==='long'?'selected':''; ?>>Ex.: 09 de Fevereiro, 2026</option>
                            <option value="short" <?php echo $date_format==='short'?'selected':''; ?>>Ex.: 09/02/2026</option>
                        </select>
                    </div>
                </div>
                <div style="display:flex; gap:20px;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:8px; color:#00ffff;">Tempo de sessão (minutos)</label>
                        <input type="number" name="timeout_minutes" min="5" max="240" value="<?php echo (int)($timeout/60); ?>" style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--border-color); background: transparent; color: var(--text-light);">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:8px; color:#00ffff;">Notificações</label>
                        <select name="notifications_enabled" style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--border-color); background: transparent; color: var(--text-light);">
                            <option value="1" <?php echo $notifications_enabled?'selected':''; ?>>Ativadas</option>
                            <option value="0" <?php echo !$notifications_enabled?'selected':''; ?>>Desativadas</option>
                        </select>
                    </div>
                </div>
                <div>
                    <button type="submit" class="btn-primary" style="padding:12px 20px;">Salvar configurações</button>
                </div>
            </form>
        </section>
    </main>

    <script src="dashboard.js"></script>
</body>
</html>
