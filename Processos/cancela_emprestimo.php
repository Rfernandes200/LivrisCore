<?php
session_start();
require '../config.php';

// 1. Bloqueio de Segurança: Apenas administradores/funcionários podem cancelar
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emprestimo_id = (int)($_POST['emprestimo_id'] ?? 0);

    if ($emprestimo_id <= 0) {
        $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Empréstimo inválido.'];
        header("Location: ../admin.php?seccao=emprestimos");
        exit();
    }

    try {
        // Iniciar uma transação para garantir que ou faz tudo bem ou não faz nada
        $pdo->beginTransaction();

        // 2. Descobrir qual é o livro associado a este empréstimo antes de o apagar
        $stmt_busca = $pdo->prepare("SELECT livro_id FROM emprestimos WHERE id = :id");
        $stmt_busca->execute(['id' => $emprestimo_id]);
        $emprestimo = $stmt_busca->fetch(PDO::FETCH_ASSOC);

        if (!$emprestimo) {
            $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'O registo de empréstimo não foi encontrado.'];
            $pdo->rollBack();
            header("Location: ../admin.php?seccao=emprestimos");
            exit();
        }

        $livro_id = $emprestimo['livro_id'];

        // 3. Libertar o livro no catálogo
        // NOTA: Se o teu sistema usar uma coluna "disponivel = 1", usa o UPDATE 1.
        // Se usar contagem de stock físico, usa o UPDATE 2. Deixei o mais comum ativo:
        
        // 3. Libertar o livro no catálogo mudando o seu estado para disponível
        $stmt_livro = $pdo->prepare("UPDATE livros SET estado = 'disponivel' WHERE id = :livro_id");
        $stmt_livro->execute(['livro_id' => $livro_id]);
        // 4. Apagar o empréstimo da tabela (ou mudar o estado para 'cancelado')
        // Se preferires guardar histórico, muda para: UPDATE emprestimos SET estado = 'cancelado' WHERE id = :id
        $stmt_delete = $pdo->prepare("DELETE FROM emprestimos WHERE id = :id");
        $stmt_delete->execute(['id' => $emprestimo_id]);

        // Confirmar todas as alterações na Base de Dados
        $pdo->commit();

        // Redireciona com o status de sucesso para ativar o banner que pusemos no ficheiro anterior
        header("Location: ../admin.php?seccao=emprestimos&status=success_cancelamento");
        exit();

    } catch (PDOException $e) {
        // Se algo falhar, desfaz as alterações para não corromper o stock
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Erro ao cancelar o empréstimo: ' . $e->getMessage()];
        header("Location: ../admin.php?seccao=emprestimos");
        exit();
    }
} else {
    header("Location: ../admin.php?seccao=emprestimos");
    exit();
}