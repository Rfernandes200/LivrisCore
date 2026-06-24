<?php
session_start();
require 'config.php';

if (!isset($_SESSION['utilizador_id'])) {
    header("Location: login.php");
    exit();
}

$id = $_SESSION['utilizador_id'];

// 1. VERIFICAÇÃO DE EMPRÉSTIMOS ATIVOS
$pode_eliminar = true;
$total_pendente = 0;

try {
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM emprestimos WHERE utilizador_id = :id AND data_devolucao_real IS NULL");
    $stmt_check->execute(['id' => $id]);
    $total_pendente = (int)$stmt_check->fetchColumn();
    
    if ($total_pendente > 0) {
        $pode_eliminar = false;
    }
} catch (Exception $e) {
    $pode_eliminar = false;
}

// Lógica de busca dos dados do utilizador
try {
    $stmt = $pdo->prepare("SELECT nome, email, telemovel, tipo, data_registo FROM utilizadores WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
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
    
   
    <link rel="stylesheet" href="Styles/StylePerfil.css">
     <link rel="stylesheet" href="Styles/StyleNav.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
</head>
<body class="perfil-body">

    <?php require 'navbar.php'; ?>

    <div class="perfil-header" style="margin-top: 70px;">
        <div class="perfil-banner-content">
            <div class="avatar-circle"><?php echo $inicial; ?></div>
            <div class="user-info-header">
                <h1><?php echo htmlspecialchars($user['nome'] ?? ''); ?></h1>
                <p><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                <div class="badges">
                    <?php if (isset($user['tipo']) && ($user['tipo'] == 'admin' || (int)$user['tipo'] === 1)): ?>
                        <span class="badge-role" style="background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);">Administrador</span>
                    <?php else: ?>
                        <span class="badge-role">Utilizador</span>
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

            <form action="Processos/atualiza_perfil.php" method="POST">
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

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; flex-wrap: wrap; gap: 15px; width: 100%;">
    
    <button type="submit" class="btn-save" style="background: #0f172a; color: white; padding: 14px 30px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s;">
        Guardar alterações
    </button>
    
    <?php if (!$pode_eliminar): ?>
        <button type="button" class="btn-delete" disabled title="Ação Bloqueada: Possui <?= $total_pendente; ?> empréstimo(s) ativo(s). Devolva os livros primeiro." 
                style="background: #cbd5e1; color: #94a3b8; border: none; padding: 12px 24px; border-radius: 8px; font-size: 0.95rem; font-weight: 600; cursor: not-allowed; transition: all 0.2s;">
            Eliminar a minha conta
        </button>
    <?php else: ?>
        <button type="button" class="btn-delete" onclick="dispararExclusao();" 
                style="background: #ef4444; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-size: 0.95rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">
            Eliminar a minha conta
        </button>
    <?php endif; ?>
    
</div>
            </form>

            <form id="formDeletarConta" action="Processos/eliminar_conta.php" method="POST" style="display: none;"></form>

        </div>
    </div>

    <script>
        function dispararExclusao() {
            if (confirm("Tem a certeza absoluta de que deseja eliminar a sua conta? Esta ação NÃO pode ser desfeita e perderá o acesso ao sistema!")) {
                document.getElementById('formDeletarConta').submit();
            }
        }
    </script>

</body>
</html>