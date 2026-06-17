<?php
session_start();
require 'config.php';

// Bloqueia o acesso se o utilizador não estiver devidamente autenticado
if (!isset($_SESSION['utilizador_id'])) {
    header("Location: login.php");
    exit();
}

// =========================================================================
// CASO 1: AÇÃO DE ELIMINAR ARTIGO PERMANENTEMENTE
// =========================================================================
if (isset($_POST['acao']) && $_POST['acao'] === 'eliminar') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id > 0) {
        try {
            // 1. Procurar o caminho da imagem para apagá-la fisicamente do servidor
            $stmt_img = $pdo->prepare("SELECT imagem_url FROM itens WHERE id = :id");
            $stmt_img->execute(['id' => $id]);
            $artigo = $stmt_img->fetch(PDO::FETCH_ASSOC);
            
            if ($artigo && !empty($artigo['imagem_url']) && file_exists($artigo['imagem_url'])) {
                unlink($artigo['imagem_url']); // Elimina o ficheiro da pasta Uploads
            }

            // 2. Eliminar o registo do artigo na Base de Dados
            $stmt = $pdo->prepare("DELETE FROM itens WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $_SESSION['alerta'] = [
                'tipo' => 'sucesso', 
                'mensagem' => 'Artigo e respetiva imagem eliminados com sucesso de forma permanente!'
            ];
        } catch (PDOException $e) {
            $_SESSION['alerta'] = [
                'tipo' => 'erro', 
                'mensagem' => 'Erro ao eliminar artigo na Base de Dados: ' . $e->getMessage()
            ];
        }
    } else {
        $_SESSION['alerta'] = [
            'tipo' => 'erro', 
            'mensagem' => 'ID do artigo inválido para eliminação.'
        ];
    }
    
    header("Location: meus_artigos.php");
    exit();
}


// =========================================================================
// CASO 2: AÇÃO DE EDITAR / GRAVAR ALTERAÇÕES DO ARTIGO
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id            = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $titulo        = trim($_POST['titulo'] ?? '');
    $autor_artista = trim($_POST['autor_artista'] ?? '');
    $categoria_id  = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
    $estado        = trim($_POST['estado'] ?? 'disponivel');
    $descricao     = trim($_POST['descricao'] ?? '');

    // Validação estrita dos campos obrigatórios no servidor
    if (empty($titulo) || empty($autor_artista) || empty($descricao) || $id === 0 || $categoria_id === 0) {
        $_SESSION['alerta'] = [
            'tipo' => 'erro', 
            'mensagem' => 'Todos os campos obrigatórios (Título, Autor, Categoria e Descrição) têm de ser preenchidos!'
        ];
        header("Location: meus_artigos.php");
        exit();
    }

    try {
        // 1. Ir buscar a imagem atual do banco para servir de salvaguarda (fallback)
        $stmt_img = $pdo->prepare("SELECT imagem_url FROM itens WHERE id = :id");
        $stmt_img->execute(['id' => $id]);
        $artigo_atual = $stmt_img->fetch(PDO::FETCH_ASSOC);
        
        // Define por padrão o caminho que já lá estava guardado
        $caminho_imagem_final = $artigo_atual['imagem_url'] ?? '';

        // 2. Processar o upload da nova imagem (Apenas se o utilizador anexar um ficheiro)
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
            $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($extensao, $extensoes_permitidas)) {
                // Criar a pasta de destino caso ela ainda não exista no servidor
                if (!is_dir('Uploads')) {
                    mkdir('Uploads', 0777, true);
                }

                // Gerar um nome de ficheiro totalmente único baseado no tempo e ID único
                $novo_nome_ficheiro = time() . '_' . uniqid() . '.' . $extensao;
                $destino = 'Uploads/' . $novo_nome_ficheiro;

                // Move o ficheiro temporário para a pasta permanente
                if (move_uploaded_file($_FILES['imagem']['tmp_name'], $destino)) {
                    
                    // Se existia uma imagem antiga associada e o ficheiro existe, apaga-o
                    if (!empty($caminho_imagem_final) && file_exists($caminho_imagem_final)) {
                        unlink($caminho_imagem_final);
                    }
                    
                    // Atualiza a variável com o novo caminho completo que vai para a Base de Dados
                    $caminho_imagem_final = $destino;
                }
            } else {
                $_SESSION['alerta'] = [
                    'tipo' => 'erro', 
                    'mensagem' => 'Formato de imagem inválido! Apenas são permitidos ficheiros JPG, JPEG, PNG e WEBP.'
                ];
                header("Location: meus_artigos.php");
                exit();
            }
        }

        // 3. Executar o UPDATE com todos os dados atualizados
        $sql = "UPDATE itens SET 
                    titulo = :titulo, 
                    autor_artista = :autor, 
                    categoria_id = :categoria, 
                    estado = :estado, 
                    descricao = :descricao, 
                    imagem_url = :imagem 
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'titulo'    => $titulo,
            'autor'     => $autor_artista,
            'categoria' => $categoria_id,
            'estado'    => $estado,
            'descricao' => $descricao,
            'imagem'    => $caminho_imagem_final, // Guarda o caminho atualizado ou o antigo preservado
            'id'        => $id
        ]);

        $_SESSION['alerta'] = [
            'tipo' => 'sucesso', 
            'mensagem' => 'Artigo atualizado com sucesso!'
        ];

    } catch (PDOException $e) {
        $_SESSION['alerta'] = [
            'tipo' => 'erro', 
            'mensagem' => 'Erro crítico na Base de Dados: ' . $e->getMessage()
        ];
    }

    header("Location: meus_artigos.php");
    exit();
}

// Se alguém tentar aceder a este ficheiro diretamente sem ser via POST, é redirecionado
header("Location: meus_artigos.php");
exit();
?>