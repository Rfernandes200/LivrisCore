<?php
session_start();
require 'config.php';

// 1. Bloqueio de Segurança: Apenas administradores podem inserir artigos
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo        = trim($_POST['titulo'] ?? '');
    $autor_artista = trim($_POST['autor_artista'] ?? '');
    $categoria_id  = (int)($_POST['categoria_id'] ?? 0);
    $estado        = trim($_POST['estado'] ?? 'disponivel');
    $descricao     = trim($_POST['descricao'] ?? '');
    
    // Configuração do Upload da Imagem
    $caminho_imagem_final = null;
    
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($extensao, $extensoes_permitidas)) {
            // Cria a pasta Uploads caso ela não exista
            if (!is_dir('Uploads')) {
                mkdir('Uploads', 0777, true);
            }
            
            // Cria um nome único para a imagem e define o caminho completo
            $imagem_nome = uniqid('capa_', true) . '.' . $extensao;
            $destino = 'Uploads/' . $imagem_nome;
            
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $destino)) {
                $caminho_imagem_final = $destino; // Caminho completo guardado de forma consistente
            }
        }
    }

    $origem = $_SERVER['HTTP_REFERER'] ?? 'index.php';

    // Validação estrita de dados obrigatórios
    if (empty($titulo) || empty($autor_artista) || $categoria_id === 0 || empty($descricao) || !$caminho_imagem_final) {
        $_SESSION['alerta'] = [
            'tipo' => 'erro',
            'mensagem' => '❌ Erro: Preencha todos os campos obrigatórios e envie uma imagem válida (JPG, JPEG, PNG, WEBP).'
        ];
        header("Location: " . $origem);
        exit;
    }

    try {
        $query = "INSERT INTO itens (titulo, autor_artista, categoria_id, estado, descricao, imagem_url) 
                  VALUES (:titulo, :autor, :categoria, :estado, :descricao, :imagem)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'titulo'    => $titulo,
            'autor'     => $autor_artista,
            'categoria' => $categoria_id,
            'estado'    => $estado,
            'descricao' => $descricao,
            'imagem'    => $caminho_imagem_final
        ]);

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

    header("Location: " . $origem);
    exit;
}