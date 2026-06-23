<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['utilizador_id'])) {
    $id = $_SESSION['utilizador_id'];

    try {
        // Validação final de segurança: impede se houver algum empréstimo não devolvido
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM emprestimos WHERE utilizador_id = :id AND data_devolucao_real IS NULL");
        $stmt_check->execute(['id' => $id]);
        $total_pendente = (int)$stmt_check->fetchColumn();

        if ($total_pendente > 0) {
            header("Location: perfil.php?erro=Não pode eliminar a sua conta porque ainda tem empréstimos ativos.");
            exit();
        }

        // Executa a remoção do utilizador. O MySQL encarrega-se de limpar reservas e empréstimos antigos via CASCADE.
        $stmt_delete = $pdo->prepare("DELETE FROM utilizadores WHERE id = :id");
        $stmt_delete->execute(['id' => $id]);

        // Termina a sessão
        session_unset();
        session_destroy();

        header("Location: login.php?sucesso=A sua conta foi eliminada permanentemente.");
        exit();

    } catch (Exception $e) {
        header("Location: perfil.php?erro=Erro ao processar a eliminação na base de dados.");
        exit();
    }
} else {
    header("Location: perfil.php");
    exit();
}