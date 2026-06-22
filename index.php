<?php 
session_start();
require 'config.php'; 

// Pegar o ID do utilizador logado (caso exista sessão) para usar na comparação dos botões
$id_logado = isset($_SESSION['utilizador_id']) ? (int)$_SESSION['utilizador_id'] : null;

// REGRA DO MÁXIMO DE 2 RESERVAS: Verificar se o utilizador já atingiu o limite máximo de reservas pendentes
$bloqueado_por_limite = false;
if ($id_logado) {
    $stmt_limite = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE utilizador_id = :user_id AND status = 'pendente'");
    $stmt_limite->execute(['user_id' => $id_logado]);
    if ((int)$stmt_limite->fetchColumn() >= 2) {
        $bloqueado_por_limite = true;
    }
}

// 1. Consulta Atualizada: foca na tabela 'livros', traz a classe CDU e agrega os múltiplos autores (N:N)
$query = "SELECT livros.*, cdu_classes.descricao as cdu_nome, reservas.utilizador_id as quem_reservou,
                 GROUP_CONCAT(autores.nome SEPARATOR ', ') as autor_artista
          FROM livros 
          LEFT JOIN cdu_classes ON livros.cdu_codigo = cdu_classes.codigo
          LEFT JOIN livro_autores ON livros.id = livro_autores.livro_id
          LEFT JOIN autores ON livro_autores.autor_id = autores.id
          LEFT JOIN reservas ON livros.id = reservas.livro_id AND reservas.status = 'pendente'
          GROUP BY livros.id";
$stmt = $pdo->query($query);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Procurar as classes CDU na Base de Dados para preencher o Select do Pop-up
$cdu_classes = [];
try {
    $stmt_cdu = $pdo->query("SELECT codigo, descricao FROM cdu_classes ORDER BY codigo ASC");
    $cdu_classes = $stmt_cdu->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Falha silenciosa
}

