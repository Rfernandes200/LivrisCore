<?php
// Deteta dinamicamente o nome do ficheiro atual para aplicar a classe 'active'
$pagina_atual = basename($_SERVER['PHP_SELF']);

// Verifica se o utilizador está efetivamente logado
$está_logado = isset($_SESSION['utilizador_id']);

if ($está_logado) {
    $nome_user = $_SESSION['utilizador_nome'] ?? "Utilizador";
    $inicial_user = strtoupper(substr($nome_user, 0, 1));
}
?>

<style>
    .btn-entrar-nav {
        background: #3b82f6; 
        color: white; 
        padding: 8px 20px; 
        border-radius: 6px; 
        text-decoration: none; 
        font-size: 0.9rem; 
        font-weight: 500; 
        transition: all 0.2s ease-in-out;
        display: inline-block;
    }
    .btn-entrar-nav:hover {
        background: #2563eb; /* Azul um pouco mais escuro ao passar o rato */
        transform: translateY(-1px); /* Sobe ligeiramente para dar efeito de clique */
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); /* Sombra suave */
    }
</style>

<nav class="navbar navbar-fixed">
    <div class="nav-left">
        <div class="logo"><strong>B</strong> BiblioBase</div>
        <div class="menu">
            <a href="index.php" class="<?= $pagina_atual === 'index.php' ? 'active' : ''; ?>">Catálogo</a>
            <a href="sobre.php" class="<?= $pagina_atual === 'sobre.php' ? 'active' : ''; ?>">Sobre</a>
            
            <?php if ($está_logado): ?>
                <a href="emprestimos.php" class="<?= $pagina_atual === 'emprestimos.php' ? 'active' : ''; ?>">Empréstimos</a>
                
                <?php if ((int)($_SESSION['utilizador_tipo'] ?? 0) === 1): ?>
                    <a href="admin.php" style="color: #60a5fa; font-weight: 600; margin-left: 15px;" class="<?= $pagina_atual === 'admin.php' ? 'active' : ''; ?>">⚡ Administração</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="nav-right">
        <?php if ($está_logado): ?>
            <div class="profile-box" style="display: flex; align-items: center; gap: 10px;">
                <a href="perfil.php" style="display: flex; align-items: center; gap: 8px; text-decoration: none; color: inherit;">
                    <div class="user-avatar"><?= $inicial_user; ?></div>
                    <span class="user-name" style="font-weight: 500; color: #f8fafc;"><?= htmlspecialchars($nome_user); ?></span>
                </a>
                
                <span style="color: #334155;">|</span> 
                <a href="logout.php" class="logout-link" style="font-size:0.85rem; color:#ef4444; text-decoration:none;">Sair</a>
            </div>
        <?php else: ?>
            <a href="login.php" class="btn-entrar-nav">Entrar</a>
        <?php endif; ?>
    </div>
</nav>