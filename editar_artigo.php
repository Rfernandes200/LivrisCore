<?php
session_start();
require 'config.php';

// Bloqueio de Segurança: Apenas administradores podem processar a edição
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $artigo_id  = (int)($_POST['artigo_id'] ?? 0);
    $titulo     = trim($_POST['titulo'] ?? '');
    $isbn       = trim($_POST['isbn'] ?? '');
    $editora    = trim($_POST['editora'] ?? '');
    $ano_edicao = !empty($_POST['ano_edicao']) ? (int)$_POST['ano_edicao'] : null;
    $autor_id   = !empty($_POST['autor_id']) ? (int)$_POST['autor_id'] : null;
    $cdu_codigo = trim($_POST['cdu_codigo'] ?? '');
    $estado     = trim($_POST['estado'] ?? 'disponivel');
    $descricao  = trim($_POST['descricao'] ?? '');

    if ($artigo_id <= 0 || empty($titulo)) {
        $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => '❌ Erro: Dados obrigatórios do artigo em falta.'];
        header("Location: admin.php?seccao=artigos");
        exit();
    }

    try {
        // Iniciar transação para garantir que livros e autores atualizam juntos
        $pdo->beginTransaction();

        // 1. Procurar se o artigo existe e recolher a imagem atual
        $stmt_img = $pdo->prepare("SELECT imagem_url FROM livros WHERE id = :id");
        $stmt_img->execute(['id' => $artigo_id]);
        $livro_atual = $stmt_img->fetch(PDO::FETCH_ASSOC);

        if (!$livro_atual) {
            throw new Exception("Artigo não encontrado no sistema.");
        }

        $caminho_imagem_final = $livro_atual['imagem_url'];

        // 2. Processar Substituição da Capa (Apenas se enviada uma nova)
        if (isset($_FILES['imagem_capa']) && $_FILES['imagem_capa']['error'] === UPLOAD_ERR_OK) {
            $fileExtension = strtolower(pathinfo($_FILES['imagem_capa']['name'], PATHINFO_EXTENSION));
            $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (in_array($fileExtension, $extensoes_permitidas)) {
                $uploadDir = 'Uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $newFileName = md5(time() . $_FILES['imagem_capa']['name']) . '.' . $fileExtension;
                $destino = $uploadDir . $newFileName;

                if (move_uploaded_file($_FILES['imagem_capa']['tmp_name'], $destino)) {
                    // Apaga a imagem física antiga do servidor se ela existir para não acumular lixo
                    if (!empty($caminho_imagem_final) && file_exists($uploadDir . $caminho_imagem_final)) {
                        unlink($uploadDir . $caminho_imagem_final);
                    }
                    $caminho_imagem_final = $newFileName; // Guarda apenas o nome do ficheiro de forma consistente
                }
            } else {
                $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => '❌ Formato de imagem inválido. Apenas JPG, JPEG, PNG e WEBP são permitidos.'];
                header("Location: admin.php?seccao=artigos");
                exit();
            }
        }

        // 3. Executar o UPDATE na tabela 'livros'
        $sql_update = "UPDATE livros SET 
                            titulo = :titulo, 
                            isbn = :isbn, 
                            editora = :editora, 
                            ano_edicao = :ano_edicao, 
                            cdu_codigo = :cdu_codigo, 
                            estado = :estado, 
                            descricao = :descricao,
                            imagem_url = :imagem_url 
                       WHERE id = :id";
        
        $stmt = $pdo->prepare($sql_update);
        $stmt->execute([
            'titulo'     => $titulo,
            'isbn'       => $isbn,
            'editora'    => $editora,
            'ano_edicao' => $ano_edicao,
            'cdu_codigo' => $cdu_codigo,
            'estado'     => $estado,
            'descricao'  => $descricao,
            'imagem_url' => $caminho_imagem_final,
            'id'         => $artigo_id
        ]);

        // 4. Atualizar o vínculo com o Autor na tabela pivot 'livro_autores'
        $pdo->prepare("DELETE FROM livro_autores WHERE livro_id = :id")->execute(['id' => $artigo_id]);
        if ($autor_id) {
            $pdo->prepare("INSERT INTO livro_autores (livro_id, autor_id) VALUES (:id, :autor_id)")
                ->execute(['id' => $artigo_id, 'autor_id' => $autor_id]);
        }

        // Confirmar alterações
        $pdo->commit();

        $_SESSION['alerta'] = [
            'tipo' => 'sucesso',
            'mensagem' => '🎉 Artigo "' . htmlspecialchars($titulo) . '" atualizado com sucesso!'
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['alerta'] = [
            'tipo' => 'erro',
            'mensagem' => '❌ Erro ao atualizar: ' . $e->getMessage()
        ];
    }
}

// Redireciona sempre de volta focado na tabela de artigos do painel de administração
header("Location: admin.php?seccao=artigos");
exit();