// 3. Procurar os autores na Base de Dados para preencher o Select do Pop-up (NOVO)
$todos_autores = [];
try {
    $stmt_autores = $pdo->query("SELECT id, nome FROM autores ORDER BY nome ASC");
    $todos_autores = $stmt_autores->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Falha silenciosa
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiblioBase - Gestão de Biblioteca</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Styles/StylesIndex.css">
    <link rel="stylesheet" href="Styles/StylesIndex2.css">
</head>
<body>

<?php if (isset($_SESSION['alerta'])): ?>
    <div id="toastAlert" class="alert-toast <?= $_SESSION['alerta']['tipo'] ?>">
        <span><?= $_SESSION['alerta']['mensagem'] ?></span>
    </div>
    <?php unset($_SESSION['alerta']); ?>
<?php endif; ?>

<header>
    <?php require 'navbar.php'; ?>

    <div class="hero-container">
        <div class="hero-text">
            <p class="hero-tag">SISTEMA DE GESTÃO</p>
            <h1>A sua biblioteca,<br><span>organizada.</span></h1>
            <p class="hero-subtitle">
                Pesquise, reserve e acompanhe todo o acervo de livros em tempo real.
            </p>
        </div>
    </div>
</header>

<main class="catalog-container" style="margin-top: 40px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding: 0 10px;">
        <h2 class="section-title" style="margin: 0;">Catálogo de Livros</h2>
        
        <?php if (isset($_SESSION['utilizador_tipo']) && ((int)$_SESSION['utilizador_tipo'] === 1 || $_SESSION['utilizador_tipo'] === 'admin')): ?>
            <button type="button" class="btn-add-catalog" id="openAddCatalogBtn" style="background: #3b82f6; color: white;">
                <svg viewBox="0 0 24 24" fill="white" style="width: 16px; height: 16px; margin-right: 5px;"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                Adicionar Livro
            </button>
        <?php endif; ?>
    </div>
    
    <div class="grid-itens">
        <?php foreach($itens as $item): 
            $itemImagem = !empty($item['imagem_url']) ? 'Uploads/'.$item['imagem_url'] : 'Images/default-cover.png';
            $criadorTipo = 'Administrador'; 
            $estadoLimpo = strtolower(trim($item['estado']));
            $quemReservou = !empty($item['quem_reservou']) ? (int)$item['quem_reservou'] : null;
        ?>
        <div class="card">
            <div class="card-header">
                <span class="status-badge <?= $item['estado'] ?>" style="<?php 
                    if($estadoLimpo === 'reservado') {
                        echo 'background: rgba(234, 179, 8, 0.15); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.3);';
                    } ?>">
                    <?= strtoupper($item['estado']) ?>
                </span>
                <span class="category-icon">📖</span>
            </div>
            <div class="card-body">
                <small class="category-label">CDU <?= htmlspecialchars($item['cdu_codigo']) ?></small>
                <h3><?= htmlspecialchars($item['titulo']) ?></h3>
                <p class="author-text"><?= htmlspecialchars($item['autor_artista'] ?? 'Autor Não Associado') ?></p>
                
                <div class="card-footer">
                    <button type="button" class="btn-details js-open-details" 
                            data-titulo="<?= htmlspecialchars($item['titulo']) ?>"
                            data-autor="<?= htmlspecialchars($item['autor_artista'] ?? 'Não Associado') ?>"
                            data-cdu="CDU <?= htmlspecialchars($item['cdu_codigo']) ?> - <?= htmlspecialchars($item['cdu_nome']) ?>"
                            data-isbn="<?= htmlspecialchars($item['isbn']) ?>"
                            data-editora="<?= htmlspecialchars($item['editora']) ?>"
                            data-ano="<?= htmlspecialchars($item['ano_edicao']) ?>"
                            data-estado="<?= htmlspecialchars($item['estado']) ?>"
                            data-descricao="<?= htmlspecialchars($item['descricao']) ?>"
                            data-imagem="<?= $itemImagem ?>"
                            data-criador="<?= $criadorTipo ?>">
                        Detalhes
                    </button>

                    <?php if($estadoLimpo === 'disponivel'): ?>
                        <?php if ($bloqueado_por_limite): ?>
                            <button disabled class="btn-disabled" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); cursor: not-allowed; font-size: 0.8rem; padding: 8px 12px; border-radius: 6px;" title="Atingiu o limite de 2 reservas pendentes.">
                                Limite Atingido
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn-action js-open-reserve" 
                                    data-id="<?= $item['id'] ?>" 
                                    data-titulo="<?= htmlspecialchars($item['titulo']) ?>"
                                    style="border:none; cursor:pointer;">
                                Reservar
                            </button>
                        <?php endif; ?>
                    <?php elseif($estadoLimpo === 'reservado'): ?>
                        <?php if($id_logado && $id_logado === $quemReservou): ?>
                            <form action="cancela_reserva.php" method="POST" style="margin:0; display:inline;">
                                <input type="hidden" name="livro_id" value="<?= $item['id'] ?>">
                                <button type="submit" class="btn-action" style="background: #ef4444; color: white; border:none; cursor:pointer;">
                                    Cancelar
                                </button>
                            </form>
                        <?php else: ?>
                            <button disabled class="btn-disabled" style="background: rgba(234, 179, 8, 0.1); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.2); cursor: not-allowed;">Reservado</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <button disabled class="btn-disabled">Indisponível</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<footer>
    <p>&copy; 2026 BiblioBase - Sistema de Gestão de Biblioteca</p>
</footer>

