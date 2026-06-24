<?php
session_start();
require 'config.php';

// Bloqueio de Segurança
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    header("Location: index.php");
    exit();
}

// GET handler to fetch article data dynamically
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $artigo_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM livros WHERE id = :id");
        $stmt->execute(['id' => $artigo_id]);
        $livro = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($livro) {
            // Fetch authors
            $stmt_autores = $pdo->prepare("SELECT autor_id FROM livro_autores WHERE livro_id = :id");
            $stmt_autores->execute(['id' => $artigo_id]);
            $autores = $stmt_autores->fetchAll(PDO::FETCH_COLUMN);
            $livro['autores'] = $autores;
            
            header('Content-Type: application/json');
            echo json_encode($livro);
            exit();
        } else {
            http_response_code(404);
            echo json_encode(['erro' => 'Livro não encontrado']);
            exit();
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['erro' => $e->getMessage()]);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $artigo_id  = (int)($_POST['artigo_id'] ?? 0);
    $titulo     = trim($_POST['titulo'] ?? '');
    $isbn       = trim($_POST['isbn'] ?? '');
    $editora    = trim($_POST['editora'] ?? '');
    $ano_edicao = !empty($_POST['ano_edicao']) ? (int)$_POST['ano_edicao'] : null;
    $autores    = $_POST['autor_id'] ?? []; // AGORA É UM ARRAY
    $cdu_codigo = trim($_POST['cdu_codigo'] ?? '');
    $estado     = trim($_POST['estado'] ?? 'disponivel');
    $descricao  = trim($_POST['descricao'] ?? '');

    if ($artigo_id <= 0 || empty($titulo)) {
        $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => '❌ Erro: Dados obrigatórios do artigo em falta.'];
        header("Location: admin.php?seccao=artigos");
        exit();
    }

    // [NOVA VALIDAÇÃO: ISBN DUPLICADO]
    if (!empty($isbn)) {
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM livros WHERE isbn = :isbn AND id != :id_atual");
        $stmt_check->execute(['isbn' => $isbn, 'id_atual' => $artigo_id]);
        
        if ((int)$stmt_check->fetchColumn() > 0) {
            $_SESSION['alerta'] = [
                'tipo' => 'erro',
                'mensagem' => '❌ Erro: Já existe OUTRO livro registado com este código ISBN!'
            ];
            header("Location: admin.php?seccao=artigos");
            exit;
        }
    }

    try {
        $pdo->beginTransaction();

        // 1. Procurar imagem atual
        $stmt_img = $pdo->prepare("SELECT imagem_url FROM livros WHERE id = :id");
        $stmt_img->execute(['id' => $artigo_id]);
        $livro_atual = $stmt_img->fetch(PDO::FETCH_ASSOC);
        
        $caminho_imagem_final = $livro_atual['imagem_url'] ?? '';

        // 2. Processar Capa
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) { // ATENÇÃO: nome do input no teu HTML é "imagem"
            $fileExtension = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
            $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (in_array($fileExtension, $extensoes_permitidas)) {
                $uploadDir = 'Uploads/';
                $newFileName = md5(time() . $_FILES['imagem']['name']) . '.' . $fileExtension;
                
                if (move_uploaded_file($_FILES['imagem']['tmp_name'], $uploadDir . $newFileName)) {
                    if (!empty($caminho_imagem_final) && file_exists($uploadDir . $caminho_imagem_final)) {
                        unlink($uploadDir . $caminho_imagem_final);
                    }
                    $caminho_imagem_final = $newFileName;
                }
            }
        }

        // 3. Update da tabela livros
        $stmt = $pdo->prepare("UPDATE livros SET titulo = :t, isbn = :i, editora = :e, ano_edicao = :a, cdu_codigo = :c, estado = :st, descricao = :d, imagem_url = :img WHERE id = :id");
        $stmt->execute([
            't' => $titulo, 'i' => $isbn, 'e' => $editora, 'a' => $ano_edicao, 
            'c' => $cdu_codigo, 'st' => $estado, 'd' => $descricao, 'img' => $caminho_imagem_final, 'id' => $artigo_id
        ]);

        // 4. Atualizar MÚLTIPLOS autores na tabela pivot 'livro_autores'
        $pdo->prepare("DELETE FROM livro_autores WHERE livro_id = :id")->execute(['id' => $artigo_id]);
        
        $stmt_autor = $pdo->prepare("INSERT INTO livro_autores (livro_id, autor_id) VALUES (:id, :autor_id)");
        foreach (array_unique($autores) as $a_id) {
            if (!empty($a_id)) {
                $stmt_autor->execute(['id' => $artigo_id, 'autor_id' => (int)$a_id]);
            }
        }

        $pdo->commit();
        $_SESSION['alerta'] = ['tipo' => 'sucesso', 'mensagem' => '🎉 Artigo atualizado com sucesso!'];

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => '❌ Erro ao atualizar: ' . $e->getMessage()];
    }
}

header("Location: admin.php?seccao=artigos");
exit();