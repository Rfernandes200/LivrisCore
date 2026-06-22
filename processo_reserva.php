<?php
session_start();
require 'config.php';

// 1. Bloqueio de Segurança: Garante que o utilizador está logado
if (!isset($_SESSION['utilizador_id'])) {
    $_SESSION['alerta'] = [
        'tipo' => 'erro',
        'mensagem' => '⚠️ A sua sessão expirou ou não efetuou o login. Por favor, entre na sua conta para reservar.'
    ];
    header("Location: index.php");
    exit();
}

$id_utilizador = (int)$_SESSION['utilizador_id'];
$id_livro = isset($_POST['livro_id']) ? (int)$_POST['livro_id'] : 0;

// Valida se o ID do livro é aceitável
if ($id_livro <= 0) {
    $_SESSION['alerta'] = [
        'tipo' => 'erro',
        'mensagem' => '❌ Erro: Artigo inválido para reserva.'
    ];
    header("Location: index.php");
    exit();
}

try {
    // 2. Verifica se o livro existe e se está mesmo disponível no catálogo
    $stmt = $pdo->prepare("SELECT estado, titulo FROM livros WHERE id = :id");
    $stmt->execute(['id' => $id_livro]);
    $livro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$livro || strtolower(trim($livro['estado'])) !== 'disponivel') {
        $_SESSION['alerta'] = [
            'tipo' => 'erro',
            'mensagem' => '❌ Este livro já não se encontra disponível para reserva.'
        ];
        header("Location: index.php");
        exit();
    }

    // 3. ALTERADO: Gerar o código de levantamento aleatório com EXATAMENTE 3 dígitos (ex: 042, 789)
    $codigo_reserva = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);

    // Iniciar Transação SQL para garantir consistência (ou faz tudo ou não faz nada)
    $pdo->beginTransaction();

    // 4. Inserir a reserva usando os nomes de colunas exatos da tua base de dados
    $sql_reserva = "INSERT INTO reservas (utilizador_id, livro_id, data_inicio, status, codigo_validacao) 
                    VALUES (:user, :livro, NOW(), 'pendente', :codigo)";
    
    $stmt_reserva = $pdo->prepare($sql_reserva);
    $stmt_reserva->execute([
        'user'   => $id_utilizador,
        'livro'  => $id_livro,
        'codigo' => $codigo_reserva
    ]);

    // 5. Atualizar o estado do livro para 'reservado' na tabela principal
    $stmt_update = $pdo->prepare("UPDATE livros SET estado = 'reservado' WHERE id = :id");
    $stmt_update->execute(['id' => $id_livro]);

    // Confirmar todas as operações no banco de dados
    $pdo->commit();

    // 6. Guardar o código gerado na sessão para que o modal do 'index.php' o possa exibir
    $_SESSION['reserva_sucesso_codigo'] = $codigo_reserva;
    
    $_SESSION['alerta'] = [
        'tipo' => 'sucesso',
        'mensagem' => '🎉 Livro "' . htmlspecialchars($livro['titulo']) . '" reservado com sucesso!'
    ];

} catch (Exception $e) {
    // Se algo falhar, desfaz as alterações para não corromper os dados
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['alerta'] = [
        'tipo' => 'erro',
        'mensagem' => '❌ Erro ao processar reserva: ' . $e->getMessage()
    ];
}

// Redireciona sempre de volta para a página inicial
header("Location: index.php");
exit();