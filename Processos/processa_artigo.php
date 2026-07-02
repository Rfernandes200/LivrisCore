<?php
session_start();
require '../config.php';

// 1. Bloqueio de Segurança
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo     = trim($_POST['titulo'] ?? '');
    $cdu_codigo = trim($_POST['cdu_codigo'] ?? ''); 
    $estado     = trim($_POST['estado'] ?? 'disponivel');
    $descricao  = trim($_POST['descricao'] ?? '');
    $isbn       = trim($_POST['isbn'] ?? '');
    $editora    = trim($_POST['editora'] ?? '');
    $ano_edicao = (int)($_POST['ano_edicao'] ?? 0);
    
    // Captura a página de onde o formulário veio para fazer o redirecionamento correto
    $origem = $_SERVER['HTTP_REFERER'] ?? '../index.php';

    // 2. Validação: ISBN Duplicado
    if (!empty($isbn)) {
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM livros WHERE isbn = :isbn");
        $stmt_check->execute(['isbn' => $isbn]);
        
        if ((int)$stmt_check->fetchColumn() > 0) {
            $_SESSION['alerta'] = [
                'tipo' => 'erro',
                'mensagem' => ' Erro: Já existe um livro registado com este código ISBN!'
            ];
            header("Location: " . $origem);
            exit(); 
        }
    }

    $nome_imagem_bd = null;
    
    // 3. Configuração Segura e Absoluta do Upload da Imagem
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($extensao, $extensoes_permitidas)) {
            // Garante o caminho correto para a pasta /Uploads na raiz do projeto, independente da página de origem
            $raiz_projeto = dirname(__DIR__); 
            $pasta_uploads = $raiz_projeto . DIRECTORY_SEPARATOR . 'Uploads';

            if (!is_dir($pasta_uploads)) {
                mkdir($pasta_uploads, 0777, true);
            }
            
            $imagem_nome = uniqid('capa_', true) . '.' . $extensao;
            $destino = $pasta_uploads . DIRECTORY_SEPARATOR . $imagem_nome;
            
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $destino)) {
                $nome_imagem_bd = $imagem_nome; 
            }
        }
    }

    // 4. Tratamento dos Autores vindo do Array Dinâmico do JS
    $autores_ids = $_POST['autor_id'] ?? []; 
    if (is_array($autores_ids)) {
        $autores_ids = array_map('intval', $autores_ids);
        $autores_ids = array_filter($autores_ids, function($id) { return $id > 0; });
        $autores_ids = array_unique($autores_ids); 
    } else {
        $autores_ids = [];
    }

    // 5. Validação Geral de Campos Obrigatórios
    if (empty($titulo) || empty($autores_ids) || empty($cdu_codigo) || empty($isbn) || empty($editora) || $ano_edicao === 0 || empty($descricao) || !$nome_imagem_bd) {
        $_SESSION['alerta'] = [
            'tipo' => 'erro',
            'mensagem' => ' Erro: Seleção de pelo menos um Autor e todos os outros campos são obrigatórios!'
        ];
        header("Location: " . $origem);
        exit();
    }

    // 6. Transação PDO para Base de Dados (Livro + Vínculo de Autores)
    try {
        $pdo->beginTransaction();

        // Inserir o livro na tabela 'livros'
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

        // Recuperar o ID gerado para o livro inserido
        $livro_id = $pdo->lastInsertId();

        // Inserir os múltiplos vínculos na tabela intermédia 'livro_autores'
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
            'mensagem' => ' Livro adicionado e vinculado aos autores com sucesso!'
        ];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['alerta'] = [
            'tipo' => 'erro',
            'mensagem' => ' Erro ao guardar na Base de Dados: ' . $e->getMessage()
        ];
    }

    header("Location: " . $origem);
    exit();
}
?>