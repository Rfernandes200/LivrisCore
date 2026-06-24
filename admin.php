<?php
session_start();
require 'config.php';

// Bloqueio de Segurança
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    header("Location: index.php");
    exit();
}

$id_admin_atual = $_SESSION['utilizador_id'] ?? null; 

// ==========================================
// PROCESSAMENTO DOS FORMULÁRIOS (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CASO 1: PROCESSAR EDICAO DE LIVRO (editar_artigo.php direciona para aqui ou crias um ficheiro focado)
    if (isset($_POST['artigo_id']) && !isset($_POST['acao_admin'])) {
        $artigo_id   = (int)$_POST['artigo_id'];
        $isbn        = trim($_POST['isbn'] ?? '');
        $titulo      = trim($_POST['titulo'] ?? '');
        $cdu_codigo  = trim($_POST['cdu_codigo'] ?? '');
        $editora     = trim($_POST['editora'] ?? '');
        $ano_edicao  = (int)$_POST['ano_edicao'] ?? 0;
        $estado      = trim($_POST['estado'] ?? 'disponivel');
        $descricao   = trim($_POST['descricao'] ?? '');
        $autores_ids = $_POST['autor_id'] ?? [];

        // Validação de Duplicado de ISBN (Garante que ignora o próprio livro atual)
        if (!empty($isbn)) {
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM livros WHERE isbn = :isbn AND id != :id_atual");
            $stmt_check->execute(['isbn' => $isbn, 'id_atual' => $artigo_id]);
            
            if ((int)$stmt_check->fetchColumn() > 0) {
                echo "<script>
                        alert('Erro: Já existe OUTRO livro registado com este código ISBN!');
                        window.history.back();
                      </script>";
                exit;
            }
        }

        // Lógica de gravação do Livro e Autores na BD segue aqui...
    }

    // CASO 2: PROCESSAMENTO DE CANCELAMENTO DE RESERVA
    if (isset($_POST['acao_admin']) && $_POST['acao_admin'] === 'cancelar_reserva_admin') {
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
}

// ==========================================
// CONSULTAS DE RENDERIZAÇÃO DA PÁGINA (GET)
// ==========================================
$seccao = $_GET['seccao'] ?? 'geral';
$total_utilizadores = $pdo->query("SELECT COUNT(*) FROM utilizadores")->fetchColumn();
$total_artigos = $pdo->query("SELECT COUNT(*) FROM livros")->fetchColumn();
$total_reservas = $pdo->query("SELECT COUNT(*) FROM reservas WHERE status = 'pendente'")->fetchColumn();
$total_emprestimos_ativos = $pdo->query("SELECT COUNT(*) FROM emprestimos WHERE data_devolucao_real IS NULL")->fetchColumn();

$reservas = [];
if ($seccao === 'reservas') {
    $reservas = $pdo->query("SELECT r.*, u.nome as user_nome, l.titulo as item_titulo 
                             FROM reservas r 
                             INNER JOIN utilizadores u ON r.utilizador_id = u.id 
                             INNER JOIN livros l ON r.livro_id = l.id 
                             WHERE r.status = 'pendente' ORDER BY r.id DESC")->fetchAll(PDO::FETCH_ASSOC);
}

$todos_autores = $pdo->query("SELECT id, nome FROM autores ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$todas_categorias = $pdo->query("SELECT codigo, descricao FROM cdu_classes ORDER BY codigo + 0 ASC, codigo ASC")->fetchAll(PDO::FETCH_ASSOC);
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

<style>
    /* Estilos globais limpos para evitar fugas de background */
    body {
        margin: 0 !important;
        padding: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        overflow-x: hidden;
        background-color: #ffffff !important;
    }
    
    .admin-container {
        display: flex;
        min-height: 100vh;
        background-color: #ffffff !important;
    }

    /* O miolo agora adapta-se de forma inteligente através do CSS externo */
    .admin-content {
        margin-left: 260px;
        width: calc(100% - 260px);
        box-sizing: border-box;
        padding: 40px;
        min-height: 100vh;
        background-color: #ffffff !important;
        transition: margin-left 0.3s ease, width 0.3s ease;
    }

    /* Garante visibilidade dentro dos selects */
    #container-autores-edit select {
        background-color: #1e293b !important; /* Cor de fundo escura */
        color: white !important;
    }
    #container-autores-edit option {
        background-color: #1e293b !important;
    color: white !important;
}

    /* Se o ecrã for menor, o miolo expande-se dinamicamente */
    @media (max-width: 1024px) {
        .admin-content {
            margin-left: 72px;
            width: calc(100% - 72px);
            padding: 20px;
        }
    }
