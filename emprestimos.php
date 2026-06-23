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
    // 1. Reservas Pendentes (Faz ligação à tabela 'livros' e traz a descrição da classe CDU se existir)
    $sql_pendentes = "SELECT reservas.*, livros.titulo as item_titulo, cdu_classes.descricao as cat_nome
                      FROM reservas
                      INNER JOIN livros ON reservas.livro_id = livros.id
                      LEFT JOIN cdu_classes ON livros.cdu_codigo = cdu_classes.codigo
                      WHERE reservas.utilizador_id = :user_id AND reservas.status = 'pendente'
                      ORDER BY reservas.id DESC";
    $stmt_p = $pdo->prepare($sql_pendentes);
    $stmt_p->execute(['user_id' => $id_logado]);
    $reservas_pendentes = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

    // 2. Empréstimos Ativos (Ligado corretamente por livro_id e cdu_codigo)
    $sql_ativos = "SELECT emprestimos.*, livros.titulo as item_titulo, cdu_classes.descricao as cat_nome
                   FROM emprestimos 
                   INNER JOIN livros ON emprestimos.livro_id = livros.id 
                   LEFT JOIN cdu_classes ON livros.cdu_codigo = cdu_classes.codigo
                   WHERE emprestimos.utilizador_id = :user_id AND emprestimos.data_devolucao_real IS NULL
                   ORDER BY emprestimos.data_prevista_devolucao ASC";
    $stmt_a = $pdo->prepare($sql_ativos);
    $stmt_a->execute(['user_id' => $id_logado]);
    $emprestimos_ativos = $stmt_a->fetchAll(PDO::FETCH_ASSOC);

    // 3. Histórico de Empréstimos
    $sql_historico = "SELECT emprestimos.*, livros.titulo as item_titulo, cdu_classes.descricao as cat_nome
                      FROM emprestimos 
                      INNER JOIN livros ON emprestimos.livro_id = livros.id 
                      LEFT JOIN cdu_classes ON livros.cdu_codigo = cdu_classes.codigo
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
    
    <style>
        body {
            background-color: #0f172a;
            color: #cbd5e1;
            font-family: 'Inter', sans-serif;
        }

        .main-wrapper {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            margin-bottom: 35px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            padding-bottom: 20px;
        }

        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.3rem;
            color: #ffffff;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #64748b;
            font-size: 1rem;
        }

        .section-card {
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 35px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.25);
        }

        .table-section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .title-pendente { color: #f59e0b; }
        .title-ativo { color: #3b82f6; }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .custom-table th {
            background: rgba(15, 23, 42, 0.4);
            color: #94a3b8;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 18px;
            border-bottom: 2px solid rgba(255,255,255,0.05);
        }

        .custom-table td {
            padding: 16px 18px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            font-size: 0.92rem;
            vertical-align: middle;
        }

        .custom-table tr:hover td {
            background: rgba(255,255,255,0.01);
        }

        /* Badges e Tags customizados */
        .cdu-badge {
            background: rgba(148, 163, 184, 0.1);
            color: #94a3b8;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-status-ativo {
            background: rgba(59, 130, 246, 0.15) !important;
            color: #60a5fa !important;
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Alertas de Tempo Dinâmicos */
        .data-aviso-normal {
            color: #cbd5e1;
        }
        .data-aviso-urgente {
            color: #f87171;
            font-weight: 600;
            animation: pulse-warn 2s infinite;
        }
        .data-subtexto {
            display: block;
            font-size: 0.78rem;
            color: #64748b;
            margin-top: 2px;
        }
        .data-aviso-urgente .data-subtexto { color: #fca5a5; }

        @keyframes pulse-warn {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        /* Botões Estilizados */
        .btn-action-base {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-confirmar-modal-custom {
            background: #10b981;
            color: #0f172a;
        }
        .btn-confirmar-modal-custom:hover {
            background: #34d399;
            box-shadow: 0 0 12px rgba(16, 185, 129, 0.4);
        }

        .btn-cancelar-inline-custom {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        .btn-cancelar-inline-custom:hover {
            background: #ef4444;
            color: #fff;
        }

        .btn-entregar-inline {
            background: #3b82f6;
            color: #fff;
        }
        .btn-entregar-inline:hover {
            background: #2563eb;
            box-shadow: 0 0 12px rgba(59, 130, 246, 0.4);
        }

        .alert-container-box {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
            font-size: 0.95rem;
        }
        .alert-sucesso-custom { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #34d399; }
        .alert-erro-custom { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #f87171; }
    </style>
</head>
<body>

    <?php require 'navbar.php'; ?>

    <div class="main-wrapper">
        
        <header class="page-header">
            <h1>Os Meus Empréstimos e Solicitações</h1>
            <p>Confirme as suas reservas ativas inserindo o código ou acompanhe os artigos em sua posse de forma simples.</p>
        </header>

        <?php if (isset($_SESSION['alerta'])): ?>
            <div class="alert-container-box <?= $_SESSION['alerta']['tipo'] === 'sucesso' ? 'alert-sucesso-custom' : 'alert-erro-custom' ?>">
                <?= $_SESSION['alerta']['mensagem']; ?>
            </div>
            <?php unset($_SESSION['alerta']); ?>
        <?php endif; ?>

        <section class="section-card">
            <h2 class="table-section-title title-pendente">⏳ Reservas Efetuadas (Aguardar Validação)</h2>
            <div style="overflow-x: auto;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Artigo Reservado</th>
                            <th>Classificação (CDU)</th>
                            <th>Hora da Solicitação</th>
                            <th style="width: 240px; text-align: center;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservas_pendentes)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #64748b; padding: 35px;">Não tem nenhuma reserva pendente de validação de momento.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reservas_pendentes as $res): ?>
                                <tr>
                                    <td style="color: #64748b; font-weight: 600;">#<?= $res['id']; ?></td>
                                    <td style="font-weight: 600; color: #f8fafc;"><?= htmlspecialchars($res['item_titulo']); ?></td>
                                    <td><span class="cdu-badge"><?= $res['cat_nome'] ? htmlspecialchars($res['cat_nome']) : 'Sem classe'; ?></span></td>
                                    <td style="color: #94a3b8;"><?= date('d/m/Y H:i', strtotime($res['data_inicio'])); ?></td>
                                    <td style="text-align: center;">
                                        <div style="display: flex; gap: 10px; justify-content: center;">
                                            <button type="button" class="btn-action-base btn-confirmar-modal-custom" 
                                                    onclick='abrirModalComDias(<?= (int)$res['id']; ?>, <?= json_encode($res['item_titulo'], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                                Confirmar
                                            </button>

                                            <form action="processo_emprestimo.php" method="POST" style="margin:0;" onsubmit="return confirm('Tem a certeza que deseja cancelar esta reserva?');">
                                                <input type="hidden" name="acao" value="cancelar_reserva">
                                                <input type="hidden" name="reserva_id" value="<?= $res['id']; ?>">
                                                <button type="submit" class="btn-action-base btn-cancelar-inline-custom">Cancelar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="section-card">
            <h2 class="table-section-title title-ativo">📖 Artigos Contigo (Em Curso)</h2>
            <div style="overflow-x: auto;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Artigo</th>
                            <th>Classificação (CDU)</th>
                            <th>Data de Saída</th>
                            <th>Data Limite</th>
                            <th style="width: 200px; text-align: center;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($emprestimos_ativos)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #64748b; padding: 35px;">Não tens nenhum artigo emprestado em tua posse de momento.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($emprestimos_ativos as $emp): 
                                $hoje = strtotime(date('Y-m-d'));
                                $data_limite = strtotime($emp['data_prevista_devolucao']);
                                $dias_restantes = (int)round(($data_limite - $hoje) / (60 * 60 * 24));
                            ?>
                                <tr>
                                    <td style="color: #64748b; font-weight: 600;">#<?= $emp['id']; ?></td>
                                    <td style="font-weight: 600; color: #f8fafc;"><?= htmlspecialchars($emp['item_titulo']); ?></td>
                                    <td><span class="cdu-badge"><?= $emp['cat_nome'] ? htmlspecialchars($emp['cat_nome']) : 'Sem classe'; ?></span></td>
                                    <td style="color: #94a3b8;"><?= date('d/m/Y', strtotime($emp['data_saida'])); ?></td>
                                    <td class="<?= ($dias_restantes <= 2) ? 'data-aviso-urgente' : 'data-aviso-normal' ?>">
                                        <?= date('d/m/Y', strtotime($emp['data_prevista_devolucao'])); ?>
                                        <small class="data-subtexto">
                                            <?= $dias_restantes < 0 ? "Atrasado por " . abs($dias_restantes) . " dias!" : "Faltam $dias_restantes dias"; ?>
                                        </small>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: flex; gap: 12px; justify-content: center; align-items: center;">
                                            <span class="badge-status-ativo">Ativo</span>
                                            
                                            <form action="processo_emprestimo.php" method="POST" style="margin:0;" onsubmit="return confirm('Confirmas que queres proceder à entrega deste artigo?');">
                                                <input type="hidden" name="acao" value="entregar_emprestimo">
                                                <input type="hidden" name="emprestimo_id" value="<?= $emp['id']; ?>">
                                                <button type="submit" class="btn-action-base btn-entregar-inline">Entregar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

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
    const DATA_HOJE_SISTEMA = "<?php echo $hoje_php; ?>";
    const TETOS_DIAS_REGRA  = <?php echo $limite_dias; ?>;

    function somarDias(dataBaseStr, quantidadeDias) {
        const d = new Date(dataBaseStr);
        d.setDate(d.getDate() + quantidadeDias);
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    }

    function abrirModalComDias(reservaId, tituloItem) {
        const modal = document.getElementById('confirmReserveModal');
        const inputInicio = document.getElementById('modalInputDataInicio');
        const inputFim = document.getElementById('modalInputDataFim');

        document.getElementById('modalTargetReservaId').value = reservaId;
        document.getElementById('modalTargetTitulo').innerText = 'Validar: ' + tituloItem;

        inputInicio.min = DATA_HOJE_SISTEMA;
        inputInicio.value = DATA_HOJE_SISTEMA;

        const dataMaximaCalculada = somarDias(DATA_HOJE_SISTEMA, TETOS_DIAS_REGRA);
        inputFim.min = DATA_HOJE_SISTEMA;
        inputFim.max = dataMaximaCalculada;
        inputFim.value = dataMaximaCalculada;

        modal.style.setProperty('display', 'flex', 'important');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('confirmReserveModal');
        const closeBtn = document.getElementById('closeConfirmModalBtn');
        const cancelBtn = document.getElementById('cancelConfirmModalBtn');
        const inputInicio = document.getElementById('modalInputDataInicio');
        const inputFim = document.getElementById('modalInputDataFim');

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