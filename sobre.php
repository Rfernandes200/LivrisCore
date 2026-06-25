<?php 
session_start();
require 'config.php'; 
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre o LivrisCore - Sistema de Biblioteca</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Styles/StyleNav.css">
    <link rel="stylesheet" href="Styles/StylesSobre.css">
</head>
<body class="about-page-body">

<?php require 'navbar.php'; ?>

<div class="about-page-header">
    <h1>Sobre o <span>LivrisCore</span></h1>
    <p style="max-width: 600px; margin: 0 auto; color: #475569; font-size: 1.05rem; line-height: 1.5; font-family: 'Inter', sans-serif;">
        A plataforma digital concebida para modernizar, organizar e facilitar o acesso ao catálogo literário da nossa biblioteca.
    </p>
</div>

<div class="about-wrapper">
    
    <div class="about-card">
        <h2>O que é o LivrisCore?</h2>
        <p style="line-height: 1.7; margin: 0; font-size: 0.95rem; color: #334155;">
            O <strong>LivrisCore</strong> é um sistema de gestão bibliotecária em tempo real. Desenvolvido com foco na simplicidade e fluidez, o sistema permite que os leitores naveguem por todo o catálogo de livros disponíveis, consultem referências técnicas catalogadas por indexação <span style="color: #2563eb; font-weight: 600;">CDU</span>, visualizem sinopses detalhadas e façam reservas instantâneas sem burocracias.
        </p>
    </div>

    <div class="about-card">
        <h2>Regras de Utilização & Reservas</h2>
        <p style="margin: 0; color: #475569; font-size: 0.95rem;">
            Para garantir que todos os utilizadores tenham oportunidades justas de acesso aos livros do catálogo, a nossa plataforma rege-se pelas seguintes diretrizes automatizadas:
        </p>

        <div class="rules-grid">
            <div class="rule-box">
                <h3>Limite de Pedidos</h3>
                <p>Cada utilizador registado pode manter, no máximo, <span class="highlight-yellow">Até 2 Reservas Ativas</span> em simultâneo no sistema. Se tentar reservar um terceiro livro, o botão ficará trancado automaticamente.</p>
            </div>

            <div class="rule-box">
                <h3>Janela de Tolerância</h3>
                <p>Após confirmar uma reserva online, o sistema gera um código exclusivo. Tem um prazo limite estrito de <span class="highlight-yellow">4 Horas</span> para se dirigir ao balcão e levantar o livro, caso contrário a reserva expira automaticamente.</p>
            </div>

            <div class="rule-box">
                <h3>Cancelamento Autónomo</h3>
                <p>Enganou-se no livro ou mudou de ideias? Não há problema. Pode clicar no botão <span style="color: #ef4444; font-weight: 600;">Cancelar</span> diretamente na página inicial para libertar o exemplar para outros leitores.</p>
            </div>

            <div class="rule-box">
                <h3>Classificação Técnica</h3>
                <p>Os livros estão arrumados segundo as normas da Classificação Decimal Universal (CDU), tornando muito fácil encontrar outras obras correlacionadas na mesma prateleira temática.</p>
            </div>
        </div>
    </div>

    <div class="about-card" style="text-align: center; background: rgba(59, 130, 246, 0.04); border: 1px solid rgba(59, 130, 246, 0.2); margin-bottom: 0;">
        <h2 style="justify-content: center; color: #1d4ed8;">Precisa de Ajuda Extra?</h2>
        <p style="margin-bottom: 0; font-size: 0.95rem; line-height: 1.6; color: #1e3a8a;">
            Se encontrar alguma dificuldade no acesso ou necessitar de prolongar o tempo de um empréstimo físico já recolhido, contacte um dos nossos funcionários ou administradores diretamente no balcão de atendimento da biblioteca.
        </p>
    </div>

</div>

<footer>
    <p>&copy; 2026 LivrisCore - Sistema de Gestão de Biblioteca</p>
</footer>

</body>
</html>