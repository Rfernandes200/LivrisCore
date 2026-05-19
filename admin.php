<?php
session_start();
require 'config.php';

// Bloqueio de Segurança: Se não for admin (1 ou 'admin'), é expulso para o index
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    header("Location: index.php");
    exit();
}

// Determinar qual secção mostrar (Geral por defeito)
$seccao = $_GET['seccao'] ?? 'geral';

// Contagens dinâmicas para os cards do Painel Geral
$total_utilizadores = 0;
$total_artigos = 0;
$utilizadores = [];
$artigos = [];

try {
    // Conta os utilizadores diretamente da tabela
    $stmt_users = $pdo->query("SELECT COUNT(*) FROM utilizadores");
    $total_utilizadores = $stmt_users->fetchColumn();

    // Conta os itens do catálogo diretamente da tabela itens
    $stmt_itens = $pdo->query("SELECT COUNT(*) FROM itens");
    $total_artigos = $stmt_itens->fetchColumn();

    // LÓGICA DA SECÇÃO UTILIZADORES
    if ($seccao === 'utilizadores') {
        $pesquisa = $_GET['q'] ?? '';
        if (!empty($pesquisa)) {
            $stmt_u = $pdo->prepare("SELECT id, nome, email, tipo, ativo, data_registo FROM utilizadores WHERE nome LIKE :q OR email LIKE :q ORDER BY id DESC");
            $stmt_u->execute(['q' => "%$pesquisa%"]);
        } else {
            $stmt_u = $pdo->query("SELECT id, nome, email, tipo, ativo, data_registo FROM utilizadores ORDER BY id DESC");
        }
        $utilizadores = $stmt_u->fetchAll(PDO::FETCH_ASSOC);
    }

    // LÓGICA DA SECÇÃO ARTIGOS (NOVA)
    if ($seccao === 'artigos') {
        $pesquisa_artigo = $_GET['q_artigo'] ?? '';
        $filtro_estado = $_GET['estado'] ?? '';

        // Base da Query com JOIN para trazer o nome legível da categoria
        $sql_artigos = "SELECT itens.*, categorias.nome as cat_nome 
                        FROM itens 
                        LEFT JOIN categorias ON itens.categoria_id = categorias.id 
                        WHERE 1=1";
        
        $params = [];

        // Filtro por texto (Título do anúncio)
        if (!empty($pesquisa_artigo)) {
            $sql_artigos .= " AND itens.titulo LIKE :q";
            $params['q'] = "%$pesquisa_artigo%";
        }

        // Filtro por Estado (disponivel, reservado, emprestado)
        if (!empty($filtro_estado)) {
            $sql_artigos .= " AND itens.estado = :estado";
            $params['estado'] = $filtro_estado;
        }

        $sql_artigos .= " ORDER BY itens.id DESC";
        
        $stmt_a = $pdo->prepare($sql_artigos);
        $stmt_a->execute($params);
        $artigos = $stmt_a->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    // Tratamento de erro seguro
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - BiblioBase</title>
    <link rel="stylesheet" href="Styles/StylesIndex.css">
    <link rel="stylesheet" href="Styles/StyleAdmin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

    <nav class="navbar" style="position: fixed; width: 100%; top: 0; left: 0; z-index: 100; box-sizing: border-box;">
        <div class="nav-left">
            <div class="logo"><strong>B</strong> BiblioBase <span style="font-size: 0.75rem; background: #3b82f6; padding: 2px 8px; border-radius: 4px; margin-left: 10px;">ADMIN</span></div>
        </div>
        <div class="nav-right">
            <a href="index.php" class="btn-login" style="background: none; border: 1px solid rgba(255,255,255,0.1);">Sair do Painel</a>
        </div>
    </nav>

    <div class="admin-container" style="padding-top: 70px;">
        <aside class="sidebar">
            <div class="sidebar-title">Navegação</div>
            <a href="admin.php?seccao=geral" class="sidebar-link <?= $seccao === 'geral' ? 'active' : '' ?>">📊 Geral</a>
            <a href="admin.php?seccao=utilizadores" class="sidebar-link <?= $seccao === 'utilizadores' ? 'active' : '' ?>">👥 Utilizadores</a>
            <a href="admin.php?seccao=reservas" class="sidebar-link <?= $seccao === 'reservas' ? 'active' : '' ?>">📅 Reservas</a>
            <a href="admin.php?seccao=emprestimos" class="sidebar-link <?= $seccao === 'emprestimos' ? 'active' : '' ?>">💼 Empréstimos</a>
            <a href="admin.php?seccao=artigos" class="sidebar-link <?= $seccao === 'artigos' ? 'active' : '' ?>">📦 Artigos (Catálogo)</a>
        </aside>

        <main class="admin-content">
            <?php if ($seccao === 'geral'): ?>
                <h1>Painel Geral</h1>
                <p class="admin-subtitle">Visão unificada do estado do sistema de gestão.</p>
                
                <div class="dashboard-grid">
                    <div class="stat-card"><h3>Utilizadores</h3><p><?= $total_utilizadores; ?></p></div>
                    <div class="stat-card"><h3>Reservas Ativas</h3><p>0</p></div>
                    <div class="stat-card"><h3>Artigos no Catálogo</h3><p><?= $total_artigos; ?></p></div>
                </div>

            <?php elseif ($seccao === 'utilizadores'): ?>
                <h1>Gestão de Utilizadores</h1>
                <p class="admin-subtitle">Adicione, edite ou remova contas de acesso à biblioteca.</p>

                <div class="admin-toolbar">
                    <form action="admin.php" method="GET" class="search-container-admin">
                        <input type="hidden" name="seccao" value="utilizadores">
                        <span class="search-icon-admin">🔍</span>
                        <input type="text" name="q" class="search-input-admin" placeholder="Pesquisar por username ou nome..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
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
                                <th>Registo</th>
                                <th>Estado</th>
                                <th style="width: 110px; text-align: center;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($utilizadores)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #64748b; padding: 30px;">
                                        Nenhum utilizador encontrado.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($utilizadores as $u): 
                                    $isAdmin = ($u['tipo'] == 'admin' || (int)$u['tipo'] === 1);
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
                                        <td style="color: #64748b;">
                                            <?= date('d/m/Y', strtotime($u['data_registo'])); ?>
                                        </td>
                                        <td>
                                            <span class="status-active" style="<?= (int)$u['ativo'] !== 1 ? 'color: #ef4444; border-color: rgba(239,68,68,0.2); background: rgba(239,68,68,0.1);' : '' ?>">
                                                <?= (int)$u['ativo'] === 1 ? 'Ativo' : 'Inativo'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="actions-cell" style="justify-content: center;">
                                                <button type="button" 
                                                        class="btn-action-square btn-edit-user btn-open-modal" 
                                                        title="Editar"
                                                        data-id="<?= $u['id']; ?>"
                                                        data-nome="<?= htmlspecialchars($u['nome']); ?>"
                                                        data-email="<?= htmlspecialchars($u['email']); ?>"
                                                        data-ativo="<?= $u['ativo']; ?>"
                                                        data-tipo="<?= htmlspecialchars($u['tipo']); ?>">
                                                    ✏️
                                                </button>
                                                
                                                <a href="eliminar_utilizador.php?id=<?= $u['id']; ?>" 
                                                   class="btn-action-square btn-delete-user" 
                                                   title="Eliminar"
                                                   onclick="return confirm('Tem a certeza que deseja eliminar o utilizador <?= htmlspecialchars($u['nome']); ?>?');">
                                                    🗑️
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($seccao === 'reservas'): ?>
                <h1>Controlo de Reservas</h1>
                <p class="admin-subtitle">Aprovar e gerir agendamentos de livros e artigos.</p>

            <?php elseif ($seccao === 'emprestimos'): ?>
                <h1>Empréstimos Ativos</h1>
                <p class="admin-subtitle">Histórico e devoluções dentro do prazo.</p>

            <?php elseif ($seccao === 'artigos'): ?>
                <h1>Gerir Artigos (Catálogo)</h1>
                <p class="admin-subtitle">Monitorize, filtre e altere a disponibilidade dos exemplares do acervo.</p>

                <div class="admin-toolbar" style="display: flex; gap: 15px; align-items: center; justify-content: space-between;">
                    <form action="admin.php" method="GET" style="display: flex; gap: 12px; width: 100%; max-width: 700px;">
                        <input type="hidden" name="seccao" value="artigos">
                        
                        <div class="search-container-admin" style="flex-grow: 2; margin: 0;">
                            <span class="search-icon-admin">🔍</span>
                            <input type="text" name="q_artigo" class="search-input-admin" placeholder="Pesquisar pelo nome do anúncio/artigo..." value="<?= htmlspecialchars($_GET['q_artigo'] ?? '') ?>">
                        </div>

                        <select name="estado" onchange="this.form.submit()" style="background: #0f172a; border: 1px solid rgba(255,255,255,0.08); color: #cbd5e1; padding: 0 15px; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.85rem; outline: none; cursor: pointer; min-width: 160px; height: 45px;">
                            <option value="">⚙️ Todos os Estados</option>
                            <option value="disponivel" <?= ($_GET['estado'] ?? '') === 'disponivel' ? 'selected' : '' ?>>🟢 Disponível</option>
                            <option value="reservado" <?= ($_GET['estado'] ?? '') === 'reservado' ? 'selected' : '' ?>>🟡 Reservado</option>
                            <option value="emprestado" <?= ($_GET['estado'] ?? '') === 'emprestado' ? 'selected' : '' ?>>🔴 Emprestado</option>
                        </select>
                        
                        <button type="submit" style="background: #3b82f6; color: white; border: none; padding: 0 20px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; height: 45px; transition: background 0.2s;">Filtrar</button>
                    </form>

                    <div class="counter-badge" style="white-space: nowrap;">
                        <span><?= count($artigos); ?> artigo(s) listado(s)</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="agent-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th style="width: 70px;">Capa</th>
                                <th>Título do Anúncio</th>
                                <th>Autor / Artista</th>
                                <th>Categoria</th>
                                <th>Estado</th>
                                <th style="width: 110px; text-align: center;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($artigos)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #64748b; padding: 40px;">
                                        ❌ Nenhum artigo corresponde aos filtros aplicados.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($artigos as $art): 
                                    $capaPath = !empty($art['imagem_url']) ? 'Uploads/'.$art['imagem_url'] : 'Images/default-cover.png';
                                    
                                    // Variáveis de estilo dinâmicas dependendo do estado
                                    $corEstado = 'color: #10b981; border-color: rgba(16,185,129,0.2); background: rgba(16,185,129,0.1);'; // Disponivel
                                    if($art['estado'] === 'reservado') {
                                        $corEstado = 'color: #f59e0b; border-color: rgba(245,158,11,0.2); background: rgba(245,158,11,0.1);';
                                    } elseif($art['estado'] === 'emprestado') {
                                        $corEstado = 'color: #ef4444; border-color: rgba(239,68,68,0.2); background: rgba(239,68,68,0.1);';
                                    }
                                ?>
                                    <tr>
                                        <td class="td-id">#<?= $art['id']; ?></td>
                                        <td>
                                            <img src="<?= $capaPath; ?>" alt="Capa" style="width: 42px; height: 55px; object-fit: cover; border-radius: 4px; border: 1px solid rgba(255,255,255,0.05);">
                                        </td>
                                        <td>
                                            <span class="user-name-text" style="font-weight: 600; color: #f8fafc;"><?= htmlspecialchars($art['titulo']); ?></span>
                                        </td>
                                        <td style="color: #cbd5e1;"><?= htmlspecialchars($art['autor_artista'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span style="font-size: 0.75rem; background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 4px; color: #94a3b8;">
                                                <?= htmlspecialchars($art['cat_nome'] ?? 'Sem Categoria'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-active" style="text-transform: uppercase; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em; <?= $corEstado ?>">
                                                <?= htmlspecialchars($art['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="actions-cell" style="justify-content: center;">
                                                <button type="button" class="btn-action-square" title="Editar Estado" style="filter: grayscale(1); opacity: 0.6; cursor: not-allowed;">
                                                    ⚙️
                                                </button>
                                                
                                                <a href="eliminar_artigo.php?id=<?= $art['id']; ?>" 
                                                   class="btn-action-square btn-delete-user" 
                                                   title="Eliminar Artigo do Acervo"
                                                   onclick="return confirm('Tem a certeza que deseja remover permanentemente o anúncio: <?= htmlspecialchars($art['titulo']); ?>?');">
                                                    🗑️
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <div id="editUserModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <span class="modal-tag">[ EDITAR UTILIZADOR ]</span>
                    <h2 id="modalUserTitle" class="modal-title">Nome do Utilizador</h2>
                </div>
                <button type="button" class="modal-close-btn" id="closeModalBtn">&times;</button>
            </div>

            <form action="atualiza_utilizador_painel.php" method="POST" class="modal-form">
                <input type="hidden" name="id" id="modalInputId">

                <div class="modal-form-grid">
                    <div class="input-group-admin">
                        <label>USERNAME / NOME</label>
                        <input type="text" name="nome" id="modalInputNome" required>
                    </div>

                    <div class="input-group-admin">
                        <label>EMAIL</label>
                        <input type="email" name="email" id="modalInputEmail" required>
                    </div>
                </div>

                <div class="modal-form-grid">
                    <div class="input-group-admin">
                        <label>NOVA PASSWORD (OPCIONAL)</label>
                        <input type="password" name="nova_pw" placeholder="Deixa em branco para não alterar">
                    </div>
                    <div class="input-group-admin"></div>
                </div>

                <div class="modal-form-grid">
                    <div class="input-group-admin">
                        <label>ESTADO DA CONTA</label>
                        <select name="ativo" id="modalSelectAtivo">
                            <option value="1">Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
                    </div>

                    <div class="input-group-admin">
                        <label>PERMISSÃO ADMIN</label>
                        <select name="tipo" id="modalSelectTipo">
                            <option value="user">Utilizador</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" id="cancelModalBtn">Cancelar</button>
                    <button type="submit" class="btn-modal-save">💾 Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('editUserModal');
        const closeBtn = document.getElementById('closeModalBtn');
        const cancelBtn = document.getElementById('cancelModalBtn');

        // Escuta os cliques em botões de edição de utilizador
        document.querySelectorAll('.btn-open-modal').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('modalInputId').value = this.dataset.id;
                document.getElementById('modalInputNome').value = this.dataset.nome;
                document.getElementById('modalInputEmail').value = this.dataset.email;
                document.getElementById('modalSelectAtivo').value = this.dataset.ativo;
                
                const tipoVal = this.dataset.tipo;
                document.getElementById('modalSelectTipo').value = (tipoVal === '1' || tipoVal === 'admin') ? 'admin' : 'user';
                
                document.getElementById('modalUserTitle').textContent = this.dataset.nome;
                modal.classList.add('show');
            });
        });

        const closeModal = () => modal.classList.remove('show');
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
    });
    </script>

</body>
</html>