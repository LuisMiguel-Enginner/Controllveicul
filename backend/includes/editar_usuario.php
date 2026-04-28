<?php
require_once 'verificar_sessao.php';
require_once 'config.php';

// Apenas administradores podem acessar
if (($_SESSION['usuario_perfil_id'] ?? 0) != 1) {
    header('Location: usuarios.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: usuarios.php');
    exit;
}

// Carregar dados do usuário
try {
    $stmt = $pdo->prepare("
        SELECT id, nome, email, perfil_id, setor_id, ativo
        FROM usuarios
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        $_SESSION['erro'] = 'Usuário não encontrado.';
        header('Location: usuarios.php');
        exit;
    }

    // Listas de perfis e setores
    $perfis = $pdo->query("SELECT id, nome FROM perfis WHERE nome IN ('Administrador','Barracão','Visualizador') ORDER BY nome")->fetchAll();
    $setores = $pdo->query("
        SELECT id, nome 
        FROM setores 
        WHERE nome NOT IN ('Administrador','Administrativo','Barracão','Visualizador','Operacional')
        ORDER BY nome
    ")->fetchAll();

} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro ao carregar dados do usuário.';
    error_log('Erro editar_usuario (GET): ' . $e->getMessage());
    header('Location: usuarios.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - Chiptronic</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <main class="main-content" style="margin-left: 0; width: 100vw; max-width: 100vw; padding: 0;">
        <div style="min-height: 100vh; width: 100%; display: flex; align-items: center; justify-content: center; padding: 0 20px;">
        <section class="table-section" style="max-width: 600px; width: 100%; margin: 0;">
            <div class="table-header">
                <h3>Editar Usuário</h3>
            </div>

            <form action="salvar_edicao_usuario.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">

                <div class="input-group">
                    <label style="display:block; margin-bottom:5px; font-size:13px;">Nome</label>
                    <input type="text" name="nome" required
                           value="<?php echo htmlspecialchars($usuario['nome']); ?>"
                           style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                </div>

                <div class="input-group">
                    <label style="display:block; margin-bottom:5px; font-size:13px;">Email</label>
                    <input type="email" name="email" required
                           value="<?php echo htmlspecialchars($usuario['email']); ?>"
                           style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                </div>

                <div class="form-row" style="display: flex; gap: 15px;">
                    <div class="input-group" style="flex:1;">
                        <label style="display:block; margin-bottom:5px; font-size:13px;">Perfil</label>
                        <select name="perfil_id" required id="perfilSelect"
                                style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                            <option value="1" <?php echo (int)$usuario['perfil_id'] === 1 ? 'selected' : ''; ?>>Administrador</option>
                            <option value="2" <?php echo (int)$usuario['perfil_id'] === 2 ? 'selected' : ''; ?>>Barracão</option>
                            <option value="3" <?php echo (int)$usuario['perfil_id'] === 3 ? 'selected' : ''; ?>>Visualizador</option>
                        </select>
                    </div>

                    <div class="input-group" style="flex:1;">
                        <label style="display:block; margin-bottom:5px; font-size:13px;">Setor</label>
                        <select name="setor_id" required
                                style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                            <?php foreach ($setores as $setor): ?>
                                <option value="<?php echo $setor['id']; ?>" <?php echo $setor['id'] == $usuario['setor_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($setor['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <?php $isAdmin = (($_SESSION['usuario_perfil_id'] ?? 0) == 1); ?>
                <div class="input-group">
                    <label style="display:block; margin-bottom:5px; font-size:13px;">Admin</label>
                    <select id="adminToggle" <?php echo $isAdmin ? '' : 'disabled'; ?>
                            style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                        <option value="Sim" <?php echo ($usuario['perfil_id'] == 1) ? 'selected' : ''; ?>>Sim</option>
                        <option value="Não" <?php echo ($usuario['perfil_id'] == 1) ? '' : 'selected'; ?>>Não</option>
                    </select>
                </div>

                <div class="input-group">
                    <label style="display:block; margin-bottom:5px; font-size:13px;">Status</label>
                    <select name="ativo"
                            style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-light);">
                        <option value="1" <?php echo $usuario['ativo'] ? 'selected' : ''; ?>>Ativo</option>
                        <option value="0" <?php echo !$usuario['ativo'] ? 'selected' : ''; ?>>Inativo</option>
                    </select>
                </div>

                <div style="display:flex; justify-content: space-between; gap: 10px; margin-top: 10px;">
                    <button type="button" class="btn-primary" style="background: transparent; border: 1px solid var(--border-color);" onclick="window.location.href='usuarios.php'">
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
        </div>
    </main>
    <script>
        const perfilSelect = document.getElementById('perfilSelect');
        const adminToggle = document.getElementById('adminToggle');
        function syncAdminFromPerfil() {
            if (!adminToggle) return;
            adminToggle.value = (perfilSelect.value === '1') ? 'Sim' : 'Não';
        }
        function syncPerfilFromAdmin() {
            if (!adminToggle) return;
            if (adminToggle.value === 'Sim') {
                perfilSelect.value = '1';
            } else if (perfilSelect.value === '1') {
                perfilSelect.value = '3';
            }
        }
        perfilSelect.addEventListener('change', syncAdminFromPerfil);
        if (adminToggle) adminToggle.addEventListener('change', syncPerfilFromAdmin);
        syncAdminFromPerfil();
    </script>
</body>
</html>


