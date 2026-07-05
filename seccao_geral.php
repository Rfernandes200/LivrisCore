<?php
// Bloqueio de Segurança direto no ficheiro incluído
if (!isset($_SESSION['utilizador_tipo']) || ((int)$_SESSION['utilizador_tipo'] !== 1 && $_SESSION['utilizador_tipo'] !== 'admin')) {
    exit('Acesso negado');
}
?>

<!-- Container Principal do Painel Geral (Design Limpo e Claro) -->
<div class="seccao-geral-container" style="background-color: #ffffff !important; color: #1e293b !important; min-height: 100vh; padding: 20px; font-family: 'Inter', sans-serif;">
    
    <!-- Cabeçalho da Secção -->
    <div style="margin-bottom: 30px;">
        <h1 style="font-family: 'Playfair Display', serif; font-size: 2.2rem; color: #0f172a; font-weight: bold; margin: 0 0 5px 0;">Painel Geral</h1>
        <p style="color: #64748b; margin: 0; font-size: 0.95rem;">Visão unificada do estado do sistema de gestão.</p>
    </div>
    
    <!-- Grid de Estatísticas (Formato Horizontal Apelativo) -->
    <div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; width: 100%;">
        
        <!-- Cartão: Utilizadores -->
        <div class="stat-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 10px; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.04)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.02)';">
            <h3 style="margin: 0; color: #64748b; font-size: 0.85rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Utilizadores</h3>
            <p style="margin: 0; font-size: 2.4rem; font-weight: 700; color: #2563eb; line-height: 1;"><?= $total_utilizadores; ?></p>
        </div>
        
        <!-- Cartão: Reservas Ativas -->
        <div class="stat-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 10px; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.04)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.02)';">
            <h3 style="margin: 0; color: #64748b; font-size: 0.85rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Reservas Ativas</h3>
            <p style="margin: 0; font-size: 2.4rem; font-weight: 700; color: #2563eb; line-height: 1;"><?= $total_reservas; ?></p>
        </div>
        
        <!-- Cartão: Empréstimos Ativos -->
        <div class="stat-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 10px; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.04)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.02)';">
            <h3 style="margin: 0; color: #64748b; font-size: 0.85rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Empréstimos Ativos</h3>
            <p style="margin: 0; font-size: 2.4rem; font-weight: 700; color: #2563eb; line-height: 1;"><?= $total_emprestimos_ativos; ?></p>
        </div>
        
        <!-- Cartão: Livros no Catálogo -->
        <div class="stat-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 10px; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.04)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.02)';">
            <h3 style="margin: 0; color: #64748b; font-size: 0.85rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Livros no Catálogo</h3>
            <p style="margin: 0; font-size: 2.4rem; font-weight: 700; color: #2563eb; line-height: 1;"><?= $total_artigos; ?></p>
        </div>
        
    </div>
</div>