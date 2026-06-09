<?php
session_start();
require 'config.php';

if (!isset($_SESSION['utilizador_id'])) {
    header("Location: login.php");
    exit();
}

$id_logado = (int)$_SESSION['utilizador_id'];

// ================= REGRAS DE NEGÓCIO (DIAS SEGUROS) =================
$hoje_php    = date('Y-m-d');
$limite_dias = 15; // Altera aqui se quiseres outro limite máximo de dias
$max_fim_php = date('Y-m-d', strtotime("+$limite_dias days"));

try {
    // 1. Reservas Pendentes
    $sql_pendentes = "SELECT reservas.*, itens.titulo as item_titulo, categorias.nome as cat_nome
                      FROM reservas
                      INNER JOIN itens ON reservas.item_id = itens.id
                      LEFT JOIN categorias ON itens.categoria_id = categorias.id
                      WHERE reservas.utilizador_id = :user_id AND reservas.status = 'pendente'
                      ORDER BY reservas.id DESC";
    $stmt_p = $pdo->prepare($sql_pendentes);
    $stmt_p->execute(['user_id' => $id_logado]);
    $reservas_pendentes = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

    // 2. Empréstimos Ativos
    $sql_ativos = "SELECT emprestimos.*, itens.titulo as item_titulo, categorias.nome as cat_nome
                   FROM emprestimos 
                   INNER JOIN itens ON emprestimos.item_id = itens.id 
                   LEFT JOIN categorias ON itens.categoria_id = categorias.id
                   WHERE emprestimos.utilizador_id = :user_id AND emprestimos.data_devolucao_real IS NULL
                   ORDER BY emprestimos.data_prevista_devolucao ASC";
    $stmt_a = $pdo->prepare($sql_ativos);
    $stmt_a->execute(['user_id' => $id_logado]);
    $emprestimos_ativos = $stmt_a->fetchAll(PDO::FETCH_ASSOC);

    // 3. Histórico de Empréstimos
    $sql_historico = "SELECT emprestimos.*, itens.titulo as item_titulo, categorias.nome as cat_nome
                      FROM emprestimos 
                      INNER JOIN itens ON emprestimos.item_id = itens.id 
                      LEFT JOIN categorias ON itens.categoria_id = categorias.id
                      WHERE emprestimos.utilizador_id = :user_id AND emprestimos.data_devolucao_real IS NOT NULL
                      ORDER BY emprestimos.data_devolucao_real DESC";
    $stmt_h = $pdo->prepare($sql_historico);
    $stmt_h->execute(['user_id' => $id_logado]);
    $historico_emprestimos = $stmt_h->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Os Meus Empréstimos - BiblioBase</title>
    <link rel="stylesheet" href="Styles/StylesIndex.css">
    <link rel="stylesheet" href="Styles/StyleAdmin.css">
    <link rel="stylesheet" href="Styles/Styleempres.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

    <nav class="navbar navbar-fixed">
        <div class="nav-left">
            <div class="logo"><strong>B</strong> BiblioBase</div>
            <div class="menu">
                <a href="index.php">Catálogo</a>
                <a href="emprestimos.php" class="active">Empréstimos</a>
                <a href="reservas.php">Reservas</a>
        
                <?php if (isset($_SESSION['utilizador_tipo']) && ((int)$_SESSION['utilizador_tipo'] === 1 || $_SESSION['utilizador_tipo'] === 'admin')): ?>
                    <a href="admin.php" style="color: #60a5fa; font-weight: 600; margin-left: 15px;">⚡ Administração</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="nav-right">
            <?php 
                $nome = $_SESSION['utilizador_nome'] ?? "Utilizador";
                $inicial = strtoupper(substr($nome, 0, 1));
            ?>
            <div class="profile-box">
                <div class="user-avatar"><?php echo $inicial; ?></div>
                <a href="perfil.php" class="user-name" style="text-decoration: none; color: inherit; font-size:0.9rem; font-weight:500; margin-right:10px;">
                    <?php echo htmlspecialchars($nome); ?>
                </a>
                <a href="logout.php" class="logout-link" style="font-size:0.85rem; color:#ef4444; text-decoration:none;">Sair</a>
            </div>
        </div>
    </nav>

    <div class="admin-container admin-container-block">
        <main class="admin-content admin-content-padded">
            
            <?php if (isset($_SESSION['alerta'])): ?>
                <div class="alert-box <?= $_SESSION['alerta']['tipo'] === 'sucesso' ? 'alert-sucesso' : 'alert-erro' ?>">
                    <?= $_SESSION['alerta']['mensagem']; ?>
                </div>
                <?php unset($_SESSION['alerta']); ?>
            <?php endif; ?>

            <h1>Os Meus Empréstimos e Solicitações</h1>
            <p class="admin-subtitle admin-subtitle-margin">Confirme as suas reservas ativas inserindo o código ou acompanhe os artigos em sua posse.</p>

            <h2 class="table-section-title title-pendente">⏳ Reservas Efetuadas (Aguardar Validação)</h2>
            <div class="table-responsive table-wrapper">
                <table class="agent-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Artigo Reservado</th>
                            <th>Formato</th>
                            <th>Hora da Solicitação</th>
                            <th style="width: 240px; text-align: center;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservas_pendentes)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #64748b; padding: 25px;">Não tem nenhuma reserva pendente de validação.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reservas_pendentes as $res): ?>
                                <tr>
                                    <td class="td-id">#<?= $res['id']; ?></td>
                                    <td style="font-weight: 600; color: #f8fafc;"><?= htmlspecialchars($res['item_titulo']); ?></td>
                                    <td style="color: #94a3b8;"><?= htmlspecialchars($res['cat_nome']); ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($res['data_inicio'])); ?></td>
                                    <td style="text-align: center; display: flex; gap: 8px; justify-content: center; align-items: center; border:none;">
                                        
                                        <button type="button" class="btn-confirmar-modal" 
                                                style="cursor: pointer;"
                                                onclick='abrirModalComDias(<?= (int)$res['id']; ?>, <?= json_encode($res['item_titulo'], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                            Confirmar
                                        </button>

                                        <form action="processo_emprestimo.php" method="POST" style="margin:0;" onsubmit="return confirm('Tem a certeza que deseja cancelar esta reserva?');">
                                            <input type="hidden" name="acao" value="cancelar_reserva">
                                            <input type="hidden" name="reserva_id" value="<?= $res['id']; ?>">
                                            <button type="submit" class="btn-cancelar-inline">Cancelar</button>
                                        </form>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h2 class="table-section-title title-ativo">📖 Artigos Contigo (Em Curso)</h2>
            <div class="table-responsive table-wrapper">
                <table class="agent-table">
                    <tbody>
                        <?php if (empty($emprestimos_ativos)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #64748b; padding: 25px;">Não tens nenhum artigo emprestado em tua posse de momento.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($emprestimos_ativos as $emp): 
                                $hoje = strtotime(date('Y-m-d'));
                                $data_limite = strtotime($emp['data_prevista_devolucao']);
                                $dias_restantes = round(($data_limite - $hoje) / (60 * 60 * 24));
                            ?>
                                <tr>
                                    <td class="td-id">#<?= $emp['id']; ?></td>
                                    <td style="font-weight: 500; color: #f8fafc;"><?= htmlspecialchars($emp['item_titulo']); ?></td>
                                    <td style="color: #94a3b8;"><?= htmlspecialchars($emp['cat_nome']); ?></td>
                                    <td><?= date('d/m/Y', strtotime($emp['data_saida'])); ?></td>
                                    <td class="<?= ($dias_restantes <= 2) ? 'data-aviso-urgente' : 'data-aviso-normal' ?>">
                                        <?= date('d/m/Y', strtotime($emp['data_prevista_devolucao'])); ?>
                                        <small class="data-subtexto">(Faltam <?= $dias_restantes; ?> dias)</small>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge-status-ativo">Ativo</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div id="confirmReserveModal" style="display: none; position: fixed !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; background: rgba(10, 15, 30, 0.92) !important; justify-content: center; align-items: center; z-index: 999999999999 !important;">
        <div style="background: #1e293b; padding: 30px; border-radius: 12px; max-width: 480px; width: 90%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.7); border: 1px solid #475569; font-family: 'Inter', sans-serif;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #334155; padding-bottom: 12px;">
                <div>
                    <small style="color: #34d399; font-weight: bold; font-size: 0.75rem; letter-spacing: 1px; display:block; margin-bottom:4px;">VALIDAR RESERVA</small>
                    <h2 id="modalTargetTitulo" style="color: #f8fafc; margin: 0; font-size: 1.25rem; font-weight: 600;">Oficializar Empréstimo</h2>
                </div>
                <button type="button" id="closeConfirmModalBtn" style="background: none; border: none; color: #94a3b8; font-size: 2.2rem; cursor: pointer; line-height: 0.8;">&times;</button>
            </div>

            <form action="processo_emprestimo.php" method="POST" id="modalFormEmprestimo">
    <input type="hidden" name="acao" value="oficializar_emprestimo">
    <input type="hidden" name="reserva_id" id="modalTargetReservaId">

    <div style="display: flex; gap: 15px; margin-bottom: 25px;">
        <div style="flex: 1; position: relative;">
            <label style="color: #94a3b8; font-size: 0.75rem; font-weight:600; display: block; margin-bottom: 6px;">DATA DE INÍCIO</label>
            <input type="date" name="data_inicio" id="modalInputDataInicio" required 
                   onkeydown="return false" 
                   onclick="if(typeof this.showPicker === 'function') this.showPicker();"
                   style="width: 100%; padding: 11px; padding-right: 30px; background: #0f172a; color: #fff; border: 1px solid #334155; border-radius: 6px; font-size:0.9rem; cursor: pointer;">
        </div>
        
        <div style="flex: 1; position: relative;">
            <label style="color: #94a3b8; font-size: 0.75rem; font-weight:600; display: block; margin-bottom: 6px;">DATA DE FIM (MÁX. <?=$limite_dias?> DIAS)</label>
            <input type="date" name="data_fim" id="modalInputDataFim" required 
                   onkeydown="return false" 
                   onclick="if(typeof this.showPicker === 'function') this.showPicker();"
                   style="width: 100%; padding: 11px; padding-right: 30px; background: #0f172a; color: #fff; border: 1px solid #334155; border-radius: 6px; font-size:0.9rem; cursor: pointer;">
        </div>
    </div>

    <div style="margin-bottom: 25px; border-top: 1px solid #334155; padding-top: 15px;">
        <label style="color: #94a3b8; font-size: 0.75rem; font-weight:600; display: block; margin-bottom: 8px;">CÓDIGO DE SEGURANÇA (3 DÍGITOS) *</label>
        <input type="text" name="codigo_validacao" required maxlength="3" pattern="\d{3}" placeholder="000" style="width: 100%; padding: 12px; background: #0f172a; color: #34d399; font-size: 1.6rem; text-align: center; font-weight: bold; border: 1px solid #334155; border-radius: 6px; letter-spacing: 6px;">
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #334155; padding-top: 15px;">
        <button type="button" id="cancelConfirmModalBtn" style="padding: 10px 18px; background: transparent; color: #94a3b8; border: 1px solid #334155; border-radius: 6px; cursor: pointer; font-weight:500;">Voltar</button>
        <button type="submit" style="padding: 10px 18px; background: #34d399; color: #0f172a; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Confirmar</button>
    </div>
</form>
        </div>
    </div>

    <script>
    // Configurações vindas de forma totalmente segura do PHP
    const DATA_HOJE_SISTEMA = "<?php echo $hoje_php; ?>";
    const TETOS_DIAS_REGRA  = <?php echo $limite_dias; ?>;

    // Função auxiliar simples para adicionar dias no formato YYYY-MM-DD
    function somarDias(dataBaseStr, quantidadeDias) {
        const d = new Date(dataBaseStr);
        d.setDate(d.getDate() + quantidadeDias);
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    }

    // Executada no clique do botão da tabela
    function abrirModalComDias(reservaId, tituloItem) {
        const modal = document.getElementById('confirmReserveModal');
        const inputInicio = document.getElementById('modalInputDataInicio');
        const inputFim = document.getElementById('modalInputDataFim');

        // Preencher identificadores textuais
        document.getElementById('modalTargetReservaId').value = reservaId;
        document.getElementById('modalTargetTitulo').innerText = 'Validar: ' + tituloItem;

        // Configurar regras do input "Data de Início"
        inputInicio.min = DATA_HOJE_SISTEMA;
        inputInicio.value = DATA_HOJE_SISTEMA;

        // Configurar regras do input "Data de Fim" (Não deixa escolher mais do que o limite estipulado)
        const dataMaximaCalculada = somarDias(DATA_HOJE_SISTEMA, TETOS_DIAS_REGRA);
        inputFim.min = DATA_HOJE_SISTEMA;
        inputFim.max = dataMaximaCalculada;
        inputFim.value = dataMaximaCalculada; // Sugere logo a data limite máxima

        // Forçar exibição soberana sobre qualquer outra regra de CSS da página
        modal.style.setProperty('display', 'flex', 'important');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('confirmReserveModal');
        const closeBtn = document.getElementById('closeConfirmModalBtn');
        const cancelBtn = document.getElementById('cancelConfirmModalBtn');
        const inputInicio = document.getElementById('modalInputDataInicio');
        const inputFim = document.getElementById('modalInputDataFim');

        // Sempre que o utilizador alterar o dia de início, recalculamos os limites permitidos do fim
        inputInicio.addEventListener('change', function() {
            if (this.value < DATA_HOJE_SISTEMA) {
                alert('A data de início não pode ser anterior ao dia de hoje!');
                this.value = DATA_HOJE_SISTEMA;
            }
            
            inputFim.min = this.value;
            const novoTetoMaximo = somarDias(this.value, TETOS_DIAS_REGRA);
            inputFim.max = novoTetoMaximo;

            if (inputFim.value < this.value) {
                inputFim.value = this.value;
            } else if (inputFim.value > novoTetoMaximo) {
                inputFim.value = novoTetoMaximo;
            }
        });

        // Monitorização reativa para a Data de Fim no calendário
        inputFim.addEventListener('change', function() {
            const limiteMaximoCorrente = somarDias(inputInicio.value, TETOS_DIAS_REGRA);
            if (this.value < inputInicio.value) {
                alert('A data de fim de empréstimo não pode ser anterior à data de início!');
                this.value = inputInicio.value;
            } else if (this.value > limiteMaximoCorrente) {
                alert(`O período máximo permitido para o empréstimo é de ${TETOS_DIAS_REGRA} dias!`);
                this.value = limiteMaximoCorrente;
            }
        });

        // Fechar o modal de forma limpa
        const fecharModal = () => { 
            modal.style.setProperty('display', 'none', 'important'); 
        };

        if (closeBtn) closeBtn.addEventListener('click', fecharModal);
        if (cancelBtn) cancelBtn.addEventListener('click', fecharModal);
        
        window.addEventListener('click', function(event) {
            if (event.target === modal) fecharModal();
        });
    });
    </script>
</body>
</html>