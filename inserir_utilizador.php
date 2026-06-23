<?php
session_start();
require 'config.php';

// Proteção: Garante que apenas administradores logados podem aceder a este script
// Se o teu sistema usa (int)$_SESSION['tipo'] === 1 para admin, mantém esta validação
if (!isset($_SESSION['utilizador_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanatização e receção dos dados do formulário do modal
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $telemovel = trim($_POST['telemovel']) ?: null; // Se estiver vazio, grava como NULL
    $tipo_raw = $_POST['tipo'];
    $password = $_POST['password'];

    // Conversão do tipo de string para tinyint(1) compatível com a tua BD
    // (admin -> 1, user -> 0)
    $tipo = ($tipo_raw === 'admin') ? 1 : 0;

    // Validações básicas de segurança
    if (empty($nome) || empty($email) || empty($password)) {
        header("Location: admin.php?erro=Por favor, preencha todos os campos obrigatórios.");
        exit();
    }

    try {
        // 1. Verificar se o e-mail já existe registado na BD para evitar duplicados
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM utilizadores WHERE email = :email");
        $stmt_check->execute(['email' => $email]);
        
        if ((int)$stmt_check->fetchColumn() > 0) {
            header("Location: admin.php?erro=O endereço de email já está a ser utilizado por outra conta.");
            exit();
        }

        // 2. Encriptar a palavra-passe inicial usando a função nativa segura do PHP
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // 3. Inserir o novo utilizador na tabela correspondendo exatamente às tuas colunas
        $sql = "INSERT INTO utilizadores (nome, email, telemovel, password_hash, tipo, ativo) 
                VALUES (:nome, :email, :telemovel, :password_hash, :tipo, 1)";
        
        $stmt_insert = $pdo->prepare($sql);
        $stmt_insert->execute([
            'nome' => $nome,
            'email' => $email,
            'telemovel' => $telemovel,
            'password_hash' => $password_hash,
            'tipo' => $tipo
        ]);

        // Redireciona de volta para o painel de administração com mensagem de sucesso
        header("Location: admin.php?sucesso=Utilizador '" . htmlspecialchars($nome) . "' criado com sucesso!");
        exit();

    } catch (Exception $e) {
        // Redireciona com mensagem amigável em caso de falha crítica de BD
        header("Location: admin.php?erro=Erro técnico ao registar o utilizador na base de dados.");
        exit();
    }
} else {
    // Se tentarem aceder ao ficheiro diretamente sem submeter o modal, manda de volta
    header("Location: admin.php");
    exit();
}