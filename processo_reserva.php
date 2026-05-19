<?php
session_start();
require 'config.php';

// Verifica se os dados foram enviados por POST e se o utilizador tem sessão iniciada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['utilizador_id'])) {
    
    $item_id = (int)$_POST['item_id'];
    $utilizador_id = (int)$_SESSION['utilizador_id'];
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];

    try {
        // Iniciamos uma transação para garantir que ambas as operações correm bem
        $pdo->beginTransaction();

        // 1. INSERIR NA TABELA RESERVAS
        // Corrigido para usar as tuas colunas exatas: data_inicio, data_fim e status
        $queryReserva = "INSERT INTO reservas (utilizador_id, item_id, data_inicio, data_fim, status) 
                         VALUES (:utilizador_id, :item_id, :data_inicio, :data_fim, 'pendente')";
        
        $stmtReserva = $pdo->prepare($queryReserva);
        $stmtReserva->execute([
            'utilizador_id' => $utilizador_id,
            'item_id'       => $item_id,
            'data_inicio'   => $data_inicio,
            'data_fim'      => $data_fim
        ]);

        // 2. ATUALIZAR O ESTADO DO ITEM NA TABELA ITENS
        // Altera o estado do livro/item para 'reservado' para que mais ninguém o possa pedir
        $queryItem = "UPDATE itens SET estado = 'reservado' WHERE id = :item_id";
        $stmtItem = $pdo->prepare($queryItem);
        $stmtItem->execute(['item_id' => $item_id]);

        // Grava as alterações na base de dados
        $pdo->commit();

        // Configura o alerta de sucesso para o toast do index.php
        $_SESSION['alerta'] = [
            'tipo' => 'sucesso',
            'mensagem' => 'Artigo reservado com sucesso!'
        ];

    } catch (PDOException $e) {
        // Se algo falhar, desfaz as alterações para não corromper a base de dados
        $pdo->rollBack();
        
        $_SESSION['alerta'] = [
            'tipo' => 'erro',
            'mensagem' => 'Erro ao processar a reserva: ' . $e->getMessage()
        ];
    }
} else {
    $_SESSION['alerta'] = [
        'tipo' => 'erro',
        'mensagem' => 'Sessão expirada ou dados inválidos. Por favor, tente novamente.'
    ];
}

// Redireciona de volta para a página principal (o catálogo)
header("Location: index.php");
exit;