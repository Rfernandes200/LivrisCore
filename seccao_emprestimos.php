<?php
// Bloqueio de Segurança direto no ficheiro incluído
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    exit('Acesso negado');
}

// ==========================================
// LÓGICA DE PROCESSAMENTO E CONSULTA SQL
// ==========================================
$emprestimos = [];

$pesquisa_reserva = $_GET['q_reserva'] ?? '';
$filtro_estado_reserva = $_GET['estado_reserva'] ?? '';

try {
    // Consulta principal baseada no teu diagrama e no JOIN do catálogo
    $sql_emprestimos = "SELECT emprestimos.*, 
                               livros.titulo AS livro_titulo, 
                               livros.isbn AS livro_isbn,
                               livros.imagem_url AS livro_capa,
                               utilizadores.nome AS user_nome, 
                               utilizadores.email AS user_email
                        FROM emprestimos
                        JOIN livros ON emprestimos.livro_id = livros.id
                        JOIN utilizadores ON emprestimos.utilizador_id = utilizadores.id
                        WHERE 1=1";
    
    $params = [];

    if (!empty($pesquisa_reserva)) {
        $sql_emprestimos .= " AND (livros.titulo LIKE :q OR utilizadores.nome LIKE :q OR utilizadores.email LIKE :q)";
        $params['q'] = "%$pesquisa_reserva%";
    }

    $sql_emprestimos .= " ORDER BY emprestimos.data_saida DESC";
    
    $stmt_e = $pdo->prepare($sql_emprestimos);
    $stmt_e->execute($params);
    $all_records = $stmt_e->fetchAll(PDO::FETCH_ASSOC);

    // Filtragem lógica de estados simulados (Ativo / Atrasado / Devolvido)
    $hoje = date('Y-m-d');
    foreach ($all_records as $row) {
        $entregue = !empty($row['data_devolucao_real']);
        
        if (!$entregue && $hoje > $row['data_prevista_devolucao']) {
            $statusReal = 'atrasado';
        } elseif (!$entregue) {
            $statusReal = 'ativo';
        } else {
            $statusReal = 'devolvido';
        }

        if (empty($filtro_estado_reserva) || $filtro_estado_reserva === $statusReal) {
            $row['estado_calculado'] = $statusReal;
            $emprestimos[] = $row;
        }
    }

} catch (PDOException $e) {
    echo "<div style='color: #ef4444; padding: 15px; background: rgba(239,68,68,0.1); border-radius: 8px; margin-bottom: 20px;'>Erro ao carregar registos: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<style>
    .seccao-container {
        background-color: #ffffff !important; 
        color: #1e293b !important; 
        min-height: 100vh; 
        padding: 20px; 
        font-family: 'Inter', sans-serif;
    }
    .topo-header {
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 25px;
    }
    .admin-toolbar {
        display: flex; 
        gap: 15px; 
        align-items: center; 
        justify-content: space-between; 
        margin-bottom: 25px;
    }
    .filtro-form {
        display: flex; 
        gap: 12px; 
        width: 100%; 
        max-width: 750px; 
        margin: 0;
    }
    .search-wrapper {
        position: relative; 
        flex-grow: 2; 
        margin: 0;
    }
    .input-busca {
        width: 100%; 
        background: #f1f5f9; 
        border: 1px solid #cbd5e1; 
        padding: 12px 16px 12px 16px; 
        border-radius: 8px; 
        color: #1e293b; 
        font-size: 0.9rem; 
        font-family: 'Inter', sans-serif; 
        box-sizing: border-box;
        transition: all 0.3s ease;
    }
    .select-estado {
        background: #ffffff; 
        border: 1px solid #cbd5e1; 
        color: #334155; 
        padding: 0 15px; 
        border-radius: 8px; 
        font-family: 'Inter', sans-serif; 
        font-size: 0.9rem; 
        outline: none; 
        cursor: pointer; 
        min-width: 190px; 
        height: 46px; 
        font-weight: 500;
    }
    .btn-filtrar {
        background: #3b82f6; 
        color: white; 
        border: none; 
        padding: 0 24px; 
        border-radius: 8px; 
        font-weight: 600; 
        font-size: 0.9rem; 
        cursor: pointer; 
        height: 46px; 
        transition: background 0.2s;
    }

    /* Regras de Responsividade */
    @media (max-width: 768px) {
        .topo-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        .topo-header h1 {
            font-size: 1.8rem !important;
        }
        .admin-toolbar {
            flex-direction: column;
            align-items: stretch;
            gap: 15px;
        }
        .filtro-form {
            flex-direction: column;
            max-width: 100%;
        }
        .select-estado, .btn-filtrar {
            width: 100%;
            height: 44px;
        }
        .contador-badge {
            text-align: center;
            align-self: flex-start;
            width: 100%;
            box-sizing: border-box;
        }
    }
</style>

<div class="seccao-container">

    <div class="topo-header">
        <div>
            <h1 style="font-family: 'Playfair Display', serif; font-size: 2.2rem; color: #0f172a; margin: 0 0 5px 0; font-weight: bold;">Gerir Reservas & Empréstimos</h1>
            <p style="color: #64748b; margin: 0; font-size: 0.95rem;">Consulte prazos, valide devoluções e monitorize o fluxo de retirada de livros.</p>
        </div>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'success_devolucao'): ?>
        <div style="color: #15803d; padding: 14px 18px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 500; display: flex; align-items: center; gap: 8px;">
            <strong>Sucesso:</strong> O artigo foi entregue e reintroduzido no catálogo como Disponível.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'success_renovacao'): ?>
        <div style="color: #b45309; padding: 14px 18px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 500; display: flex; align-items: center; gap: 8px;">
            <strong>Sucesso:</strong> O prazo de devolução previsto foi estendido por mais 14 dias.
        </div>
    <?php endif; ?>

    <div class="admin-toolbar">
        <form action="admin.php" method="GET" class="filtro-form">
            <input type="hidden" name="seccao" value="emprestimos">
            
            <div class="search-wrapper">
                <input type="text" name="q_reserva" class="input-busca" placeholder="Pesquisar por livro, utilizador ou email..." value="<?= htmlspecialchars($pesquisa_reserva) ?>">
            </div>

            <select name="estado_reserva" onchange="this.form.submit()" class="select-estado">
                <option value="">Todos os Estados</option>
                <option value="ativo" <?= $filtro_estado_reserva === 'ativo' ? 'selected' : '' ?>>Ativos</option>
                <option value="atrasado" <?= $filtro_estado_reserva === 'atrasado' ? 'selected' : '' ?>>Atrasados</option>
                <option value="devolvido" <?= $filtro_estado_reserva === 'devolvido' ? 'selected' : '' ?>>Devolvidos</option>
            </select>
            
            <button type="submit" class="btn-filtrar" onmouseover="this.style.backgroundColor='#2563eb'" onmouseout="this.style.backgroundColor='#3b82f6'">Filtrar</button>
        </form>

        <div class="contador-badge" style="white-space: nowrap; font-size: 0.9rem; color: #64748b; background: #f8fafc; padding: 8px 14px; border-radius: 20px; border: 1px solid #e2e8f0; font-weight: 500;">
            <span><?= count($emprestimos); ?> artigo(s) listado(s)</span>
        </div>
    </div>

    <div style="width: 100%; overflow-x: auto; border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.02); -webkit-overflow-scrolling: touch;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; min-width: 850px;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                    <th style="width: 60px; padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">ID</th>
                    <th style="width: 70px; padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Capa</th>
                    <th style="padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Livro Reservado / Detalhes</th>
                    <th style="padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Quem Reservou</th>
                    <th style="padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Data Início</th>
                    <th style="padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Data Fim</th>
                    <th style="padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Estado</th>
                    <th style="width: 200px; padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($emprestimos)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #64748b; padding: 50px; font-weight: 500;">Nenhum registo de empréstimo corresponde aos critérios.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($emprestimos as $emp): 
                        $capaPath = !empty($emp['livro_capa']) ? 'Uploads/'.$emp['livro_capa'] : 'Images/default-cover.png';
                        
                        $dataIni = date('d/m/Y', strtotime($emp['data_saida']));
                        $dataFim = date('d/m/Y', strtotime($emp['data_prevista_devolucao']));
                        
                        if ($emp['estado_calculado'] === 'atrasado') {
                            $textoExibido = "Atrasado";
                            $corEstado = 'color: #dc2626; border: 1px solid #fecaca; background: #fef2f2;';
                        } elseif ($emp['estado_calculado'] === 'ativo') {
                            $textoExibido = "Ativo";
                            $corEstado = 'color: #2563eb; border: 1px solid #bfdbfe; background: #eff6ff;';
                        } else {
                            $textoExibido = "Devolvido";
                            $corEstado = 'color: #475569; border: 1px solid #e2e8f0; background: #f8fafc;';
                        }
                    ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding: 14px 20px; font-weight: 600; color: #64748b;">#<?= $emp['id']; ?></td>
                            <td style="padding: 14px 20px;">
                                <img src="<?= $capaPath; ?>" alt="Capa" style="width: 40px; height: 52px; object-fit: cover; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                            </td>
                            <td style="padding: 14px 20px;">
                                <div style="display: flex; flex-direction: column; gap: 3px;">
                                    <span style="font-weight: 600; color: #1e293b; font-size: 0.95rem;"><?= htmlspecialchars($emp['livro_titulo']); ?></span>
                                    <small style="color: #64748b; font-size: 0.78rem;">ISBN: <?= htmlspecialchars($emp['livro_isbn']); ?></small>
                                </div>
                            </td>
                            <td style="padding: 14px 20px;">
                                <div style="display: flex; flex-direction: column; gap: 3px;">
                                    <span style="color: #334155; font-weight: 550; font-size: 0.9rem;"><?= htmlspecialchars($emp['user_nome']); ?></span>
                                    <small style="color: #64748b; font-size: 0.78rem;"><?= htmlspecialchars($emp['user_email']); ?></small>
                                </div>
                            </td>
                            <td style="padding: 14px 20px; color: #475569; font-size: 0.88rem; white-space: nowrap;"><?= $dataIni; ?></td>
                            <td style="padding: 14px 20px; color: #475569; font-size: 0.88rem; white-space: nowrap;"><?= $dataFim; ?></td>
                            <td style="padding: 14px 20px;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; padding: 5px 12px; min-width: 95px; border-radius: 6px; text-transform: uppercase; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.04em; <?= $corEstado ?>">
                                    <?= $textoExibido; ?>
                                </span>
                            </td>
                            <td style="padding: 14px 20px; text-align: center;">
                                <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                    <?php if ($emp['estado_calculado'] !== 'devolvido'): ?>
                                        <form action="processa_devolucao.php" method="POST" style="margin: 0; display: inline;">
                                            <input type="hidden" name="emprestimo_id" value="<?= $emp['id']; ?>">
                                            <button type="submit" style="padding: 8px 16px; font-size: 0.85rem; background: #2563eb; border: none; color: white; border-radius: 6px; cursor: pointer; font-weight: 600; transition: background 0.15s;" onmouseover="this.style.backgroundColor='#1d4ed8'" onmouseout="this.style.backgroundColor='#2563eb'" title="Registar Devolução">Devolver</button>
                                        </form>

                                        <form action="processa_renovacao.php" method="POST" style="margin: 0; display: inline;">
                                            <input type="hidden" name="emprestimo_id" value="<?= $emp['id']; ?>">
                                            <button type="submit" style="background: #ffffff; border: 1px solid #cbd5e1; color: #d97706; padding: 7px 16px; font-size: 0.85rem; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.15s;" onmouseover="this.style.background='#fffbeb'; this.style.borderColor='#fef3c7';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#cbd5e1';" title="Renovar Prazo">Renovar</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #16a34a; font-size: 1.2rem; font-weight: bold; display: inline-flex; align-items: center; justify-content: center; min-height: 33px;">✓</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>