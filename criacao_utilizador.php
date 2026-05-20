<?php
// 1. Incluir a ligação à base de dados
require 'config.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Receber e limpar espaços inúteis nas extremidades
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Aplicamos trim também na password para capturar se enviaram apenas espaços vazios
    $password_bruta = $_POST['password'] ?? '';
    $password_limpa = trim($password_bruta);

    // --- VALIDAÇÕES PERSONALIZADAS ---

    // 1. Proteção para Nome em branco ou só com espaços
    if (empty($nome)) {
        header("Location: registo.php?erro=O nome é obrigatório.");
        exit();
    }

    // 2. Verificar se o email é válido (Filtro nativo que exige o formato "texto@dominio.algo")
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: registo.php?erro=O email não é válido e precisa de conter \"@\" e depois \".\"");
        exit();
    }

    // 3. Verificar se a password está totalmente vazia (ou continha apenas espaços)
    if ($password_bruta === '' || empty($password_limpa)) {
        header("Location: registo.php?erro=A password não pode estar vazia");
        exit();
    }

    // 4. Verificar o tamanho mínimo seguro da password
    if (strlen($password_bruta) < 8) {
        header("Location: registo.php?erro=A password é inválida e precisa de ter 8 caracteres ou mais");
        exit();
    }

    // --- PROCESSAMENTO SEGURO ---

    try {
        // Criar o hash seguro usando a password original
        $password_segura = password_hash($password_bruta, PASSWORD_DEFAULT);

        // Preparar o SQL para inserir o utilizador
        $sql = "INSERT INTO utilizadores (nome, email, password_hash, tipo) VALUES (:nome, :email, :pass, 'utilizador')";
        $stmt = $pdo->prepare($sql);
        
        // Bind dos parâmetros
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':pass', $password_segura);
        
        if ($stmt->execute()) {
            // Conta criada com sucesso! Redireciona para o login
            header("Location: login.php?sucesso=Conta criada com sucesso! Faça login.");
            exit();
        } else {
            header("Location: registo.php?erro=Erro ao criar conta.");
            exit();
        }

    } catch (PDOException $e) {
        // Verificar violação de chave única (Email já registado)
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