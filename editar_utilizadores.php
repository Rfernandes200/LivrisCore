<?php
session_start();
require 'config.php';

// Bloqueio de Segurança: Se não for admin, cancela a operação imediatamente
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id_alvo = (int)($_POST['utilizador_id'] ?? 0);
    $id_admin_atual = (int)($_SESSION['utilizador_id'] ?? 0);

    if ($id_alvo <= 0) {
        $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => '❌ Utilizador inválido ou não especificado.'];
        header("Location: admin.php?seccao=utilizadores");
        exit();
    }

    try {
        // 1. GRAVAR DADOS DO POP-UP (NOME, EMAIL, CARGO, ESTADO)
        if ($acao === 'atualizar_completo') {
            $nome = trim($_POST['nome']);
            $email = trim($_POST['email']);
            
            if (empty($nome) || empty($email)) {
                $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => '❌ Os campos Nome e Email são obrigatórios.'];
                header("Location: admin.php?seccao=utilizadores");
                exit();
            }

            // Salvaguarda no Servidor: Se o admin estiver a modificar-se a si próprio...
            if ($id_alvo === $id_admin_atual) {
                // ...Atualiza apenas nome e email por segurança
                $stmt = $pdo->prepare("UPDATE utilizadores SET nome = :nome, email = :email WHERE id = :id");
                $stmt->execute(['nome' => $nome, 'email' => $email, 'id' => $id_alvo]);
            } else {
                // CORREÇÃO AQUI: Captura o cargo vindo do select
                $tipo_vindo = trim($_POST['tipo'] ?? 'user');
                
                // Forçamos o valor para o formato correto (Se a tua BD usar 1 para admin, enviamos 1, senão enviamos 'admin')
                // Vamos tentar cobrir as duas hipóteses enviando o valor correto baseado no que selecionaste
                if ($tipo_vindo === 'admin') {
                    // Se a tua tabela usar números inteiros na coluna 'tipo', mudamos para 1. Caso contrário, mantém 'admin'
                    $tipo_final = 1; 
                } else {
                    $tipo_final = 0;
                }

                $ativo = (int)($_POST['ativo'] ?? 1);

                // Executa a atualização com o valor convertido
                $stmt = $pdo->prepare("UPDATE utilizadores SET nome = :nome, email = :email, tipo = :tipo, ativo = :ativo WHERE id = :id");
                
                // Se der erro por causa do número, o PDO vai tentar adaptar para String automaticamente.
                try {
                    $stmt->execute([
                        'nome' => $nome,
                        'email' => $email,
                        'tipo' => $tipo_final,
                        'ativo' => $ativo,
                        'id' => $id_alvo
                    ]);
                } catch (PDOException $e) {
                    // Caso a tua base de dados use estritamente TEXTO ('admin' / 'user') em vez de números (1 / 0):
                    $tipo_texto = ($tipo_vindo === 'admin') ? 'admin' : 'user';
                    $stmt = $pdo->prepare("UPDATE utilizadores SET nome = :nome, email = :email, tipo = :tipo, ativo = :ativo WHERE id = :id");
                    $stmt->execute([
                        'nome' => $nome,
                        'email' => $email,
                        'tipo' => $tipo_texto,
                        'ativo' => $ativo,
                        'id' => $id_alvo
                    ]);
                }
            }

            $_SESSION['alerta'] = ['tipo' => 'sucesso', 'mensagem' => '🎉 Perfil do utilizador atualizado com sucesso!'];
        }

        // 2. ELIMINAÇÃO PERMANENTE DE CONTAS
        elseif ($acao === 'eliminar_utilizador') {
            if ($id_alvo === $id_admin_atual) {
                $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => '❌ Operação Cancelada: Não podes eliminar o teu próprio perfil de administrador.'];
            } else {
                $stmt = $pdo->prepare("DELETE FROM utilizadores WHERE id = :id");
                $stmt->execute(['id' => $id_alvo]);
                $_SESSION['alerta'] = ['tipo' => 'sucesso', 'mensagem' => '🗑️ A conta do utilizador foi eliminada permanentemente.'];
            }
        }

    } catch (PDOException $e) {
        $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => '❌ Erro de Base de Dados: ' . $e->getMessage()];
    }
}

// Redireciona de volta mantendo o Administrador focado na aba correta
header("Location: admin.php?seccao=utilizadores");
exit();