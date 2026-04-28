<?php
require_once 'verificar_sessao.php';
require_once 'config.php';

// Apenas administradores podem acessar a tela de usuários
if (($_SESSION['usuario_perfil_id'] ?? 0) != 1) {
    header('Location: dashboard.php');
    exit;
}

// Dados do usuário logado
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_email = $_SESSION['usuario_email'] ?? '';
$usuario_setor = $_SESSION['usuario_setor'] ?? '';
$usuario_foto = getAvatarUsuario();
$iniciais = getIniciaisNome($usuario_nome);
$isAdmin = ($_SESSION['usuario_perfil_id'] ?? 0) == 1;

// Buscar usuários cadastrados
try {
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.nome,
            u.email,
            u.ativo,
            p.nome AS perfil_nome,
            s.nome AS setor_nome
        FROM usuarios u
        INNER JOIN perfis p ON u.perfil_id = p.id
        INNER JOIN setores s ON u.setor_id = s.id
        ORDER BY u.nome ASC
    ");
    $usuarios = $stmt->fetchAll();

    $total_usuarios = count($usuarios);

} catch (PDOException $e) {
    error_log('Erro ao carregar usuários: ' . $e->getMessage());
    $usuarios = [];
    $total_usuarios = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - Chiptronic</title>
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

            <a href="usuarios.php" class="nav-item active">
                <i class="fas fa-users"></i>
                <span>Usuários</span>
            </a>
            <a href="empresas.php" class="nav-item">
                <i class="fas fa-building"></i>
                <span>Empresas</span>
            </a>
            <a href="relatorios.php" class="nav-item">
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

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-left">
                <h1>Usuários</h1>
                <p class="header-subtitle">Gerencie os usuários do sistema</p>
            </div>
            <div class="header-right">
                <?php if ($isAdmin): ?>
                    <button class="btn-primary" onclick="window.location.href='salvar_usuario.php'">
                        <i class="fas fa-user-plus"></i>
                        Novo Usuário
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
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Total de Usuários</p>
                    <h2 class="stat-value"><?php echo $total_usuarios; ?></h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon earning">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-label">Permissões</p>
                    <h2 class="stat-value"><?php echo $isAdmin ? 'Admin' : 'Restrito'; ?></h2>
                </div>
            </div>
        </section>

        <!-- Tabela de Usuários -->
        <section class="table-section">
            <div class="table-header">
                <h3>Usuários Cadastrados</h3>
                <span style="color: var(--text-gray); font-size: 13px;">
                    <?php if ($isAdmin): ?>
                        Apenas administradores podem adicionar, editar ou excluir usuários.
                    <?php else: ?>
                        Você possui acesso somente para visualização da lista de usuários.
                    <?php endif; ?>
                </span>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Perfil</th>
                            <th>Setor</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-gray);">
                                    <i class="fas fa-user-slash" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                    Nenhum usuário cadastrado ainda.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $user): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($user['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['perfil_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($user['setor_nome']); ?></td>
                                    <td>
                                        <?php if ($user['ativo']): ?>
                                            <span class="status-badge completed">Ativo</span>
                                        <?php else: ?>
                                            <span class="status-badge pending">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isAdmin): ?>
                                            <button class="action-btn edit" title="Editar usuário" onclick="window.location.href='editar_usuario.php?id=<?php echo $user['id']; ?>'">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($user['email'] !== $usuario_email): ?>
                                                <form action="excluir_usuario.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="action-btn delete" title="Excluir usuário" data-usuario="<?php echo htmlspecialchars($user['nome']); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: var(--text-gray); font-size: 12px;">Somente admin</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Modal de confirmação de exclusão -->
    <div id="confirm-delete-modal" class="confirm-modal">
        <div class="confirm-modal-content">
            <h3>Confirmar exclusão</h3>
            <p id="confirm-delete-text">Tem certeza que deseja excluir?</p>
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
