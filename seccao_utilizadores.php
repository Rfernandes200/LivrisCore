<?php
// Bloqueio de Segurança direto no ficheiro incluído
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    exit('Acesso negado');
}

// ==========================================
// LÓGICA DE PROCESSAMENTO E CONSULTA SQL
// ==========================================
$utilizadores = [];
$pesquisa = $_GET['q'] ?? '';

try {
    if (!empty($pesquisa)) {
        $stmt_u = $pdo->prepare("SELECT id, nome, email, telemovel, tipo, ativo, data_registo FROM utilizadores WHERE nome LIKE :q OR email LIKE :q ORDER BY id DESC");
        $stmt_u->execute(['q' => "%$pesquisa%"]);
    } else {
        $stmt_u = $pdo->query("SELECT id, nome, email, telemovel, tipo, ativo, data_registo FROM utilizadores ORDER BY id DESC");
    }
    $utilizadores = $stmt_u->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div style='color: #ef4444; padding: 15px; background: rgba(239,68,68,0.1); border-radius: 8px; margin-bottom: 20px;'>Erro ao carregar utilizadores: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<style>
    .seccao-container-utilizadores {
        background-color: #ffffff !important; 
        color: #1e293b !important; 
        min-height: 100vh; 
        padding: 20px; 
        font-family: 'Inter', sans-serif;
    }
    .topo-header-utilizadores {
        display: flex; 
        justify-content: space-between; 
        align-items: flex-start; 
        margin-bottom: 25px; 
        width: 100%;
    }
    .admin-toolbar-utilizadores {
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 25px; 
        width: 100%;
    }
    .filtro-form-utilizadores {
        display: flex; 
        align-items: center; 
        gap: 12px; 
        margin: 0;
    }
    .search-wrapper-utilizadores {
        position: relative; 
        width: 320px;
    }
    .input-busca-utilizadores {
        width: 100%; 
        background: #f1f5f9; 
        border: 1px solid #cbd5e1; 
        padding: 12px 14px 12px 16px; 
        border-radius: 8px; 
        color: #1e293b; 
        font-size: 0.9rem; 
        font-family: 'Inter', sans-serif; 
        outline: none; 
        box-sizing: border-box;
        transition: all 0.3s ease;
    }
    .btn-filtrar-utilizadores {
        background: #3b82f6; 
        color: white; 
        border: none; 
        padding: 12px 24px; 
        border-radius: 8px; 
        font-weight: 600; 
        cursor: pointer; 
        font-size: 0.9rem; 
        transition: background 0.2s;
    }
    .btn-novo-utilizador {
        background: #3b82f6; 
        color: white; 
        border: none; 
        padding: 12px 22px; 
        border-radius: 8px; 
        font-weight: 600; 
        cursor: pointer; 
        display: flex; 
        align-items: center; 
        gap: 8px; 
        transition: background 0.2s; 
        font-size: 0.9rem; 
        margin-top: 5px;
    }

    /* Regras de Responsividade */
    @media (max-width: 768px) {
        .topo-header-utilizadores {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        .topo-header-utilizadores h1 {
            font-size: 1.8rem !important;
        }
        .btn-novo-utilizador {
            width: 100%;
            justify-content: center;
            margin-top: 0;
        }
        .admin-toolbar-utilizadores {
            flex-direction: column;
            align-items: stretch;
            gap: 15px;
        }
        .filtro-form-utilizadores {
            flex-direction: column;
            align-items: stretch;
            width: 100%;
        }
        .search-wrapper-utilizadores {
            width: 100%;
        }
        .btn-filtrar-utilizadores {
            width: 100%;
        }
        .contador-badge-utilizadores {
            text-align: center;
            align-self: flex-start;
            width: 100%;
            box-sizing: border-box;
        }
    }
</style>

<div class="seccao-container-utilizadores">

    <div class="topo-header-utilizadores">
        <div>
            <h1 style="font-family: 'Playfair Display', serif; font-size: 2.2rem; color: #0f172a; font-weight: bold; margin: 0 0 5px 0;">Lista de Utilizadores</h1>
            <p style="color: #64748b; margin: 0; font-size: 0.95rem;">Consulta de contas com acesso à biblioteca.</p>
        </div>

        <button onclick="abrirModalAdicionarUtilizador()" class="btn-novo-utilizador" onmouseover="this.style.backgroundColor='#2563eb'" onmouseout="this.style.backgroundColor='#3b82f6'">
            <span style="font-size: 1.1rem; font-weight: bold;">+</span> Novo Utilizador
        </button>
    </div>

    <div class="admin-toolbar-utilizadores">
        <form action="admin.php" method="GET" class="filtro-form-utilizadores">
            <input type="hidden" name="seccao" value="utilizadores">
            
            <div class="search-wrapper-utilizadores">
                <input type="text" name="q" class="input-busca-utilizadores" placeholder="Pesquisar por username ou nome..." value="<?= htmlspecialchars($pesquisa) ?>">
            </div>
            
            <button type="submit" class="btn-filtrar-utilizadores" onmouseover="this.style.backgroundColor='#2563eb'" onmouseout="this.style.backgroundColor='#3b82f6'">
                Filtrar
            </button>
        </form>

        <div class="contador-badge-utilizadores" style="white-space: nowrap; font-size: 0.9rem; color: #64748b; background: #f8fafc; padding: 8px 14px; border-radius: 20px; border: 1px solid #e2e8f0; font-weight: 500;">
            <span><?= count($utilizadores); ?> utilizador(es) encontrado(s)</span>
        </div>
    </div>

    <div style="width: 100%; overflow-x: auto; border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.02); -webkit-overflow-scrolling: touch;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; min-width: 850px;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                    <th style="width: 60px; padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">ID</th>
                    <th style="padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Nome</th>
                    <th style="padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Email</th>
                    <th style="padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Telemóvel</th>
                    <th style="padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Registo</th>
                    <th style="padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Cargo</th>
                    <th style="padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Estado</th>
                    <th style="width: 200px; padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($utilizadores)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #64748b; padding: 50px; font-weight: 500;">Nenhum utilizador encontrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($utilizadores as $u): 
                        $u_tipo_normalizado = trim(strtolower($u['tipo']));
                        $isAdmin = ($u_tipo_normalizado === 'admin' || (int)$u['tipo'] === 1);
                        $eProprioAdmin = ($id_admin_atual !== null && (int)$u['id'] === (int)$id_admin_atual);
                    ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding: 14px 20px; font-weight: 600; color: #64748b;">#<?= $u['id']; ?></td>
                            <td style="padding: 14px 20px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-weight: 600; color: #1e293b; font-size: 0.95rem;"><?= htmlspecialchars($u['nome']); ?></span>
                                    <?php if ($isAdmin): ?>
                                        <span style="background: #fef3c7; color: #d97706; border: 1px solid #fde68a; font-size: 0.65rem; font-weight: 700; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.05em;">Admin</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="padding: 14px 20px; color: #334155; font-weight: 500;"><?= htmlspecialchars($u['email']); ?></td>
                            <td style="padding: 14px 20px; color: #475569;">
                                <?= !empty($u['telemovel']) ? htmlspecialchars($u['telemovel']) : '<span style="color:#94a3b8; font-style:italic; font-size: 0.85rem;">Nenhum</span>'; ?>
                            </td>
                            <td style="padding: 14px 20px; color: #64748b; font-size: 0.88rem; white-space: nowrap;">
                                <?= !empty($u['data_registo']) ? date('d/m/Y', strtotime($u['data_registo'])) : 'N/A'; ?>
                            </td>
                            <td style="padding: 14px 20px; color: #475569; font-weight: 500;"><?= $isAdmin ? 'Administrador' : 'Utilizador'; ?></td>
                            <td style="padding: 14px 20px;">
                                <?php if ((int)$u['ativo'] === 1): ?>
                                    <span style="display: inline-flex; align-items: center; justify-content: center; padding: 4px 10px; min-width: 80px; border-radius: 6px; text-transform: uppercase; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; color: #16a34a; border: 1px solid #bbf7d0; background: #f0fdf4;">Ativo</span>
                                <?php else: ?>
                                    <span style="display: inline-flex; align-items: center; justify-content: center; padding: 4px 10px; min-width: 80px; border-radius: 6px; text-transform: uppercase; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; color: #dc2626; border: 1px solid #fecaca; background: #fef2f2;">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 14px 20px; text-align: center;">
                                <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                    
                                    <button type="button" 
                                            onclick="abrirModalEditar(this)" 
                                            data-id="<?= $u['id']; ?>" 
                                            data-nome="<?= htmlspecialchars($u['nome']); ?>" 
                                            data-email="<?= htmlspecialchars($u['email']); ?>" 
                                            data-telemovel="<?= htmlspecialchars($u['telemovel'] ?? ''); ?>" 
                                            data-tipo="<?= $isAdmin ? 'admin' : 'user'; ?>" 
                                            data-ativo="<?= $u['ativo']; ?>" 
                                            data-self="<?= $eProprioAdmin ? 'true' : 'false'; ?>"
                                            style="background: #2563eb; color: white; border: none; padding: 8px 18px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 0.85rem; display: inline-flex; align-items: center; justify-content: center; transition: background 0.15s ease-in-out; font-family: 'Inter', sans-serif;"
                                            onmouseover="this.style.backgroundColor='#1d4ed8'"
                                            onmouseout="this.style.backgroundColor='#2563eb'">
                                        Editar
                                    </button>

                                    <?php if (!$eProprioAdmin): ?>
                                        <form action="editar_utilizadores.php" method="POST" style="margin:0;" onsubmit="return confirm('Tem a certeza absoluta que deseja eliminar permanentemente a conta de: <?= htmlspecialchars($u['nome']); ?>?');">
                                            <input type="hidden" name="acao" value="eliminar_utilizador">
                                            <input type="hidden" name="utilizador_id" value="<?= $u['id']; ?>">
                                            <button type="submit" 
                                                    style="background: #ffe4e6; color: #e11d48; border: 1px solid #fecdd3; padding: 8px 18px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 0.85rem; display: inline-flex; align-items: center; justify-content: center; transition: all 0.15s ease-in-out; font-family: 'Inter', sans-serif;" 
                                                    onmouseover="this.style.background='#fecdd3'; this.style.color='#be123c';" 
                                                    onmouseout="this.style.background='#ffe4e6'; this.style.color='#e11d48';">
                                                Eliminar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size: 0.78rem; color: #94a3b8; font-style: italic; font-weight: 500; min-width: 80px; display: inline-block;">Sua Conta</span>
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