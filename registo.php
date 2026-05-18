<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Criar Conta - BiblioBase</title>
    <link rel="stylesheet" href="Styles/StyleRegistro.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Playfair+Display:italic,wght@700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-screen">
        <div class="login-sidebar">
            <div class="sidebar-content">
                <h2>"O leitor que não lê não é melhor do que o que não sabe ler."</h2>
                <p>— Mark Twain</p>
                <div class="icons">📚 🎵 🎬</div>
            </div>
        </div>

        <div class="login-main">
            <div class="login-card">
                <h1>Criar conta</h1>
                <p class="subtitle">Junte-se à BiblioBase e comece a explorar o catálogo.</p>
                
                <!-- Exibir mensagens de erro vindas do PHP, se existirem -->
                <?php if(isset($_GET['erro'])): ?>
                    <div style="color: #ef4444; background: #fee2e2; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;">
                        <?php echo htmlspecialchars($_GET['erro']); ?>
                    </div>
                <?php endif; ?>

                <form action="criacao_utilizador.php" method="POST">
                    <label>Nome completo</label>
                    <!-- required garante que não fica em branco -->
                    <input type="text" name="nome" class="input-control" placeholder="João Silva" required>
                    
                    <label>Email</label>
                    <!-- type="email" valida automaticamente o @ e o ponto -->
                    <input type="email" name="email" class="input-control" placeholder="exemplo@email.com" required>
                    
                    <label>Palavra-passe</label>
                    <!-- minlength="8" garante o mínimo de caracteres -->
                    <input type="password" name="password" class="input-control" placeholder="Mínimo 8 caracteres" minlength="8" required>
                    
                    <button type="submit" class="btn-submit">Criar conta</button>
                </form>
                
                <div class="footer-link">
                    Já tem conta? <a href="login.php">Iniciar sessão →</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>