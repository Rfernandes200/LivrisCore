<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['utilizador_id'])) {
    $item_id = $_POST['item_id'];
    $utilizador_id = $_SESSION['utilizador_id'];

    try {
        $pdo->beginTransaction();

        // 1. Cancela a reserva ativa deste utilizador para este item
        $stmt1 = $pdo->prepare("UPDATE reservas SET status = 'cancelada' WHERE item_id = :item_id AND utilizador_id = :utilizador_id AND status = 'pendente'");
        $stmt1->execute(['item_id' => $item_id, 'utilizador_id' => $utilizador_id]);

        // 2. Devolve o estado do item para disponível
        $stmt2 = $pdo->prepare("UPDATE itens SET estado = 'disponivel' WHERE id = :item_id");
        $stmt2->execute(['item_id' => $item_id]);

        $pdo->commit();
        $_SESSION['alerta'] = ['tipo' => 'sucesso', 'mensagem' => 'Reserva cancelada com sucesso!'];
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Erro ao cancelar reserva.'];
    }
}

header("Location: index.php");
exit;