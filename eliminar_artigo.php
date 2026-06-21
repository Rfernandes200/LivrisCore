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
        $pdo->beginTransaction();

        // CORREÇÃO 1: Mudar de 'itens' para 'livros' no SELECT para encontrar a imagem de capa
        $stmt_img = $pdo->prepare("SELECT imagem_url FROM livros WHERE id = :id");
        $stmt_img->execute(['id' => $artigo_id]);
        $artigo = $stmt_img->fetch(PDO::FETCH_ASSOC);

        // CORREÇÃO 2: Verificar o caminho correto dentro da pasta 'Uploads/'
        if ($artigo && !empty($artigo['imagem_url'])) {
            $caminho_fisico = 'Uploads/' . $artigo['imagem_url'];
            if (file_exists($caminho_fisico)) {
                unlink($caminho_fisico); // Apaga o ficheiro físico para não acumular lixo no servidor
            }
        }

        // CORREÇÃO 3: Remover primeiro o vínculo do livro com o autor na tabela pivot (evita erros de Foreign Key)
        $stmt_autor = $pdo->prepare("DELETE FROM livro_autores WHERE livro_id = :id");
        $stmt_autor->execute(['id' => $artigo_id]);

        // De seguida, apaga o registo na BD
        $stmt = $pdo->prepare("DELETE FROM livros WHERE id = :id");
        $stmt->execute(['id' => $artigo_id]);

        // Confirmar todas as remoções com segurança
        $pdo->commit();

        $_SESSION['alerta'] = ['tipo' => 'sucesso', 'mensagem' => '🗑️ O artigo foi removido do acervo com sucesso!'];
        header("Location: admin.php?seccao=artigos");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => '❌ Erro ao eliminar o artigo: ' . $e->getMessage()];
        header("Location: admin.php?seccao=artigos");
        exit();
    }
} else {
    $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => '❌ ID do artigo inválido ou não especificado.'];
    header("Location: admin.php?seccao=artigos");
    exit();
}