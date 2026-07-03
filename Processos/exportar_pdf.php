<?php
session_start();
require_once '../config.php';

// Bloqueio de segurança rápido: Só o admin extrai o relatório
if (!isset($_SESSION['utilizador_tipo']) || (int)$_SESSION['utilizador_tipo'] !== 1) {
    header("Location: ../index.php");
    exit();
}

// 1. CARREGAR O AUTOLOAD OFICIAL (A pontar para a tua nova pasta 'dompdf')
require_once __DIR__ . '/../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ==========================================================
// 2. CONSULTAS À BASE DE DADOS
// ==========================================================
try {
    // Procurar Empréstimos em Curso (data_devolucao_real ESTÁ VAZIA)
    $stmt_em_curso = $pdo->prepare("
        SELECT e.*, u.nome as leitor_nome, l.titulo as livro_titulo, l.isbn 
        FROM emprestimos e 
        JOIN utilizadores u ON e.utilizador_id = u.id 
        JOIN livros l ON e.livro_id = l.id 
        WHERE e.data_devolucao_real IS NULL 
        ORDER BY e.data_saida DESC
    ");
    $stmt_em_curso->execute();
    $em_curso = $stmt_em_curso->fetchAll(PDO::FETCH_ASSOC);

    // Procurar Empréstimos Devolvidos (data_devolucao_real NÃO ESTÁ VAZIA)
    $stmt_devolvidos = $pdo->prepare("
        SELECT e.*, u.nome as leitor_nome, l.titulo as livro_titulo, l.isbn 
        FROM emprestimos e 
        JOIN utilizadores u ON e.utilizador_id = u.id 
        JOIN livros l ON e.livro_id = l.id 
        WHERE e.data_devolucao_real IS NOT NULL 
        ORDER BY e.data_devolucao_real DESC
    ");
    $stmt_devolvidos->execute();
    $devolvidos = $stmt_devolvidos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao gerar dados para o relatório: " . $e->getMessage());
}

// ==========================================================
// 3. CONSTRUÇÃO DO HTML / CSS DO PDF
// ==========================================================
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 40px 30px; }
        body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; background-color: #0b0f19; color: #cbd5e1; margin: 0; padding: 0; }
        .header { border-bottom: 2px solid #3b82f6; padding-bottom: 12px; margin-bottom: 30px; }
        .header h1 { color: #ffffff; font-size: 24px; margin: 0; font-weight: 700; }
        .header p { color: #64748b; font-size: 12px; margin: 5px 0 0 0; }
        
        h2 { color: #60a5fa; font-size: 16px; font-weight: 600; margin-top: 25px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.05em; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background-color: #1e293b; color: #ffffff; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 10px; text-align: left; border: 1px solid rgba(255,255,255,0.05); }
        td { padding: 10px; font-size: 11px; color: #94a3b8; border-bottom: 1px solid rgba(255,255,255,0.05); }
        tr:nth-child(even) { background-color: rgba(255, 255, 255, 0.01); }
        
        .badge { font-weight: 700; font-size: 10px; padding: 3px 7px; border-radius: 4px; text-transform: uppercase; display: inline-block; }
        .badge-curso { background-color: rgba(234, 179, 8, 0.15); color: #eab308; }
        .badge-devolvido { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }
        
        .no-data { text-align: center; color: #64748b; padding: 20px; font-style: italic; background: rgba(255,255,255,0.02); border-radius: 6px; font-size: 12px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Relatório Geral de Empréstimos</h1>
        <p>Gerado em: ' . date('d/m/Y H:i') . ' | Painel de Administração</p>
    </div>

    <!-- SECÇÃO 1: EM CURSO -->
    <h2>1. Empréstimos em Curso</h2>
    ';

if (count($em_curso) > 0) {
    $html .= '<table>
        <thead>
            <tr>
                <th style="width: 25%;">Leitor</th>
                <th style="width: 35%;">Livro (ISBN)</th>
                <th style="width: 15%;">Data Saída</th>
                <th style="width: 15%;">Prev. Devolução</th>
                <th style="width: 10%;">Estado</th>
            </tr>
        </thead>
        <tbody>';
    foreach ($em_curso as $row) {
        $html .= '<tr>
            <td><strong>' . htmlspecialchars($row['leitor_nome']) . '</strong></td>
            <td>' . htmlspecialchars($row['livro_titulo']) . ' <br><span style="color:#64748b; font-size:9px;">' . htmlspecialchars($row['isbn']) . '</span></td>
            <td>' . date('d/m/Y', strtotime($row['data_saida'])) . '</td>
            <td>' . date('d/m/Y', strtotime($row['data_prevista_devolucao'])) . '</td>
            <td><span class="badge badge-curso">Em Curso</span></td>
        </tr>';
    }
    $html .= '</tbody></table>';
} else {
    $html .= '<div class="no-data">Não existem empréstimos ativos de momento.</div>';
}

// <!-- SECÇÃO 2: DEVOLVIDOS -->
$html .= '<h2>2. Histórico de Devolvidos</h2>';

if (count($devolvidos) > 0) {
    $html .= '<table>
        <thead>
            <tr>
                <th style="width: 25%;">Leitor</th>
                <th style="width: 35%;">Livro</th>
                <th style="width: 15%;">Data Saída</th>
                <th style="width: 15%;">Data Devolução</th>
                <th style="width: 10%;">Estado</th>
            </tr>
        </thead>
        <tbody>';
    foreach ($devolvidos as $row) {
        $html .= '<tr>
            <td>' . htmlspecialchars($row['leitor_nome']) . '</td>
            <td>' . htmlspecialchars($row['livro_titulo']) . '</td>
            <td>' . date('d/m/Y', strtotime($row['data_saida'])) . '</td>
            <td>' . date('d/m/Y', strtotime($row['data_devolucao_real'])) . '</td>
            <td><span class="badge badge-devolvido">Devolvido</span></td>
        </tr>';
    }
    $html .= '</tbody></table>';
} else {
    $html .= '<div class="no-data">Nenhum registo de devolução encontrado no histórico.</div>';
}

$html .= '</body></html>';

// ==========================================================
// 4. RENDERIZAÇÃO E DOWNLOAD DO PDF
// ==========================================================
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Faz o download automático do ficheiro
$dompdf->stream("relatorio_biblioteca_" . date('Ymd') . ".pdf", array("Attachment" => true));
exit();