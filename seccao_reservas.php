<?php
// Bloqueio de Segurança direto no ficheiro incluído
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    exit('Acesso negado');
}
?>

<div class="seccao-artigos-container" style="background-color: #ffffff !important; color: #1e293b !important; min-height: 100vh; padding: 20px; font-family: 'Inter', sans-serif;">

    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; width: 100%;">
        <div>
            <h1 style="font-family: 'Playfair Display', serif; font-size: 2.2rem; color: #0f172a; font-weight: bold; margin: 0 0 5px 0;">Controlo de Reservas Ativas</h1>
            <p style="color: #64748b; margin: 0; font-size: 0.95rem;">Abaixo encontram-se todos os pedidos de reserva pendentes de levantamento.</p>
        </div>
    </div>

    <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 20px; width: 100%;">
        <div style="white-space: nowrap; font-size: 0.9rem; color: #64748b; background: #f8fafc; padding: 8px 14px; border-radius: 20px; border: 1px solid #e2e8f0; font-weight: 500;">
            <span><?= count($reservas); ?> reserva(s) encontrada(s)</span>
        </div>
    </div>

    <div style="width: 100%; overflow-x: auto; border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                    <th style="width: 60px; padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">ID</th>
                    <th style="padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Utilizador</th>
                    <th style="padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Artigo / Livro</th>
                    <th style="padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Data de Início</th>
                    <th style="padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Data Limite</th>
                    <th style="padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Estado</th>
                    <th style="width: 160px; padding: 16px 20px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservas)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #64748b; padding: 50px; font-weight: 500;">Não existem reservas ativas no sistema de momento.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reservas as $res): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding: 14px 20px; font-weight: 600; color: #64748b;">#<?= $res['id']; ?></td>
                            <td style="padding: 14px 20px; font-weight: 600; color: #1e293b; font-size: 0.95rem;"><?= htmlspecialchars($res['user_nome']); ?></td>
                            <td style="padding: 14px 20px; color: #334155; font-weight: 500;"><?= htmlspecialchars($res['item_titulo']); ?></td>
                            <td style="padding: 14px 20px; color: #475569; font-size: 0.88rem; white-space: nowrap;"><?= date('d/m/Y', strtotime($res['data_inicio'])); ?></td>
                            <td style="padding: 14px 20px; color: #b45309; font-weight: 600; font-size: 0.88rem; white-space: nowrap;">
                                <?= !empty($res['data_fim']) ? date('d/m/Y', strtotime($res['data_fim'])) : 'N/A'; ?>
                            </td>
                            <td style="padding: 14px 20px;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; padding: 5px 12px; min-width: 95px; border-radius: 6px; text-transform: uppercase; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.04em; color: #2563eb; border: 1px solid #bfdbfe; background: #eff6ff;">
                                    <?= htmlspecialchars($res['status']); ?>
                                </span>
                            </td>
                            <td style="padding: 14px 20px; text-align: center;">
                                <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                    <form action="admin.php" method="POST" onsubmit="return confirm('Tem a certeza que deseja cancelar esta reserva administrativamente?');" style="margin: 0;">
                                        <input type="hidden" name="acao_admin" value="cancelar_reserva_admin">
                                        <input type="hidden" name="reserva_id" value="<?= $res['id']; ?>">
                                        
                                        <button type="submit" style="background: #fee2e2 !important; color: #dc2626 !important; border: 1px solid #fecaca !important; padding: 6px 14px; font-size: 0.8rem; font-weight: 600; border-radius: 6px; cursor: pointer; transition: background 0.2s; display: inline-flex; align-items: center; gap: 4px;" onmouseover="this.style.background='#fca5a5'" onmouseout="this.style.background='#fee2e2'">
                                            Cancelar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>