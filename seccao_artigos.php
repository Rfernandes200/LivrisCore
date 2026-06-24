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

<style>
    .seccao-artigos-container {
        background-color: #ffffff !important; 
        color: #1e293b !important; 
        min-height: 100vh; 
        padding: 20px; 
        font-family: 'Inter', sans-serif;
    }
    .topo-header-artigos {
        display: flex; 
        justify-content: space-between; 
        align-items: flex-start; 
        margin-bottom: 25px; 
        width: 100%;
        gap: 15px;
    }
    .admin-toolbar-artigos {
        display: flex; 
        gap: 15px; 
        align-items: center; 
        justify-content: space-between; 
        margin-bottom: 25px; 
        background: #ffffff !important; 
        padding: 15px; 
        border-radius: 12px; 
        border: 1px solid #e2e8f0 !important;
    }
    .filtro-form-artigos {
        display: flex; 
        gap: 12px; 
        align-items: center;
        width: 100%; 
        max-width: 700px;
    }
    .search-container-admin-artigos {
        flex-grow: 2; 
        margin: 0; 
        position: relative; 
        min-width: 250px;
    }
    .input-busca-artigos {
        width: 100%; 
        height: 42px; 
        background: #f1f5f9 !important; 
        border: 1px solid #cbd5e1 !important; 
        color: #0f172a !important; 
        padding: 0 15px; 
        border-radius: 8px; 
        font-size: 0.88rem; 
        box-sizing: border-box;
        outline: none;
        transition: border-color 0.2s;
    }
    .input-busca-artigos:focus {
        border-color: #3b82f6 !important;
    }
    .select-estado-artigos {
        background: #ffffff !important; 
        border: 1px solid #cbd5e1 !important; 
        color: #334155 !important; 
        padding: 0 15px; 
        border-radius: 8px; 
        font-size: 0.88rem; 
        outline: none; 
        cursor: pointer; 
        min-width: 160px; 
        height: 42px; 
        box-sizing: border-box;
    }
    .btn-filtrar-artigos {
        background: #3b82f6 !important; 
        color: white !important; 
        border: none; 
        padding: 0 22px; 
        border-radius: 8px; 
        font-weight: 600; 
        font-size: 0.88rem; 
        cursor: pointer; 
        height: 42px; 
        transition: background 0.2s;
    }
    .btn-adicionar-artigo {
        background: #3b82f6 !important; 
        color: white !important; 
        border: none; 
        height: 42px; 
        padding: 0 20px; 
        border-radius: 8px; 
        font-weight: 600; 
        font-size: 0.88rem; 
        cursor: pointer; 
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background 0.2s;
        white-space: nowrap;
    }
    .contador-badge-artigos {
        white-space: nowrap; 
        font-size: 0.88rem; 
        font-weight: 500; 
        color: #64748b; 
        background: #f8fafc; 
        padding: 8px 14px; 
        border-radius: 20px;
        border: 1px solid #e2e8f0;
    }

    /* Media Queries para Responsividade (Mobile e Tablet) */
    @media (max-width: 768px) {
        .topo-header-artigos {
            flex-direction: column;
            align-items: flex-start;
        }
        .topo-header-artigos h1 {
            font-size: 1.8rem !important;
        }
        .btn-adicionar-artigo {
            width: 100%;
            justify-content: center;
        }
        .admin-toolbar-artigos {
            flex-direction: column;
            align-items: stretch;
            padding: 12px;
        }
        .filtro-form-artigos {
            flex-direction: column;
            align-items: stretch;
            width: 100%;
        }
        .search-container-admin-artigos {
            min-width: 100%;
        }
        .select-estado-artigos {
            width: 100%;
        }
        .btn-filtrar-artigos {
            width: 100%;
        }
        .contador-badge-artigos {
            text-align: center;
            width: 100%;
            box-sizing: border-box;
        }
    }
</style>

