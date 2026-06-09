<?php
session_start();
require 'config.php';

// Proteção: Se não estiver logado ou não houver ação, bloqueia o acesso direto
if (!isset($_SESSION['utilizador_id']) || !isset($_POST['acao'])) {
    header("Location: login.php");
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
        
        // Verificar se a reserva pertence mesmo ao utilizador logado e está pendente
        $stmt = $pdo->prepare("SELECT item_id FROM reservas WHERE id = :id AND utilizador_id = :user_id AND status = 'pendente'");
        $stmt->execute(['id' => $reserva_id, 'user_id' => $id_logado]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reserva) {
            // 1. Devolve o estado do artigo para disponível no catálogo
            $stmt_item = $pdo->prepare("UPDATE itens SET estado = 'disponivel' WHERE id = :item_id");
            $stmt_item->execute(['item_id' => $reserva['item_id']]);
            
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
                header("Location: emprestimos.php");
                exit();
            }

            // 1. Atualizar o estado da reserva para concluído
            $stmt_up_res = $pdo->prepare("UPDATE reservas SET status = 'concluido' WHERE id = :id");
            $stmt_up_res->execute(['id' => $reserva_id]);

            // 2. Criar o registo oficial na tabela de empréstimos com as datas do Pop-up
            $sql_emp = "INSERT INTO emprestimos (utilizador_id, item_id, reserva_id, data_saida, data_prevista_devolucao) 
                        VALUES (:user_id, :item_id, :reserva_id, :data_inicio, :data_fim)";
            $stmt_emp = $pdo->prepare($sql_emp);
            $stmt_emp->execute([
                'user_id' => $id_logado,
                'item_id' => $reserva['item_id'],
                'reserva_id' => $reserva_id,
                'data_inicio' => $data_inicio,
                'data_fim' => $data_fim
            ]);

            // 3. Garantir que o artigo fica marcado como indisponível para outros utilizadores
            $stmt_item = $pdo->prepare("UPDATE itens SET estado = 'indisponivel' WHERE id = :item_id");
            $stmt_item->execute(['item_id' => $reserva['item_id']]);

            $_SESSION['alerta'] = ['tipo' => 'sucesso', 'mensagem' => 'Empréstimo confirmado com sucesso! Boa leitura. 🎉'];
        } else {
            $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Solicitação inválida ou expirada.'];
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Erro ao processar empréstimo: ' . $e->getMessage()];
    }
}

// Redireciona sempre de volta para a página visual limpa
header("Location: emprestimos.php");
exit();