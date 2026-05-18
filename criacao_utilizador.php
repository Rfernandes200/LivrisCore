<?php
// 1. Incluir a ligação à base de dados logo no início ou antes de usar o $pdo
require 'config.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Receber os dados do formulário
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // --- VALIDAÇÕES ---

    // 1. Verificar se o nome está em branco
    if (empty($nome)) {
        header("Location: registo.php?erro=O nome é obrigatório.");
        exit();
    }

    // 2. Verificar se o email é válido
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: registo.php?erro=Email inválido.");
        exit();
    }

    // 3. Verificar se a pass tem pelo menos 8 caracteres
    if (strlen($password) < 8) {
        header("Location: registo.php?erro=A palavra-passe deve ter pelo menos 8 caracteres.");
        exit();
    }

    // --- PROCESSAMENTO ---

    try {
        // Criar o hash seguro da password
        $password_segura = password_hash($password, PASSWORD_DEFAULT);

        // Preparar o SQL para inserir o utilizador
        // Usamos 'utilizador' como valor padrão para a coluna 'tipo'
        $sql = "INSERT INTO utilizadores (nome, email, password_hash, tipo) VALUES (:nome, :email, :pass, 'utilizador')";
        $stmt = $pdo->prepare($sql);
        
        // Bind dos parâmetros (mais seguro)
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':pass', $password_segura);
        
        if ($stmt->execute()) {
            // Se correr bem, redireciona para o login com uma mensagem de sucesso
            header("Location: login.php?sucesso=Conta criada com sucesso! Faça login.");
            exit();
        } else {
            header("Location: registo.php?erro=Erro ao criar conta.");
            exit();
        }

    } catch (PDOException $e) {
        // Verificar se o erro é de email duplicado
        if ($e->getCode() == 23000) {
            header("Location: registo.php?erro=Este email já está registado.");
        } else {
            header("Location: registo.php?erro=Erro técnico na base de dados.");
        }
        exit();
    }
} else {
    header("Location: registo.php");
    exit();
}