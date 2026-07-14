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

// CAPTURAR TERMO DE PESQUISA (Para a barra de filtros de todos os utilizadores)
$pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';

// 1. Consulta Atualizada com suporte a Filtro/Pesquisa
$query = "SELECT livros.*, cdu_classes.descricao as cdu_nome, reservas.utilizador_id as quem_reservou,
                 GROUP_CONCAT(autores.nome SEPARATOR ', ') as autor_artista
          FROM livros 
          LEFT JOIN cdu_classes ON livros.cdu_codigo = cdu_classes.codigo
          LEFT JOIN livro_autores ON livros.id = livro_autores.livro_id
          LEFT JOIN autores ON livro_autores.autor_id = autores.id
          LEFT JOIN reservas ON livros.id = reservas.livro_id AND reservas.status = 'pendente'";

// Se houver pesquisa, adicionamos as condições adequadas
if (!empty($pesquisa)) {
    // Usamos HAVING para conseguir pesquisar também pelo resultado gerado no GROUP_CONCAT (autor_artista)
    $query .= " GROUP BY livros.id 
                HAVING livros.titulo LIKE :pesquisa 
                OR autor_artista LIKE :pesquisa 
                OR livros.isbn LIKE :pesquisa
                ORDER BY livros.id DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['pesquisa' => "%$pesquisa%"]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Sem pesquisa ativa, roda a listagem padrão
    $query .= " GROUP BY livros.id ORDER BY livros.id DESC";
    $itens = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
}

// 2. Procurar as classes CDU na Base de Dados para preencher o Select do Pop-up
$cdu_classes = [];
try {
    $stmt_cdu = $pdo->query("SELECT codigo, descricao FROM cdu_classes ORDER BY codigo ASC");
    $cdu_classes = $stmt_cdu->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Falha silenciosa
}

// 3. Procurar os autores na Base de Dados para preencher o Select do Pop-up
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
    <title>LivrisCore - Gestão de Biblioteca</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Styles/StylesIndex.css">
    <link rel="stylesheet" href="Styles/StylesIndex2.css">
    <link rel="stylesheet" href="Styles/StyleNav.css">
</head>
<body>

<style>
    .grid-itens {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)) !important;
        gap: 25px !important;
        width: 100% !important;
        padding: 0 !important;
        margin-top: 30px !important;
    }

    .card {
        background: #ffffff !important;
        border-radius: 12px !important;
        border: 1px solid #e2e8f0 !important;
        display: flex !important;
        flex-direction: column !important;
        width: 100% !important; /* Força a largura do item a preencher a célula */
        box-sizing: border-box !important;


    }/* Container que agrupa os botões (deve ser flex) */
.card-footer {
    display: flex;
    gap: 10px; /* Espaço entre os botões */
    margin-top: 15px;
}

/* Estilo comum para ambos os botões */
.btn-action, .btn-details {
    flex: 1; /* Faz com que ambos ocupem o mesmo espaço */
    padding: 10px 0;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    text-align: center;
    border: none;
    transition: background 0.2s;
}

/* Estilo específico do botão Detalhes */
.btn-details {
    background: #e2e8f0 !important; /* Cor de fundo cinza claro para destaque */
    color: #475569 !important;      /* Cor de texto legível */
}

.btn-details:hover {
    background: #cbd5e1 !important;
}

/* Estilo específico do botão Reservar */
.btn-action {
    background: #3b82f6 !important;
    color: white !important;
}

.btn-action:hover {
    background: #2563eb !important;
}
    
</style>
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
                Pesquise, reserve e acompanhe todo o catlogo de livros em tempo real.
            </p>
        </div>
    </div>
</header>


