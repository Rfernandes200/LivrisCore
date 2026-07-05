<?php
session_start();
require '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['utilizador_id'])) {
    $id = $_SESSION['utilizador_id'];
    $nome = trim($_POST['nome']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $telemovel = trim($_POST['telemovel'] ?? ''); 
    $nova_pw = $_POST['nova_pw'];
    $confirma_pw = $_POST['confirma_pw'];

    // 1. Validações Básicas
    if (empty($nome) || empty($email)) {
        header("Location: ../perfil.php?erro=Nome e Email são obrigatórios.");
        exit();
    }

    // Validação de formato: garante que se houver telemóvel, tem exatamente 9 números
    if (!empty($telemovel) && !preg_match('/^[0-9]{9}$/', $telemovel)) {
        header("Location: ../perfil.php?erro=O número de telemóvel tem de conter exatamente 9 números.");
        exit();
    }

    try {
        // ==========================================================
        // NOVA VALIDAÇÃO: Verificar se o email já existe em OUTRA conta
        // ==========================================================
        $stmt_check_email = $pdo->prepare("SELECT id FROM utilizadores WHERE email = ? AND id != ?");
        $stmt_check_email->execute([$email, $id]);
        if ($stmt_check_email->fetch()) {
            header("Location: ../perfil.php?erro=Este email já está registado noutra conta.");
            exit();
        }
        // ==========================================================

        // ==========================================================
        // NOVA VALIDAÇÃO: Verificar se o telemóvel já existe em OUTRA conta
        // ==========================================================
        if (!empty($telemovel)) {
            // Procuramos se o telemóvel existe, mas ignoramos o ID do próprio utilizador atual
            $stmt_check = $pdo->prepare("SELECT id FROM utilizadores WHERE telemovel = ? AND id != ?");
            $stmt_check->execute([$telemovel, $id]);
            
            if ($stmt_check->fetch()) {
                header("Location: ../perfil.php?erro=Este número de telemóvel já está registado noutra conta.");
                exit();
            }
        }
        // ==========================================================

        // 2. Atualizar Nome, Email E Telemóvel na Base de Dados
        $stmt = $pdo->prepare("UPDATE utilizadores SET nome = ?, email = ?, telemovel = ? WHERE id = ?");
        $stmt->execute([$nome, $email, $telemovel, $id]);
        
        // Atualiza o nome na sessão para refletir na navbar imediatamente
        $nomes = explode(' ', $nome);
        $_SESSION['utilizador_nome'] = $nomes[0];

        // 3. Validação e Atualização da Password (apenas se for preenchida)
        if (!empty($nova_pw)) {
            if (strlen($nova_pw) < 8) {
                header("Location: ../perfil.php?erro=A password deve ter pelo menos 8 caracteres.");
                exit();
            }
            if ($nova_pw !== $confirma_pw) {
                header("Location: ../perfil.php?erro=As passwords não coincidem.");
                exit();
            }
            
            $hash = password_hash($nova_pw, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE utilizadores SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $id]);
        }

        header("Location: ../perfil.php?sucesso=Perfil updated com sucesso!");
        exit();

    } catch (PDOException $e) {
        header("Location: ../perfil.php?erro=Erro ao atualizar: " . $e->getMessage());
        exit();
    }
}