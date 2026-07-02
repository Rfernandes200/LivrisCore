<?php
// Inicia a sessão para podermos guardar os dados do utilizador
session_start();

// Importa a ligação à base de dados
require '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Receber e limpar os dados enviados pelo formulário
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Verificar se os campos não estão vazios
    if (empty($email) || empty($password)) {
        header("Location: ../login.php?erro=Preencha todos os campos.");
        exit();
    }

    try {
        // CORREÇÃO: Adicionada a coluna 'ativo' no SELECT
        $stmt = $pdo->prepare("SELECT id, nome, password_hash, tipo, ativo FROM utilizadores WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Validar se o utilizador existe e se a password coincide (usando hash seguro)
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // ==========================================================
            // [NOVA VALIDAÇÃO]: VERIFICAR SE A CONTA ESTÁ ATIVA
            // ==========================================================
            if ((int)$user['ativo'] !== 1) {
                header("Location: ../login.php?erro=A sua conta está inativa. Contacte o administrador.");
                exit();
            }
            // ==========================================================

            // --- LOGIN COM SUCESSO ---

            // Extrair apenas o primeiro nome (ex: "Rodrigo Silva" -> "Rodrigo")
            $nomes = explode(' ', trim($user['nome']));
            $primeiroNome = $nomes[0];

            // Guardar informações do utilizador na sessão
            $_SESSION['utilizador_id'] = $user['id'];
            $_SESSION['utilizador_nome'] = $primeiroNome;
            
            // MELHORIA: Forçar o tipo a ser guardado como Número Inteiro (0 ou 1)
            $_SESSION['utilizador_tipo'] = (int)$user['tipo'];

            // Redirecionar para a página principal
            header("Location: ../index.php");
            exit();

        } else {
            // --- LOGIN FALHOU ---
            header("Location: ../login.php?erro=Email ou palavra-passe incorretos.");
            exit();
        }

    } catch (PDOException $e) {
        // Erro técnico de base de dados
        header("Location: ../login.php?erro=Ocorreu um erro. Tente novamente mais tarde.");
        exit();
    }

} else {
    // Se tentarem aceder ao ficheiro diretamente sem POST
    header("Location: ../login.php");
    exit();
}