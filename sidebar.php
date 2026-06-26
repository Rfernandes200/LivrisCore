<?php
// 1. LÓGICA DE DETEÇÃO DO UTILIZADOR
$footer_logado = isset($_SESSION['utilizador_id']);
if ($footer_logado) {
    $f_nome = $_SESSION['utilizador_nome'] ?? "Utilizador";
    $f_inicial = strtoupper(substr($f_nome, 0, 1));
}
?>

<aside class="sidebar-responsiva">
    
    <div class="sidebar-menu-links">
        <div class="sidebar-title">Administração</div>
        <a href="admin.php?seccao=geral" class="sidebar-link <?= ($seccao ?? 'geral') === 'geral' ? 'active' : '' ?>">
            <span class="sb-icon">G</span> <span class="sb-text">Geral</span>
        </a>
        <a href="admin.php?seccao=utilizadores" class="sidebar-link <?= ($seccao ?? '') === 'utilizadores' ? 'active' : '' ?>">
            <span class="sb-icon">U</span> <span class="sb-text">Utilizadores</span>
        </a>
        <a href="admin.php?seccao=reservas" class="sidebar-link <?= ($seccao ?? '') === 'reservas' ? 'active' : '' ?>">
            <span class="sb-icon">R</span> <span class="sb-text">Reservas</span>
        </a>
        <a href="admin.php?seccao=emprestimos" class="sidebar-link <?= ($seccao ?? '') === 'emprestimos' ? 'active' : '' ?>">
            <span class="sb-icon">E</span> <span class="sb-text">Empréstimos</span>
        </a>
        <a href="admin.php?seccao=artigos" class="sidebar-link <?= ($seccao ?? '') === 'artigos' ? 'active' : '' ?>">
            <span class="sb-icon">L</span> <span class="sb-text">Livros</span>
        </a>
    </div>

    <div class="custom-sidebar-footer">
        <?php if ($footer_logado): ?>
            <div class="sidebar-profile-box">
                <a href="perfil.php" class="sidebar-profile-link">
                    <div class="user-avatar-sidebar"><?= $f_inicial; ?></div>
                    <span class="user-name-sidebar"><?= htmlspecialchars($f_nome); ?></span>
                </a>
                
                <span class="sidebar-nav-divider">|</span>
                
                <a href="logout.php" class="sidebar-logout-link">Sair</a>
            </div>
        <?php endif; ?>
        
        <a href="index.php" class="sidebar-back-button">
            <span class="sb-text">Voltar ao início</span> <span class="sb-icon-mbi">V</span>
        </a>
    </div>
</aside>