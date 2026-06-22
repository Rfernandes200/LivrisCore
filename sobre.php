<?php 
session_start();
require 'config.php'; 
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre o BiblioBase - Sistema de Biblioteca</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Styles/StylesIndex.css">
    <link rel="stylesheet" href="Styles/StylesIndex2.css">
    <style>
        /* Reset para evitar interferências de corte vindas de outros ficheiros */
        .about-page-header {
            width: 100%;
            padding: 40px 20px 20px 20px;
            text-align: center;
            box-sizing: border-box;
            background: transparent;
            height: auto !important; /* Força a altura a adaptar-se ao texto */
            min-height: unset !important;
            overflow: visible !important;
        }
        
        .about-page-header h1 {
            font-family: 'Playfair Display', serif;
            color: white;
            font-size: 2.8rem;
            margin: 0 0 15px 0;
            line-height: 1.2 !important; /* Garante que o texto respira na vertical */
            height: auto !important;
            display: block;
        }
        
        .about-page-header h1 span {
            color: #3b82f6;
        }

        .about-wrapper {
            max-width: 900px;
            margin: 20px auto 60px auto;
            padding: 0 20px;
            font-family: 'Inter', sans-serif;
            color: #cbd5e1;
            text-align: left;
            box-sizing: border-box;
        }
        
        .about-card {
            background: #0b0f19;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            margin-bottom: 30px;
        }
        
        .about-card h2 {
            color: white;
            font-size: 1.4rem;
            margin-top: 0;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .rules-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 25px;
        }
        
        @media (max-width: 768px) {
            .rules-grid {
                grid-template-columns: 1fr;
            }
        }

        .rule-box {
            background: rgba(30, 41, 59, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.03);
            padding: 20px;
            border-radius: 8px;
        }
        
        .rule-box h3 {
            color: #60a5fa;
            margin-top: 0;
            font-size: 1.05rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .rule-box p {
            margin: 0;
            font-size: 0.9rem;
            line-height: 1.6;
            color: #94a3b8;
        }
        
        .highlight-yellow {
            color: #eab308;
            font-weight: 600;
        }
    </style>
</head>
<body>

<?php require 'navbar.php'; ?>

<div class="about-page-header" style="margin-top: 100px;">
    <h1>Sobre o <span>BiblioBase</span></h1>
    <p style="max-width: 600px; margin: 0 auto; color: #94a3b8; font-size: 1.05rem; line-height: 1.5; font-family: 'Inter', sans-serif;">
        A plataforma digital concebida para modernizar, organizar e facilitar o acesso ao acervo literário da nossa biblioteca.
    </p>
</div>

<div class="about-wrapper">
    
    <div class="about-card">
        <h2><span>📖</span> O que é o BiblioBase?</h2>
        <p style="line-height: 1.7; margin: 0; font-size: 0.95rem;">
            O <strong>BiblioBase</strong> é um sistema de gestão bibliotecária em tempo real. Desenvolvido com foco na simplicidade e fluidez, o sistema permite que os leitores naveguem por todo o catálogo de livros disponíveis, consultem referências técnicas catalogadas por indexação <span style="color: #3b82f6; font-weight: 600;">CDU</span>, visualizem sinopses detalhadas e façam reservas instantâneas sem burocracias.
        </p>
    </div>

    <div class="about-card">
        <h2><span>⚖️</span> Regras de Utilização & Reservas</h2>
        <p style="margin: 0; color: #94a3b8; font-size: 0.95rem;">
            Para garantir que todos os utilizadores tenham oportunidades justas de acesso aos livros do catálogo, a nossa plataforma rege-se pelas seguintes diretrizes automatizadas:
        </p>

        <div class="rules-grid">
            <div class="rule-box">
                <h3><span>📅</span> Limite de Pedidos</h3>
                <p>Cada utilizador registado pode manter, no máximo, <span class="highlight-yellow">Até 2 Reservas Ativas</span> em simultâneo no sistema. Se tentar reservar um terceiro livro, o botão ficará trancado automaticamente.</p>
            </div>

            <div class="rule-box">
                <h3><span>⏳</span> Janela de Tolerância</h3>
                <p>Após confirmar uma reserva online, o sistema gera um código exclusivo. Tem um prazo limite estrito de <span class="highlight-yellow">4 Horas</span> para se dirigir ao balcão e levantar o livro, caso contrário a reserva expira automaticamente.</p>
            </div>

            <div class="rule-box">
                <h3><span>🔄</span> Cancelamento Autónomo</h3>
                <p>Enganou-se no livro ou mudou de ideias? Não há problema. Pode clicar no botão <span style="color: #ef4444; font-weight: 600;">Cancelar</span> diretamente na página inicial para libertar o exemplar para outros leitores.</p>
            </div>

            <div class="rule-box">
                <h3><span>🏷️</span> Classificação Técnica</h3>
                <p>Os livros estão arrumados segundo as normas da Classificação Decimal Universal (CDU), tornando muito fácil encontrar outras obras correlacionadas na mesma prateleira temática.</p>
            </div>
        </div>
    </div>

    <div class="about-card" style="text-align: center; background: rgba(59, 130, 246, 0.03); border: 1px solid rgba(59, 130, 246, 0.15); margin-bottom: 0;">
        <h2 style="justify-content: center; color: #60a5fa;">👋 Precisa de Ajuda Extra?</h2>
        <p style="margin-bottom: 0; font-size: 0.95rem; line-height: 1.6;">
            Se encontrar alguma dificuldade no acesso ou necessitar de prolongar o tempo de um empréstimo físico já recolhido, contacte um dos nossos funcionários ou administradores diretamente no balcão de atendimento da biblioteca.
        </p>
    </div>

</div>

<footer style="margin-top: 40px;">
    <p>&copy; 2026 BiblioBase - Sistema de Gestão de Biblioteca</p>
</footer>

</body>
</html>