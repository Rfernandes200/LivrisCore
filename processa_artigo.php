<?php
session_start();
require 'config.php';

// 1. Bloqueio de Segurança
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo        = trim($_POST['titulo'] ?? '');
    $autor_id      = (int)($_POST['autor_id'] ?? 0); // Recebe o ID obrigatório selecionado
    $cdu_codigo    = trim($_POST['cdu_codigo'] ?? ''); 
    $estado        = trim($_POST['estado'] ?? 'disponivel');
    $descricao     = trim($_POST['descricao'] ?? '');
    $isbn          = trim($_POST['isbn'] ?? '');
    $editora       = trim($_POST['editora'] ?? '');
    $ano_edicao    = (int)($_POST['ano_edicao'] ?? 0);
    
    $nome_imagem_bd = null;
    
    // Configuração do Upload da Imagem
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($extensao, $extensoes_permitidas)) {
            if (!is_dir('Uploads')) {
                mkdir('Uploads', 0777, true);
            }
            
            $imagem_nome = uniqid('capa_', true) . '.' . $extensao;
            $destino = 'Uploads/' . $imagem_nome;
            
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $destino)) {
                $nome_imagem_bd = $imagem_nome; 
            }
        }
    }

    $origem = $_SERVER['HTTP_REFERER'] ?? 'index.php';

    // VALIDAÇÃO ESTRETA: Verifica se o autor_id é maior que 0 (ou seja, se foi selecionado)
    if (empty($titulo) || $autor_id === 0 || empty($cdu_codigo) || empty($isbn) || empty($editora) || $ano_edicao === 0 || empty($descricao) || !$nome_imagem_bd) {
        $_SESSION['alerta'] = [
            'tipo' => 'erro',
            'mensagem' => '❌ Erro: Seleção de Autor e todos os outros campos são obrigatórios!'
        ];
        header("Location: " . $origem);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Inserir o livro
        $query_livro = "INSERT INTO livros (titulo, cdu_codigo, isbn, editora, ano_edicao, estado, descricao, imagem, imagem_url) 
                        VALUES (:titulo, :cdu, :isbn, :editora, :ano, :estado, :descricao, :imagem, :imagem_url)";
        
        $stmt_livro = $pdo->prepare($query_livro);
        $stmt_livro->execute([
            'titulo'     => $titulo,
            'cdu'        => $cdu_codigo,
            'isbn'       => $isbn,
            'editora'    => $editora,
            'ano'        => $ano_edicao,
            'estado'     => $estado,
            'descricao'  => $descricao,
            'imagem'     => $nome_imagem_bd,
            'imagem_url' => $nome_imagem_bd
        ]);

        $livro_id = $pdo->lastInsertId();

        // 2. Criar o vínculo direto na tabela intermédia com o ID selecionado
        $stmt_vinculo = $pdo->prepare("INSERT INTO livro_autores (livro_id, autor_id) VALUES (:livro_id, :autor_id)");
        $stmt_vinculo->execute([
            'livro_id' => $livro_id,
            'autor_id' => $autor_id
        ]);

        $pdo->commit();

        $_SESSION['alerta'] = [
            'tipo' => 'sucesso',
            'mensagem' => '🎉 Livro adicionado e vinculado ao autor com sucesso!'
        ];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['alerta'] = [
            'tipo' => 'erro',
            'mensagem' => '❌ Erro ao guardar na Base de Dados: ' . $e->getMessage()
        ];
    }

    header("Location: " . $origem);
    exit;
}