<?php
// Bloqueio de Segurança direto no ficheiro incluído
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    exit('Acesso negado');
}

// ==========================================
// LÓGICA DE PROCESSAMENTO E CONSULTA SQL
// ==========================================
$todas_categorias = [];
$todos_autores = [];
$artigos = [];

$pesquisa_artigo = $_GET['q_artigo'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';

try {
    // Carregar dados auxiliares para os formulários de inserção/edição (modais)
    $todas_categorias = $pdo->query("SELECT codigo, descricao FROM cdu_classes ORDER BY descricao ASC")->fetchAll(PDO::FETCH_ASSOC);
    $todos_autores = $pdo->query("SELECT id, nome FROM autores ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Consulta principal dos artigos
    $sql_artigos = "SELECT livros.*, 
                           cdu_classes.descricao AS cat_nome,
                           autores.nome AS autor_artista,
                           autores.id AS autor_id
                    FROM livros 
                    LEFT JOIN cdu_classes ON livros.cdu_codigo = cdu_classes.codigo
                    LEFT JOIN livro_autores ON livros.id = livro_autores.livro_id
                    LEFT JOIN autores ON livro_autores.autor_id = autores.id
                    WHERE 1=1";
    
    $params = [];

    if (!empty($pesquisa_artigo)) {
        $sql_artigos .= " AND (livros.titulo LIKE :q OR livros.isbn LIKE :q OR livros.editora LIKE :q)";
        $params['q'] = "%$pesquisa_artigo%";
    }

    if (!empty($filtro_estado)) {
        if ($filtro_estado === 'indisponivel') {
            $sql_artigos .= " AND livros.estado != 'disponivel'";
        } else {
            $sql_artigos .= " AND livros.estado = :estado";
            $params['estado'] = $filtro_estado;
        }
    }

    $sql_artigos .= " ORDER BY livros.id DESC";
    
    $stmt_a = $pdo->prepare($sql_artigos);
    $stmt_a->execute($params);
    $artigos = $stmt_a->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div style='color: #ef4444; padding: 15px; background: rgba(239,68,68,0.1); border-radius: 8px; margin-bottom: 20px;'>Erro ao carregar o catálogo: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
    <div>
        <h1>Gerir Artigos (Catálogo)</h1>
        <p class="admin-subtitle">Monitorize, filtre, edite e insira novos exemplares no acervo da biblioteca.</p>
    </div>
    <button class="btn-edit-trigger" style="background: #10b981; height: 45px; padding: 0 20px;" onclick="abrirModalAdicionarArtigo()">➕ Adicionar Artigo</button>
</div>

<div class="admin-toolbar" style="display: flex; gap: 15px; align-items: center; justify-content: space-between;">
    <form action="admin.php" method="GET" style="display: flex; gap: 12px; width: 100%; max-width: 700px;">
        <input type="hidden" name="seccao" value="artigos">
        
        <div class="search-container-admin" style="flex-grow: 2; margin: 0;">
            <span class="search-icon-admin">🔍</span>
            <input type="text" name="q_artigo" class="search-input-admin" placeholder="Pesquisar por título, ISBN ou editora..." value="<?= htmlspecialchars($pesquisa_artigo) ?>">
        </div>

        <select name="estado" onchange="this.form.submit()" style="background: #0f172a; border: 1px solid rgba(255,255,255,0.08); color: #cbd5e1; padding: 0 15px; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.85rem; outline: none; cursor: pointer; min-width: 180px; height: 45px;">
            <option value="">⚙️ Todos os Estados</option>
            <option value="disponivel" <?= $filtro_estado === 'disponivel' ? 'selected' : '' ?>>🟢 Disponível</option>
            <option value="indisponivel" <?= $filtro_estado === 'indisponivel' ? 'selected' : '' ?>>🔴 Indisponível</option>
        </select>
        
        <button type="submit" style="background: #3b82f6; color: white; border: none; padding: 0 20px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; height: 45px; transition: background 0.2s;">Filtrar</button>
    </form>

    <div class="counter-badge" style="white-space: nowrap;">
        <span><?= count($artigos); ?> artigo(s) listado(s)</span>
    </div>
</div>

<div class="table-responsive">
    <table class="agent-table">
        <thead>
            <tr>
                <th style="width: 50px;">ID</th>
                <th style="width: 60px;">Capa</th>
                <th>Título / Detalhes</th>
                <th>Autor / Artista</th>
                <th>Categoria (CDU)</th>
                <th>Estado</th>
                <th style="width: 140px; text-align: center;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($artigos)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #64748b; padding: 40px;">❌ Nenhum artigo corresponde aos filtros aplicados.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($artigos as $art): 
                    $capaPath = !empty($art['imagem_url']) ? 'Uploads/'.$art['imagem_url'] : 'Images/default-cover.png';
                    $estadoLimpo = strtolower(trim($art['estado']));

                    if ($estadoLimpo === 'disponivel' || $estadoLimpo === 'disponível') {
                        $textoExibido = "Disponível";
                        $corEstado = 'color: #10b981; border: 1px solid rgba(16,185,129,0.3); background: rgba(16,185,129,0.1);';
                    } else {
                        $textoExibido = "Indisponível";
                        $corEstado = 'color: #ef4444; border: 1px solid rgba(239,68,68,0.3); background: rgba(239,68,68,0.1);';
                    }
                ?>
                    <tr>
                        <td class="td-id">#<?= $art['id']; ?></td>
                        <td>
                            <img src="<?= $capaPath; ?>" alt="Capa" style="width: 42px; height: 55px; object-fit: cover; border-radius: 4px; border: 1px solid rgba(255,255,255,0.05);">
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 2px;">
                                <span class="user-name-text" style="font-weight: 600; color: #f8fafc;"><?= htmlspecialchars($art['titulo']); ?></span>
                                <small style="color: #64748b; font-size: 0.75rem;">
                                    ISBN: <?= !empty($art['isbn']) ? htmlspecialchars($art['isbn']) : 'N/A'; ?> | 
                                    Ed: <?= !empty($art['editora']) ? htmlspecialchars($art['editora']) : 'N/A'; ?> 
                                    (<?= !empty($art['ano_edicao']) ? $art['ano_edicao'] : 'N/A'; ?>)
                                </small>
                            </div>
                        </td>
                        <td style="color: #cbd5e1;"><?= htmlspecialchars($art['autor_artista'] ?? 'Desconhecido'); ?></td>
                        <td>
                            <span style="font-size: 0.75rem; background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 4px; color: #94a3b8;" title="<?= htmlspecialchars($art['cat_nome'] ?? '') ?>">
                                <?= !empty($art['cdu_codigo']) ? htmlspecialchars($art['cdu_codigo']) : 'Sem CDU'; ?>
                            </span>
                        </td>
                        <td>
                            <span style="display: inline-flex; align-items: center; justify-content: center; padding: 6px 12px; min-width: 100px; border-radius: 6px; text-transform: uppercase; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em; <?= $corEstado ?>">
                                <?= $textoExibido; ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                <button class="btn-edit-trigger" style="padding: 4px 10px; font-size: 0.8rem; background: #3b82f6;"
                                        data-id="<?= $art['id']; ?>"
                                        data-titulo="<?= htmlspecialchars($art['titulo']); ?>"
                                        data-isbn="<?= htmlspecialchars($art['isbn'] ?? ''); ?>"
                                        data-editora="<?= htmlspecialchars($art['editora'] ?? ''); ?>"
                                        data-ano="<?= $art['ano_edicao'] ?? ''; ?>"
                                        data-autor="<?= $art['autor_id'] ?? ''; ?>"
                                        data-cdu="<?= htmlspecialchars($art['cdu_codigo'] ?? ''); ?>"
                                        data-estado="<?= $art['estado']; ?>"
                                        data-descricao="<?= htmlspecialchars($art['descricao'] ?? ''); ?>"
                                        onclick="abrirModalEditarArtigo(this)">✏️</button>
                                
                                <a href="eliminar_artigo.php?id=<?= $art['id']; ?>" 
                                   style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); padding: 4px 8px; border-radius: 6px; text-decoration: none;"
                                   title="Eliminar Artigo"
                                   onclick="return confirm('Tem a certeza que deseja remover permanentemente o anúncio: <?= htmlspecialchars($art['titulo']); ?>?');">🗑️</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>