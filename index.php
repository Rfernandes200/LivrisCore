<?php 
session_start();
require 'config.php'; 

// Pegar o ID do utilizador logado (caso exista sessão) para usar na comparação dos botões
$id_logado = isset($_SESSION['utilizador_id']) ? (int)$_SESSION['utilizador_id'] : null;

// 1. Consulta atualizada: traz os itens, a categoria e quem reservou o item (se estiver reservado e pendente)
$query = "SELECT itens.*, categorias.nome as cat_nome, reservas.utilizador_id as quem_reservou 
          FROM itens 
          LEFT JOIN categorias ON itens.categoria_id = categorias.id
          LEFT JOIN reservas ON itens.id = reservas.item_id AND reservas.status = 'pendente'";
$stmt = $pdo->query($query);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Procurar as categorias disponíveis na Base de Dados para preencher o Select do Pop-up
$categorias = [];
try {
    $stmt_cat = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC");
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Falha silenciosa caso a tabela ainda não exista
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
    <nav class="navbar">
        <div class="nav-left">
            <div class="logo"><strong>B</strong> BiblioBase</div>
            <div class="menu">
                <a href="index.php" class="active">Catálogo</a>
                <a href="#">Empréstimos</a>
                <a href="#">Reservas</a>
        
                <?php if (isset($_SESSION['utilizador_tipo']) && ((int)$_SESSION['utilizador_tipo'] === 1 || $_SESSION['utilizador_tipo'] === 'admin')): ?>
                    <a href="admin.php" style="color: #60a5fa; font-weight: 600; margin-left: 15px;">⚡ Administração</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="nav-right">
            <?php if (isset($_SESSION['utilizador_nome'])): 
                $nome = $_SESSION['utilizador_nome'];
                $exibirNome = !empty($nome) ? $nome : "Utilizador";
                $inicial = strtoupper(substr($exibirNome, 0, 1));
            ?>
                <div class="profile-box">
                    <div class="user-avatar">
                        <?php echo $inicial; ?>
                    </div>
                    <a href="perfil.php" class="user-name" style="text-decoration: none; color: inherit;">
                        <?php echo htmlspecialchars($exibirNome); ?>
                    </a>
                    <a href="logout.php" class="logout-link">Sair</a>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn-login">Entrar</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="hero-container">
        <div class="hero-text">
            <p class="hero-tag">SISTEMA DE GESTÃO</p>
            <h1>A sua biblioteca,<br><span>organizada.</span></h1>
            <p class="hero-subtitle">
                Pesquise, reserve e acompanhe livros, CDs e Blu-rays em tempo real.
            </p>
            
           
        </div>
    </div>
</header>

<main class="catalog-container" style="margin-top: 40px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding: 0 10px;">
        <h2 class="section-title" style="margin: 0;">Catálogo de Artigos</h2>
        
        <?php if (isset($_SESSION['utilizador_tipo']) && ((int)$_SESSION['utilizador_tipo'] === 1 || $_SESSION['utilizador_tipo'] === 'admin')): ?>
            <button type="button" class="btn-add-catalog" id="openAddCatalogBtn">
                <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                Adicionar Artigo
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
                <span class="category-icon">
                    <?= ($item['cat_nome'] == 'Livro') ? '📖' : (($item['cat_nome'] == 'CD') ? '💿' : '🎬') ?>
                </span>
            </div>
            <div class="card-body">
                <small class="category-label"><?= strtoupper($item['cat_nome']) ?></small>
                <h3><?= htmlspecialchars($item['titulo']) ?></h3>
                <p class="author-text"><?= htmlspecialchars($item['autor_artista']) ?></p>
                
                <div class="card-footer">
                    <button type="button" class="btn-details js-open-details" 
                            data-titulo="<?= htmlspecialchars($item['titulo']) ?>"
                            data-autor="<?= htmlspecialchars($item['autor_artista']) ?>"
                            data-categoria="<?= htmlspecialchars($item['cat_nome']) ?>"
                            data-estado="<?= htmlspecialchars($item['estado']) ?>"
                            data-descricao="<?= htmlspecialchars($item['descricao']) ?>"
                            data-imagem="<?= $itemImagem ?>"
                            data-criador="<?= $criadorTipo ?>">
                        Detalhes
                    </button>

                    <?php if($estadoLimpo === 'disponivel'): ?>
                        <button type="button" class="btn-action js-open-reserve" 
                                data-id="<?= $item['id'] ?>" 
                                data-titulo="<?= htmlspecialchars($item['titulo']) ?>"
                                style="border:none; cursor:pointer;">
                            Reservar
                        </button>
                    <?php elseif($estadoLimpo === 'reservado'): ?>
                        <?php if($id_logado && $id_logado === $quemReservou): ?>
                            <form action="cancela_reserva.php" method="POST" style="margin:0; display:inline;">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
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

<div id="detailsCatalogModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11, 15, 25, 0.95); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 99999; display: none; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
    <div style="background: #0b0f19; border: 1px solid rgba(255, 255, 255, 0.08); width: 100%; max-width: 650px; border-radius: 12px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7); overflow: hidden; font-family: 'Inter', sans-serif;">
        <div style="padding: 20px 28px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); display: flex; justify-content: space-between; align-items: center; background: rgba(30, 41, 59, 0.2);">
            <div>
                <span id="txtDetailCategoria" style="font-size: 0.7rem; color: #3b82f6; letter-spacing: 0.15em; font-weight: 700; display: block; margin-bottom: 4px; text-align: left;">CATEGORIA</span>
                <h2 id="txtDetailTitulo" style="font-size: 1.4rem; color: white; font-weight: 600; margin: 0; text-align: left;">Título do Artigo</h2>
            </div>
            <button type="button" id="closeDetailModalBtn" style="background: transparent; border: none; color: #64748b; font-size: 1.8rem; cursor: pointer; line-height: 1; transition: color 0.2s;">&times;</button>
        </div>
        <div style="padding: 28px; display: flex; gap: 24px; box-sizing: border-box;">
            <div style="flex-shrink: 0;">
                <img id="imgDetailCapa" src="Images/default-cover.png" alt="Capa" style="width: 140px; height: 190px; object-fit: cover; border-radius: 8px; box-shadow: 0 8px 20px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.05);">
            </div>
            <div style="flex-grow: 1; display: flex; flex-direction: column; gap: 14px; text-align: left;">
                <div>
                    <label style="font-size: 0.65rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em; display: block; margin-bottom: 2px;">AUTOR / ARTISTA</label>
                    <span id="txtDetailAutor" style="color: #cbd5e1; font-size: 0.95rem; font-weight: 500;">-</span>
                </div>
                <div>
                    <label style="font-size: 0.65rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em; display: block; margin-bottom: 4px;">ESTADO DO EXEMPLAR</label>
                    <span id="txtDetailEstado" style="font-size: 0.75rem; padding: 4px 10px; border-radius: 4px; font-weight: 600; text-transform: uppercase; display: inline-block;">-</span>
                </div>
                <div>
                    <label style="font-size: 0.65rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em; display: block; margin-bottom: 2px;">SINOPSE / DESCRIÇÃO</label>
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

<div id="addCatalogModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11, 15, 25, 0.95); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 99999; display: none; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
    <div style="background: #0b0f19; border: 1px solid rgba(255, 255, 255, 0.08); width: 100%; max-width: 600px; border-radius: 12px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7); overflow: hidden; font-family: 'Inter', sans-serif;">
        <div style="padding: 24px 28px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); display: flex; justify-content: space-between; align-items: center; background: rgba(30, 41, 59, 0.2);">
            <div>
                <span style="font-size: 0.7rem; color: #3b82f6; letter-spacing: 0.15em; font-weight: 700; display: block; margin-bottom: 4px; text-align: left;">[ Adicionar ao Catálogo ]</span>
                <h2 style="font-size: 1.3rem; color: white; font-weight: 600; margin: 0; text-align: left;">Novo Artigo no Catálogo</h2>
            </div>
            <button type="button" id="closeAddModalBtn" style="background: transparent; border: none; color: #64748b; font-size: 1.8rem; cursor: pointer; line-height: 1; transition: color 0.2s;">&times;</button>
        </div>
        <form action="processa_artigo.php" method="POST" enctype="multipart/form-data" style="padding: 28px; margin: 0; box-sizing: border-box;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">TÍTULO / NOME DO PRODUTO *</label>
                    <input type="text" name="titulo" class="modal-field" placeholder="Ex: Moby Dick" required>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">AUTOR / ARTISTA *</label>
                    <input type="text" name="autor_artista" class="modal-field" placeholder="Ex: Herman Melville" required>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">CATEGORIA *</label>
                    <select name="categoria_id" class="modal-field" required style="height: 45px;">
                        <option value="" disabled selected style="background:#0b0f19;">Selecione o formato...</option>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?= $cat['id']; ?>" style="background:#0b0f19; color:white;"><?= htmlspecialchars($cat['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
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
                <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">DESCRIÇÃO / RESUMO *</label>
                <textarea name="descricao" rows="4" class="modal-field" placeholder="Escreva uma breve sinopse ou detalhes do artigo..." required style="resize: vertical;"></textarea>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px; text-align: left; margin-bottom: 2px;">
                <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">IMAGEM DE CAPA (OBRIGATÓRIO) *</label>
                <input type="file" name="imagem" accept="image/*" required class="modal-field" style="padding: 8px 10px !important;">
                <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display:block;">Apenas ficheiros de imagem válidos (JPG, PNG, WEBP).</small>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 25px; border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 20px;">
                <button type="button" id="cancelAddModalBtn" style="background: transparent; border: 1px solid rgba(255, 255, 255, 0.1); color: #cbd5e1; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-family: 'Inter', sans-serif;">Cancelar Criação</button>
                <button type="submit" style="background: #3b82f6; border: none; color: white; padding: 10px 22px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: 600; font-family: 'Inter', sans-serif;">Confirmar Criação</button>
            </div>
        </form>
    </div>
</div>

<div id="reserveCatalogModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11, 15, 25, 0.95); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 99999; display: none; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
    <div style="background: #0b0f19; border: 1px solid rgba(255, 255, 255, 0.08); width: 100%; max-width: 450px; border-radius: 12px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7); overflow: hidden; font-family: 'Inter', sans-serif;">
        
        <div style="padding: 20px 24px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); display: flex; justify-content: space-between; align-items: center; background: rgba(30, 41, 59, 0.2);">
            <div>
                <span style="font-size: 0.7rem; color: #3b82f6; letter-spacing: 0.15em; font-weight: 700; display: block; margin-bottom: 4px; text-align: left;">[ SOLICITAR RESERVA ]</span>
                <h2 id="txtReserveTitulo" style="font-size: 1.2rem; color: white; font-weight: 600; margin: 0; text-align: left;">Reservar Artigo</h2>
            </div>
            <button type="button" id="closeReserveModalBtn" style="background: transparent; border: none; color: #64748b; font-size: 1.8rem; cursor: pointer; line-height: 1; transition: color 0.2s;">&times;</button>
        </div>

        <form action="processo_reserva.php" method="POST" style="padding: 24px; margin: 0; box-sizing: border-box;">
            <input type="hidden" name="item_id" id="formReserveItemId">

            <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 20px;">
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">DATA DE INÍCIO *</label>
                    <input type="date" name="data_inicio" id="resDataInicio" required 
                           style="width: 100%; box-sizing: border-box; height: 45px; background-color: #ffffff; color: #0f172a; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; font-family: 'Inter', sans-serif; font-size: 0.95rem; font-weight: 500; outline: none;">
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">DATA DE FIM *</label>
                    <input type="date" name="data_fim" id="resDataFim" required 
                           style="width: 100%; box-sizing: border-box; height: 45px; background-color: #ffffff; color: #0f172a; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; font-family: 'Inter', sans-serif; font-size: 0.95rem; font-weight: 500; outline: none;">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 20px;">
                <button type="button" id="cancelReserveModalBtn" style="background: transparent; border: 1px solid rgba(255, 255, 255, 0.1); color: #cbd5e1; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-family: 'Inter', sans-serif;">Cancelar</button>
                <button type="submit" style="background: #3b82f6; border: none; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; font-family: 'Inter', sans-serif;">Confirmar Reserva</button>
            </div>
        </form>
    </div>
</div>

<style>
    #resDataInicio::-webkit-calendar-picker-indicator,
    #resDataFim::-webkit-calendar-picker-indicator {
        background-color: transparent;
        cursor: pointer;
        filter: invert(0);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // CONTROLO DO MODAL DE ADICIONAR ARTIGO
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
    addModal.addEventListener('click', function(e) { if (e.target === addModal) closeAddModal(); });

    // CONTROLO DO MODAL DE DETALHES
    const detailModal = document.getElementById('detailsCatalogModal');
    const closeDetailBtn = document.getElementById('closeDetailModalBtn');
    const cancelDetailBtn = document.getElementById('cancelDetailModalBtn');

    document.querySelectorAll('.js-open-details').forEach(button => {
        button.addEventListener('click', function() {
            const titulo = this.dataset.titulo;
            const autor = this.dataset.autor;
            const categoria = this.dataset.categoria;
            const estado = this.dataset.estado;
            const descricao = this.dataset.descricao;
            const imagem = this.dataset.imagem;
            const criador = this.dataset.criador;

            document.getElementById('txtDetailTitulo').innerText = titulo;
            document.getElementById('txtDetailAutor').innerText = autor;
            document.getElementById('txtDetailCategoria').innerText = `[ ${categoria.toUpperCase()} ]`;
            document.getElementById('txtDetailDescricao').innerText = descricao;
            document.getElementById('imgDetailCapa').src = imagem;
            document.getElementById('txtDetailCriador').innerText = criador;

            const badgeEstado = document.getElementById('txtDetailEstado');
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
    detailModal.addEventListener('click', function(e) { if (e.target === detailModal) closeDetailModal(); });

    // CONTROLO DO POP-UP DE RESERVA
    const reserveModal = document.getElementById('reserveCatalogModal');
    const closeReserveBtn = document.getElementById('closeReserveModalBtn');
    const cancelReserveBtn = document.getElementById('cancelReserveModalBtn');
    
    const inputInicio = document.getElementById('resDataInicio');
    const inputFim = document.getElementById('resDataFim');

    const hoje = new Date().toISOString().split('T')[0];

    document.querySelectorAll('.js-open-reserve').forEach(element => {
        element.addEventListener('click', function(e) {
            e.preventDefault(); 
            
            const id = this.dataset.id;
            const titulo = this.dataset.titulo;

            document.getElementById('formReserveItemId').value = id;
            document.getElementById('txtReserveTitulo').innerText = 'Reservar: ' + titulo;

            inputInicio.min = hoje;
            inputInicio.value = hoje; 

            inputFim.min = hoje;
            inputFim.value = hoje;

            reserveModal.style.display = 'flex';
        });
    });

    inputInicio.addEventListener('change', function() {
        const dataSelecionada = this.value;
        inputFim.min = dataSelecionada;
        
        if (inputFim.value < dataSelecionada) {
            inputFim.value = dataSelecionada;
        }
    });

    const closeReserveModal = () => { reserveModal.style.display = 'none'; };

    if (closeReserveBtn) closeReserveBtn.addEventListener('click', closeReserveModal);
    if (cancelReserveBtn) cancelReserveBtn.addEventListener('click', closeReserveModal);
    reserveModal.addEventListener('click', function(e) { if (e.target === reserveModal) closeReserveModal(); });

    // TOAST
    const toast = document.getElementById('toastAlert');
    if (toast) {
        setTimeout(() => { toast.classList.add('show'); }, 200);
        setTimeout(() => { toast.classList.remove('show'); }, 4000);
    }
});
</script>

</body>
</html>