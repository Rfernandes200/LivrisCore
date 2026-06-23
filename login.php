<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar - BiblioBase</title>
    <link rel="stylesheet" href="Styles/StylesIndex.css">   
</head>
<body class="auth-body">

<div class="login-screen">
    <div class="login-sidebar">
        <div class="sidebar-logo">
             <strong>B</strong> BiblioBase
        </div>

        <div class="quote-container">
            <div class="quote-text">
                "Uma biblioteca é um hospital para a mente."
            </div>
            <div class="quote-author">— Anónimo</div>
            <div class="sidebar-icons">
                
            </div>
        </div>
    </div>

    <div class="login-main">
        <div class="login-content-wrapper">
            <a href="index.php" class="back-link">← Voltar ao início</a>
            
            <h1>Bem-vindo de volta</h1>
            <p class="subtitle">Inicie sessão para aceder ao seu catálogo e empréstimos.</p>

            <?php if (isset($_GET['erro'])): ?>
                <div style="color: #ef4444; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 500;">
                    <?php echo htmlspecialchars($_GET['erro']); ?>
                </div>
            <?php endif; ?>

            <form action="processo_login.php" method="POST">
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" class="input-control" placeholder="exemplo@email.com" required>
                </div>

                <div class="input-group">
                    <label>Palavra-passe</label>
                    <input type="password" name="password" class="input-control" placeholder="••••••••" required>
                </div>

                

                <button type="submit" class="btn-login-submit">Entrar</button>
            </form>

            <div class="divider">
                <span>ou</span>
            </div>

            <p class="signup-text">
                Não tem conta? <a href="registo.php">Criar conta</a>
            </p>
        </div>
    </div>
</div>

</body>
</html>