<div class="seccao-artigos-container">

    <div class="topo-header-artigos">
        <div>
            <h1 style="font-family: 'Playfair Display', serif; color: #0f172a !important; font-size: 2.2rem; font-weight: bold; margin: 0 0 5px 0;">Gerir Artigos (Catálogo)</h1>
            <p style="color: #64748b !important; margin: 0; font-size: 0.95rem;">Monitorize, filtre, edite e insira novos exemplares no acervo da biblioteca.</p>
        </div>
        <button class="btn-adicionar-artigo" onclick="abrirModalAdicionarArtigo()" onmouseover="this.style.backgroundColor='#2563eb'" onmouseout="this.style.backgroundColor='#3b82f6'">
            <span style="font-size: 1.1rem; font-weight: bold;">+</span> Adicionar Artigo
        </button>
    </div>

    <div class="admin-toolbar-artigos">
        <form action="admin.php" method="GET" class="filtro-form-artigos">
            <input type="hidden" name="seccao" value="artigos">
            
            <div class="search-container-admin-artigos">
                <input type="text" name="q_artigo" class="input-busca-artigos" placeholder="Pesquisar por título, ISBN ou editora..." value="<?= htmlspecialchars($pesquisa_artigo) ?>">
            </div>

            <select name="estado" class="select-estado-artigos" onchange="this.form.submit()">
                <option value="">Todos os Estados</option>
                <option value="disponivel" <?= $filtro_estado === 'disponivel' ? 'selected' : '' ?>>Disponível</option>
                <option value="indisponivel" <?= $filtro_estado === 'indisponivel' ? 'selected' : '' ?>>Indisponível</option>
            </select>
            
            <button type="submit" class="btn-filtrar-artigos" onmouseover="this.style.backgroundColor='#2563eb'" onmouseout="this.style.backgroundColor='#3b82f6'">Filtrar</button>
        </form>

        <div class="contador-badge-artigos">
            <span><?= count($artigos); ?> artigo(s) listado(s)</span>
        </div>
    </div>

    <div style="width: 100% !important; overflow-x: auto !important; background: #ffffff !important; border-radius: 12px; border: 1px solid #e2e8f0 !important; box-shadow: 0 1px 3px rgba(0,0,0,0.02); -webkit-overflow-scrolling: touch;">
        <table style="width: 100% !important; min-width: 900px; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                    <th style="padding: 16px 20px; width: 60px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">ID</th>
                    <th style="padding: 16px 20px; width: 70px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Capa</th>
                    <th style="padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Título / Detalhes</th>
                    <th style="padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Autor / Artista</th>
                    <th style="padding: 16px 20px; width: 150px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Categoria (CDU)</th>
                    <th style="padding: 16px 20px; width: 130px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Estado</th>
                    <th style="padding: 16px 20px; width: 180px; text-align: center; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($artigos)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #64748b; padding: 50px; font-weight: 500; background: #ffffff;">Nenhum artigo corresponde aos filtros aplicados.</td>
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
                        <tr style="border-bottom: 1px solid #f1f5f9; background: #ffffff; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding: 14px 20px; font-weight: 600; color: #64748b;">#<?= $art['id']; ?></td>
                            <td style="padding: 14px 20px;">
                                <img src="<?= $capaPath; ?>" alt="Capa" style="width: 40px; height: 52px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1;">
                            </td>
                            <td style="padding: 14px 20px;">
                                <div style="display: flex; flex-direction: column; gap: 3px;">
                                    <span style="font-weight: 600; color: #1e293b; font-size: 0.95rem;"><?= htmlspecialchars($art['titulo']); ?></span>
                                    <small style="color: #64748b; font-size: 0.78rem; font-weight: 500;">
                                        ISBN: <?= !empty($art['isbn']) ? htmlspecialchars($art['isbn']) : 'N/A'; ?> | 
                                        Editora: <?= !empty($art['editora']) ? htmlspecialchars($art['editora']) : 'N/A'; ?> 
                                        (<?= !empty($art['ano_edicao']) ? $art['ano_edicao'] : 'N/A'; ?>)
                                    </small>
                                </div>
                            </td>
                            <td style="padding: 14px 20px; color: #334155; font-weight: 500;"><?= htmlspecialchars($art['autor_artista'] ?? 'Desconhecido'); ?></td>
                            <td style="padding: 14px 20px;">
                                <span style="font-size: 0.75rem; font-weight: 600; background: #f1f5f9; padding: 5px 9px; border-radius: 6px; color: #475569; border: 1px solid #e2e8f0;" title="<?= htmlspecialchars($art['cat_nome'] ?? '') ?>">
                                    <?= !empty($art['cdu_codigo']) ? htmlspecialchars($art['cdu_codigo']) : 'Sem CDU'; ?>
                                </span>
                            </td>
                            <td style="padding: 14px 20px;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; padding: 4px 10px; min-width: 90px; border-radius: 6px; text-transform: uppercase; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; <?= $corEstado ?>">
                                    <?= $textoExibido; ?>
                                </span>
                            </td>
                            <td style="padding: 14px 20px; text-align: center;">
                                <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
    
    <button type="button"
            data-id="<?= $art['id']; ?>"
            style="background: #2563eb !important; color: white !important; border: none; padding: 8px 0; min-width: 90px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: background 0.15s; font-family: 'Inter', sans-serif;"
            onclick="abrirModalEditarArtigo(this)"
            onmouseover="this.style.backgroundColor='#1d4ed8'"
            onmouseout="this.style.backgroundColor='#2563eb'">Editar</button>
    
    <a href="Processos/eliminar_artigo.php?id=<?= $art['id']; ?>" 
       style="background: #ffe4e6 !important; color: #e11d48 !important; border: 1px solid #fecdd3 !important; padding: 8px 0; min-width: 90px; font-size: 0.85rem; font-weight: 600; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; transition: all 0.15s; font-family: 'Inter', sans-serif;"
       onmouseover="this.style.background='#fecdd3'; this.style.color='#be123c';" 
       onmouseout="this.style.background='#ffe4e6'; this.style.color='#e11d48';"
       onclick="return confirm('Tem a certeza que deseja remover este artigo?');">Eliminar</a>
</div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>