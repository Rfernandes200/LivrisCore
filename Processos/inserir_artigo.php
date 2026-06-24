<?php
session_start();
require '../config.php';

// Bloqueio de Segurança
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $editora = trim($_POST['editora'] ?? '');
    $ano_edicao = !empty($_POST['ano_edicao']) ? (int)$_POST['ano_edicao'] : null;
    $autor_id = !empty($_POST['autor_id']) ? (int)$_POST['autor_id'] : null;
    $cdu_codigo = trim($_POST['cdu_codigo'] ?? '');
    $estado = trim($_POST['estado'] ?? 'disponivel');
    $descricao = trim($_POST['descricao'] ?? '');
    
    $imagem_url = null;

    // Processamento do Upload da Imagem de Capa
    if (isset($_FILES['imagem_capa']) && $_FILES['imagem_capa']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['imagem_capa']['tmp_name'];
        $fileName = $_FILES['imagem_capa']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Extensões permitidas
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            // Gerar um nome único para evitar sobreposições
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            
            // Certifica-te de que a pasta 'Uploads' existe
            $uploadFileDir = '../Uploads/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            
            $dest_path = $uploadFileDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $imagem_url = $newFileName; // Guarda apenas o nome do ficheiro na BD
            }
        }
    }

    try {
        // Iniciar transação para garantir consistência nas duas tabelas
        $pdo->beginTransaction();

        // 1. Inserir o livro na tabela 'livros'
        $sql_livro = "INSERT INTO livros (titulo, isbn, editora, ano_edicao, cdu_codigo, estado, imagem_url, descricao) 
                      VALUES (:titulo, :isbn, :editora, :ano_edicao, :cdu_codigo, :estado, :imagem_url, :descricao)";
        
        $stmt_livro = $pdo->prepare($sql_livro);
        $stmt_livro->execute([
            'titulo' => $titulo,
            'isbn' => $isbn,
            'editora' => $editora,
            'ano_edicao' => $ano_edicao,
            'cdu_codigo' => $cdu_codigo,
            'estado' => $estado,
            'imagem_url' => $imagem_url,
            'descricao' => $descricao
        ]);

        // Obter o ID do livro acabado de inserir
        $livro_id = $pdo->lastInsertId();

        // 2. Associar o autor na tabela pivot 'livro_autores' (se selecionado)
        if ($livro_id && $autor_id) {
            $sql_autor = "INSERT INTO livro_autores (livro_id, autor_id) VALUES (:livro_id, :autor_id)";
            $stmt_autor = $pdo->prepare($sql_autor);
            $stmt_autor->execute([
                'livro_id' => $livro_id,
                'autor_id' => $autor_id
            ]);
        }

        // Confirmar alterações na Base de Dados
        $pdo->commit();

        $_SESSION['alerta'] = [
            'tipo' => 'sucesso',
            'mensagem' => 'Artigo "' . htmlspecialchars($titulo) . '" adicionado com sucesso !'
        ];

    } catch (PDOException $e) {
        // Cancelar alterações em caso de erro
        $pdo->rollBack();
        $_SESSION['alerta'] = [
            'tipo' => 'erro',
            'mensagem' => 'Erro ao inserir artigo: ' . $e->getMessage()
        ];
    }

    // Redireciona de volta para a secção de artigos
    header("Location: ../admin.php?seccao=artigos");
    exit();
} else {
    // Se tentarem aceder diretamente sem POST, manda para o painel
    header("Location: ../admin.php?seccao=artigos");
    exit();
}