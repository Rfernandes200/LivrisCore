<?php
session_start();
require 'config.php';

// Bloqueia se não estiver logado
if (!isset($_SESSION['utilizador_id'])) {
    header("Location: login.php");
    exit();
}

$id_artigo = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Procura os dados atuais do artigo
$stmt = $pdo->prepare("SELECT * FROM itens WHERE id = :id");
$stmt->execute(['id' => $id_artigo]);
$artigo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$artigo) {
    $_SESSION['alerta'] = ['tipo' => 'erro', 'mensagem' => 'Artigo não encontrado.'];
    header("Location: index.php");
    exit();
}

// Carrega as categorias para o <select>
$stmt_cat = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC");
$categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Artigo - BiblioBase</title>
    <link rel="stylesheet" href="Styles/StylesIndex.css">
    <link rel="stylesheet" href="Styles/StyleAdmin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body style="background: #0f172a; color: white; font-family: 'Inter', sans-serif; padding: 40px 20px;">

    <div style="max-width: 600px; margin: 0 auto; background: #0b0f19; border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; padding: 30px;">
        <h2 style="margin-top: 0; color: white;">📝 Editar Dados do Artigo</h2>
        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 25px;">Altere as informações necessárias do exemplar selecionado.</p>

        <form action="processa_edicao_artigo.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $artigo['id']; ?>">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="font-size: 0.75rem; color: #64748b; font-weight: 600;">TÍTULO</label>
                    <input type="text" name="titulo" value="<?= htmlspecialchars($artigo['titulo']); ?>" required style="background: #0f172a; border: 1px solid #334155; color: white; padding: 10px; border-radius: 6px;">
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="font-size: 0.75rem; color: #64748b; font-weight: 600;">AUTOR / ARTISTA</label>
                    <input type="text" name="autor_artista" value="<?= htmlspecialchars($artigo['autor_artista']); ?>" required style="background: #0f172a; border: 1px solid #334155; color: white; padding: 10px; border-radius: 6px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="font-size: 0.75rem; color: #64748b; font-weight: 600;">CATEGORIA</label>
                    <select name="categoria_id" required style="background: #0f172a; border: 1px solid #334155; color: white; padding: 10px; border-radius: 6px; height: 42px;">
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?= $cat['id']; ?>" <?= $artigo['categoria_id'] == $cat['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($cat['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="font-size: 0.75rem; color: #64748b; font-weight: 600;">DISPONIBILIDADE</label>
                    <select name="estado" required style="background: #0f172a; border: 1px solid #334155; color: white; padding: 10px; border-radius: 6px; height: 42px;">
                        <option value="disponivel" <?= $artigo['estado'] === 'disponivel' ? 'selected' : ''; ?>>🟢 Disponível</option>
                        <option value="indisponivel" <?= $artigo['estado'] !== 'disponivel' ? 'selected' : ''; ?>>🔴 Indisponível</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                <label style="font-size: 0.75rem; color: #64748b; font-weight: 600;">DESCRIÇÃO / SINOPSE</label>
                <textarea name="descricao" rows="4" required style="background: #0f172a; border: 1px solid #334155; color: white; padding: 10px; border-radius: 6px; resize: vertical;"><?= htmlspecialchars($artigo['descricao']); ?></textarea>
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 25px;">
                <label style="font-size: 0.75rem; color: #64748b; font-weight: 600;">IMAGEM DE CAPA (Deixe vazio para manter a atual)</label>
                <input type="file" name="imagem" accept="image/*" style="background: #0f172a; border: 1px solid #334155; color: white; padding: 8px; border-radius: 6px;">
                <?php if(!empty($artigo['imagem_url'])): ?>
                    <small style="color: #94a3b8;">Imagem atual: <code><?= $artigo['imagem_url']; ?></code></small>
                <?php endif; ?>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 20px;">
                <a href="meus_artigos.php" style="background: transparent; border: 1px solid rgba(255, 255, 255, 0.1); color: #cbd5e1; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 0.9rem;">Voltar</a>
                <button type="submit" style="background: #10b981; border: none; color: #0f172a; padding: 10px 22px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: 600;">Gravar Alterações</button>
            </div>
        </form>
    </div>

</body>
</html>