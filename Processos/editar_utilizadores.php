<?php
session_start();
require '../config.php';

// Bloqueio de Segurança: Se não for admin, cancela a operação imediatamente
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id_alvo = (int)($_POST['utilizador_id'] ?? 0);
    $id_admin_atual = (int)($_SESSION['utilizador_id'] ?? 0);

    if ($id_alvo <= 0) {
        $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Utilizador inválido ou não especificado.'];
        header("Location: ../admin.php?seccao=utilizadores");
        exit();
    }

    try {
        // ==========================================
        // 1. GRAVAR DADOS DO POP-UP (NOME, EMAIL, TELEMÓVEL, CARGO, ESTADO)
        // ==========================================
        if ($acao === 'actualizar_completo') {
            $nome = trim($_POST['nome'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $telemovel = trim($_POST['telemovel'] ?? '');

            if (empty($nome) || empty($email)) {
                $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Os campos Nome e Email são obrigatórios.'];
                header("Location: ../admin.php?seccao=utilizadores");
                exit();
            }

            // NOVA VALIDAÇÃO: Se o telemóvel não estiver vazio, valida se tem apenas números e exatamente 9 dígitos
            if (!empty($telemovel)) {
                if (!preg_match('/^[0-9]{9}$/', $telemovel)) {
                    $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'O número de telemóvel deve conter apenas números e ter exatamente 9 dígitos.'];
                    header("Location: ../admin.php?seccao=utilizadores");
                    exit();
                }
            } else {
                $telemovel = null; // Caso queiras permitir limpar o telemóvel (deixar em branco)
            }

            // Salvaguarda no Servidor: Se o admin estiver a modificar-se a si próprio...
            if ($id_alvo === $id_admin_atual) {
                // ...Atualiza nome, email e telemóvel por segurança (não mexe no cargo nem no estado ativo)
                $stmt = $pdo->prepare("UPDATE utilizadores SET nome = :nome, email = :email, telemovel = :telemovel WHERE id = :id");
                $stmt->execute([
                    'nome' => $nome, 
                    'email' => $email, 
                    'telemovel' => $telemovel, 
                    'id' => $id_alvo
                ]);
            } else {
                $tipo_vindo = trim($_POST['tipo'] ?? 'user');
                $ativo = (int)($_POST['ativo'] ?? 1);

                // Tratamento inteligente do Cargo (Aceita 'admin', '1', 'user' ou '0')
                if ($tipo_vindo === 'admin' || $tipo_vindo === '1') {
                    $tipo_final = 1; 
                } else {
                    $tipo_final = 0; 
                }

                // Executa a primeira tentativa (Formato Numérico Avançado) com telemovel
                $stmt = $pdo->prepare("UPDATE utilizadores SET nome = :nome, email = :email, telemovel = :telemovel, tipo = :tipo, ativo = :ativo WHERE id = :id");
                
                try {
                    $stmt->execute([
                        'nome'      => $nome,
                        'email'     => $email,
                        'telemovel' => $telemovel,
                        'tipo'      => $tipo_final,
                        'ativo'     => $ativo,
                        'id'        => $id_alvo
                    ]);
                } catch (PDOException $e) {
                    // CASO DE RECURSO: Se a tua tabela usar estritamente Texto/VARCHAR ('admin' / 'user') e rejeitar números:
                    $tipo_texto = ($tipo_vindo === 'admin' || $tipo_vindo === '1') ? 'admin' : 'user';
                    
                    $stmt = $pdo->prepare("UPDATE utilizadores SET nome = :nome, email = :email, telemovel = :telemovel, tipo = :tipo, ativo = :ativo WHERE id = :id");
                    $stmt->execute([
                        'nome'      => $nome,
                        'email'     => $email,
                        'telemovel' => $telemovel,
                        'tipo'      => $tipo_texto,
                        'ativo'     => $ativo,
                        'id'        => $id_alvo
                    ]);
                }
            }

            $_SESSION['alerta'] = ['tipo' => 'sucesso', 'mensagem' => '🎉 Perfil do utilizador atualizado com sucesso!'];
        }

        // ==========================================
        // 2. ELIMINAÇÃO PERMANENTE DE CONTAS
        // ==========================================
        elseif ($acao === 'eliminar_utilizador') {
            if ($id_alvo === $id_admin_atual) {
                $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Operação Cancelada: Não podes eliminar o teu próprio perfil de administrador.'];
            } else {
                $stmt = $pdo->prepare("DELETE FROM utilizadores WHERE id = :id");
                $stmt->execute(['id' => $id_alvo]);
                $_SESSION['alerta'] = ['tipo' => 'sucesso', 'mensagem' => 'A conta do utilizador foi eliminada permanentemente.'];
            }
        }

    } catch (PDOException $e) {
        $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Erro de Base de Dados: ' . $e->getMessage()];
    }
}

// Redireciona de volta mantendo o Administrador focado na aba correta
header("Location: ../admin.php?seccao=utilizadores");
exit();