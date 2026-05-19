<?php
session_start();
require 'config.php';

// 1. Bloqueio de Segurança: Apenas administradores podem aceder
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    header("Location: index.php");
    exit();
}

// 2. Verificar se o ID do artigo foi passado na URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $artigo_id = (int)$_GET['id'];

    try {
        // Query direta pelo ID do item, sem JOINs desnecessários que usem utilizador_id
        $stmt = $pdo->prepare("DELETE FROM itens WHERE id = :id");
        $stmt->execute(['id' => $artigo_id]);

        // Redireciona de volta para o painel de administração na secção de artigos
        header("Location: admin.php?seccao=artigos&status=eliminado");
        exit();

    } catch (PDOException $e) {
        // Em caso de erro na base de dados, mostra uma mensagem limpa ou lida com o erro
        die("Erro ao eliminar o artigo: " . $e->getMessage());
    }
} else {
    // Se não houver ID válido, volta para o painel
    header("Location: admin.php?seccao=artigos");
    exit();
}
?>