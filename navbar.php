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


<nav class="navbar">
    <div class="nav-left">
        <button class="hamburger-toggle" id="hamburgerBtn" aria-label="Abrir menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="logo"><strong>LC</strong> LivrisCore</div>
        
        <div class="menu" id="navMenu">
            <a href="index.php" class="<?= $pagina_atual === 'index.php' ? 'active' : ''; ?>">Catálogo</a>
            <a href="sobre.php" class="<?= $pagina_atual === 'sobre.php' ? 'active' : ''; ?>">Sobre</a>
            
            <?php if ($está_logado): ?>
                <a href="emprestimos.php" class="<?= $pagina_atual === 'emprestimos.php' ? 'active' : ''; ?>">Empréstimos</a>
                
                <?php if ((int)($_SESSION['utilizador_tipo'] ?? 0) === 1): ?>
                    <a href="admin.php" class="nav-admin <?= $pagina_atual === 'admin.php' ? 'active' : ''; ?>">Administração</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="nav-right">
        <?php if ($está_logado): ?>
            <div class="profile-box">
                <a href="perfil.php" class="profile-link">
                    <div class="user-avatar"><?= $inicial_user; ?></div>
                    <span class="user-name"><?= htmlspecialchars($nome_user); ?></span>
                </a>
                <span class="nav-divider">|</span>
                <a href="logout.php" class="logout-link">Sair</a>
            </div>
        <?php else: ?>
            <a href="login.php" class="btn-entrar-nav">Entrar</a>
        <?php endif; ?>
    </div>
</nav>

<script>
    document.getElementById('hamburgerBtn').addEventListener('click', function() {
        this.classList.toggle('open');
        document.getElementById('navMenu').classList.toggle('open');
    });
</script>