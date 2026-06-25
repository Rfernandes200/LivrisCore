<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="pt"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar - LivrisCore</title>
    <link rel="stylesheet" href="Styles/StyleRegistro.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    
</head>
<body>
    <div class="login-screen">
        
        <div class="login-sidebar">
            <div class="sidebar-brand">
                <span class="brand-box">L</span> LivrisCore
            </div>
            <div class="sidebar-content">
                <h2>"Uma biblioteca é um hospital para a mente."</h2>
                <p>— Anónimo</p>
                
            </div>
        </div>

        <div class="login-main">
            <div class="login-content-wrapper">
                
                <div class="top-navigation">
                    <p class="nav-text">
                        Deseja navegar? <a href="index.php">Voltar ao início</a>
                    </p>
                </div>

                <h1>Bem-vindo de volta</h1>
                <p class="subtitle">Inicie sessão para aceder ao seu catálogo e empréstimos.</p>
                
                <?php if(isset($_GET['erro'])): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($_GET['erro']); ?>
                    </div>
                <?php endif; ?>

                <form action="Processos/processo_login.php" method="POST">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="input-control" placeholder="exemplo@email.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Palavra-passe</label>
                        <input type="password" name="password" class="input-control" placeholder="Mínimo 8 caracteres" required>
                    </div>
                    
                    <button type="submit" class="btn-submit">Entrar</button>
                </form>

                <div class="bottom-navigation">
                    <p class="nav-text">
                        Não tem conta? <a href="registo.php">Criar conta</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>