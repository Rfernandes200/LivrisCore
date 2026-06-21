<?php
session_start();
require 'config.php';

if (!isset($_SESSION['utilizador_id'])) {
    header("Location: login.php");
    exit();
}

$id = $_SESSION['utilizador_id'];

// CORREÇÃO: Busca os dados certos incluindo o telemovel usando as colunas exatas da BD
try {
    $stmt = $pdo->prepare("SELECT nome, email, telemovel, tipo, data_registo FROM utilizadores WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Caso ocorra alguma falha, mantém um plano de contingência seguro
    $stmt = $pdo->prepare("SELECT nome, email, telemovel, tipo FROM utilizadores WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $user['data_registo'] = date('Y-m-d H:i:s'); 
}

$inicial = strtoupper(substr($user['nome'] ?? 'U', 0, 1));
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - BiblioBase</title>
    <link rel="stylesheet" href="Styles/StylesIndex.css">
    <link rel="stylesheet" href="Styles/StylePerfil.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="perfil-body">

    <div class="perfil-header">
        <nav class="navbar-perfil">
            <a href="index.php" class="logo"><strong>B</strong> BiblioBase</a>
            
            <div style="display: flex; gap: 15px; align-items: center;">
                <?php if (isset($user['tipo']) && ($user['tipo'] == 'admin' || (int)$user['tipo'] === 1)): ?>
                    <a href="admin.php" style="color: #60a5fa; text-decoration: none; font-size: 0.9rem; font-weight: 600;">⚡ Voltar ao Painel</a>
                <?php endif; ?>
                
                <a href="index.php" style="color: #60a5fa; text-decoration: none; font-size: 0.9rem; font-weight: 600;">Voltar ao Inicio</a> 
                <a href="logout.php" class="logout-btn">Sair</a>
            </div>
        </nav>

        <div class="perfil-banner-content">
            <div class="avatar-circle"><?php echo $inicial; ?></div>
            <div class="user-info-header">
                <h1><?php echo htmlspecialchars($user['nome'] ?? ''); ?></h1>
                <p><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                <div class="badges">
                    <?php if (isset($user['tipo']) && ($user['tipo'] == 'admin' || (int)$user['tipo'] === 1)): ?>
                        <span class="badge-role" style="background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);">⚡ Administrador</span>
                    <?php else: ?>
                        <span class="badge-role">🔰 Utilizador</span>
                    <?php endif; ?>

                    <span class="badge-date">Membro desde <?php echo date('d/m/Y', strtotime($user['data_registo'] ?? 'now')); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="perfil-main-content">
        <div class="edit-card">
            <h2>Editar perfil</h2>
            
            <?php if (isset($_GET['erro'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_GET['erro']); ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['sucesso'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['sucesso']); ?></div>
            <?php endif; ?>
            
            <p class="subtitle">Atualize as suas informações pessoais</p>

            <form action="atualiza_perfil.php" method="POST">
                <div class="input-group">
                    <label>Nome completo</label>
                    <input type="text" name="nome" value="<?php echo htmlspecialchars($user['nome'] ?? ''); ?>" required>
                </div>

                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>

                <div class="input-group">
                    <label>Telemóvel</label>
                    <input 
                        type="tel" 
                        name="telemovel" 
                        value="<?php echo htmlspecialchars($user['telemovel'] ?? ''); ?>" 
                        placeholder="9xxxxxxxx" 
                        pattern="[0-9]{9}" 
                        maxlength="9"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9)"
                        title="O número de telemóvel deve ter exatamente 9 dígitos numéricos.">
                </div>

                <hr class="divider">
                <p class="password-hint">Deixe em branco para manter a palavra-passe atual</p>

                <div class="input-group">
                    <label>Nova palavra-passe</label>
                    <div class="pw-wrapper">
                        <input type="password" name="nova_pw" placeholder="Mínimo 8 caracteres">
                    </div>
                </div>

                <div class="input-group">
                    <label>Confirmar nova palavra-passe</label>
                    <div class="pw-wrapper">
                        <input type="password" name="confirma_pw" placeholder="Repetir palavra-passe">
                    </div>
                </div>

                <button type="submit" class="btn-save">Guardar alterações</button>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('.eye-icon').forEach(icon => {
            icon.addEventListener('click', function() {
                const input = this.previousElementSibling;
                if (input.type === 'password') {
                    input.type = 'text';
                    this.textContent = '🙈';
                } else {
                    input.type = 'password';
                    this.textContent = '👁️';
                }
            });
        });
    </script>

</body>
</html>