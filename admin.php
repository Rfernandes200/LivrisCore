<?php
session_start();
require 'config.php';

// Bloqueio de Segurança
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    header("Location: index.php");
    exit();
}

$id_admin_atual = $_SESSION['utilizador_id'] ?? null; 

// Processamento de Cancelamento de Reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_admin']) && $_POST['acao_admin'] === 'cancelar_reserva_admin') {
    $reserva_id = (int)$_POST['reserva_id'];
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT livro_id FROM reservas WHERE id = :id AND status = 'pendente'");
        $stmt->execute(['id' => $reserva_id]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reserva) {
            $pdo->prepare("UPDATE livros SET estado = 'disponivel' WHERE id = :livro_id")->execute(['livro_id' => $reserva['livro_id']]);
            $pdo->prepare("DELETE FROM reservas WHERE id = :id")->execute(['id' => $reserva_id]);
            $_SESSION['alerta'] = ['tipo' => 'sucesso', 'mensagem' => 'Reserva #' . $reserva_id . ' cancelada com sucesso.'];
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Erro: ' . $e->getMessage()];
    }
    header("Location: admin.php?seccao=reservas");
    exit();
}

$seccao = $_GET['seccao'] ?? 'geral';
$total_utilizadores = $pdo->query("SELECT COUNT(*) FROM utilizadores")->fetchColumn();
$total_artigos = $pdo->query("SELECT COUNT(*) FROM livros")->fetchColumn();
$total_reservas = $pdo->query("SELECT COUNT(*) FROM reservas WHERE status = 'pendente'")->fetchColumn();