</style>

<body>

    <div class="admin-container">

        <?php require 'sidebar.php'; ?>

        <main class="admin-content">
            <?php if (isset($_SESSION['alerta'])): ?>
                <div style="padding: 15px; margin-bottom: 20px; border-radius: 8px; font-size: 0.9rem; font-weight: 500; 
                    <?= $_SESSION['alerta']['tipo'] === 'sucesso' ? 'background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.2);' : 'background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.2);' ?>">
                    <?= $_SESSION['alerta']['mensagem']; ?>
                </div>
                <?php unset($_SESSION['alerta']); ?>
            <?php endif; ?>

            <?php if ($seccao === 'geral'): ?>
                <?php include 'seccao_geral.php'; ?>

            <?php elseif ($seccao === 'utilizadores'): ?>
               <?php include 'seccao_utilizadores.php'; ?>

            <?php elseif ($seccao === 'reservas'): ?>
                <?php include 'seccao_reservas.php'; ?>

            <?php elseif ($seccao === 'emprestimos'): ?>
                <?php include 'seccao_emprestimos.php'; ?>

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
                <h2>Editar Perfil do Utilizador</h2>
                <button class="btn-close-modal" onclick="fecharModalEditar()">✕</button>
            </div>
            <form id="formEditarUtilizador" action="Processos/editar_utilizadores.php" method="POST">
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
                        <option value="1"> Ativo</option>
                        <option value="0"> Inativo</option>
                    </select>
                </div>
                <p id="aviso_self_edit" style="color: #eab308; font-size: 0.75rem; display: none; margin-top: 10px; background: rgba(234,179,8,0.1); padding: 8px; border-radius: 4px;">
                    Nota: Por segurança, não pode alterar o seu próprio cargo nem desativar a sua conta atual.
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
                <h2> Criar Novo Utilizador</h2>
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

    <!-- 3. MODAL: ADICIONAR ARTIGO -->^
