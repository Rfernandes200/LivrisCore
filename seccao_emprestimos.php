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

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
    <div>
        <h1>Gerir Reservas & Empréstimos</h1>
        <?php if (isset($_GET['status']) && $_GET['status'] === 'success_devolucao'): ?>
    <div style="color: #10b981; padding: 12px; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); border-radius: 8px; margin-bottom: 15px; font-size: 0.85rem; font-weight: 500;">
        🟢 Sucesso: O artigo foi entregue e reintroduzido no catálogo como Disponível.
    </div>
<?php endif; ?>

<?php if (isset($_GET['status']) && $_GET['status'] === 'success_renovacao'): ?>
    <div style="color: #f59e0b; padding: 12px; background: rgba(234,179,8,0.1); border: 1px solid rgba(234,179,8,0.2); border-radius: 8px; margin-bottom: 15px; font-size: 0.85rem; font-weight: 500;">
        🟡 Sucesso: O prazo de devolução previsto foi estendido por mais 14 dias.
    </div>
<?php endif; ?>
        <p class="admin-subtitle">Consulte prazos, valide devoluções e monitorize o fluxo de retirada de livros.</p>
    </div>
</div>

<div class="admin-toolbar" style="display: flex; gap: 15px; align-items: center; justify-content: space-between;">
    <form action="admin.php" method="GET" style="display: flex; gap: 12px; width: 100%; max-width: 700px;">
        <input type="hidden" name="seccao" value="emprestimos">
        
        <div class="search-container-admin" style="flex-grow: 2; margin: 0;">
            <span class="search-icon-admin">🔍</span>
            <input type="text" name="q_reserva" class="search-input-admin" placeholder="Pesquisar por livro, utilizador ou email..." value="<?= htmlspecialchars($pesquisa_reserva) ?>">
        </div>

        <select name="estado_reserva" onchange="this.form.submit()" style="background: #0f172a; border: 1px solid rgba(255,255,255,0.08); color: #cbd5e1; padding: 0 15px; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.85rem; outline: none; cursor: pointer; min-width: 180px; height: 45px;">
            <option value="">⚙️ Todos os Estados</option>
            <option value="ativo" <?= $filtro_estado_reserva === 'ativo' ? 'selected' : '' ?>>🔵 Ativos</option>
            <option value="atrasado" <?= $filtro_estado_reserva === 'atrasado' ? 'selected' : '' ?>>🔴 Atrasados</option>
            <option value="devolvido" <?= $filtro_estado_reserva === 'devolvido' ? 'selected' : '' ?>>⚪ Devolvidos</option>
        </select>
        
        <button type="submit" style="background: #3b82f6; color: white; border: none; padding: 0 20px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; height: 45px;">Filtrar</button>
    </form>

    <div class="counter-badge" style="white-space: nowrap;">
        <span><?= count($emprestimos); ?> registo(s) encontrado(s)</span>
    </div>
</div>

<div class="table-responsive">
    <table class="agent-table">
        <thead>
            <tr>
                <th style="width: 50px;">ID</th>
                <th style="width: 60px;">Capa</th>
                <th>Livro Reservado / Detalhes</th>
                <th>Quem Reservou</th>
                <th>Data Início</th>
                <th>Data Fim</th>
                <th>Estado</th>
                <th style="width: 160px; text-align: center;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($emprestimos)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: #64748b; padding: 40px;">❌ Nenhum registo de empréstimo corresponde aos critérios.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($emprestimos as $emp): 
                    $capaPath = !empty($emp['livro_capa']) ? 'Uploads/'.$emp['livro_capa'] : 'Images/default-cover.png';
                    
                    $dataIni = date('d/m/Y', strtotime($emp['data_saida']));
                    $dataFim = date('d/m/Y', strtotime($emp['data_prevista_devolucao']));
                    
                    // Definição idêntica de Badges com os fundos translúcidos e bordas do teu painel
                    if ($emp['estado_calculado'] === 'atrasado') {
                        $textoExibido = "Atrasado";
                        $corEstado = 'color: #ef4444; border: 1px solid rgba(239,68,68,0.3); background: rgba(239,68,68,0.1);';
                    } elseif ($emp['estado_calculado'] === 'ativo') {
                        $textoExibido = "Ativo";
                        $corEstado = 'color: #3b82f6; border: 1px solid rgba(59,130,246,0.3); background: rgba(59,130,246,0.1);';
                    } else {
                        $textoExibido = "Devolvido";
                        $corEstado = 'color: #94a3b8; border: 1px solid rgba(148,163,184,0.3); background: rgba(148,163,184,0.05);';
                    }
                ?>
                    <tr>
                        <td class="td-id">#<?= $emp['id']; ?></td>
                        <td>
                            <img src="<?= $capaPath; ?>" alt="Capa" style="width: 42px; height: 55px; object-fit: cover; border-radius: 4px; border: 1px solid rgba(255,255,255,0.05);">
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 2px;">
                                <span class="user-name-text" style="font-weight: 600; color: #f8fafc;"><?= htmlspecialchars($emp['livro_titulo']); ?></span>
                                <small style="color: #64748b; font-size: 0.75rem;">ISBN: <?= htmlspecialchars($emp['livro_isbn']); ?></small>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 2px;">
                                <span style="color: #cbd5e1; font-weight: 500; font-size: 0.85rem;"><?= htmlspecialchars($emp['user_nome']); ?></span>
                                <small style="color: #64748b; font-size: 0.75rem;"><?= htmlspecialchars($emp['user_email']); ?></small>
                            </div>
                        </td>
                        <td style="color: #cbd5e1; font-size: 0.85rem; white-space: nowrap;"><?= $dataIni; ?></td>
                        <td style="color: #cbd5e1; font-size: 0.85rem; white-space: nowrap;"><?= $dataFim; ?></td>
                        <td>
                            <span style="display: inline-flex; align-items: center; justify-content: center; padding: 6px 12px; min-width: 100px; border-radius: 6px; text-transform: uppercase; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em; <?= $corEstado ?>">
                                <?= $textoExibido; ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                <?php if ($emp['estado_calculado'] !== 'devolvido'): ?>
                                    <form action="processa_devolucao.php" method="POST" style="margin: 0; display: inline;">
                                        <input type="hidden" name="emprestimo_id" value="<?= $emp['id']; ?>">
                                        <button type="submit" class="btn-edit-trigger" style="padding: 6px 10px; font-size: 0.75rem; background: #10b981; border: none; color: white; border-radius: 6px; cursor: pointer; font-weight: 600;" title="Registar Devolução">Devolver</button>
                                    </form>

                                    <form action="processa_renovacao.php" method="POST" style="margin: 0; display: inline;">
                                        <input type="hidden" name="emprestimo_id" value="<?= $emp['id']; ?>">
                                        <button type="submit" style="background: rgba(234,179,8,0.1); border: 1px solid rgba(234,179,8,0.3); color: #f59e0b; padding: 5px 10px; font-size: 0.75rem; border-radius: 6px; cursor: pointer; font-weight: 600;" title="Renovar Prazo">Renovar</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #475569; font-size: 0.9rem;">-</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        <?php endif; ?>
    </table>
</div>