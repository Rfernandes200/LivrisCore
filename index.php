<?php 
session_start();
require 'config.php'; 

// 1. Consulta para buscar todos os itens e o nome da categoria para o catálogo geral
$query = "SELECT itens.*, categorias.nome as cat_nome 
          FROM itens 
          LEFT JOIN categorias ON itens.categoria_id = categorias.id";
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

// 3. Filtrar dinamicamente apenas os itens que PODEM SER RESERVADOS (estado = disponivel)
$itens_para_reservar = array_filter($itens, function($item) {
    return $item['estado'] === 'disponivel';
});
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
            
            <form action="pesquisa.php" method="GET" class="search-group">
                <input type="text" name="q" class="search-bar" placeholder="Pesquisar por título, autor...">
                <button type="submit" class="btn-search">Pesquisar</button>
            </form>
        </div>
    </div>
</header>

<section class="reservas-container">
    <h3 style="color: white; font-family: 'Inter', sans-serif; font-size: 1.1rem; font-weight: 600; text-align: left; margin-bottom: 5px;">
        ⚡ Disponível Para Reservar Já
    </h3>
    <p style="color: #64748b; font-size: 0.85rem; text-align: left; margin: 0 0 15px 0;">Clique diretamente no cubo para gerir ou criar a reserva.</p>

    <?php if (!empty($itens_para_reservar)): ?>
        <div class="reservas-grid">
            <?php foreach ($itens_para_reservar as $item_res): 
                $fotoCapa = !empty($item_res['imagem']) ? 'Uploads/'.$item_res['imagem'] : 'Images/default-cover.png';
            ?>
                <a href="reservar.php?id=<?= $item_res['id'] ?>" class="reserva-cube">
                    <img src="<?= $fotoCapa ?>" alt="Capa" class="reserva-img">
                    <div class="reserva-info">
                        <h4><?= htmlspecialchars($item_res['titulo']) ?></h4>
                        <p><?= htmlspecialchars($item_res['autor_artista']) ?></p>
                        <span style="font-size: 0.7rem; color: #3b82f6; background: rgba(59, 130, 246, 0.1); padding: 4px 8px; border-radius: 4px; width: fit-content; font-weight: 600; letter-spacing: 0.05em;">
                            RESERVAR ➜
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-reservas">
            ❌ De momento, não existem reservas disponíveis. Todos os artigos encontram-se indisponíveis.
        </div>
    <?php endif; ?>
</section>

<hr style="max-width: 1200px; margin: 40px auto; border: 0; border-top: 1px solid rgba(255,255,255,0.05);">

<main class="catalog-container" style="margin-top: 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding: 0 10px;">
        <h2 class="section-title" style="margin: 0;">Catálogo</h2>
        
        <?php if (isset($_SESSION['utilizador_tipo']) && ((int)$_SESSION['utilizador_tipo'] === 1 || $_SESSION['utilizador_tipo'] === 'admin')): ?>
            <button type="button" class="btn-add-catalog" id="openAddCatalogBtn">
                <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                Adicionar Artigo
            </button>
        <?php endif; ?>
    </div>
    
    <div class="grid-itens">
        <?php foreach($itens as $item): ?>
        <div class="card">
            <div class="card-header">
                <span class="status-badge <?= $item['estado'] ?>">
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
                    <a href="detalhes.php?id=<?= $item['id'] ?>" class="btn-details">Detalhes</a>
                    <?php if($item['estado'] == 'disponivel'): ?>
                        <a href="reservar.php?id=<?= $item['id'] ?>" class="btn-action">Reservar</a>
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

<div id="addCatalogModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11, 15, 25, 0.95); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 99999; display: none; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
    <div style="background: #0b0f19; border: 1px solid rgba(255, 255, 255, 0.08); width: 100%; max-width: 600px; border-radius: 12px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7); overflow: hidden; font-family: 'Inter', sans-serif;">
        
        <div style="padding: 24px 28px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); display: flex; justify-content: space-between; align-items: center; background: rgba(30, 41, 59, 0.2);">
            <div>
                <span style="font-size: 0.7rem; color: #3b82f6; letter-spacing: 0.15em; font-weight: 700; display: block; margin-bottom: 4px; text-align: left;">[ ACERVO DIGITAL ]</span>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Controlo de abertura/fecho do Modal
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

    // Animação e Controlo do Alerta/Toast de Confirmação
    const toast = document.getElementById('toastAlert');
    if (toast) {
        setTimeout(() => { toast.classList.add('show'); }, 200);
        setTimeout(() => { toast.classList.remove('show'); }, 4000);
    }
});
</script>

</body>
</html>