<?php
session_start();
require 'config.php';

// Validar se o utilizador está logado e se enviou um ID de item válido
if (!isset($_SESSION['utilizador_id']) || !isset($_POST['item_id'])) {
    $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Inicie sessão para efetuar uma reserva.'];
    header("Location: login.php");
    exit();
}

$utilizador_id = (int)$_SESSION['utilizador_id'];
$item_id = (int)$_POST['item_id'];

try {
    $pdo->beginTransaction();

    // 1. Verificar se o artigo ainda está disponível
    $stmt = $pdo->prepare("SELECT estado FROM itens WHERE id = :id FOR UPDATE");
    $stmt->execute(['id' => $item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item || strtolower($item['estado']) !== 'disponivel') {
        $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Este artigo já não se encontra disponível para reserva.'];
        $pdo->rollBack();
        header("Location: index.php");
        exit();
    }

    // 2. GERAR O CÓDIGO DE VALIDAÇÃO DE 3 DÍGITOS
    // str_pad garante que números menores que 100 fiquem com zeros à esquerda (ex: 007, 042)
    $codigo_validacao = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);

    // Definir as datas limite (Hora atual e expiração em 4 horas)
    $data_inicio = date('Y-m-d H:i:s');
    $data_fim = date('Y-m-d H:i:s', strtotime('+4 hours'));

    // 3. Criar o registo na tabela de reservas com o código gerado
    $sql_reserva = "INSERT INTO reservas (utilizador_id, item_id, data_inicio, data_fim, status, codigo_validacao) 
                    VALUES (:user_id, :item_id, :data_inicio, :data_fim, 'pendente', :codigo)";
    $stmt_reserva = $pdo->prepare($sql_reserva);
    $stmt_reserva->execute([
        'user_id'     => $utilizador_id,
        'item_id'     => $item_id,
        'data_inicio' => $data_inicio,
        'data_fim'    => $data_fim,
        'codigo'      => $codigo_validacao
    ]);

    // 4. Mudar o estado do item para 'reservado'
    $stmt_update = $pdo->prepare("UPDATE itens SET estado = 'reservado' WHERE id = :id");
    $stmt_update->execute(['id' => $item_id]);

    // Guardar o código na sessão para o HTML do catálogo o conseguir exibir no Pop-up
    $_SESSION['reserva_sucesso_codigo'] = $codigo_validacao;
    $_SESSION['alerta'] = ['tipo' => 'sucesso', 'mensagem' => 'Artigo reservado com sucesso!'];

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Erro ao processar reserva: ' . $e->getMessage()];
}

// Redireciona de volta para o catálogo (index.php) onde o Pop-up vai disparar
header("Location: index.php");
exit();