<div id="modalAdicionarArtigo" class="modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11, 15, 25, 0.95); backdrop-filter: blur(8px); z-index: 99999; display: none; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
    
    <div style="background: #0b0f19; border: 1px solid rgba(255, 255, 255, 0.08); width: 100%; max-width: 600px; max-height: 90vh; border-radius: 12px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7); display: flex; flex-direction: column; overflow: hidden; font-family: 'Inter', sans-serif;">
        
        <div style="padding: 20px 28px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); display: flex; justify-content: space-between; align-items: center; background: rgba(30, 41, 59, 0.2); flex-shrink: 0;">
            <div>
                <span style="font-size: 0.7rem; color: #3b82f6; letter-spacing: 0.15em; font-weight: 700; display: block; margin-bottom: 4px; text-align: left;">[ ADICIONAR LIVRO ]</span>
                <h2 style="font-size: 1.3rem; color: white; font-weight: 600; margin: 0; text-align: left;">Novo Livro no Catálogo</h2>
            </div>
            <button type="button" onclick="fecharModalAdicionarArtigo()" style="background: transparent; border: none; color: #64748b; font-size: 1.8rem; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <form action="processa_artigo.php" method="POST" enctype="multipart/form-data" style="margin: 0; padding: 28px; overflow-y: auto; flex-grow: 1; display: flex; flex-direction: column; gap: 20px; box-sizing: border-box;">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">TÍTULO DO LIVRO *</label>
                    <input type="text" name="titulo" class="modal-field" placeholder="Ex: Os Maias" required style="width: 100%; height: 45px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 0 14px; box-sizing: border-box; font-family: inherit;">
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">AUTOR(ES) DO LIVRO *</label>
                    <div id="container-autores" style="display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <select name="autor_id[]" class="modal-field select-autor-dinamico" required style="height: 45px; flex-grow: 1; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 0 14px; box-sizing: border-box; font-family: inherit;">
                                <option value="" disabled selected style="background:#0b0f19;">Selecione um Autor...</option>
                                <?php foreach($todos_autores as $autor): ?>
                                    <option value="<?= $autor['id']; ?>" style="background:#0b0f19; color:white;">
                                        <?= htmlspecialchars($autor['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" id="btn-add-autor-row" style="height: 45px; width: 45px; min-width: 45px; background: #10b981; border: none; border-radius: 6px; color: white; font-size: 1.3rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center;">+</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">CÓDIGO ISBN *</label>
                    <input type="text" name="isbn" class="modal-field" placeholder="Ex: 978-972-0-04671-0" required style="width: 100%; height: 45px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 0 14px; box-sizing: border-box; font-family: inherit;">
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">CLASSIFICAÇÃO CDU *</label>
                    <select name="cdu_codigo" class="modal-field" required style="height: 45px; width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 0 14px; box-sizing: border-box; font-family: inherit;">
                        <option value="" disabled selected style="background:#0b0f19;">Selecione a Classe CDU...</option>
                        <?php foreach($todas_categorias as $classe): ?>
                            <option value="<?= htmlspecialchars($classe['codigo']); ?>" style="background:#0b0f19; color:white;">
                                <?= htmlspecialchars($classe['codigo'] . ' - ' . $classe['descricao']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">EDITORA *</label>
                    <input type="text" name="editora" class="modal-field" placeholder="Ex: Porto Editora" required style="width: 100%; height: 45px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 0 14px; box-sizing: border-box; font-family: inherit;">
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">ANO DE EDIÇÃO *</label>
                    <input type="number" name="ano_edicao" class="modal-field" placeholder="Ex: 2026" min="1000" max="2026" required style="width: 100%; height: 45px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 0 14px; box-sizing: border-box; font-family: inherit;">
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">ESTADO INICIAL *</label>
                    <select name="estado" class="modal-field" required style="height: 45px; width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 0 14px; box-sizing: border-box; font-family: inherit;">
                        <option value="disponivel" selected style="background:#0b0f19;">Disponível</option>
                        <option value="reservado" style="background:#0b0f19;">Reservado</option>
                        <option value="indisponivel" style="background:#0b0f19;">Indisponível</option>
                    </select>
                </div>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">SINOPSE / RESUMO *</label>
                <textarea name="descricao" rows="4" class="modal-field" placeholder="Escreva uma breve sinopse do livro..." required style="resize: vertical; width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 12px 14px; box-sizing: border-box; font-family: inherit;"></textarea>
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">IMAGEM DE CAPA (OBRIGATÓRIO) *</label>
                <input type="file" name="imagem" accept="image/*" required class="modal-field" style="width: 100%; height: 45px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: #64748b; padding: 10px 14px; box-sizing: border-box; font-family: inherit;">
                <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display:block;">Apenas ficheiros de imagem válidos (JPG, PNG, WEBP).</small>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px; border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 20px; flex-shrink: 0;">
                <button type="button" onclick="fecharModalAdicionarArtigo()" style="background: transparent; border: 1px solid rgba(255, 255, 255, 0.1); color: #cbd5e1; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 0.9rem;">Cancelar Criação</button>
                <button type="submit" style="background: #3b82f6; border: none; color: white; padding: 10px 22px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: 600;">Confirmar Criação</button>
            </div>
        </form>
    </div>
</div>


    <!-- 4. MODAL: EDITAR ARTIGO -->

    <!-- 4. MODAL: EDITAR ARTIGO -->

<div id="modalEditarArtigo" class="modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11, 15, 25, 0.95); backdrop-filter: blur(8px); z-index: 99999; display: none; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
    
    <div style="background: #0b0f19; border: 1px solid rgba(255, 255, 255, 0.08); width: 100%; max-width: 600px; max-height: 90vh; border-radius: 12px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7); display: flex; flex-direction: column; overflow: hidden; font-family: 'Inter', sans-serif;">
        
        <div style="padding: 20px 28px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); display: flex; justify-content: space-between; align-items: center; background: rgba(30, 41, 59, 0.2); flex-shrink: 0;">
            <div>
                <span style="font-size: 0.7rem; color: #3b82f6; letter-spacing: 0.15em; font-weight: 700; display: block; margin-bottom: 4px; text-align: left;">[ MODIFICAR ARTIGO ]</span>
                <h2 style="font-size: 1.3rem; color: white; font-weight: 600; margin: 0; text-align: left;">Editar Detalhes do Livro</h2>
            </div>
            <button type="button" onclick="fecharModalEditarArtigo()" style="background: transparent; border: none; color: #64748b; font-size: 1.8rem; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <form action="Processos/editar_artigo.php" method="POST" enctype="multipart/form-data" style="margin: 0; padding: 28px; overflow-y: auto; flex-grow: 1; display: flex; flex-direction: column; gap: 20px; box-sizing: border-box;">
            
            <input type="hidden" id="edit_artigo_id" name="artigo_id">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">TÍTULO DO LIVRO *</label>
                    <input type="text" id="edit_titulo" name="titulo" required style="width: 100%; height: 45px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 0 14px; box-sizing: border-box; font-family: inherit;">
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">AUTOR(ES) DO LIVRO *</label>
                    <div id="container-autores-edit" style="display: flex; flex-direction: column; gap: 8px;"></div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">CÓDIGO ISBN *</label>
                    <input type="text" id="edit_isbn" name="isbn" required style="width: 100%; height: 45px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 0 14px; box-sizing: border-box; font-family: inherit;">
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">CLASSIFICAÇÃO CDU *</label>
                    <select id="edit_cdu_codigo" name="cdu_codigo" required style="height: 45px; width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 0 14px; box-sizing: border-box; font-family: inherit;">
                        <option value="" disabled selected style="background:#0b0f19;">Selecione a Classe CDU...</option>
                        <?php foreach ($todas_categorias as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['codigo']); ?>" style="background:#0b0f19; color:white;"><?= htmlspecialchars($cat['codigo'] . ' - ' . $cat['descricao']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">EDITORA *</label>
                    <input type="text" id="edit_editora" name="editora" required style="width: 100%; height: 45px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 0 14px; box-sizing: border-box; font-family: inherit;">
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">ANO DE EDIÇÃO *</label>
                    <input type="number" id="edit_ano" name="ano_edicao" min="1000" max="2026" required style="width: 100%; height: 45px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 0 14px; box-sizing: border-box; font-family: inherit;">
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">ESTADO ACTUAL *</label>
                    <select id="edit_estado" name="estado" required style="height: 45px; width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 0 14px; box-sizing: border-box; font-family: inherit;">
                        <option value="disponivel" style="background:#0b0f19;">Disponível</option>
                        <option value="reservado" style="background:#0b0f19;">Reservado</option>
                        <option value="indisponivel" style="background:#0b0f19;">Indisponível</option>
                    </select>
                </div>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">SINOPSE / RESUMO *</label>
                <textarea id="edit_descricao" name="descricao" rows="4" required style="resize: vertical; width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 12px 14px; box-sizing: border-box; font-family: inherit;"></textarea>
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">SUBSTITUIR CAPA (OPCIONAL)</label>
                <input type="file" name="imagem" accept="image/*" style="width: 100%; height: 45px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: #64748b; padding: 10px 14px; box-sizing: border-box; font-family: inherit;">
                <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display:block;">Deixe vazio para manter a imagem atual.</small>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px; border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 20px; flex-shrink: 0;">
                <button type="button" onclick="fecharModalEditarArtigo()" style="background: transparent; border: 1px solid rgba(255, 255, 255, 0.1); color: #cbd5e1; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 0.9rem;">Cancelar Edição</button>
                <button type="submit" style="background: #3b82f6; border: none; color: white; padding: 10px 22px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: 600;">Atualizar Artigo</button>
            </div>
        </form>
    </div>
</div>

<select id="template-select-autor" style="display: none;">
    <option value="" disabled selected>Selecione um Autor...</option>
    <?php foreach($todos_autores as $autor): ?>
        <option value="<?= $autor['id']; ?>"><?= htmlspecialchars($autor['nome']); ?></option>
    <?php endforeach; ?>
</select>
    <!-- ==================================================================
         SCRIPTS JAVASCRIPT GERAIS DO PAINEL
         ================================================================== -->
<script>

// 1. FUNÇÃO MESTRE
function alternarModal(id, mostrar = true) {
    const modal = document.getElementById(id);
    if (!modal) return;
    if (mostrar) {
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('show'), 10);
    } else {
        modal.classList.remove('show');
        setTimeout(() => modal.style.display = 'none', 300);
    }
}

// 2. FUNÇÕES DE UTILIZADORES
function abrirModalEditarUtilizador(btn) {
    document.getElementById('modal_id').value        = btn.getAttribute('data-id');
    document.getElementById('modal_nome').value      = btn.getAttribute('data-nome');
    document.getElementById('modal_email').value     = btn.getAttribute('data-email');
    document.getElementById('modal_telemovel').value = btn.getAttribute('data-telemovel');
    document.getElementById('modal_tipo').value      = btn.getAttribute('data-tipo');
    document.getElementById('modal_ativo').value     = btn.getAttribute('data-ativo');
    
    // check if editing own admin account
    const isSelf = btn.getAttribute('data-self') === 'true';
    const avisoSelf = document.getElementById('aviso_self_edit');
    const selectTipo = document.getElementById('modal_tipo');
    const selectAtivo = document.getElementById('modal_ativo');
    if (isSelf) {
        if (avisoSelf) avisoSelf.style.display = 'block';
        if (selectTipo) selectTipo.disabled = true;
        if (selectAtivo) selectAtivo.disabled = true;
    } else {
        if (avisoSelf) avisoSelf.style.display = 'none';
        if (selectTipo) selectTipo.disabled = false;
        if (selectAtivo) selectAtivo.disabled = false;
    }

    alternarModal('modalEditarUtilizador', true);
}

function fecharModalEditar() { alternarModal('modalEditarUtilizador', false); }
function abrirModalAdicionarUtilizador() { alternarModal('modalAdicionarUtilizador', true); }
function fecharModalAdicionarUtilizador() { alternarModal('modalAdicionarUtilizador', false); }

// 3. FUNÇÕES DE ARTIGOS
function abrirModalAdicionarArtigo() { alternarModal('modalAdicionarArtigo', true); }
function fecharModalAdicionarArtigo() { alternarModal('modalAdicionarArtigo', false); }

function abrirModalEditarArtigo(btn) {
    const id = btn.getAttribute('data-id');
    
    fetch(`Processos/editar_artigo.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.erro) {
                alert('Erro ao carregar dados: ' + data.erro);
                return;
            }
            // Preencher os campos do formulário
            document.getElementById('edit_artigo_id').value = data.id;
            document.getElementById('edit_titulo').value = data.titulo;
            document.getElementById('edit_isbn').value = data.isbn;
            document.getElementById('edit_editora').value = data.editora;
            document.getElementById('edit_ano').value = data.ano_edicao;
            document.getElementById('edit_cdu_codigo').value = data.cdu_codigo;
            document.getElementById('edit_estado').value = data.estado;
            document.getElementById('edit_descricao').value = data.descricao;

            // Preencher autores
            const container = document.getElementById('container-autores-edit');
            const template = document.getElementById('template-select-autor');
            container.innerHTML = '';
            
            const autoresIds = data.autores || [];
            if (autoresIds.length === 0) {
                criarLinhaAutorGenerica(container, template, '', true, 'select-autor-dinamico-edit', 'btn-add-autor-row-edit');
            } else {
                autoresIds.forEach((autorId, index) => {
                    criarLinhaAutorGenerica(container, template, autorId, index === 0, 'select-autor-dinamico-edit', 'btn-add-autor-row-edit');
                });
            }

            alternarModal('modalEditarArtigo', true);
        })
        .catch(err => {
            console.error('Erro ao buscar dados do livro:', err);
            alert('Erro ao carregar os dados do livro da base de dados.');
        });
}

function fecharModalEditarArtigo() { alternarModal('modalEditarArtigo', false); }

// 4. FUNÇÃO AUXILIAR DE AUTORES
function criarLinhaAutorGenerica(container, template, valorId, ePrimeiraLinha, classeSelect, idBotaoMais) {
    const newRow = document.createElement('div');
    newRow.style.cssText = "display: flex; gap: 6px; align-items: center; margin-bottom: 8px;";
    
    const select = template.cloneNode(true);
    select.style.display = 'block';
    select.style.flexGrow = '1';
    select.name = 'autor_id[]';
    select.classList.add(classeSelect);
    select.required = true;
    select.style.height = "45px";
    select.style.background = "rgba(255,255,255,0.03)";
    select.style.border = "1px solid rgba(255,255,255,0.08)";
    select.style.borderRadius = "6px";
    select.style.color = "white";
    select.style.padding = "0 14px";
    select.style.boxSizing = "border-box";
    select.style.fontFamily = "inherit";

    if (valorId) select.value = valorId;

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.style.cssText = "height: 45px; width: 45px; min-width: 45px; border: none; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.3rem; font-weight: 600;";
    btn.innerHTML = ePrimeiraLinha ? '+' : '&times;';
    btn.style.background = ePrimeiraLinha ? '#10b981' : '#ef4444';
    if (!ePrimeiraLinha) btn.onclick = () => newRow.remove();
    else btn.id = idBotaoMais;

    newRow.appendChild(select);
    newRow.appendChild(btn);
    container.appendChild(newRow);
}

// 5. EVENTOS GLOBAIS
document.addEventListener('click', (e) => {
    if (e.target.id === 'btn-add-autor-row-edit') {
        const container = document.getElementById('container-autores-edit');
        const template = document.getElementById('template-select-autor');
        criarLinhaAutorGenerica(container, template, '', false, 'select-autor-dinamico-edit', '');
    } else if (e.target.id === 'btn-add-autor-row') {
        const container = document.getElementById('container-autores');
        const template = document.getElementById('template-select-autor');
        criarLinhaAutorGenerica(container, template, '', false, 'select-autor-dinamico', '');
    }
});
</script>
</body>
</html>