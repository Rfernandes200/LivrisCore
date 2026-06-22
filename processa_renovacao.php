<?php
session_start();
require 'config.php';

// Bloqueio de Segurança para o Admin
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    exit('Acesso negado');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['emprestimo_id'])) {
    $emprestimo_id = (int)$_POST['emprestimo_id'];

    try {
        // 1. Buscar a data limite atual do empréstimo
        $stmt_busca = $pdo->prepare("SELECT data_prevista_devolucao FROM emprestimos WHERE id = :id AND data_devolucao_real IS NULL");
        $stmt_busca->execute(['id' => $emprestimo_id]);
        $emprestimo = $stmt_busca->fetch(PDO::FETCH_ASSOC);

        if ($emprestimo) {
            // Calcula a nova data com base no prazo atual
            $data_atual_limite = $emprestimo['data_prevista_devolucao'];
            $nova_data_fim = date('Y-m-d', strtotime($data_atual_limite . ' + 14 days'));

            // 2. Atualiza o registo com a nova data calculada
            $stmt_update = $pdo->prepare("UPDATE emprestimos SET data_prevista_devolucao = :nova_data WHERE id = :id");
            $stmt_update->execute([
                'nova_data' => $nova_data_fim,
                'id' => $emprestimo_id
            ]);

            header("Location: admin.php?seccao=emprestimos&status=success_renovacao");
            exit();
        } else {
            exit('Registo de empréstimo não encontrado ou já se encontra devolvido.');
        }

    } catch (PDOException $e) {
        exit("Erro ao processar renovação: " . $e->getMessage());
    }
} else {
    header("Location: admin.php?seccao=emprestimos");
    exit();
}