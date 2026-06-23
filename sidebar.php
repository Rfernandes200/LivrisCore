<?php
// 1. LÓGICA DE DETEÇÃO DO UTILIZADOR
$footer_logado = isset($_SESSION['utilizador_id']);
if ($footer_logado) {
    $f_nome = $_SESSION['utilizador_nome'] ?? "Utilizador";
    $f_inicial = strtoupper(substr($f_nome, 0, 1));
}
?>

<aside class="sidebar" style="display: flex !important; flex-direction: column !important; justify-content: space-between !important; height: 100vh !important; position: fixed !important; top: 0 !important; left: 0 !important; width: 260px !important; padding-bottom: 25px !important; box-sizing: border-box !important; z-index: 99 !important;">
    
    <div class="sidebar-menu-links" style="display: flex; flex-direction: column; width: 100%;">
        <div class="sidebar-title">Administração</div>
        <a href="admin.php?seccao=geral" class="sidebar-link <?= ($seccao ?? 'geral') === 'geral' ? 'active' : '' ?>">📊 Geral</a>
        <a href="admin.php?seccao=utilizadores" class="sidebar-link <?= ($seccao ?? '') === 'utilizadores' ? 'active' : '' ?>">👥 Utilizadores</a>
        <a href="admin.php?seccao=reservas" class="sidebar-link <?= ($seccao ?? '') === 'reservas' ? 'active' : '' ?>">📅 Reservas</a>
        <a href="admin.php?seccao=emprestimos" class="sidebar-link <?= ($seccao ?? '') === 'emprestimos' ? 'active' : '' ?>">💼 Empréstimos</a>
        <a href="admin.php?seccao=artigos" class="sidebar-link <?= ($seccao ?? '') === 'artigos' ? 'active' : '' ?>">📦 Artigos (Catálogo)</a>
    </div>

    <div class="custom-sidebar-footer" style="width: 90%; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; padding-top: 15px; border-top: 1px solid rgba(255, 255, 255, 0.08) !important; box-sizing: border-box !important;">
        
        <?php if ($footer_logado): ?>
            <div class="sidebar-profile-box" style="display: flex !important; align-items: center !important; justify-content: space-between !important; background-color: #111827 !important; padding: 8px 14px !important; border-radius: 12px !important; border: 1px solid #1e293b !important; text-decoration: none !important;">
                
                <a href="perfil.php" style="display: flex !important; align-items: center !important; gap: 10px !important; text-decoration: none !important; background: none !important;">
                    <div style="width: 30px !important; height: 30px !important; display: flex !important; align-items: center !important; justify-content: center !important; border-radius: 50% !important; background-color: #1e293b !important; color: #ffffff !important; font-weight: 600 !important; font-size: 0.9rem !important; text-decoration: none !important;">
                        <?= $f_inicial; ?>
                    </div>
                    <span style="font-weight: 500 !important; color: #ffffff !important; font-size: 0.95rem !important; text-decoration: none !important; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 90px; display: inline-block;">
                        <?= htmlspecialchars($f_nome); ?>
                    </span>
                </a>
                
                <span style="color: #334155 !important; font-weight: normal !important; user-select: none; margin: 0 4px;">|</span>
                
                <a href="logout.php" style="font-size: 0.9rem !important; color: #ef4444 !important; text-decoration: none !important; font-weight: 500 !important; background: none !important; padding: 0 !important;">
                    Sair
                </a>
            </div>
        <?php endif; ?>
        
        <a href="index.php" style="display: flex !important; align-items: center !important; justify-content: center !important; gap: 8px !important; padding: 10px !important; background-color: rgba(255, 255, 255, 0.05) !important; color: #f8fafc !important; text-decoration: none !important; font-size: 0.85rem !important; font-weight: 500 !important; border-radius: 8px !important; border: none !important;">
            🏠 Voltar ao início
        </a>
    </div>
</aside>