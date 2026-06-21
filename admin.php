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
$total_reservas = 0;
$utilizadores = [];
$artigos = [];
$reservas = [];
$todas_categorias = [];
$todos_autores = [];

try {
    // 1. Conta os utilizadores diretamente da tabela
    $stmt_users = $pdo->query("SELECT COUNT(*) FROM utilizadores");
    $total_utilizadores = $stmt_users->fetchColumn();

    // 2. Conta os itens do catálogo diretamente da tabela livros
    $stmt_itens = $pdo->query("SELECT COUNT(*) FROM livros");
    $total_artigos = $stmt_itens->fetchColumn();

    // 3. Conta as reservas pendentes/ativas diretamente da tabela reservas
    $stmt_res_count = $pdo->query("SELECT COUNT(*) FROM reservas WHERE status = 'pendente'");
    $total_reservas = $stmt_res_count->fetchColumn();

    // LÓGICA DA SECÇÃO UTILIZADORES
    

    // LÓGICA DA SECÇÃO RESERVAS
    if ($seccao === 'reservas') {
        $sql_reservas = "SELECT reservas.*, utilizadores.nome as user_nome, livros.titulo as item_titulo 
                         FROM reservas 
                         INNER JOIN utilizadores ON reservas.utilizador_id = utilizadores.id 
                         INNER JOIN livros ON reservas.livro_id = livros.id 
                         WHERE reservas.status = 'pendente' 
                         ORDER BY reservas.id DESC";
        $reservas = $pdo->query($sql_reservas)->fetchAll(PDO::FETCH_ASSOC);
    }

    // LÓGICA DA SECÇÃO ARTIGOS
    

} catch (PDOException $e) {
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

    <?php require 'navbar.php'; ?>

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
                    <div class="stat-card"><h3>Reservas Ativas</h3><p><?= $total_reservas; ?></p></div>
                    <div class="stat-card"><h3>Artigos no Catálogo</h3><p><?= $total_artigos; ?></p></div>
                </div>

            <?php elseif ($seccao === 'utilizadores'): ?>
               <?php include 'seccao_utilizadores.php'; ?>
                </div>

            <?php elseif ($seccao === 'reservas'): ?>
                <h1>Controlo de Reservas Ativas</h1>
                <p class="admin-subtitle">Abaixo encontram-se todos os pedidos de reserva pendentes de levantamento.</p>

                <div class="table-responsive" style="margin-top: 20px;">
                    <table class="agent-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th>Utilizador</th>
                                <th>Artigo / Livro</th>
                                <th>Data de Início</th>
                                <th>Data Limite</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reservas)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #64748b; padding: 40px;">📅 Não existem reservas ativas no sistema de momento.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reservas as $res): ?>
                                    <tr>
                                        <td class="td-id">#<?= $res['id']; ?></td>
                                        <td style="font-weight: 500; color: #f8fafc;"><?= htmlspecialchars($res['user_nome']); ?></td>
                                        <td style="color: #cbd5e1;"><?= htmlspecialchars($res['item_titulo']); ?></td>
                                        <td><?= date('d/m/Y', strtotime($res['data_inicio'])); ?></td>
                                        <td style="color: #f59e0b; font-weight: 500;">
                                            <?= !empty($res['data_fim']) ? date('d/m/Y', strtotime($res['data_fim'])) : 'N/A'; ?>
                                        </td>
                                        <td>
                                            <span style="color: #3b82f6; border: 1px solid rgba(59,130,246,0.3); background: rgba(59,130,246,0.1); padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                                                <?= htmlspecialchars($res['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($seccao === 'emprestimos'): ?>
                <h1>Empréstimos Ativos</h1>
                <p class="admin-subtitle">Histórico e devoluções dentro do prazo.</p>

            <?php elseif ($seccao === 'artigos'): ?>
                <?php include 'seccao_artigos.php'; ?>
            <?php endif; ?>
        </main>
    </div>

    <!-- MODAL: EDITAR UTILIZADOR -->
    <div id="modalEditarUtilizador" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h2>✏️ Editar Perfil do Utilizador</h2>
                <button class="btn-close-modal" onclick="fecharModalEditar()">✕</button>
            </div>
            <form id="formEditarUtilizador" action="editar_utilizadores.php" method="POST">
                <input type="hidden" name="acao" value="actualizar_completo">
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
                    <label for="modal_telemovel">Número de Telemóvel</label>
                    <input type="tel" id="modal_telemovel" name="telemovel">
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

    <!-- MODAL: ADICIONAR ARTIGO -->
    <div id="modalAdicionarArtigo" class="modal-overlay">
        <div class="modal-box" style="max-width: 600px;">
            <div class="modal-header">
                <h2>➕ Adicionar Novo Artigo ao Catálogo</h2>
                <button class="btn-close-modal" onclick="fecharModalAdicionarArtigo()">✕</button>
            </div>
            <form action="inserir_artigo.php" method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group-modal">
                        <label>Título do Livro/Artigo</label>
                        <input type="text" name="titulo" placeholder="Ex: O Principezinho" required>
                    </div>
                    <div class="form-group-modal">
                        <label>ISBN</label>
                        <input type="text" name="isbn" placeholder="Ex: 9789722524223">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
                    <div class="form-group-modal">
                        <label>Editora</label>
                        <input type="text" name="editora" placeholder="Ex: Porto Editora">
                    </div>
                    <div class="form-group-modal">
                        <label>Ano de Edição</label>
                        <input type="number" name="ano_edicao" min="1000" max="2026" placeholder="Ex: 2020">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
                    <div class="form-group-modal">
                        <label>Autor Principal</label>
                        <select name="autor_id" required style="width: 100%; height: 40px; background: #0f172a; border: 1px solid rgba(255,255,255,0.1); color: #cbd5e1; border-radius: 6px; padding: 0 10px;">
                            <option value="">Selecione o Autor...</option>
                            <?php foreach ($todos_autores as $autor): ?>
                                <option value="<?= $autor['id']; ?>"><?= htmlspecialchars($autor['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group-modal">
                        <label>Categoria (CDU)</label>
                        <select name="cdu_codigo" required style="width: 100%; height: 40px; background: #0f172a; border: 1px solid rgba(255,255,255,0.1); color: #cbd5e1; border-radius: 6px; padding: 0 10px;">
                            <option value="">Selecione a Classe...</option>
                            <?php foreach ($todas_categorias as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['codigo']); ?>"><?= htmlspecialchars($cat['codigo'] . ' - ' . $cat['descricao']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
                    <div class="form-group-modal">
                        <label>Estado Inicial</label>
                        <select name="estado" style="width: 100%; height: 40px; background: #0f172a; border: 1px solid rgba(255,255,255,0.1); color: #cbd5e1; border-radius: 6px; padding: 0 10px;">
                            <option value="disponivel">🟢 Disponível</option>
                            <option value="indisponivel">🔴 Indisponível</option>
                        </select>
                    </div>
                    <div class="form-group-modal">
                        <label>Imagem da Capa</label>
                        <input type="file" name="imagem_capa" accept="image/*" style="padding: 5px 0;">
                    </div>
                </div>
                <div class="form-group-modal" style="margin-top: 10px;">
                    <label>Descrição / Resumo</label>
                    <textarea name="descricao" rows="3" placeholder="Insira a sinopse ou notas do exemplar..." style="width: 100%; background: #0f172a; border: 1px solid rgba(255,255,255,0.1); color: #cbd5e1; border-radius: 6px; padding: 10px; font-family: inherit; resize: vertical;"></textarea>
                </div>
                <div class="modal-footer" style="margin-top: 20px;">
                    <button type="button" class="btn-modal btn-modal-cancel" onclick="fecharModalAdicionarArtigo()">Cancelar</button>
                    <button type="submit" class="btn-modal btn-modal-save" style="background: #10b981;">Adicionar </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: EDITAR ARTIGO EXISTENTE -->
    <div id="modalEditarArtigo" class="modal-overlay">
        <div class="modal-box" style="max-width: 600px;">
            <div class="modal-header">
                <h2>✏️ Editar Detalhes do Artigo</h2>
                <button class="btn-close-modal" onclick="fecharModalEditarArtigo()">✕</button>
            </div>
            <form action="editar_artigo.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="edit_artigo_id" name="artigo_id">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group-modal">
                        <label>Título do Livro/Artigo</label>
                        <input type="text" id="edit_titulo" name="titulo" required>
                    </div>
                    <div class="form-group-modal">
                        <label>ISBN</label>
                        <input type="text" id="edit_isbn" name="isbn">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
                    <div class="form-group-modal">
                        <label>Editora</label>
                        <input type="text" id="edit_editora" name="editora">
                    </div>
                    <div class="form-group-modal">
                        <label>Ano de Edição</label>
                        <input type="number" id="edit_ano" name="ano_edicao" min="1000" max="2026">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
                    <div class="form-group-modal">
                        <label>Autor Principal</label>
                        <select id="edit_autor_id" name="autor_id" required style="width: 100%; height: 40px; background: #0f172a; border: 1px solid rgba(255,255,255,0.1); color: #cbd5e1; border-radius: 6px; padding: 0 10px;">
                            <?php foreach ($todos_autores as $autor): ?>
                                <option value="<?= $autor['id']; ?>"><?= htmlspecialchars($autor['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group-modal">
                        <label>Categoria (CDU)</label>
                        <select id="edit_cdu_codigo" name="cdu_codigo" required style="width: 100%; height: 40px; background: #0f172a; border: 1px solid rgba(255,255,255,0.1); color: #cbd5e1; border-radius: 6px; padding: 0 10px;">
                            <?php foreach ($todas_categorias as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['codigo']); ?>"><?= htmlspecialchars($cat['codigo'] . ' - ' . $cat['descricao']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
                    <div class="form-group-modal">
                        <label>Estado</label>
                        <select id="edit_estado" name="estado" style="width: 100%; height: 40px; background: #0f172a; border: 1px solid rgba(255,255,255,0.1); color: #cbd5e1; border-radius: 6px; padding: 0 10px;">
                            <option value="disponivel">🟢 Disponível</option>
                            <option value="indisponivel">🔴 Indisponível</option>
                        </select>
                    </div>
                    <div class="form-group-modal">
                        <label>Substituir Capa (Opcional)</label>
                        <input type="file" name="imagem_capa" accept="image/*" style="padding: 5px 0;">
                    </div>
                </div>
                <div class="form-group-modal" style="margin-top: 10px;">
                    <label>Descrição / Resumo</label>
                    <textarea id="edit_descricao" name="descricao" rows="3" style="width: 100%; background: #0f172a; border: 1px solid rgba(255,255,255,0.1); color: #cbd5e1; border-radius: 6px; padding: 10px; font-family: inherit; resize: vertical;"></textarea>
                </div>
                <div class="modal-footer" style="margin-top: 20px;">
                    <button type="button" class="btn-modal btn-modal-cancel" onclick="fecharModalEditarArtigo()">Cancelar</button>
                    <button type="submit" class="btn-modal btn-modal-save" style="background: #3b82f6;">Atualizar Artigo</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Funções do Modal de Utilizadores
        function abrirModalEditar(botao) {
            const id = botao.getAttribute('data-id');
            const nome = botao.getAttribute('data-nome');
            const email = botao.getAttribute('data-email');
            const telemovel = botao.getAttribute('data-telemovel');
            const tipo = botao.getAttribute('data-tipo');
            const ativo = botao.getAttribute('data-ativo');
            const isSelf = botao.getAttribute('data-self') === 'true';

            document.getElementById('modal_id').value = id;
            document.getElementById('modal_nome').value = nome;
            document.getElementById('modal_email').value = email;
            document.getElementById('modal_telemovel').value = telemovel;
            document.getElementById('modal_tipo').value = tipo;
            document.getElementById('modal_ativo').value = ativo;

            if (isSelf) {
                document.getElementById('modal_tipo').disabled = true;
                document.getElementById('modal_ativo').disabled = true;
                document.getElementById('aviso_self_edit').style.display = 'block';
            } else {
                document.getElementById('modal_tipo').disabled = false;
                document.getElementById('modal_ativo').disabled = false;
                document.getElementById('aviso_self_edit').style.display = 'none';
            }
            document.getElementById('modalEditarUtilizador').classList.add('active');
        }
        function fecharModalEditar() {
            document.getElementById('modalEditarUtilizador').classList.remove('active');
        }

        // Funções do Modal Adicionar Artigo
        function abrirModalAdicionarArtigo() {
            document.getElementById('modalAdicionarArtigo').classList.add('active');
        }
        function fecharModalAdicionarArtigo() {
            document.getElementById('modalAdicionarArtigo').classList.remove('active');
        }

        // Funções do Modal Editar Artigo
        function abrirModalEditarArtigo(botao) {
            document.getElementById('edit_artigo_id').value = botao.getAttribute('data-id');
            document.getElementById('edit_titulo').value = botao.getAttribute('data-titulo');
            document.getElementById('edit_isbn').value = botao.getAttribute('data-isbn');
            document.getElementById('edit_editora').value = botao.getAttribute('data-editora');
            document.getElementById('edit_ano').value = botao.getAttribute('data-ano');
            document.getElementById('edit_autor_id').value = botao.getAttribute('data-autor');
            document.getElementById('edit_cdu_codigo').value = botao.getAttribute('data-cdu');
            document.getElementById('edit_estado').value = botao.getAttribute('data-estado');
            document.getElementById('edit_descricao').value = botao.getAttribute('data-descricao');

            document.getElementById('modalEditarArtigo').classList.add('active');
        }
        function fecharModalEditarArtigo() {
            document.getElementById('modalEditarArtigo').classList.remove('active');
        }
    </script>
</body>
</html>