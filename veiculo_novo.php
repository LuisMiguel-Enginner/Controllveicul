<?php
require_once 'verificar_sessao.php';
require_once 'config.php';

// Apenas perfis com permissão podem adicionar veículos
if (!temPermissao('adicionar')) {
    header('Location: veiculos.php');
    exit;
}

// Informações do usuário logado
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_email = $_SESSION['usuario_email'] ?? '';
$usuario_setor = $_SESSION['usuario_setor'] ?? '';
$usuario_foto = getAvatarUsuario();
$iniciais = getIniciaisNome($usuario_nome);

// Carregar empresas para o select
try {
    $empresas = $pdo->query("SELECT id, nome FROM empresas ORDER BY nome")->fetchAll();
} catch (PDOException $e) {
    error_log('Erro ao carregar empresas: ' . $e->getMessage());
    $empresas = [];
}

// Mensagens de feedback
$mensagem_erro = $_SESSION['erro'] ?? null;
$mensagem_sucesso = $_SESSION['sucesso'] ?? null;
unset($_SESSION['erro'], $_SESSION['sucesso']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Veículo - Chiptronic</title>
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
                <a href="#" class="nav-subitem">
                    <span class="submenu-dot submenu-dot-light"></span>
                    <span class="nav-subitem-text">Injetron leves</span>
                </a>
                <a href="#" class="nav-subitem">
                    <span class="submenu-dot submenu-dot-heavy"></span>
                    <span class="nav-subitem-text">Barracão pesados</span>
                </a>
            </div>

            <a href="usuarios.php" class="nav-item">
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
                <h1>Novo Veículo</h1>
                <p class="header-subtitle">Cadastre um veículo no barracão</p>
            </div>
            <div class="header-right">
                <button class="btn-primary" style="background: transparent; border: 1px solid var(--border-color);" onclick="window.location.href='veiculos.php'">
                    <i class="fas fa-arrow-left"></i>
                    Voltar à lista
                </button>
            </div>
        </header>

        <?php if ($mensagem_erro): ?>
            <div class="message error" style="padding: 15px; border-radius: 8px; margin-bottom: 20px; background: rgba(255, 0, 0, 0.1); border: 2px solid rgba(255, 0, 0, 0.5); color: #ff0000; text-align: center;">
                <?php echo htmlspecialchars($mensagem_erro); ?>
            </div>
        <?php endif; ?>

        <?php if ($mensagem_sucesso): ?>
            <div class="message success" style="padding: 15px; border-radius: 8px; margin-bottom: 20px; background: rgba(0, 255, 0, 0.08); border: 2px solid rgba(0, 200, 0, 0.5); color: #00aa00; text-align: center;">
                <?php echo htmlspecialchars($mensagem_sucesso); ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de Novo Veículo -->
        <section class="table-section">
            <div class="table-header">
                <h3>Dados do Veículo</h3>
            </div>

            <form action="salvar_veiculo.php" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 20px;">
                <div class="form-row" style="display: flex; gap: 20px;">
                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Segmento do veículo</label>
                        <select name="segmento" id="segmento" required style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                            <option value="">Selecione o segmento</option>
                            <option value="Construção">Construção</option>
                            <option value="Agrícola">Agrícola</option>
                            <option value="Florestal">Florestal</option>
                            <option value="Leves">Leves</option>
                            <option value="Pesados">Pesados</option>
                        </select>
                    </div>
                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Placa (formato válido)</label>
                        <input type="text" name="placa" id="placa" required placeholder="Ex: ABC1234, ABC-1234 ou ABC1D23" style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                    </div>
                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Oficina</label>
                        <select name="empresa_id" style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                            <option value="">Selecione a oficina</option>
                            <option value="1">Injetron</option>
                            <option value="2">Barracão Pesados</option>
                        </select>
                    </div>
                </div>

                <div class="form-row" style="display: flex; gap: 20px;">
                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Empresa (opcional)</label>
                        <input type="text" name="empresa_nome"
                               placeholder="Ex: Cliente XYZ"
                               style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                    </div>

                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Modelo</label>
                        <input type="text" name="modelo"
                               placeholder="Ex: Gol 1.6"
                               style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                    </div>
                </div>

                <div class="form-row" style="display: flex; gap: 20px;">
                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Montadora</label>
                        <input type="text" name="montadora"
                               placeholder="Ex: Volkswagen"
                               style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                    </div>

                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Ano</label>
                        <input type="number" name="ano" min="1980" max="2100"
                               placeholder="Ex: 2018"
                               style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                    </div>
                </div>

                <div class="form-row" style="display: flex; gap: 20px;">
                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Combustível</label>
                        <select name="combustivel" required
                                style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                            <option value="Gasolina">Gasolina</option>
                            <option value="Etanol">Etanol</option>
                            <option value="Diesel">Diesel</option>
                            <option value="Flex">Flex</option>
                            <option value="GNV">GNV</option>
                            <option value="Elétrico">Elétrico</option>
                        </select>
                    </div>

                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Data de chegada na mecânica</label>
                        <input type="date" name="data_chegada"
                               style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                    </div>
                </div>

                <div class="form-row" style="display: flex; gap: 20px;">
                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Pode dar partida?</label>
                        <select name="pode_partida"
                                style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                            <option value="Sim">Sim</option>
                            <option value="Não">Não</option>
                        </select>
                    </div>

                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Pode mexer no veículo?</label>
                        <select name="pode_mexer"
                                style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                            <option value="">Selecione</option>
                            <option value="Sim">Sim</option>
                            <option value="Não">Não</option>
                        </select>
                    </div>
                </div>

                <div class="form-row" style="display: flex; gap: 20px;">
                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Previsão de saída (dias)</label>
                        <input type="number" name="tempo_estimado_dias" min="1" max="365"
                               placeholder="Ex: 3"
                               style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                    </div>

                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Status</label>
                        <select name="status" required
                                style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                            <option value="Pendente">Pendente</option>
                            <option value="Em andamento">Em andamento</option>
                            <option value="Concluído">Concluído</option>
                        </select>
                    </div>
                </div>

                <div class="form-row" style="display: flex; gap: 20px;">
                    <div class="input-group" style="flex: 1;">
                        <label class="select-label">Foto do veículo (opcional)</label>
                        <input type="file" name="foto_veiculo" accept="image/*"
                               style="width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                    </div>
                </div>

                <div style="display:flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                    <button type="button" class="btn-primary" style="background: transparent; border: 1px solid var(--border-color);" onclick="window.location.href='veiculos.php'">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i>
                        Salvar Veículo
                    </button>
                </div>
            </form>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="dashboard.js"></script>
    <script>
        (function() {
            const segmentoEl = document.getElementById('segmento');
            const placaEl = document.getElementById('placa');
            function updatePlacaState() {
                const seg = (segmentoEl.value || '').toLowerCase();
                const desabilitar = ['construção','construcao','agrícola','agricola','florestal'].includes(seg);
                placaEl.disabled = desabilitar;
                placaEl.required = !desabilitar;
                if (desabilitar) {
                    placaEl.value = '';
                    placaEl.placeholder = 'Placa desabilitada para este segmento';
                } else {
                    placaEl.placeholder = 'Ex: ABC1234, ABC-1234 ou ABC1D23';
                }
            }
            segmentoEl.addEventListener('change', updatePlacaState);
            updatePlacaState();
        })();
    </script>
</body>
</html>

