<?php
session_start();
require 'config.php';

// 1. Bloqueia o acesso se o utilizador não estiver logado ou não for Admin (tipo 1)
if (!isset($_SESSION['utilizador_id']) || (int)($_SESSION['utilizador_tipo'] ?? 0) !== 1) {
    $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Acesso restrito a administradores.'];
    header("Location: index.php");
    exit();
}

try {
    // Query calibrada exatamente para a estrutura das tuas tabelas 'emprestimos' e 'reservas'
    $sql = "SELECT itens.*, categorias.nome as cat_nome,
                   -- Verifica se há empréstimo ativo (data_devolucao_real é NULL)
                   (SELECT COUNT(*) FROM emprestimos 
                    WHERE emprestimos.item_id = itens.id 
                    AND emprestimos.data_devolucao_real IS NULL) as em_emprestimo,
                    
                   -- Verifica se há alguma reserva ativa (que não esteja cancelada nem concluída)
                   (SELECT COUNT(*) FROM reservas 
                    WHERE reservas.item_id = itens.id 
                    AND reservas.status NOT IN ('cancelada', 'concluida')) as em_reserva
            FROM itens 
            LEFT JOIN categorias ON itens.categoria_id = categorias.id
            ORDER BY itens.id DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $todos_artigos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Erro ao carregar os artigos: " . $e->getMessage());
}