<div id="detailsCatalogModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11, 15, 25, 0.95); backdrop-filter: blur(8px); z-index: 99999; display: none; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
    <div style="background: #0b0f19; border: 1px solid rgba(255, 255, 255, 0.08); width: 100%; max-width: 650px; border-radius: 12px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7); overflow: hidden; font-family: 'Inter', sans-serif;">
        <div style="padding: 20px 28px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); display: flex; justify-content: space-between; align-items: center; background: rgba(30, 41, 59, 0.2);">
            <div>
                <span id="txtDetailCdu" style="font-size: 0.7rem; color: #3b82f6; letter-spacing: 0.15em; font-weight: 700; display: block; margin-bottom: 4px; text-align: left;">CLASSIFICAÇÃO CDU</span>
                <h2 id="txtDetailTitulo" style="font-size: 1.4rem; color: white; font-weight: 600; margin: 0; text-align: left;">Título do Livro</h2>
            </div>
            <button type="button" id="closeDetailModalBtn" style="background: transparent; border: none; color: #64748b; font-size: 1.8rem; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <div style="padding: 28px; display: flex; gap: 24px; box-sizing: border-box;">
            <div style="flex-shrink: 0;">
                <img id="imgDetailCapa" src="Images/default-cover.png" alt="Capa" style="width: 140px; height: 190px; object-fit: cover; border-radius: 8px; box-shadow: 0 8px 20px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.05);">
            </div>
            <div style="flex-grow: 1; display: flex; flex-direction: column; gap: 14px; text-align: left;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <label style="font-size: 0.65rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em; display: block; margin-bottom: 2px;">AUTOR(ES)</label>
                        <span id="txtDetailAutor" style="color: #cbd5e1; font-size: 0.95rem; font-weight: 500;">-</span>
                    </div>
                    <div>
                        <label style="font-size: 0.65rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em; display: block; margin-bottom: 2px;">ISBN</label>
                        <span id="txtDetailIsbn" style="color: #cbd5e1; font-size: 0.95rem; font-weight: 500;">-</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <label style="font-size: 0.65rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em; display: block; margin-bottom: 2px;">EDITORA / ANO</label>
                        <span id="txtDetailEditoraAno" style="color: #cbd5e1; font-size: 0.95rem; font-weight: 500;">-</span>
                    </div>
                    <div>
                        <label style="font-size: 0.65rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em; display: block; margin-bottom: 4px;">ESTADO</label>
                        <span id="txtDetailEstado" style="font-size: 0.75rem; padding: 4px 10px; border-radius: 4px; font-weight: 600; text-transform: uppercase; display: inline-block;">-</span>
                    </div>
                </div>

                <div>
                    <label style="font-size: 0.65rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em; display: block; margin-bottom: 2px;">SINOPSE / RESUMO</label>
                    <p id="txtDetailDescricao" style="color: #94a3b8; font-size: 0.85rem; line-height: 1.5; margin: 0; max-height: 100px; overflow-y: auto; padding-right: 5px;">-</p>
                </div>
                <div style="margin-top: auto; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 6px;">
                    <span style="font-size: 0.8rem; color: #64748b;">Criado por:</span>
                    <strong id="txtDetailCriador" style="font-size: 0.8rem; color: #60a5fa;">Administrador</strong>
                </div>
            </div>
        </div>
        <div style="padding: 16px 28px; background: rgba(11, 15, 25, 0.4); border-top: 1px solid rgba(255, 255, 255, 0.05); display: flex; justify-content: flex-end;">
            <button type="button" id="cancelDetailModalBtn" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255, 255, 255, 0.1); color: #cbd5e1; padding: 8px 18px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-family: 'Inter', sans-serif;">Fechar Janela</button>
        </div>
    </div>
</div>

