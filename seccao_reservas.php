<?php
// Bloqueio de Segurança direto no ficheiro incluído
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    exit('Acesso negado');
}
?>

<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; width: 100%;">
    <div>
        <h1 style="font-size: 2.2rem; color: white; font-weight: 600; margin: 0; font-family: 'Playfair Display', 'Georgia', 'Times New Roman', serif;">Controlo de Reservas Ativas</h1>
        <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1rem;">Abaixo encontram-se todos os pedidos de reserva pendentes de levantamento.</p>
    </div>
</div>

<div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 20px; width: 100%;">
    <div style="color: #64748b; font-size: 0.9rem; font-weight: 500;">
        <span><?= count($reservas); ?> reserva(s) encontrada(s)</span>
    </div>
</div>

<div class="table-responsive">
    <table class="agent-table">
        <thead>
            <tr>
                <th style="width: 60px;">ID</th>
                <th>Utilizador</th>
                <th>Artigo / Livro</th>
                <th>Data de Início</th>
                <th>Data Limite</th>
                <th>Estado</th>
                <th style="text-align: center; width: 150px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reservas)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #64748b; padding: 40px;">📅 Não existem reservas ativas no sistema de momento.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($reservas as $res): ?>
                    <tr>
                        <td class="td-id">#<?= $res['id']; ?></td>
                        <td style="font-weight: 500; color: #f8fafc;"><?= htmlspecialchars($res['user_nome']); ?></td>
                        <td style="color: #cbd5e1;"><?= htmlspecialchars($res['item_titulo']); ?></td>
                        <td><?= date('d/m/Y', strtotime($res['data_inicio'])); ?></td>
                        <td style="color: #f59e0b; font-weight: 500;">
                            <?= !empty($res['data_fim']) ? date('d/m/Y', strtotime($res['data_fim'])) : 'N/A'; ?>
                        </td>
                        <td>
                            <span style="color: #3b82f6; border: 1px solid rgba(59,130,246,0.3); background: rgba(59,130,246,0.1); padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                                <?= htmlspecialchars($res['status']); ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <form action="admin.php" method="POST" onsubmit="return confirm('Tem a certeza que deseja cancelar esta reserva administrativamente?');" style="margin: 0;">
                                <input type="hidden" name="acao_admin" value="cancelar_reserva_admin">
                                <input type="hidden" name="reserva_id" value="<?= $res['id']; ?>">
                                <button type="submit" class="btn-cancelar-reserva" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 500; cursor: pointer; transition: all 0.2s;">
                                    ❌ Cancelar
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>