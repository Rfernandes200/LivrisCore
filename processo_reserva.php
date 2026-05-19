<?php
session_start();
require 'config.php';

// 1. Verificar se o formulário foi enviado via método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// 2. Verificar se o utilizador está autenticado (Ajusta 'utilizador_id' conforme usas na tua session)
if (!isset($_SESSION['utilizador_id'])) {
    $_SESSION['alerta'] = [
        'tipo' => 'danger',
        'mensagem' => '❌ Precisa de iniciar sessão para efetuar uma reserva.'
    ];
    header("Location: login.php");
    exit();
}

// 3. Recolher e limpar os dados enviados pelo formulário do Pop-up
$item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
$data_inicio_raw = filter_input(INPUT_POST, 'data_inicio', FILTER_DEFAULT);
$data_fim_raw = filter_input(INPUT_POST, 'data_fim', FILTER_DEFAULT);
$utilizador_id = $_SESSION['utilizador_id'];

// Validar se os campos obrigatórios não estão vazios
if (!$item_id || empty($data_inicio_raw) || empty($data_fim_raw)) {
    $_SESSION['alerta'] = [
        'tipo' => 'danger',
        'mensagem' => '❌ Erro nos dados enviados. Por favor, preencha todos os campos.'
    ];
    header("Location: index.php");
    exit();
}

try {
    // Converter strings para objetos DateTime para poder fazer comparações matemáticas robustas
    $data_atual = new DateTime(date('Y-m-d'));
    $data_inicio = new DateTime($data_inicio_raw);
    $data_fim = new DateTime($data_fim_raw);

    // 4. Validações de Segurança das Datas (Mesmo que o JS faça, o PHP deve sempre re-validar)
    if ($data_inicio < $data_atual) {
        $_SESSION['alerta'] = [
            'tipo' => 'danger',
            'mensagem' => '❌ A data de início não pode ser no passado.'
        ];
        header("Location: index.php");
        exit();
    }

    if ($data_fim < $data_inicio) {
        $_SESSION['alerta'] = [
            'tipo' => 'danger',
            'mensagem' => '❌ A data de fim não pode ser anterior à data de início.'
        ];
        header("Location: index.php");
        exit();
    }

    // 5. Iniciar uma Transação para garantir consistência total na Base de Dados
    $pdo->beginTransaction();

    // 6. Verificar se o artigo existe e se o estado dele ainda é 'disponivel'
    // O comando FOR UPDATE tranca esta linha temporariamente para evitar que dois utilizadores reservem ao mesmo milissegundo.
    $stmt_check = $pdo->prepare("SELECT estado FROM itens WHERE id = ? FOR UPDATE");
    $stmt_check->execute([$item_id]);
    $item = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        $pdo->rollBack();
        $_SESSION['alerta'] = [
            'tipo' => 'danger',
            'mensagem' => '❌ O artigo selecionado não existe.'
        ];
        header("Location: index.php");
        exit();
    }

    if ($item['estado'] !== 'disponivel') {
        $pdo->rollBack();
        $_SESSION['alerta'] = [
            'tipo' => 'danger',
            'mensagem' => '❌ Lamentamos, mas este artigo já foi reservado ou emprestado por outro utilizador.'
        ];
        header("Location: index.php");
        exit();
    }

    // 7. Inserir o registo na tua tabela de reservas
    // Nota: Adapta os nomes das colunas ('utilizador_id', 'item_id', 'data_inicio', 'data_fim', 'estado') à estrutura real da tua base de dados.
    $query_reserva = "INSERT INTO reservas (utilizador_id, item_id, data_inicio, data_fim, estado) 
                      VALUES (?, ?, ?, ?, 'ativa')";
    $stmt_reserva = $pdo->prepare($query_reserva);
    $stmt_reserva->execute([
        $utilizador_id,
        $item_id,
        $data_inicio->format('Y-m-d'),
        $data_fim->format('Y-m-d')
    ]);

    // 8. Atualizar o estado do artigo para 'indisponivel' (ou 'reservado', se usares esse estado no teu enum)
    $stmt_update_item = $pdo->prepare("UPDATE itens SET estado = 'indisponivel' WHERE id = ?");
    $stmt_update_item->execute([$item_id]);

    // Se correu tudo bem até aqui, grava definitivamente as alterações nas duas tabelas
    $pdo->commit();

    $_SESSION['alerta'] = [
        'tipo' => 'success',
        'mensagem' => '🎉 Reserva efetuada com sucesso! O exemplar foi reservado para si.'
    ];

} catch (Exception $e) {
    // Se algo falhou no bloco try, cancela qualquer alteração feita para não corromper os dados
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['alerta'] = [
        'tipo' => 'danger',
        'mensagem' => '❌ Ocorreu um erro inesperado ao processar a reserva. Erro: ' . $e->getMessage()
    ];
}

// 9. Redirecionar de volta para a página principal onde o Toast dinâmico tratará de exibir a mensagem
header("Location: index.php");
exit();