<?php
session_start();
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['utilizador_id'])) {
    $id = $_SESSION['utilizador_id'];
    $nome = trim($_POST['nome']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $nova_pw = $_POST['nova_pw'];
    $confirma_pw = $_POST['confirma_pw'];

    // 1. Validações Básicas
    if (empty($nome) || empty($email)) {
        header("Location: perfil.php?erro=Nome e Email são obrigatórios.");
        exit();
    }

    try {
        // 2. Atualizar Nome e Email
        $stmt = $pdo->prepare("UPDATE utilizadores SET nome = ?, email = ? WHERE id = ?");
        $stmt->execute([$nome, $email, $id]);
        
        // Atualiza o nome na sessão para refletir na navbar imediatamente
        $nomes = explode(' ', $nome);
        $_SESSION['utilizador_nome'] = $nomes[0];

        // 3. Validação de Password (se preenchida)
        if (!empty($nova_pw)) {
            if (strlen($nova_pw) < 8) {
                header("Location: perfil.php?erro=A password deve ter pelo menos 8 caracteres.");
                exit();
            }
            if ($nova_pw !== $confirma_pw) {
                header("Location: perfil.php?erro=As passwords não coincidem.");
                exit();
            }
            
            $hash = password_hash($nova_pw, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE utilizadores SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $id]);
        }

        header("Location: perfil.php?sucesso=Perfil atualizado com sucesso!");
        exit();

    } catch (PDOException $e) {
        header("Location: perfil.php?erro=Erro ao atualizar: " . $e->getMessage());
        exit();
    }
}