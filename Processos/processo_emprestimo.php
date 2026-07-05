<?php
session_start();
require '../config.php';

// Proteção: Se não estiver logado ou não houver ação, bloqueia o acesso direto
if (!isset($_SESSION['utilizador_id']) || !isset($_POST['acao'])) {
    header("Location: ../login.php");
    exit();
}

$id_logado = (int)$_SESSION['utilizador_id'];
$acao = $_POST['acao'];

// ==========================================
// AÇÃO 1: CANCELAR RESERVA PENDENTE
// ==========================================
if ($acao === 'cancelar_reserva') {
    $reserva_id = (int)$_POST['reserva_id'];
    
    try {
        $pdo->beginTransaction();
        
        // CORREÇÃO: Alterado de item_id para livro_id
        $stmt = $pdo->prepare("SELECT livro_id FROM reservas WHERE id = :id AND utilizador_id = :user_id AND status = 'pendente'");
        $stmt->execute(['id' => $reserva_id, 'user_id' => $id_logado]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reserva) {
            // CORREÇÃO: Alterado de itens para livros e item_id para livro_id
            $stmt_item = $pdo->prepare("UPDATE livros SET estado = 'disponivel' WHERE id = :livro_id");
            $stmt_item->execute(['livro_id' => $reserva['livro_id']]);
            
            // 2. Remove a reserva pendente
            $stmt_del = $pdo->prepare("DELETE FROM reservas WHERE id = :id");
            $stmt_del->execute(['id' => $reserva_id]);
            
            $_SESSION['alerta'] = ['tipo' => 'sucesso', 'mensagem' => 'Reserva cancelada com sucesso!'];
        } else {
            $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Reserva não encontrada ou já processada.'];
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Erro ao cancelar: ' . $e->getMessage()];
    }
}

// ==========================================
// AÇÃO 2: CONFIRMAR RESERVA (TRANSFORMAR EM EMPRÉSTIMO)
// ==========================================
if ($acao === 'oficializar_emprestimo') {
    $reserva_id = (int)$_POST['reserva_id'];
    $codigo_inserido = trim($_POST['codigo_validacao']);
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];

    try {
        $pdo->beginTransaction();

        // Validar se a reserva existe e está pendente para este utilizador
        $stmt = $pdo->prepare("SELECT * FROM reservas WHERE id = :id AND utilizador_id = :user_id AND status = 'pendente'");
        $stmt->execute(['id' => $reserva_id, 'user_id' => $id_logado]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($reserva) {
            // Validar o código de 3 dígitos gerado previamente
            if ((int)$reserva['codigo_validacao'] !== (int)$codigo_inserido) {
                $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'O código de 3 dígitos inserido está incorreto!'];
                $pdo->rollBack();
                header("Location: ../emprestimos.php");
                exit();
            }

            // 1. Atualizar o estado da reserva para concluído
            $stmt_up_res = $pdo->prepare("UPDATE reservas SET status = 'concluida' WHERE id = :id");
            $stmt_up_res->execute(['id' => $reserva_id]);

            //  Criar o registo oficial trocando item_id por livro_id
            $sql_emp = "INSERT INTO emprestimos (utilizador_id, livro_id, reserva_id, data_saida, data_prevista_devolucao) 
                        VALUES (:user_id, :livro_id, :reserva_id, :data_inicio, :data_fim)";
            $stmt_emp = $pdo->prepare($sql_emp);
            $stmt_emp->execute([
                'user_id' => $id_logado,
                'livro_id' => $reserva['livro_id'],
                'reserva_id' => $reserva_id,
                'data_inicio' => $data_inicio,
                'data_fim' => $data_fim
            ]);

            // 3. Ajustado para a tabela livros, livro_id e o estado correto ('indisponivel' de acordo com o teu enum do banco)
            $stmt_item = $pdo->prepare("UPDATE livros SET estado = 'indisponivel' WHERE id = :livro_id");
            $stmt_item->execute(['livro_id' => $reserva['livro_id']]);

            $_SESSION['alerta'] = ['tipo' => 'sucesso', 'mensagem' => 'Empréstimo confirmado com sucesso! Boa leitura.'];
        } else {
            $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Solicitação inválida ou expirada.'];
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Erro ao processar empréstimo: ' . $e->getMessage()];
    }
}

// ==========================================
// AÇÃO 3: ENTREGAR / DEVOLVER EMPRÉSTIMO ATIVO
// ==========================================
if ($acao === 'entregar_emprestimo') {
    $emprestimo_id = (int)$_POST['emprestimo_id'];

    try {
        $pdo->beginTransaction();

       
        $stmt = $pdo->prepare("SELECT livro_id FROM emprestimos WHERE id = :id AND utilizador_id = :user_id AND data_devolucao_real IS NULL");
        $stmt->execute(['id' => $emprestimo_id, 'user_id' => $id_logado]);
        $emprestimo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($emprestimo) {
            // 1. Regista a data atual como a data de devolução real
            $stmt_devolucao = $pdo->prepare("UPDATE emprestimos SET data_devolucao_real = NOW() WHERE id = :id");
            $stmt_devolucao->execute(['id' => $emprestimo_id]);

          
            $stmt_item = $pdo->prepare("UPDATE livros SET estado = 'disponivel' WHERE id = :livro_id");
            $stmt_item->execute(['livro_id' => $emprestimo['livro_id']]);

            $_SESSION['alerta'] = ['tipo' => 'sucesso', 'mensagem' => 'Artigo entregue e devolvido com sucesso! Obrigado.'];
        } else {
            $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Empréstimo inválido ou já finalizado anteriormente.'];
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Erro ao processar a devolução: ' . $e->getMessage()];
    }
}

// Redireciona sempre de volta para a página visual limpa dos empréstimos
header("Location: ../emprestimos.php");
exit();