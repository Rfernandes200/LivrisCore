<?php
// Bloqueio de Segurança direto no ficheiro incluído
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    exit('Acesso negado');
}

// ==========================================
// LÓGICA DE PROCESSAMENTO E CONSULTA SQL
// ==========================================
$utilizadores = [];
$pesquisa = $_GET['q'] ?? '';

try {
    if (!empty($pesquisa)) {
        $stmt_u = $pdo->prepare("SELECT id, nome, email, telemovel, tipo, ativo, data_registo FROM utilizadores WHERE nome LIKE :q OR email LIKE :q ORDER BY id DESC");
        $stmt_u->execute(['q' => "%$pesquisa%"]);
    } else {
        $stmt_u = $pdo->query("SELECT id, nome, email, telemovel, tipo, ativo, data_registo FROM utilizadores ORDER BY id DESC");
    }
    $utilizadores = $stmt_u->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div style='color: #ef4444; padding: 15px; background: rgba(239,68,68,0.1); border-radius: 8px; margin-bottom: 20px;'>Erro ao carregar utilizadores: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<h1>Lista de Utilizadores</h1>
<p class="admin-subtitle">Consulta de contas com acesso à biblioteca.</p>
<button type="button" onclick="abrirModalAdicionarUtilizador()" style="background: #10b981; color: #ffffff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; margin-bottom: 20px; font-family: 'Inter', sans-serif;">
    ➕ Novo Utilizador
</button>
<div class="admin-toolbar">
    <form action="admin.php" method="GET" class="search-container-admin">
        <input type="hidden" name="seccao" value="utilizadores">
        <span class="search-icon-admin">🔍</span>
        <input type="text" name="q" class="search-input-admin" placeholder="Pesquisar por username ou nome..." value="<?= htmlspecialchars($pesquisa) ?>">
    </form>
    <div class="counter-badge">
        <span><?= count($utilizadores); ?> utilizador(s) encontrado(s)</span>
    </div>
</div>

<div class="table-responsive">
    <table class="agent-table">
        <thead>
            <tr>
                <th style="width: 60px;">ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Telemóvel</th>
                <th>Registo</th>
                <th>Cargo</th>
                <th>Estado</th>
                <th style="width: 180px; text-align: center;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($utilizadores)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: #64748b; padding: 30px;">Nenhum utilizador encontrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($utilizadores as $u): 
                    $u_tipo_normalizado = trim(strtolower($u['tipo']));
                    $isAdmin = ($u_tipo_normalizado === 'admin' || (int)$u['tipo'] === 1);
                    $eProprioAdmin = ($id_admin_atual !== null && (int)$u['id'] === (int)$id_admin_atual);
                ?>
                    <tr>
                        <td class="td-id">#<?= $u['id']; ?></td>
                        <td>
                            <div class="user-profile-cell">
                                <span class="user-name-text"><?= htmlspecialchars($u['nome']); ?></span>
                                <?php if ($isAdmin): ?>
                                    <span class="badge-admin-tag">Admin</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($u['email']); ?></td>
                        <td style="color: #cbd5e1;">
                            <?= !empty($u['telemovel']) ? htmlspecialchars($u['telemovel']) : '<span style="color:#64748b; font-style:italic;">Nenhum</span>'; ?>
                        </td>
                        <td style="color: #64748b;">
                            <?= !empty($u['data_registo']) ? date('d/m/Y', strtotime($u['data_registo'])) : 'N/A'; ?>
                        </td>
                        <td><?= $isAdmin ? 'Administrador' : 'Utilizador'; ?></td>
                        <td>
                            <span class="status-active" style="<?= (int)$u['ativo'] !== 1 ? 'color: #ef4444; border-color: rgba(239,68,68,0.2); background: rgba(239,68,68,0.1);' : '' ?>">
                                <?= (int)$u['ativo'] === 1 ? 'Ativo' : 'Inativo'; ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 10px; justify-content: center; align-items: center;">
                              <button type="button" 
                                      class="btn-edit-trigger"
                                      onclick="abrirModalEditar(this)" 
                                      data-id="<?= $u['id']; ?>" 
                                      data-nome="<?= htmlspecialchars($u['nome']); ?>" 
                                      data-email="<?= htmlspecialchars($u['email']); ?>" 
                                      data-telemovel="<?= htmlspecialchars($u['telemovel'] ?? ''); ?>" 
                                      data-tipo="<?= $isAdmin ? 'admin' : 'user'; ?>" 
                                      data-ativo="<?= $u['ativo']; ?>" 
                                      data-self="<?= $eProprioAdmin ? 'true' : 'false'; ?>">
                                  ✏️ Editar
                              </button>

                                <?php if (!$eProprioAdmin): ?>
                                    <form action="editar_utilizadores.php" method="POST" style="margin:0;" onsubmit="return confirm('Tem a certeza absoluta que deseja eliminar permanentemente a conta de: <?= htmlspecialchars($u['nome']); ?>?');">
                                        <input type="hidden" name="acao" value="eliminar_utilizador">
                                        <input type="hidden" name="utilizador_id" value="<?= $u['id']; ?>">
                                        <button type="submit" style="background: none; border: none; cursor: pointer; font-size: 1.1rem; padding: 4px;" title="Eliminar Utilizador">🗑️</button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size: 0.75rem; color: #64748b; font-style: italic;">Sua Conta</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>