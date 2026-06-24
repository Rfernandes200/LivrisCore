<?php
session_start();
require '../config.php';

// 1. Bloqueio de Segurança: Garante que o utilizador está logado
if (!isset($_SESSION['utilizador_id'])) {
    $_SESSION['alerta'] = [
        'tipo' => 'erro',
        'mensagem' => '⚠️ A sua sessão expirou. Por favor, faça login novamente.'
    ];
    header("Location: ../index.php");
    exit();
}

$id_utilizador = (int)$_SESSION['utilizador_id'];
$id_livro = isset($_POST['livro_id']) ? (int)$_POST['livro_id'] : 0;

if ($id_livro <= 0) {
    $_SESSION['alerta'] = [
        'tipo' => 'erro',
        'mensagem' => ' Erro: Livro inválido.'
    ];
    header("Location: ../index.php");
    exit();
}

try {
    // Iniciar Transação para garantir segurança nas duas tabelas
    $pdo->beginTransaction();

    // 2. Verifica se a reserva pendente realmente pertence a este utilizador para este livro
    $stmt_check = $pdo->prepare("SELECT id FROM reservas WHERE livro_id = :livro_id AND utilizador_id = :user_id AND status = 'pendente'");
    $stmt_check->execute([
        'livro_id' => $id_livro,
        'user_id'  => $id_utilizador
    ]);
    $reserva = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) {
        throw new Exception("Não foi encontrada nenhuma reserva ativa sua para este livro.");
    }

    // 3. Atualiza o estado da reserva para 'cancelada' (ou podes fazer DELETE se preferires apagar)
    $stmt_del = $pdo->prepare("UPDATE reservas SET status = 'cancelada' WHERE id = :reserva_id");
    $stmt_del->execute(['reserva_id' => $reserva['id']]);

    // 4. Liberta o livro colocando-o novamente como 'disponivel' na tabela correta
    $stmt_livro = $pdo->prepare("UPDATE livros SET estado = 'disponivel' WHERE id = :livro_id");
    $stmt_livro->execute(['livro_id' => $id_livro]);

    // Confirmar alterações na BD
    $pdo->commit();

    $_SESSION['alerta'] = [
        'tipo' => 'sucesso',
        'mensagem' => ' Reserva cancelada com sucesso. O livro voltou a ficar disponível!'
    ];

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['alerta'] = [
        'tipo' => 'erro',
        'mensagem' => 'Erro ao cancelar reserva: ' . $e->getMessage()
    ];
}

// Redireciona de volta para a página principal
header("Location: ../index.php");
exit();