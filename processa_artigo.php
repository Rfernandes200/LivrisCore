<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $autor_artista = trim($_POST['autor_artista']);
    $categoria_id = (int)$_POST['categoria_id'];
    $estado = trim($_POST['estado']);
    $descricao = trim($_POST['descricao']);
    
    // Configuração do Upload da Imagem
    $imagem_nome = null;
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($extensao, $extensoes_permitidas)) {
            // Cria um nome único para a imagem para não sobrepor ficheiros antigos
            $imagem_nome = uniqid('capa_', true) . '.' . $extensao;
            $destino = 'Uploads/' . $imagem_nome;
            
            // Cria a pasta Uploads caso ela não exista
            if (!is_dir('Uploads')) {
                mkdir('Uploads', 0777, true);
            }
            
            move_uploaded_uploaded_file($_FILES['imagem']['tmp_name'], $destino);
        }
    }

    // Se faltar a imagem ou dados obrigatórios, devolve erro
    if (empty($titulo) || empty($autor_artista) || empty($categoria_id) || !$imagem_nome) {
        $_SESSION['alerta'] = [
            'tipo' => 'erro',
            'mensagem' => '❌ Erro: Preencha todos os campos e envie uma imagem válida.'
        ];
        header('Location: index.php');
        exit;
    }

    try {
        // Inserção na Base de Dados
        $query = "INSERT INTO itens (titulo, autor_artista, categoria_id, estado, descricao, imagem) 
                  VALUES (:titulo, :autor, :categoria, :estado, :descricao, :imagem)";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'titulo' => $titulo,
            'autor' => $autor_artista,
            'categoria' => $categoria_id,
            'estado' => $estado,
            'descricao' => $descricao,
            'imagem' => $imagem_nome
        ]);

        // Define a mensagem de SUCESSO!
        $_SESSION['alerta'] = [
            'tipo' => 'sucesso',
            'mensagem' => '🎉 Artigo adicionado ao catálogo com sucesso!'
        ];

    } catch (PDOException $e) {
        $_SESSION['alerta'] = [
            'tipo' => 'erro',
            'mensagem' => '❌ Erro ao guardar na Base de Dados: ' . $e->getMessage()
        ];
    }

    // Redireciona de volta para atualizar o catálogo instantaneamente
    header('Location: index.php');
    exit;
}