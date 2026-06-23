<?php
session_start();
require 'config.php';

// 1. Bloqueio de Segurança
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo         = trim($_POST['titulo'] ?? '');
    
    // CORREÇÃO: O name correto que vem do formulário HTML é 'autor_id' (enviado via array pelo JS/HTML)
    $autores_ids    = $_POST['autor_id'] ?? []; 
    
    $cdu_codigo     = trim($_POST['cdu_codigo'] ?? ''); 
    $estado         = trim($_POST['estado'] ?? 'disponivel');
    $descricao      = trim($_POST['descricao'] ?? '');
    $isbn           = trim($_POST['isbn'] ?? '');
    $editora        = trim($_POST['editora'] ?? '');
    $ano_edicao     = (int)($_POST['ano_edicao'] ?? 0);
    
    $origem = $_SERVER['HTTP_REFERER'] ?? 'index.php';

    // [NOVA VALIDAÇÃO: ISBN DUPLICADO]
    if (!empty($isbn)) {
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM livros WHERE isbn = :isbn");
        $stmt_check->execute(['isbn' => $isbn]);
        
        if ((int)$stmt_check->fetchColumn() > 0) {
            $_SESSION['alerta'] = [
                'tipo' => 'erro',
                'mensagem' => '❌ Erro: Já existe um livro registado com este código ISBN!'
            ];
            header("Location: " . $origem);
            exit; // Para o script imediatamente para não fazer upload nem INSERT
        }
    }

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

    // TRATAMENTO DOS AUTORES: Garantir formato numérico, remover vazios e duplicados
    if (is_array($autores_ids)) {
        $autores_ids = array_map('intval', $autores_ids);
        $autores_ids = array_filter($autores_ids, function($id) { return $id > 0; });
        $autores_ids = array_unique($autores_ids); 
    } else {
        $autores_ids = [];
    }

    // VALIDAÇÃO: Verifica se pelo menos 1 autor foi selecionado e se os restantes campos estão preenchidos
    if (empty($titulo) || empty($autores_ids) || empty($cdu_codigo) || empty($isbn) || empty($editora) || $ano_edicao === 0 || empty($descricao) || !$nome_imagem_bd) {
        $_SESSION['alerta'] = [
            'tipo' => 'erro',
            'mensagem' => '❌ Erro: Seleção de pelo menos um Autor e todos os outros campos são obrigatórios!'
        ];
        header("Location: " . $origem);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Inserir o livro na tabela 'livros'
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

        // Pega no ID do livro que acabou de ser criado
        $livro_id = $pdo->lastInsertId();

        // 2. Criar os múltiplos vínculos na tabela intermédia 'livro_autores'
        $stmt_vinculo = $pdo->prepare("INSERT INTO livro_autores (livro_id, autor_id) VALUES (:livro_id, :autor_id)");
        
        foreach ($autores_ids as $autor_id) {
            $stmt_vinculo->execute([
                'livro_id' => $livro_id,
                'autor_id' => $autor_id
            ]);
        }

        $pdo->commit();

        $_SESSION['alerta'] = [
            'tipo' => 'sucesso',
            'mensagem' => '🎉 Livro adicionado e vinculado aos autores com sucesso!'
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
?>