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
    /* Estilos base da Navbar para garantir o alinhamento correto */
    .navbar-fixed {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 24px; /* Mais espaço nas bordas da barra */
        box-sizing: border-box;
        height: 70px; /* Altura fixa confortável */
    }

    .btn-entrar-nav {
        background: #3b82f6;
        color: white;
        padding: 10px 24px; /* Mais espaçoso */
        border-radius: 8px;
        text-decoration: none;
        font-size: 0.95rem;
        font-weight: 500;
        transition: all 0.2s ease-in-out;
        display: inline-block;
    }
    .btn-entrar-nav:hover {
        background: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    /* --- BOTÃO HAMBÚRGUER À ESQUERDA --- */
    .hamburger-toggle {
        display: none;
        background: none;
        border: none;
        cursor: pointer;
        flex-direction: column;
        gap: 6px;
        padding: 8px;
        z-index: 1001;
        margin-right: 12px; /* Afasta o hambúrguer do Logótipo */
    }

    .hamburger-toggle span {
        display: block;
        width: 26px;
        height: 3px;
        background-color: #f8fafc;
        border-radius: 3px;
        transition: all 0.3s ease;
    }

    /* Animação do X */
    .hamburger-toggle.open span:nth-child(1) {
        transform: rotate(45deg) translate(6deg, 6deg);
    }
    .hamburger-toggle.open span:nth-child(2) {
        opacity: 0;
    }
    .hamburger-toggle.open span:nth-child(3) {
        transform: rotate(-45deg) translate(6deg, -6deg);
    }

    /* LINKS DO MENU (DESKTOP) - Mais afastados */
    .nav-left .menu a {
        text-decoration: none;
        padding: 8px 16px; /* Espaço interno individual para não ficarem colados */
        font-size: 0.95rem;
        transition: color 0.2s;
    }

    /* RESPONSIVIDADE (Telemóveis e ecrãs pequenos) */
    @media (max-width: 768px) {
        .hamburger-toggle {
            display: flex; /* Ativa o botão */
        }

        .nav-left .menu {
            position: fixed;
            top: 0;
            left: -100%; /* Agora esconde e surge do lado ESQUERDO */
            width: 280px;
            height: 100vh;
            background: #0f172a;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            flex-direction: column;
            align-items: flex-start !important;
            justify-content: flex-start;
            padding: 90px 24px 30px 24px;
            gap: 16px !important; /* Distância confortável entre as linhas do menu mobile */
            transition: left 0.3s ease-in-out;
            z-index: 1000;
            box-shadow: 5px 0 25px rgba(0,0,0,0.5);
        }

        /* Abre vindo da esquerda */
        .nav-left .menu.open {
            left: 0;
        }

        .nav-left .menu a {
            font-size: 1.1rem;
            width: 100%;
            padding: 12px 16px;
            box-sizing: border-box;
            display: block;
        }
    }
</style>

<nav class="navbar navbar-fixed">
    <div class="nav-left" style="display: flex; align-items: center;">
        
        <button class="hamburger-toggle" id="hamburgerBtn" aria-label="Abrir menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="logo" style="font-size: 1.2rem; margin-right: 25px;"><strong>B</strong> BiblioBase</div>
        
        <div class="menu" id="navMenu" style="display: flex; align-items: center; gap: 12px;">
            <a href="index.php" class="<?= $pagina_atual === 'index.php' ? 'active' : ''; ?>">Catálogo</a>
            <a href="sobre.php" class="<?= $pagina_atual === 'sobre.php' ? 'active' : ''; ?>">Sobre</a>
            
            <?php if ($está_logado): ?>
                <a href="emprestimos.php" class="<?= $pagina_atual === 'emprestimos.php' ? 'active' : ''; ?>">Empréstimos</a>
                
                <?php if ((int)($_SESSION['utilizador_tipo'] ?? 0) === 1): ?>
                    <a href="admin.php" style="color: #60a5fa; font-weight: 600; margin-left: 10px;" class="<?= $pagina_atual === 'admin.php' ? 'active' : ''; ?>"> Administração</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="nav-right" style="display: flex; align-items: center;">
        <?php if ($está_logado): ?>
            <div class="profile-box" style="display: flex; align-items: center; gap: 16px;">
                <a href="perfil.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
                    <div class="user-avatar" style="width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: #1e293b; color: white; font-weight: 600;"><?= $inicial_user; ?></div>
                    <span class="user-name" style="font-weight: 500; color: #f8fafc;"><?= htmlspecialchars($nome_user); ?></span>
                </a>
                
                <span style="color: #334155;" class="nav-divider">|</span>
                <a href="logout.php" class="logout-link" style="font-size: 0.9rem; color: #ef4444; text-decoration: none; font-weight: 500; padding: 4px 8px;">Sair</a>
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