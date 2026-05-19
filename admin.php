<?php
session_start();
require 'config.php';

// Bloqueio de Segurança: Se não for admin (1 ou 'admin'), é expulso para o index
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    header("Location: index.php");
    exit();
}

$id_admin_atual = $_SESSION['utilizador_id'] ?? null; 

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

    // LÓGICA DA SECÇÃO ARTIGOS
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

        // Filtro por Estado adaptado para (disponivel / indisponivel)
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
    die("Erro na Base de Dados: " . $e->getMessage());
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
            <?php if (isset($_SESSION['alerta'])): ?>
                <div style="padding: 15px; margin-bottom: 20px; border-radius: 8px; font-size: 0.9rem; font-weight: 500; 
                    <?= $_SESSION['alerta']['tipo'] === 'sucesso' ? 'background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.2);' : 'background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.2);' ?>">
                    <?= $_SESSION['alerta']['mensagem']; ?>
                </div>
                <?php unset($_SESSION['alerta']); ?>
            <?php endif; ?>

            <?php if ($seccao === 'geral'): ?>
                <h1>Painel Geral</h1>
                <p class="admin-subtitle">Visão unificada do estado do sistema de gestão.</p>
                
                <div class="dashboard-grid">
                    <div class="stat-card"><h3>Utilizadores</h3><p><?= $total_utilizadores; ?></p></div>
                    <div class="stat-card"><h3>Reservas Ativas</h3><p>0</p></div>
                    <div class="stat-card"><h3>Artigos no Catálogo</h3><p><?= $total_artigos; ?></p></div>
                </div>

            <?php elseif ($seccao === 'utilizadores'): ?>
                <h1>Lista de Utilizadores</h1>
                <p class="admin-subtitle">Consulta de contas com acesso à biblioteca.</p>

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
                                <th>Cargo</th>
                                <th>Estado</th>
                                <th style="width: 180px; text-align: center;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($utilizadores)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #64748b; padding: 30px;">
                                        Nenhum utilizador encontrado.
                                    </td>
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
                                        <td style="color: #64748b;">
                                            <?= date('d/m/Y', strtotime($u['data_registo'])); ?>
                                        </td>
                                        <td><?= $isAdmin ? 'Administrador' : 'Utilizador'; ?></td>
                                        <td>
                                            <span class="status-active" style="<?= (int)$u['ativo'] !== 1 ? 'color: #ef4444; border-color: rgba(239,68,68,0.2); background: rgba(239,68,68,0.1);' : '' ?>">
                                                <?= (int)$u['ativo'] === 1 ? 'Ativo' : 'Inativo'; ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <div style="display: flex; gap: 10px; justify-content: center; align-items: center;">
                                                
                                                <button class="btn-edit-trigger" 
                                                        data-id="<?= $u['id']; ?>" 
                                                        data-nome="<?= htmlspecialchars($u['nome']); ?>" 
                                                        data-email="<?= htmlspecialchars($u['email']); ?>" 
                                                        data-tipo="<?= $isAdmin ? 'admin' : 'user'; ?>" 
                                                        data-ativo="<?= $u['ativo']; ?>"
                                                        data-self="<?= $eProprioAdmin ? 'true' : 'false'; ?>"
                                                        onclick="abrirModalEditar(this)">
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

            <?php elseif ($seccao === 'reservas'): ?>
                <h1>Controlo de Reservas</h1>
                <p class="admin-subtitle">Aprovar e gerir agendamentos de livros e artigos.</p>

            <?php elseif ($seccao === 'emprestimos'): ?>
                <h1>Empréstimos Ativos</h1>
                <p class="admin-subtitle">Histórico e devoluções dentro do prazo.</p>

            <?php elseif ($seccao === 'artigos'): ?>
                <h1>Gerir Artigos (Catálogo)</h1>
                <p class="admin-subtitle">Monitorize, filtre e remova permanentemente os exemplares do acervo.</p>

                <div class="admin-toolbar" style="display: flex; gap: 15px; align-items: center; justify-content: space-between;">
                    <form action="admin.php" method="GET" style="display: flex; gap: 12px; width: 100%; max-width: 700px;">
                        <input type="hidden" name="seccao" value="artigos">
                        
                        <div class="search-container-admin" style="flex-grow: 2; margin: 0;">
                            <span class="search-icon-admin">🔍</span>
                            <input type="text" name="q_artigo" class="search-input-admin" placeholder="Pesquisar pelo nome do anúncio/artigo..." value="<?= htmlspecialchars($_GET['q_artigo'] ?? '') ?>">
                        </div>

                        <select name="estado" onchange="this.form.submit()" style="background: #0f172a; border: 1px solid rgba(255,255,255,0.08); color: #cbd5e1; padding: 0 15px; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.85rem; outline: none; cursor: pointer; min-width: 180px; height: 45px;">
                            <option value="">⚙️ Todos os Estados</option>
                            <option value="disponivel" <?= ($_GET['estado'] ?? '') === 'disponivel' ? 'selected' : '' ?>>🟢 Disponível</option>
                            <option value="indisponivel" <?= ($_GET['estado'] ?? '') === 'indisponivel' ? 'selected' : '' ?>>🔴 Indisponível</option>
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
                                    $estadoLimpo = strtolower(trim($art['estado']));

                                    if ($estadoLimpo === 'disponivel' || $estadoLimpo === 'disponível') {
                                        $textoExibido = "Disponível";
                                        $corEstado = 'color: #10b981; border: 1px solid rgba(16,185,129,0.3); background: rgba(16,185,129,0.1);';
                                    } else {
                                        $textoExibido = "Indisponível";
                                        $corEstado = 'color: #ef4444; border: 1px solid rgba(239,68,68,0.3); background: rgba(239,68,68,0.1);';
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
                                            <span style="display: inline-flex; align-items: center; justify-content: center; padding: 6px 12px; min-width: 110px; border-radius: 6px; text-transform: uppercase; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em; <?= $corEstado ?>">
                                                <?= $textoExibido; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="actions-cell" style="justify-content: center;">
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

    <div id="modalEditarUtilizador" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h2>✏️ Editar Perfil do Utilizador</h2>
                <button class="btn-close-modal" onclick="fecharModalEditar()">✕</button>
            </div>
            
            <form action="editar_utilizadores.php" method="POST">
                <input type="hidden" name="acao" value="atualizar_completo">
                <input type="hidden" id="modal_id" name="utilizador_id">

                <div class="form-group-modal">
                    <label for="modal_nome">Nome Completo</label>
                    <input type="text" id="modal_nome" name="nome" required>
                </div>

                <div class="form-group-modal">
                    <label for="modal_email">Endereço de Email</label>
                    <input type="email" id="modal_email" name="email" required>
                </div>

                <div class="form-group-modal">
                    <label for="modal_tipo">Cargo / Nível de Acesso</label>
                    <select id="modal_tipo" name="tipo">
                        <option value="user">Utilizador Comum</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>

                <div class="form-group-modal">
                    <label for="modal_ativo">Estado da Conta</label>
                    <select id="modal_ativo" name="ativo">
                        <option value="1">🟢 Ativo</option>
                        <option value="0">🔴 Inativo</option>
                    </select>
                </div>

                <p id="aviso_self_edit" style="color: #eab308; font-size: 0.75rem; display: none; margin-top: 10px; background: rgba(234,179,8,0.1); padding: 8px; border-radius: 4px;">
                    ⚠️ Nota: Por segurança, não pode alterar o seu próprio cargo nem desativar a sua conta atual.
                </p>

                <div class="modal-footer">
                    <button type="button" class="btn-modal btn-modal-cancel" onclick="fecharModalEditar()">Cancelar</button>
                    <button type="submit" class="btn-modal btn-modal-save">Guardar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalEditar(botao) {
            // Extrair as informações embutidas do utilizador selecionado
            const id = botao.getAttribute('data-id');
            const nome = botao.getAttribute('data-nome');
            const email = botao.getAttribute('data-email');
            const tipo = botao.getAttribute('data-tipo');
            const ativo = botao.getAttribute('data-ativo');
            const isSelf = botao.getAttribute('data-self') === 'true';

            // Alimentar dinamicamente os inputs do Pop-up
            document.getElementById('modal_id').value = id;
            document.getElementById('modal_nome').value = nome;
            document.getElementById('modal_email').value = email;
            document.getElementById('modal_tipo').value = tipo;
            document.getElementById('modal_ativo').value = ativo;

            // Restrição de segurança no Front-end: Trava selects se for a própria conta conectada
            if (isSelf) {
                document.getElementById('modal_tipo').disabled = true;
                document.getElementById('modal_ativo').disabled = true;
                document.getElementById('aviso_self_edit').style.display = 'block';
            } else {
                document.getElementById('modal_tipo').disabled = false;
                document.getElementById('modal_ativo').disabled = false;
                document.getElementById('aviso_self_edit').style.display = 'none';
            }

            // Ativa o display do Pop-up
            document.getElementById('modalEditarUtilizador').classList.add('active');
        }

        function fecharModalEditar() {
            document.getElementById('modalEditarUtilizador').classList.remove('active');
        }

        // Fecha automaticamente se o utilizador clicar na área escura (fora da caixa)
        window.onclick = function(event) {
            const modal = document.getElementById('modalEditarUtilizador');
            if (event.target === modal) {
                fecharModalEditar();
            }
        }
    </script>

</body>
</html>