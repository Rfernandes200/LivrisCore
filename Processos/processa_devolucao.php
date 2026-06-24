<?php
session_start();
require '../config.php';

// Bloqueio de Segurança para o Admin
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    exit('Acesso negado');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['emprestimo_id'])) {
    $emprestimo_id = (int)$_POST['emprestimo_id'];

    try {
        $pdo->beginTransaction();

        // Verificar se o empréstimo existe e ainda não foi devolvido
        $stmt = $pdo->prepare("SELECT livro_id FROM emprestimos WHERE id = :id AND data_devolucao_real IS NULL");
        $stmt->execute(['id' => $emprestimo_id]);
        $emprestimo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($emprestimo) {
            // 1. Regista a data e hora atual como devolução real
            $stmt_devolucao = $pdo->prepare("UPDATE emprestimos SET data_devolucao_real = NOW() WHERE id = :id");
            $stmt_devolucao->execute(['id' => $emprestimo_id]);

            // 2. Atualiza o estado do livro de volta para 'disponivel'
            $stmt_item = $pdo->prepare("UPDATE livros SET estado = 'disponivel' WHERE id = :livro_id");
            $stmt_item->execute(['livro_id' => $emprestimo['livro_id']]);

            $pdo->commit();
            header("Location: ../admin.php?seccao=emprestimos&status=success_devolucao");
            exit();
        } else {
            $pdo->rollBack();
            exit('Empréstimo inválido ou já finalizado.');
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        exit('Erro ao processar a devolução: ' . $e->getMessage());
    }
} else {
    header("Location: ../admin.php?seccao=emprestimos");
    exit();
}