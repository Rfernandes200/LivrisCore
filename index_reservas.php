<?php
// index_reservas.php
// Este ficheiro funciona como uma secção  incluída no index.php
?>

<?php if (isset($_SESSION['reserva_sucesso_codigo'])): ?>
<div id="codeSuccessModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11, 15, 25, 0.95); backdrop-filter: blur(8px); z-index: 100000; display: flex; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
    <div style="background: #0b0f19; border: 1px solid rgba(16, 185, 129, 0.3); width: 100%; max-width: 420px; border-radius: 12px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7); text-align: center; font-family: 'Inter', sans-serif; padding: 30px;">
        <h2 style="color: white; font-size: 1.5rem; margin-top: 10px; margin-bottom: 5px;">Reserva Confirmada!</h2>
        <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 25px;">Apresente o código abaixo ao funcionário para levantar o seu livro.</p>
        
        <div style="background: rgba(16, 185, 129, 0.1); border: 2px dashed #10b981; color: #10b981; font-size: 2.5rem; font-weight: 700; letter-spacing: 5px; padding: 15px; border-radius: 8px; display: inline-block; margin-bottom: 25px; font-variant-numeric: tabular-nums;">
            <?= $_SESSION['reserva_sucesso_codigo']; ?>
        </div>
        
        <p style="color: #eab308; font-size: 0.8rem; font-weight: 500; margin-bottom: 20px;">
             Atenção: Este código é único e só pode ser usado uma vez. Guarde-o.
        </p>
        
        <button type="button" id="closeCodeModalBtn" style="background: #10b981; border: none; color: #0f172a; font-weight: 600; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; width: 100%;">
            Guardei o Código, Fechar
        </button>
    </div>
</div>
<?php unset($_SESSION['reserva_sucesso_codigo']); ?>
<?php endif; ?>


<div id="reserveCatalogModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11, 15, 25, 0.95); backdrop-filter: blur(8px); z-index: 99999; display: none; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
    <div style="background: #0b0f19; border: 1px solid rgba(255, 255, 255, 0.08); width: 100%; max-width: 450px; border-radius: 12px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7); overflow: hidden; font-family: 'Inter', sans-serif;">
        <div style="padding: 20px 24px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); display: flex; justify-content: space-between; align-items: center; background: rgba(30, 41, 59, 0.2);">
            <div>
                <span style="font-size: 0.7rem; color: #3b82f6; letter-spacing: 0.15em; font-weight: 700; display: block; margin-bottom: 4px; text-align: left;">[ SOLICITAR RESERVA ]</span>
                <h2 id="txtReserveTitulo" style="font-size: 1.2rem; color: white; font-weight: 600; margin: 0; text-align: left;">Reservar Livro</h2>
            </div>
            <button type="button" id="closeReserveModalBtn" style="background: transparent; border: none; color: #64748b; font-size: 1.8rem; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <form action="Processos/processo_reserva.php" method="POST" style="padding: 24px; margin: 0; box-sizing: border-box; text-align: left;">
            <input type="hidden" name="livro_id" id="formReserveItemId">

            <p style="color: #cbd5e1; font-size: 0.9rem; line-height: 1.5; margin: 0; margin-bottom: 15px;">
                Deseja confirmar a reserva imediata deste livro? 
            </p>
            <p style="color: #eab308; background: rgba(234, 179, 8, 0.1); border: 1px solid rgba(234, 179, 8, 0.2); padding: 10px; border-radius: 6px; font-size: 0.8rem; line-height: 1.4; margin: 0;">
                 Após confirmar, será gerado um **código de levantamento**.Terá de levantar o livro na biblioteca caso contratrio o seu empréstimo será cancelado.
            </p>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 20px; margin-top: 25px;">
                <button type="button" id="cancelReserveModalBtn" style="background: transparent; border: 1px solid rgba(255, 255, 255, 0.1); color: #cbd5e1; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-size: 0.85rem;">Cancelar</button>
                <button type="submit" style="background: #3b82f6; border: none; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600;">Confirmar Reserva</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Controlo do Modal de Código Gerado
    const codeModal = document.getElementById('codeSuccessModal');
    const closeCodeBtn = document.getElementById('closeCodeModalBtn');
    if (codeModal && closeCodeBtn) {
        closeCodeBtn.addEventListener('click', function() {
            codeModal.style.display = 'none';
        });
    }

    // 2. Controlo do Modal de Formulário de Reserva
    const reserveModal = document.getElementById('reserveCatalogModal');
    const closeReserveBtn = document.getElementById('closeReserveModalBtn');
    const cancelReserveBtn = document.getElementById('cancelReserveModalBtn');

    document.querySelectorAll('.js-open-reserve').forEach(element => {
        element.addEventListener('click', function(e) {
            e.preventDefault(); 
            const id = this.dataset.id;
            const titulo = this.dataset.titulo;

            document.getElementById('formReserveItemId').value = id;
            document.getElementById('txtReserveTitulo').innerText = 'Reservar: ' + titulo;

            reserveModal.style.display = 'flex';
        });
    });

    const closeReserveModal = () => { if(reserveModal) reserveModal.style.display = 'none'; };
    if (closeReserveBtn) closeReserveBtn.addEventListener('click', closeReserveModal);
    if (cancelReserveBtn) cancelReserveBtn.addEventListener('click', closeReserveModal);
    if (reserveModal) {
        reserveModal.addEventListener('click', function(e) { 
            if (e.target === reserveModal) closeReserveModal(); 
        });
    }
});
</script>