<main style="background: #f8fafc; width: 100%; min-height: 100vh; padding: 40px 0; margin: 0; box-sizing: border-box;">
    
    <div class="catalog-container" style="max-width: 1280px; margin: 0 auto; padding: 0 20px; box-sizing: border-box;">
        
        <div style="margin-bottom: 20px; width: 100%;">
            <h2 class="section-title" style="margin: 0; font-family: 'Playfair Display', 'Georgia', serif; font-size: 2.2rem; color: black;">Catálogo de Livros</h2>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 35px; width: 100%; flex-wrap: wrap;">
            
            <form method="GET" action="index.php" style="display: flex; align-items: center; gap: 12px; margin: 0; flex: 1; max-width: 580px;">
                <div style="position: relative; width: 100%;">
                    <span style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #3b82f6; font-size: 1.1rem; z-index: 2; pointer-events: none;"></span>
                    
                    <input type="text" 
                           name="pesquisa" 
                           value="<?= htmlspecialchars($pesquisa) ?>" 
                           placeholder="Pesquisar por título, autor ou ISBN..." 
                           style="width: 100%; background: #1e293b; border: 2px solid #334155; color: #f8fafc; padding: 13px 16px 13px 48px; border-radius: 10px; font-size: 1rem; outline: none; font-weight: 500; transition: all 0.25s ease-in-out; box-sizing: border-box; box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);"
                           onfocus="this.style.borderColor='#3b82f6'; this.style.background='#0f172a'; this.style.boxShadow='0 0 0 4px rgba(59, 130, 246, 0.15), inset 0 2px 4px rgba(0,0,0,0.1)';" 
                           onblur="this.style.borderColor='#334155'; this.style.background='#1e293b'; this.style.boxShadow='inset 0 2px 4px rgba(0,0,0,0.2)';">
                </div>

                <button type="submit" style="background: #3b82f6; color: white; border: none; padding: 14px 28px; border-radius: 10px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: all 0.2s; white-space: nowrap; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);" onmouseover="this.style.background='#2563eb'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='#3b82f6'; this.style.transform='none;'">
                    Filtrar
                </button>
                
                <?php if (!empty($pesquisa)): ?>
                    <a href="index.php" style="color: #64748b; font-size: 0.9rem; text-decoration: none; margin-left: 5px; white-space: nowrap; font-weight: 500; transition: color 0.2s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#64748b'">Limpar Filtro</a>
                <?php endif; ?>
            </form>

            <?php if (isset($_SESSION['utilizador_tipo']) && ((int)$_SESSION['utilizador_tipo'] === 1 || $_SESSION['utilizador_tipo'] === 'admin')): ?>
                <button type="button" class="btn-add-catalog" id="openAddCatalogBtn" style="background: #3b82f6; color: white; border: none; padding: 14px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 1rem; transition: all 0.2s; white-space: nowrap; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);" onmouseover="this.style.background='#2563eb'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='#3b82f6'; this.style.transform='none;'">
                    <svg viewBox="0 0 24 24" fill="white" style="width: 18px; height: 18px;"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
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
                <div class="card-header" style="position: relative; width: 100%; height: 220px; background: rgba(0, 0, 0, 0.02); display: flex; align-items: center; justify-content: center; overflow: hidden; border-top-left-radius: 12px; border-top-right-radius: 12px;">
                    
                    <span class="status-badge <?= $item['estado'] ?>" style="position: absolute; top: 12px; right: 12px; z-index: 10; <?php 
                        if($estadoLimpo === 'reservado') {
                            echo 'background: rgba(234, 179, 8, 0.15); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.3);';
                        } ?>">
                        <?= strtoupper($item['estado']) ?>
                    </span>
                    
                    <img src="<?= $itemImagem ?>" alt="Capa de <?= htmlspecialchars($item['titulo']) ?>" style="width: 100%; height: 100%; object-fit: contain; padding: 15px; filter: drop-shadow(0px 8px 16px rgba(0, 0, 0, 0.15));">
                </div>

                <div class="card-body">
                    <small class="category-label">CDU <?= htmlspecialchars($item['cdu_codigo']) ?></small>
                   <h3 style="color: #1e293b !important; font-size: 1.1rem; margin: 8px 0;">
    <?= htmlspecialchars($item['titulo']) ?>
</h3>
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
                                <form action="Processos/cancela_reserva.php" method="POST" style="margin:0; display:inline;">
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
    </div>
</main>

<footer>
    <p>&copy; 2026 LivrisCore - Sistema de Gestão de Biblioteca</p>
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
<?php include 'modal_adicionar_livro.php'; ?>

