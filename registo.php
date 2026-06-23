<!DOCTYPE html>
<html lang="pt"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - BiblioBase</title>
    <link rel="stylesheet" href="Styles/StyleRegistro.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        
    </style>
</head>
<body>
    <div class="login-screen">
        
        <div class="login-sidebar">
            <div class="sidebar-brand">
                <span class="brand-box">B</span> BiblioBase
            </div>
            <div class="sidebar-content">
                <h2>"O leitor que não lê não é melhor do que o que não sabe ler."</h2>
                <p>— Mark Twain</p>
                <div class="icons">📚 💿 🎬</div>
            </div>
        </div>

        <div class="login-main">
            <div class="login-content-wrapper">
                
                <div class="top-navigation">
                    <p class="nav-serif-text">
                        Deseja navegar? <a href="index.php">Voltar ao início</a>
                    </p>
                </div>

                <h1>Criar conta</h1>
                <p class="subtitle">Junte-se à BiblioBase e comece a explorar o catálogo.</p>
                
                <?php if(isset($_GET['erro'])): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($_GET['erro']); ?>
                    </div>
                <?php endif; ?>

                <form action="criacao_utilizador.php" method="POST">
                    <div class="form-group">
                        <label>Nome completo</label>
                        <input type="text" name="nome" class="input-control" placeholder="João Silva" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="input-control" placeholder="exemplo@email.com" required>
                    </div>

                    <div class="form-group">
                        <label>Telemóvel</label>
                        <input 
                            type="tel" 
                            name="telemovel" 
                            class="input-control" 
                            placeholder="9xxxxxxxx" 
                            required
                            pattern="[0-9]{9}" 
                            maxlength="9"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9)"
                            title="O número de telemóvel deve ter exatamente 9 dígitos numéricos.">
                    </div>
                    
                    <div class="form-group">
                        <label>Palavra-passe</label>
                        <input type="password" name="password" class="input-control" placeholder="Mínimo 8 caracteres" required>
                    </div>
                    
                    <button type="submit" class="btn-submit">Criar conta</button>
                </form>

                <div class="bottom-navigation">
                    <p class="nav-serif-text">
                        Já tem conta? <a href="login.php">Iniciar sessão</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>