// Carrega as categorias para o select do Pop-up
$stmt_cat = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC");
$categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Artigos - BiblioBase</title>
    <link rel="stylesheet" href="Styles/StylesIndex.css">
    <link rel="stylesheet" href="Styles/StyleAdmin.css">
    <link rel="stylesheet" href="Styles/Styleempres.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

    <?php require 'navbar.php'; ?>

    <div class="admin-container admin-container-block">
        <main class="admin-content admin-content-padded">
            
            <?php if (isset($_SESSION['alerta'])): ?>
                <div class="alert-box <?= $_SESSION['alerta']['tipo'] === 'sucesso' ? 'alert-sucesso' : 'alert-erro' ?>" style="padding: 15px; margin-bottom: 20px; border-radius: 6px;">
                    <?= $_SESSION['alerta']['mensagem']; ?>
                </div>
                <?php unset($_SESSION['alerta']); ?>
            <?php endif; ?>

            <h1>📢 Gestão de Artigos da Biblioteca</h1>
            <p class="admin-subtitle admin-subtitle-margin">
                Modo Administrador: Edite ou elimine qualquer artigo no acervo da biblioteca.
            </p>

            <div class="table-responsive table-wrapper">
                <table class="agent-table">
                    <thead>
                        <tr>
                            <th style="width: 70px;">Imagem</th>
                            <th>Artigo / Título</th>
                            <th>Formato</th>
                            <th>Disponibilidade</th>
                            <th style="text-align: center; width: 220px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($todos_artigos)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #64748b; padding: 30px;">Nenhum artigo encontrado no acervo.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($todos_artigos as $artigo): 
                                $caminhoImagem = !empty($artigo['imagem_url']) ? 'Uploads/'.$artigo['imagem_url'] : 'Images/default-cover.png';
                                $estadoLower = strtolower($artigo['estado'] ?? 'disponivel');
                                
                                $estaEmprestado = ((int)$artigo['em_emprestimo'] > 0);
                                $estaReservado = ((int)$artigo['em_reserva'] > 0);
                                $bloqueado = ($estaEmprestado || $estaReservado);
                                
                                $motivoBloqueio = '';
                                if ($estaEmprestado) $motivoBloqueio = 'emprestado';
                                elseif ($estaReservado) $motivoBloqueio = 'reservado';
                            ?>
                                <tr>
                                    <td>
                                        <img src="<?= htmlspecialchars($caminhoImagem); ?>" alt="Capa" style="width: 45px; height: 55px; object-fit: cover; border-radius: 4px; border: 1px solid #334155;">
                                    </td>
                                    <td style="font-weight: 600; color: #f8fafc;">
                                        <?= htmlspecialchars($artigo['titulo']); ?>
                                        <small style="display:block; color:#64748b; font-weight:400; margin-top:2px;">Criador/Artista: <?= htmlspecialchars($artigo['autor_artista'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td style="color: #94a3b8; font-size: 0.9rem;"><?= htmlspecialchars($artigo['cat_nome'] ?? 'Sem Formato'); ?></td>
                                    <td>
                                        <?php if ($estaEmprestado): ?>
                                            <span style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; padding: 4px 10px; border-radius: 50px; font-size: 0.8rem; font-weight: 500; display: inline-block;">📙 Emprestado</span>
                                        <?php elseif ($estaReservado): ?>
                                            <span style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 4px 10px; border-radius: 50px; font-size: 0.8rem; font-weight: 500; display: inline-block;">🔵 Reservado</span>
                                        <?php elseif ($estadoLower === 'disponivel'): ?>
                                            <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 10px; border-radius: 50px; font-size: 0.8rem; font-weight: 500; display: inline-block;">🟢 Disponível</span>
                                        <?php else: ?>
                                            <span style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 4px 10px; border-radius: 50px; font-size: 0.8rem; font-weight: 500; display: inline-block;">🔴 Indisponível</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: flex; gap: 8px; justify-content: center;">
                                            <button type="button" class="btn-confirmar-modal btn-trigger-edit" 
                                                    data-id="<?= $artigo['id']; ?>"
                                                    data-titulo="<?= htmlspecialchars($artigo['titulo']); ?>"
                                                    data-autor="<?= htmlspecialchars($artigo['autor_artista']); ?>"
                                                    data-categoria="<?= $artigo['categoria_id']; ?>"
                                                    data-estado="<?= htmlspecialchars($estadoLower); ?>"
                                                    data-bloqueio="<?= $motivoBloqueio; ?>"
                                                    data-descricao="<?= htmlspecialchars($artigo['descricao']); ?>"
                                                    style="border:none; cursor:pointer; font-size: 0.85rem; padding: 7px 14px; line-height:1.2;">
                                                Editar
                                            </button>
                                            
                                            <form action="processa_edicao_artigo.php" method="POST" style="margin:0;" 
                                                  onsubmit="return <?= $bloqueado ? "alert('Ação Interrompida: Não é possível eliminar um artigo com empréstimo ou reserva ativos!'); false;" : "confirm('Tem a certeza que deseja eliminar este artigo de forma permanente?')"; ?>">
                                                <input type="hidden" name="acao" value="eliminar">
                                                <input type="hidden" name="id" value="<?= $artigo['id']; ?>">
                                                <button type="submit" class="btn-cancelar-inline" <?= $bloqueado ? 'style="padding: 7px 14px; font-size: 0.85rem; border:none; opacity: 0.3; cursor: not-allowed;" disabled' : 'style="padding: 7px 14px; font-size: 0.85rem; cursor:pointer; border:none;"'; ?>>Eliminar</button>
                                            </form> 
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div id="editArtigoModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11, 15, 25, 0.95); backdrop-filter: blur(8px); z-index: 99999; display: none; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
        <div style="background: #0b0f19; border: 1px solid rgba(255, 255, 255, 0.08); width: 100%; max-width: 600px; border-radius: 12px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7); overflow: hidden; font-family: 'Inter', sans-serif;">
            
            <div style="padding: 24px 28px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); display: flex; justify-content: space-between; align-items: center; background: rgba(30, 41, 59, 0.2);">
                <div>
                    <span style="font-size: 0.7rem; color: #3b82f6; letter-spacing: 0.15em; font-weight: 700; display: block; margin-bottom: 4px; text-align: left;">[ PAINEL DE GESTÃO ]</span>
                    <h2 style="font-size: 1.3rem; color: white; font-weight: 600; margin: 0; text-align: left;">Editar Dados do Artigo</h2>
                </div>
                <button type="button" id="closeEditModalBtn" style="background: transparent; border: none; color: #64748b; font-size: 1.8rem; cursor: pointer; line-height: 1;">&times;</button>
            </div>

            <form id="formEditArtigo" action="processa_edicao_artigo.php" method="POST" enctype="multipart/form-data" style="padding: 28px; margin: 0; box-sizing: border-box;">
                <input type="hidden" name="id" id="modalEditId">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                        <label style="font-size: 0.75rem; color: #64748b; font-weight: 600;">TÍTULO *</label>
                        <input type="text" name="titulo" id="modalEditTitulo" required style="background: #0f172a; border: 1px solid #334155; color: white; padding: 10px; border-radius: 6px; font-family: inherit;">
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                        <label style="font-size: 0.75rem; color: #64748b; font-weight: 600;">AUTOR / ARTISTA *</label>
                        <input type="text" name="autor_artista" id="modalEditAutor" required style="background: #0f172a; border: 1px solid #334155; color: white; padding: 10px; border-radius: 6px; font-family: inherit;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 10px;">
                    <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                        <label style="font-size: 0.75rem; color: #64748b; font-weight: 600;">CATEGORIA *</label>
                        <select name="categoria_id" id="modalEditCategoria" required style="background: #0f172a; border: 1px solid #334155; color: white; padding: 10px; border-radius: 6px; height: 42px; font-family: inherit;">
                            <?php foreach($categorias as $cat): ?>
                                <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                        <label style="font-size: 0.75rem; color: #64748b; font-weight: 600;">DISPONIBILIDADE *</label>
                        <select name="estado" id="modalEditEstado" required style="background: #0f172a; border: 1px solid #334155; color: white; padding: 10px; border-radius: 6px; height: 42px; font-family: inherit;">
                            <option value="disponivel">🟢 Disponível</option>
                            <option value="indisponivel">🔴 Indisponível</option>
                            <option value="emprestado">📙 Emprestado</option>
                            <option value="reservado">🔵 Reservado</option>
                        </select>
                    </div>
                </div>

                <div id="modalAvisoVinculo" style="display: none; text-align: left; margin-bottom: 20px; color: #f59e0b; font-size: 0.8rem; font-weight: 500; background: rgba(245, 158, 11, 0.05); padding: 8px 12px; border-left: 3px solid #f59e0b; border-radius: 4px;"></div>

                <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; text-align: left;">
                    <label style="font-size: 0.75rem; color: #64748b; font-weight: 600;">DESCRIÇÃO / SINOPSE *</label>
                    <textarea name="descricao" id="modalEditDescricao" rows="4" required style="background: #0f172a; border: 1px solid #334155; color: white; padding: 10px; border-radius: 6px; resize: vertical; font-family: inherit;"></textarea>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 25px; text-align: left;">
                    <label style="font-size: 0.75rem; color: #64748b; font-weight: 600;">IMAGEM DE CAPA (Opcional)</label>
                    <input type="file" name="imagem" accept="image/*" style="background: #0f172a; border: 1px solid #334155; color: white; padding: 8px; border-radius: 6px;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 20px;">
                    <button type="button" id="cancelEditModalBtn" style="background: transparent; border: 1px solid rgba(255, 255, 255, 0.1); color: #cbd5e1; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 0.9rem;">Cancelar</button>
                    <button type="submit" style="background: #10b981; border: none; color: #0f172a; padding: 10px 22px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: 600;">Gravar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const editModal = document.getElementById('editArtigoModal');
        const closeBtn = document.getElementById('closeEditModalBtn');
        const cancelBtn = document.getElementById('cancelEditModalBtn');
        const form = document.getElementById('formEditArtigo');
        const selectEstado = document.getElementById('modalEditEstado');
        const avisoVinculo = document.getElementById('modalAvisoVinculo');

        document.querySelectorAll('.btn-trigger-edit').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('modalEditId').value = this.dataset.id;
                document.getElementById('modalEditTitulo').value = this.dataset.titulo;
                document.getElementById('modalEditAutor').value = this.dataset.autor;
                document.getElementById('modalEditCategoria').value = this.dataset.categoria;
                document.getElementById('modalEditDescricao').value = this.dataset.descricao;
                
                const estadoOriginal = this.dataset.estado ? this.dataset.estado.toLowerCase() : 'disponivel';
                selectEstado.value = estadoOriginal;

                const bloqueio = this.dataset.bloqueio ? this.dataset.bloqueio.trim() : '';
                
                if (bloqueio !== '') {
                    selectEstado.disabled = true;
                    avisoVinculo.style.display = 'block';
                    if (bloqueio === 'emprestado') {
                        avisoVinculo.innerHTML = '⚠️ <strong>Artigo Emprestado:</strong> Existe um empréstimo em curso para este artigo. O estado não pode ser modificado manualmente até ser devolvido.';
                    } else {
                        avisoVinculo.innerHTML = '⚠️ <strong>Artigo Reservado:</strong> Existe uma reserva ativa associada a este artigo. O estado está protegido.';
                    }
                } else {
                    selectEstado.disabled = false;
                    avisoVinculo.style.display = 'none';
                }

                editModal.style.display = 'flex';
            });
        });

        form.addEventListener('submit', function(e) {
            const titulo = document.getElementById('modalEditTitulo').value.trim();
            const autor = document.getElementById('modalEditAutor').value.trim();
            const descricao = document.getElementById('modalEditDescricao').value.trim();

            if (titulo === "" || autor === "" || descricao === "") {
                e.preventDefault();
                alert("⚠️ Erro: Todos os campos obrigatórios (*) devem ser preenchidos.");
                return;
            }

            selectEstado.disabled = false;
        });

        const fecharModal = () => { editModal.style.display = 'none'; };
        if (closeBtn) closeBtn.addEventListener('click', fecharModal);
        if (cancelBtn) cancelBtn.addEventListener('click', fecharModal);
        
        window.addEventListener('click', function(e) {
            if (e.target === editModal) fecharModal();
        });
    });
    </script>

</body>
</html>