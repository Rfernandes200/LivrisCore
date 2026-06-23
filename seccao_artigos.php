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

<div class="seccao-artigos-container" style="background-color: #f8fafc !important; color: #1e293b !important; min-height: 100vh; padding: 20px; font-family: 'Inter', sans-serif;">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="color: #0f172a !important; font-size: 1.75rem; font-weight: 700; margin: 0;">Gerir Artigos (Catálogo)</h1>
            <p style="color: #64748b !important; margin: 4px 0 0 0; font-size: 0.9rem;">Monitorize, filtre, edite e insira novos exemplares no acervo da biblioteca.</p>
        </div>
        <button class="btn-edit-trigger" style="background: #2563eb !important; color: white !important; border: none; height: 42px; padding: 0 18px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: background 0.2s;" onclick="abrirModalAdicionarArtigo()">➕ Adicionar Artigo</button>
    </div>

    <div class="admin-toolbar" style="display: flex; gap: 15px; align-items: center; justify-content: space-between; flex-wrap: wrap; margin-bottom: 20px; background: #ffffff !important; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0 !important;">
        <form action="admin.php" method="GET" style="display: flex; gap: 12px; width: 100%; max-width: 700px; flex-wrap: wrap;">
            <input type="hidden" name="seccao" value="artigos">
            
            <div class="search-container-admin" style="flex-grow: 2; margin: 0; position: relative; min-width: 250px;">
                <input type="text" name="q_artigo" style="width: 100%; height: 42px; background: #f1f5f9 !important; border: 1px solid #cbd5e1 !important; color: #0f172a !important; padding: 0 15px 0 35px; border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;" placeholder="Pesquisar por título, ISBN ou editora..." value="<?= htmlspecialchars($pesquisa_artigo) ?>">
                <span style="position: absolute; left: 12px; top: 12px; color: #64748b;">🔍</span>
            </div>

            <select name="estado" onchange="this.form.submit()" style="background: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #334155 !important; padding: 0 15px; border-radius: 8px; font-size: 0.85rem; outline: none; cursor: pointer; min-width: 160px; height: 42px; box-sizing: border-box;">
                <option value="">Todos os Estados</option>
                <option value="disponivel" <?= $filtro_estado === 'disponivel' ? 'selected' : '' ?>>Disponível</option>
                <option value="indisponivel" <?= $filtro_estado === 'indisponivel' ? 'selected' : '' ?>>Indisponível</option>
            </select>
            
            <button type="submit" style="background: #334155 !important; color: white !important; border: none; padding: 0 20px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; height: 42px; transition: background 0.2s;">Filtrar</button>
        </form>

        <div style="white-space: nowrap; font-size: 0.85rem; font-weight: 600; color: #475569; background: #f1f5f9; padding: 8px 14px; border-radius: 20px;">
            <span>📊 <?= count($artigos); ?> artigo(s) listado(s)</span>
        </div>
    </div>

    <div style="width: 100% !important; overflow-x: auto !important; background: #ffffff !important; border-radius: 12px; border: 1px solid #e2e8f0 !important;">
        <table style="width: 100% !important; min-width: 850px; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
            <thead>
                <tr style="background-color: #f1f5f9 !important; border-bottom: 2px solid #e2e8f0 !important;">
                    <th style="padding: 14px; width: 60px; color: #475569; font-weight: 600;">ID</th>
                    <th style="padding: 14px; width: 70px; color: #475569; font-weight: 600;">Capa</th>
                    <th style="padding: 14px; color: #475569; font-weight: 600;">Título / Detalhes</th>
                    <th style="padding: 14px; color: #475569; font-weight: 600;">Autor / Artista</th>
                    <th style="padding: 14px; width: 150px; color: #475569; font-weight: 600;">Categoria (CDU)</th>
                    <th style="padding: 14px; width: 130px; color: #475569; font-weight: 600;">Estado</th>
                    <th style="padding: 14px; width: 160px; text-align: center; color: #475569; font-weight: 600;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($artigos)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #64748b; padding: 40px; background: #ffffff;">Nenhum artigo corresponde aos filtros aplicados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($artigos as $art): 
                        $capaPath = !empty($art['imagem_url']) ? 'Uploads/'.$art['imagem_url'] : 'Images/default-cover.png';
                        $estadoLimpo = strtolower(trim($art['estado']));

                        if ($estadoLimpo === 'disponivel' || $estadoLimpo === 'disponível') {
                            $textoExibido = "Disponível";
                            $corEstado = 'color: #16a34a; border: 1px solid #bbf7d0; background: #f0fdf4;';
                        } else {
                            $textoExibido = "Indisponível";
                            $corEstado = 'color: #dc2626; border: 1px solid #fecaca; background: #fef2f2;';
                        }
                    ?>
                        <tr style="border-bottom: 1px solid #e2e8f0 !important; background: #ffffff; transition: background 0.15s;">
                            <td style="padding: 14px; font-weight: 600; color: #64748b;">#<?= $art['id']; ?></td>
                            <td style="padding: 14px;">
                                <img src="<?= $capaPath; ?>" alt="Capa" style="width: 40px; height: 52px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1;">
                            </td>
                            <td style="padding: 14px;">
                                <div style="display: flex; flex-direction: column; gap: 3px;">
                                    <span style="font-weight: 600; color: #0f172a; font-size: 0.95rem;"><?= htmlspecialchars($art['titulo']); ?></span>
                                    <small style="color: #64748b; font-size: 0.75rem; font-weight: 500;">
                                        ISBN: <?= !empty($art['isbn']) ? htmlspecialchars($art['isbn']) : 'N/A'; ?> | 
                                        Editora: <?= !empty($art['editora']) ? htmlspecialchars($art['editora']) : 'N/A'; ?> 
                                        (<?= !empty($art['ano_edicao']) ? $art['ano_edicao'] : 'N/A'; ?>)
                                    </small>
                                </div>
                            </td>
                            <td style="padding: 14px; color: #334155; font-weight: 500;"><?= htmlspecialchars($art['autor_artista'] ?? 'Desconhecido'); ?></td>
                            <td style="padding: 14px;">
                                <span style="font-size: 0.75rem; font-weight: 600; background: #f1f5f9; padding: 5px 9px; border-radius: 6px; color: #475569; border: 1px solid #e2e8f0;" title="<?= htmlspecialchars($art['cat_nome'] ?? '') ?>">
                                    <?= !empty($art['cdu_codigo']) ? htmlspecialchars($art['cdu_codigo']) : 'Sem CDU'; ?>
                                </span>
                            </td>
                            <td style="padding: 14px;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; padding: 5px 10px; min-width: 95px; border-radius: 6px; text-transform: uppercase; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.03em; <?= $corEstado ?>">
                                    <?= $textoExibido; ?>
                                </span>
                            </td>
                            <td style="padding: 14px; text-align: center;">
                                <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                    
                                    <button style="padding: 6px 12px; font-size: 0.8rem; font-weight: 600; background: #2563eb !important; color: white !important; border: none; border-radius: 6px; cursor: pointer; transition: background 0.2s;"
                                            data-id="<?= $art['id']; ?>"
                                            data-titulo="<?= htmlspecialchars($art['titulo']); ?>"
                                            data-isbn="<?= htmlspecialchars($art['isbn'] ?? ''); ?>"
                                            data-editora="<?= htmlspecialchars($art['editora'] ?? ''); ?>"
                                            data-ano="<?= $art['ano_edicao'] ?? ''; ?>"
                                            data-autor="<?= $art['autor_id'] ?? ''; ?>"
                                            data-cdu="<?= htmlspecialchars($art['cdu_codigo'] ?? ''); ?>"
                                            data-estado="<?= $art['estado']; ?>"
                                            data-descricao="<?= htmlspecialchars($art['descricao'] ?? ''); ?>"
                                            onclick="abrirModalEditarArtigo(this)">Editar</button>
                                    
                                    <a href="eliminar_artigo.php?id=<?= $art['id']; ?>" 
                                       style="background: #fee2e2 !important; color: #dc2626 !important; border: 1px solid #fecaca !important; padding: 5px 11px; font-size: 0.8rem; font-weight: 600; border-radius: 6px; text-decoration: none; display: inline-block; transition: background 0.2s;"
                                       title="Eliminar Artigo"
                                       onclick="return confirm('Tem a certeza que deseja remover permanentemente o anúncio: <?= htmlspecialchars($art['titulo']); ?>?');">Eliminar</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>