$reservas = [];
if ($seccao === 'reservas') {
    $reservas = $pdo->query("SELECT r.*, u.nome as user_nome, l.titulo as item_titulo 
                             FROM reservas r 
                             INNER JOIN utilizadores u ON r.utilizador_id = u.id 
                             INNER JOIN livros l ON r.livro_id = l.id 
                             WHERE r.status = 'pendente' ORDER BY r.id DESC")->fetchAll(PDO::FETCH_ASSOC);
}

$todos_autores = $pdo->query("SELECT id, nome FROM autores ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$todas_categorias = $pdo->query("SELECT codigo, descricao FROM cdu_classes ORDER BY codigo ASC")->fetchAll(PDO::FETCH_ASSOC);
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
        <!-- MENU LATERAL DE NAVEGAÇÃO -->
        <aside class="sidebar">
            <div class="sidebar-title">Navegação</div>
            <a href="admin.php?seccao=geral" class="sidebar-link <?= $seccao === 'geral' ? 'active' : '' ?>">📊 Geral</a>
            <a href="admin.php?seccao=utilizadores" class="sidebar-link <?= $seccao === 'utilizadores' ? 'active' : '' ?>">👥 Utilizadores</a>
            <a href="admin.php?seccao=reservas" class="sidebar-link <?= $seccao === 'reservas' ? 'active' : '' ?>">📅 Reservas</a>
            <a href="admin.php?seccao=emprestimos" class="sidebar-link <?= $seccao === 'emprestimos' ? 'active' : '' ?>">💼 Empréstimos</a>
            <a href="admin.php?seccao=artigos" class="sidebar-link <?= $seccao === 'artigos' ? 'active' : '' ?>">📦 Artigos (Catálogo)</a>
        </aside>

        <!-- CONTEÚDO PRINCIPAL DINÂMICO -->
        <main class="admin-content">
            <!-- SESSÃO DE ALERTAS DO SISTEMA (SUCESSO / ERRO) -->
            <?php if (isset($_SESSION['alerta'])): ?>
                <div style="padding: 15px; margin-bottom: 20px; border-radius: 8px; font-size: 0.9rem; font-weight: 500; 
                    <?= $_SESSION['alerta']['tipo'] === 'sucesso' ? 'background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.2);' : 'background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.2);' ?>">
                    <?= $_SESSION['alerta']['mensagem']; ?>
                </div>
                <?php unset($_SESSION['alerta']); ?>
            <?php endif; ?>

            <!-- SECÇÃO: GERAL (DASHBOARD) -->
            <?php if ($seccao === 'geral'): ?>
                <h1>Painel Geral</h1>
                <p class="admin-subtitle">Visão unificada do estado do sistema de gestão.</p>
                
                <div class="dashboard-grid">
                    <div class="stat-card"><h3>Utilizadores</h3><p><?= $total_utilizadores; ?></p></div>
                    <div class="stat-card"><h3>Reservas Ativas</h3><p><?= $total_reservas; ?></p></div>
                    <div class="stat-card"><h3>Artigos no Catálogo</h3><p><?= $total_artigos; ?></p></div>
                </div>

            <!-- SECÇÃO: UTILIZADORES -->
            <?php elseif ($seccao === 'utilizadores'): ?>
               <?php include 'seccao_utilizadores.php'; ?>

            <!-- SECÇÃO: RESERVAS -->
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
                                <th style="text-align: center; width: 150px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reservas)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #64748b; padding: 40px;">📅 Não existem reservas ativas no sistema de momento.</td>
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
                                        <td style="text-align: center;">
                                            <form action="admin.php" method="POST" onsubmit="return confirm('Tem a certeza que deseja cancelar esta reserva administrativamente?');" style="margin: 0;">
                                                <input type="hidden" name="acao_admin" value="cancelar_reserva_admin">
                                                <input type="hidden" name="reserva_id" value="<?= $res['id']; ?>">
                                                <button type="submit" class="btn-cancelar-reserva" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 500; cursor: pointer; transition: all 0.2s;">
                                                    ❌ Cancelar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <!-- SECÇÃO: EMPRÉSTIMOS -->
            <?php elseif ($seccao === 'emprestimos'): ?>
                <?php include 'seccao_emprestimos.php'; ?>

            <!-- SECÇÃO: ARTIGOS -->
            <?php elseif ($seccao === 'artigos'): ?>
                <?php include 'seccao_artigos.php'; ?>
            <?php endif; ?>
        </main>
    </div>

    <!-- ==================================================================
         ZONA DE MODAIS (JANELAS EM OVERLAY)
         ================================================================== -->

    <!-- 1. MODAL: EDITAR UTILIZADOR -->
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

    <!-- 2. MODAL: ADICIONAR NOVO UTILIZADOR -->
    <div id="modalAdicionarUtilizador" class="modal-overlay">
        <div class="modal-box" style="max-width: 500px;">
            <div class="modal-header">
                <h2>➕ Criar Novo Utilizador</h2>
                <button class="btn-close-modal" onclick="fecharModalAdicionarUtilizador()">✕</button>
            </div>
            <form action="inserir_utilizador.php" method="POST">
                <div class="form-group-modal">
                    <label>Nome Completo</label>
                    <input type="text" name="nome" placeholder="Ex: João Silva" required>
                </div>
                <div class="form-group-modal">
                    <label>Endereço de Email</label>
                    <input type="email" name="email" placeholder="Ex: joao@email.com" required>
                </div>
                <div class="form-group-modal">
                    <label>Número de Telemóvel</label>
                    <input type="tel" name="telemovel" placeholder="Ex: 912345678">
                </div>
                <div class="form-group-modal">
                    <label>Cargo / Nível de Acesso</label>
                    <select name="tipo">
                        <option value="user">Utilizador Comum</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <div class="form-group-modal">
                    <label>Palavra-passe Inicial</label>
                    <input type="password" name="password" placeholder="Defina uma senha provisória..." required>
                </div>
                <div class="modal-footer" style="margin-top: 20px;">
                    <button type="button" class="btn-modal btn-modal-cancel" onclick="fecharModalAdicionarUtilizador()">Cancelar</button>
                    <button type="submit" class="btn-modal btn-modal-save" style="background: #10b981;">Criar Utilizador</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. MODAL: ADICIONAR ARTIGO -->
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
                    <button type="submit" class="btn-modal btn-modal-save" style="background: #10b981;">Adicionar Artigo</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 4. MODAL: EDITAR ARTIGO EXISTENTE -->
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

    <!-- ==================================================================
         SCRIPTS JAVASCRIPT GERAIS DO PAINEL
         ================================================================== -->

    <script>
/**
 * BIBLIOBASE - GESTÃO DE MODAIS
 * Versão otimizada para CSS com suporte a transições (.show)
 */

// 1. FUNÇÃO MESTRE PARA ALTERNAR MODAIS
function alternarModal(id, mostrar = true) {
    const modal = document.getElementById(id);
    if (!modal) return;

    if (mostrar) {
        modal.style.display = 'flex';
        // Delay minúsculo para permitir a transição CSS
        setTimeout(() => modal.classList.add('show'), 10);
    } else {
        modal.classList.remove('show');
        // Espera a duração da transição CSS (300ms) antes de esconder
        setTimeout(() => modal.style.display = 'none', 300);
    }
}

// 2. FUNÇÕES DE UTILIZADORES
function abrirModalEditar(btn) {
    document.getElementById('modal_id').value        = btn.getAttribute('data-id');
    document.getElementById('modal_nome').value      = btn.getAttribute('data-nome');
    document.getElementById('modal_email').value     = btn.getAttribute('data-email');
    document.getElementById('modal_telemovel').value = btn.getAttribute('data-telemovel');
    document.getElementById('modal_tipo').value      = btn.getAttribute('data-tipo');
    document.getElementById('modal_ativo').value     = btn.getAttribute('data-ativo');

    // Lógica de segurança (aviso e bloqueio)
    const isSelf = btn.getAttribute('data-self') === 'true';
    const aviso = document.getElementById('aviso_self_edit');
    if (aviso) aviso.style.display = isSelf ? 'block' : 'none';
    
    document.getElementById('modal_tipo').disabled  = isSelf;
    document.getElementById('modal_ativo').disabled = isSelf;

    alternarModal('modalEditarUtilizador', true);
}

function abrirModalAdicionarUtilizador() { alternarModal('modalAdicionarUtilizador', true); }
function fecharModalEditar() { alternarModal('modalEditarUtilizador', false); }
function fecharModalAdicionarUtilizador() { alternarModal('modalAdicionarUtilizador', false); }

// 3. FUNÇÕES DE ARTIGOS
function abrirModalAdicionarArtigo() { alternarModal('modalAdicionarArtigo', true); }
function fecharModalAdicionarArtigo() { alternarModal('modalAdicionarArtigo', false); }

function abrirModalEditarArtigo(btn) {
    document.getElementById('edit_artigo_id').value    = btn.getAttribute('data-id');
    document.getElementById('edit_titulo').value       = btn.getAttribute('data-titulo');
    document.getElementById('edit_isbn').value         = btn.getAttribute('data-isbn');
    document.getElementById('edit_editora').value      = btn.getAttribute('data-editora');
    document.getElementById('edit_ano').value          = btn.getAttribute('data-ano');
    document.getElementById('edit_autor_id').value     = btn.getAttribute('data-autor');
    document.getElementById('edit_cdu_codigo').value   = btn.getAttribute('data-cdu');
    document.getElementById('edit_estado').value       = btn.getAttribute('data-estado');
    document.getElementById('edit_descricao').value    = btn.getAttribute('data-descricao');

    alternarModal('modalEditarArtigo', true);
}
function fecharModalEditarArtigo() { alternarModal('modalEditarArtigo', false); }

// 4. EVENTO GLOBAL DE FECHO (Clicar fora do card)
window.addEventListener('click', function(event) {
    const listaModais = [
        'modalEditarUtilizador', 
        'modalAdicionarUtilizador', 
        'modalAdicionarArtigo', 
        'modalEditarArtigo'
    ];
    
    listaModais.forEach(id => {
        const modal = document.getElementById(id);
        // Fecha apenas se o clique for exatamente no overlay (fundo escuro)
        if (event.target === modal) {
            alternarModal(id, false);
        }
    });
});
</script>
</body>
</html>