<?php require 'index_reservas.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================================
    // 1. CONTROLO DO MODAL DE ADICIONAR LIVRO
    // ==========================================================
    const addModal = document.getElementById('addCatalogModal');
    const openBtn = document.getElementById('openAddCatalogBtn');
    const closeBtn = document.getElementById('closeAddModalBtn');
    const cancelBtn = document.getElementById('cancelAddModalBtn');

    if (openBtn && addModal) {
        openBtn.addEventListener('click', function() {
            addModal.style.display = 'flex';
        });
    }

    const closeAddModal = () => { 
        if (addModal) {
            addModal.style.display = 'none';
            // Reset do preview de imagem local ao fechar
            const wrapperPreview = document.getElementById('wrapper-preview-capa');
            const previewImg = document.getElementById('preview-capa-livro');
            const inputImg = document.getElementById('input-imagem-capa');
            if (wrapperPreview) wrapperPreview.style.display = 'none';
            if (previewImg) previewImg.src = '';
            if (inputImg) inputImg.value = '';
        }
    };

    if (closeBtn) closeBtn.addEventListener('click', closeAddModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeAddModal);
    if (addModal) addModal.addEventListener('click', function(e) { if (e.target === addModal) closeAddModal(); });

    // ==========================================================
    // 2. PRÉ-VISUALIZAÇÃO DA CAPA DO LIVRO EM TEMPO REAL
    // ==========================================================
    const inputImagemCapa = document.getElementById('input-imagem-capa');
    if (inputImagemCapa) {
        inputImagemCapa.addEventListener('change', function() {
            const previewImg = document.getElementById('preview-capa-livro');
            const wrapperPreview = document.getElementById('wrapper-preview-capa');

            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    if (previewImg) previewImg.src = event.target.result;
                    if (wrapperPreview) wrapperPreview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            } else {
                if (previewImg) previewImg.src = '';
                if (wrapperPreview) wrapperPreview.style.display = 'none';
            }
        });
    }

    // ==========================================================
    // 3. LÓGICA DINÂMICA DE MÚLTIPLOS AUTORES SEM REPETIÇÃO
    // ==========================================================
    const containerAutores = document.getElementById('container-autores');
    const btnAddAutor = document.getElementById('btn-add-autor-row');

    function atualizarAutoresDisponiveis() {
        const todosSelects = document.querySelectorAll('.select-autor-dinamico');
        const valoresSelecionados = Array.from(todosSelects).map(s => s.value).filter(val => val !== "");

        todosSelects.forEach(selectAtual => {
            const opcoes = selectAtual.querySelectorAll('option');
            opcoes.forEach(opcao => {
                if (opcao.value !== "") {
                    if (valoresSelecionados.includes(opcao.value) && selectAtual.value !== opcao.value) {
                        opcao.disabled = true;
                        opcao.style.display = 'none';
                    } else {
                        opcao.disabled = false;
                        opcao.style.display = 'block';
                    }
                }
            });
        });
    }

    if (containerAutores) {
        containerAutores.addEventListener('change', function(e) {
            if (e.target.classList.contains('select-autor-dinamico')) {
                atualizarAutoresDisponiveis();
            }
        });
    }

    if (btnAddAutor) {
        btnAddAutor.addEventListener('click', function() {
            const todosSelects = document.querySelectorAll('.select-autor-dinamico');
            if (todosSelects.length > 0 && todosSelects[todosSelects.length - 1].value === "") {
                alert("Por favor, selecione o autor na linha anterior antes de adicionar um novo.");
                return;
            }

            const primeiroSelect = document.querySelector('.select-autor-dinamico');
            if (!primeiroSelect) return;

            const novaLinha = document.createElement('div');
            novaLinha.style.cssText = "display: flex; gap: 6px; align-items: center; margin-top: 8px;";

            const novoSelect = primeiroSelect.cloneNode(true);
            novoSelect.value = ""; 
            novoSelect.required = true;

            const btnRemover = document.createElement('button');
            btnRemover.type = 'button';
            btnRemover.style.cssText = "height: 45px; width: 45px; background: #ef4444; border: none; border-radius: 6px; color: white; font-size: 1.2rem; cursor: pointer;";
            btnRemover.innerText = '✕';

            btnRemover.addEventListener('click', function() {
                novaLinha.remove();
                atualizarAutoresDisponiveis();
            });

            novaLinha.appendChild(novoSelect);
            novaLinha.appendChild(btnRemover);
            containerAutores.appendChild(novaLinha);

            atualizarAutoresDisponiveis();
        });
    }

    // ==========================================================
    // 4. CONTROLO DO MODAL DE DETALHES (COM FIX PARA A CAPA)
    // ==========================================================
    const detailModal = document.getElementById('detailsCatalogModal');
    const closeDetailBtn = document.getElementById('closeDetailModalBtn');
    const cancelDetailBtn = document.getElementById('cancelDetailModalBtn');

    document.querySelectorAll('.js-open-details').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('txtDetailTitulo').innerText = this.dataset.titulo || '-';
            document.getElementById('txtDetailAutor').innerText = this.dataset.autor || '-';
            document.getElementById('txtDetailCdu').innerText = this.dataset.cdu ? `[ ${this.dataset.cdu.toUpperCase()} ]` : '[ CDU ]';
            document.getElementById('txtDetailIsbn').innerText = this.dataset.isbn || '-';
            document.getElementById('txtDetailEditoraAno').innerText = `${this.dataset.editora || '-'} (${this.dataset.ano || '-'})`;
            document.getElementById('txtDetailDescricao').innerText = this.dataset.descricao || 'Sem descrição disponível.';
            document.getElementById('txtDetailCriador').innerText = this.dataset.criador || 'Sistema';

            // --- TRATAMENTO INTELIGENTE DA IMAGEM DA CAPA ---
            const imgComponent = document.getElementById('imgDetailCapa');
            if (imgComponent) {
                if (this.dataset.imagem && this.dataset.imagem.trim() !== "") {
                    const caminhoImg = this.dataset.imagem.trim();
                    
                    // Se a string já vier com "Uploads/", aplica diretamente, senão concatena
                    if (caminhoImg.startsWith('Uploads/')) {
                        imgComponent.src = caminhoImg;
                    } else {
                        imgComponent.src = 'Uploads/' + caminhoImg;
                    }
                } else {
                    // Imagem padrão caso o campo esteja vazio na BD
                    imgComponent.src = 'Images/default-cover.png';
                }
            }
            // ------------------------------------------------

            const badgeEstado = document.getElementById('txtDetailEstado');
            if (badgeEstado) {
                const estado = this.dataset.estado ? this.dataset.estado.toLowerCase() : '';
                badgeEstado.innerText = this.dataset.estado || '-';
                
                if (estado === 'disponivel' || estado === 'disponível') {
                    badgeEstado.style.background = 'rgba(16, 185, 129, 0.1)';
                    badgeEstado.style.color = '#10b981';
                } else if (estado === 'reservado') {
                    badgeEstado.style.background = 'rgba(234, 179, 8, 0.15)';
                    badgeEstado.style.color = '#eab308';
                } else {
                    badgeEstado.style.background = 'rgba(239, 68, 68, 0.1)';
                    badgeEstado.style.color = '#ef4444';
                }
            }

            if (detailModal) detailModal.style.display = 'flex';
        });
    });

    const closeDetailModal = () => { if (detailModal) detailModal.style.display = 'none'; };
    if (closeDetailBtn) closeDetailBtn.addEventListener('click', closeDetailModal);
    if (cancelDetailBtn) cancelDetailBtn.addEventListener('click', closeDetailModal);
    if (detailModal) detailModal.addEventListener('click', function(e) { if (e.target === detailModal) closeDetailModal(); });

    // ==========================================================
    // 5. TOAST ALERT
    // ==========================================================
    const toast = document.getElementById('toastAlert');
    if (toast) {
        setTimeout(() => { toast.classList.add('show'); }, 200);
        setTimeout(() => { toast.classList.remove('show'); }, 4000);
    }
});
</script>

</body>
</html>