<div id="addCatalogModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11, 15, 25, 0.95); backdrop-filter: blur(8px); z-index: 99999; display: none; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
    <div style="background: #0b0f19; border: 1px solid rgba(255, 255, 255, 0.08); width: 100%; max-width: 600px; border-radius: 12px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7); overflow: hidden; font-family: 'Inter', sans-serif;">
        <div style="padding: 24px 28px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); display: flex; justify-content: space-between; align-items: center; background: rgba(30, 41, 59, 0.2);">
            <div>
                <span style="font-size: 0.7rem; color: #3b82f6; letter-spacing: 0.15em; font-weight: 700; display: block; margin-bottom: 4px; text-align: left;">[ Adicionar Livro ]</span>
                <h2 style="font-size: 1.3rem; color: white; font-weight: 600; margin: 0; text-align: left;">Novo Livro no Catálogo</h2>
            </div>
            <button type="button" id="closeAddModalBtn" style="background: transparent; border: none; color: #64748b; font-size: 1.8rem; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <form action="processa_artigo.php" method="POST" enctype="multipart/form-data" style="padding: 28px; margin: 0; box-sizing: border-box;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">TÍTULO DO LIVRO *</label>
                    <input type="text" name="titulo" class="modal-field" placeholder="Ex: Os Maias" required>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">AUTOR DO LIVRO *</label>
                    <select name="autor_id" class="modal-field" required style="height: 45px;">
                        <option value="" disabled selected style="background:#0b0f19;">Selecione um Autor...</option>
                        <?php foreach($todos_autores as $autor): ?>
                            <option value="<?= $autor['id']; ?>" style="background:#0b0f19; color:white;">
                                <?= htmlspecialchars($autor['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">CÓDIGO ISBN *</label>
                    <input type="text" name="isbn" class="modal-field" placeholder="Ex: 978-972-0-04671-0" required>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">CLASSIFICAÇÃO CDU *</label>
                    <select name="cdu_codigo" class="modal-field" required style="height: 45px;">
                        <option value="" disabled selected style="background:#0b0f19;">Selecione a Classe CDU...</option>
                        <?php foreach($cdu_classes as $classe): ?>
                            <option value="<?= $classe['codigo']; ?>" style="background:#0b0f19; color:white;">
                                <?= $classe['codigo'] . ' - ' . htmlspecialchars(mb_strimwidth($classe['descricao'], 0, 48, "...")); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">EDITORA *</label>
                    <input type="text" name="editora" class="modal-field" placeholder="Ex: Porto Editora" required>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">ANO DE EDIÇÃO *</label>
                    <input type="number" name="ano_edicao" class="modal-field" placeholder="Ex: 2026" min="1000" max="2026" required>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">ESTADO INICIAL *</label>
                    <select name="estado" class="modal-field" required style="height: 45px;">
                        <option value="disponivel" selected style="background:#0b0f19;">Disponível</option>
                        <option value="reservado" style="background:#0b0f19;">Reservado</option>
                        <option value="indisponivel" style="background:#0b0f19;">Indisponível</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px; text-align: left; margin-bottom: 20px;">
                <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">SINOPSE / RESUMO *</label>
                <textarea name="descricao" rows="4" class="modal-field" placeholder="Escreva uma breve sinopse do livro..." required style="resize: vertical;"></textarea>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px; text-align: left; margin-bottom: 2px;">
                <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">IMAGEM DE CAPA (OBRIGATÓRIO) *</label>
                <input type="file" name="imagem" accept="image/*" required class="modal-field" style="padding: 8px 10px !important;">
                <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display:block;">Apenas ficheiros de imagem válidos (JPG, PNG, WEBP).</small>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 25px; border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 20px;">
                <button type="button" id="cancelAddModalBtn" style="background: transparent; border: 1px solid rgba(255, 255, 255, 0.1); color: #cbd5e1; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 0.9rem;">Cancelar Criação</button>
                <button type="submit" style="background: #3b82f6; border: none; color: white; padding: 10px 22px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: 600;">Confirmar Criação</button>
            </div>
        </form>
    </div>
</div>

<?php require 'index_reservas.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // CONTROLO DO MODAL DE ADICIONAR LIVRO
    const addModal = document.getElementById('addCatalogModal');
    const openBtn = document.getElementById('openAddCatalogBtn');
    const closeBtn = document.getElementById('closeAddModalBtn');
    const cancelBtn = document.getElementById('cancelAddModalBtn');

    if (openBtn) {
        openBtn.addEventListener('click', function() {
            addModal.style.display = 'flex';
        });
    }

    const closeAddModal = () => { addModal.style.display = 'none'; };
    if (closeBtn) closeBtn.addEventListener('click', closeAddModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeAddModal);
    if (addModal) addModal.addEventListener('click', function(e) { if (e.target === addModal) closeAddModal(); });

    // CONTROLO DO MODAL DE DETALHES
    const detailModal = document.getElementById('detailsCatalogModal');
    const closeDetailBtn = document.getElementById('closeDetailModalBtn');
    const cancelDetailBtn = document.getElementById('cancelDetailModalBtn');

    document.querySelectorAll('.js-open-details').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('txtDetailTitulo').innerText = this.dataset.titulo;
            document.getElementById('txtDetailAutor').innerText = this.dataset.autor;
            document.getElementById('txtDetailCdu').innerText = `[ ${this.dataset.cdu.toUpperCase()} ]`;
            document.getElementById('txtDetailIsbn').innerText = this.dataset.isbn;
            document.getElementById('txtDetailEditoraAno').innerText = `${this.dataset.editora} (${this.dataset.ano})`;
            document.getElementById('txtDetailDescricao').innerText = this.dataset.descricao;
            document.getElementById('imgDetailCapa').src = this.dataset.imagem;
            document.getElementById('txtDetailCriador').innerText = this.dataset.criador;

            const badgeEstado = document.getElementById('txtDetailEstado');
            const estado = this.dataset.estado;
            badgeEstado.innerText = estado;
            
            if (estado.toLowerCase() === 'disponivel') {
                badgeEstado.style.background = 'rgba(16, 185, 129, 0.1)';
                badgeEstado.style.color = '#10b981';
            } else if (estado.toLowerCase() === 'reservado') {
                badgeEstado.style.background = 'rgba(234, 179, 8, 0.15)';
                badgeEstado.style.color = '#eab308';
            } else {
                badgeEstado.style.background = 'rgba(239, 68, 68, 0.1)';
                badgeEstado.style.color = '#ef4444';
            }

            detailModal.style.display = 'flex';
        });
    });

    const closeDetailModal = () => { detailModal.style.display = 'none'; };
    if (closeDetailBtn) closeDetailBtn.addEventListener('click', closeDetailModal);
    if (cancelDetailBtn) cancelDetailBtn.addEventListener('click', closeDetailModal);
    if (detailModal) detailModal.addEventListener('click', function(e) { if (e.target === detailModal) closeDetailModal(); });

    // TOAST ALERT
    const toast = document.getElementById('toastAlert');
    if (toast) {
        setTimeout(() => { toast.classList.add('show'); }, 200);
        setTimeout(() => { toast.classList.remove('show'); }, 4000);
    }
});
</script>

</body>
</html>