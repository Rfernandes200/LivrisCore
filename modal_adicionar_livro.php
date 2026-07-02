<!-- Ficheiro Único e Centralizado: modal_adicionar_livro.php -->
<div id="addCatalogModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11, 15, 25, 0.95); backdrop-filter: blur(8px); z-index: 99999; display: none; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
    
    <div style="background: #0b0f19; border: 1px solid rgba(255, 255, 255, 0.08); width: 100%; max-width: 600px; max-height: 90vh; border-radius: 12px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7); display: flex; flex-direction: column; overflow: hidden; font-family: 'Inter', sans-serif;">
        
        <!-- Cabeçalho do Modal -->
        <div style="padding: 20px 28px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); display: flex; justify-content: space-between; align-items: center; background: rgba(30, 41, 59, 0.2); flex-shrink: 0;">
            <div>
                <span style="font-size: 0.7rem; color: #3b82f6; letter-spacing: 0.15em; font-weight: 700; display: block; margin-bottom: 4px; text-align: left;">[ Adicionar Livro ]</span>
                <h2 style="font-size: 1.3rem; color: white; font-weight: 600; margin: 0; text-align: left;">Novo Livro no Catálogo</h2>
            </div>
            <button type="button" id="closeAddModalBtn" style="background: transparent; border: none; color: #64748b; font-size: 1.8rem; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <!-- Formulário que envia para o processa_artigo.php -->
        <form action="Processos/processa_artigo.php" method="POST" enctype="multipart/form-data" style="margin: 0; padding: 28px; overflow-y: auto; flex-grow: 1; display: flex; flex-direction: column; gap: 20px; box-sizing: border-box;">
            
            <!-- Linha 1: Título e Autores -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">TÍTULO DO LIVRO *</label>
                    <input type="text" name="titulo" class="modal-field" placeholder="Ex: Os Maias" required style="width: 100%; height: 45px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 0 14px; box-sizing: border-box; font-family: inherit;">
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">AUTOR(ES) DO LIVRO *</label>
                    <div id="container-autores" style="display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <select name="autor_id[]" class="modal-field select-autor-dinamico" required style="height: 45px; flex-grow: 1; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 0 14px; box-sizing: border-box; font-family: inherit;">
                                <option value="" disabled selected style="background:#0b0f19;">Selecione um Autor...</option>
                                <?php foreach($todos_autores as $autor): ?>
                                    <option value="<?= $autor['id']; ?>" style="background:#0b0f19; color:white;">
                                        <?= htmlspecialchars($autor['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" id="btn-add-autor-row" style="height: 45px; width: 45px; min-width: 45px; background: #10b981; border: none; border-radius: 6px; color: white; font-size: 1.3rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center;">+</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Linha 2: ISBN e CDU -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">CÓDIGO ISBN *</label>
                    <input type="text" name="isbn" class="modal-field" placeholder="Ex: 978-972-0-04671-0" required style="width: 100%; height: 45px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 0 14px; box-sizing: border-box; font-family: inherit;">
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">CLASSIFICAÇÃO CDU *</label>
                    <select name="cdu_codigo" class="modal-field" required style="height: 45px; width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 0 14px; box-sizing: border-box; font-family: inherit;">
                        <option value="" disabled selected style="background:#0b0f19;">Selecione a Classe CDU...</option>
                        <?php 
                        $categorias = $cdu_classes ?? $todas_categorias ?? [];
                        foreach($categorias as $classe): 
                        ?>
                            <option value="<?= htmlspecialchars($classe['codigo']); ?>" style="background:#0b0f19; color:white;">
                                <?= htmlspecialchars($classe['codigo'] . ' - ' . $classe['descricao']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Linha 3: Editora, Ano e Estado -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">EDITORA *</label>
                    <input type="text" name="editora" class="modal-field" placeholder="Ex: Porto Editora" required style="width: 100%; height: 45px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 0 14px; box-sizing: border-box; font-family: inherit;">
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">ANO DE EDIÇÃO *</label>
                    <input type="number" name="ano_edicao" class="modal-field" placeholder="Ex: 2026" min="1000" max="2026" required style="width: 100%; height: 45px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 0 14px; box-sizing: border-box; font-family: inherit;">
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                    <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">ESTADO INICIAL *</label>
                    <select name="estado" class="modal-field" required style="height: 45px; width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 0 14px; box-sizing: border-box; font-family: inherit;">
                        <option value="disponivel" selected style="background:#0b0f19;">Disponível</option>
                        <option value="reservado" style="background:#0b0f19;">Reservado</option>
                        <option value="indisponivel" style="background:#0b0f19;">Indisponível</option>
                    </select>
                </div>
            </div>

            <!-- Linha 4: Sinopse -->
            <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">SINOPSE / RESUMO *</label>
                <textarea name="descricao" rows="4" class="modal-field" placeholder="Escreva uma breve sinopse do livro..." required style="resize: vertical; width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: white; padding: 12px 14px; box-sizing: border-box; font-family: inherit;"></textarea>
            </div>

            <!-- Linha 5: Imagem de Capa com Pré-visualização Larga e Confortável -->
            <div style="display: flex; flex-direction: column; gap: 8px; text-align: left;">
                <label style="font-size: 0.7rem; color: #64748b; font-weight: 600; letter-spacing: 0.05em;">IMAGEM DE CAPA (OBRIGATÓRIO) *</label>
                
                <div style="display: flex; gap: 20px; align-items: center;">
                    <!-- Contentor Mais Largo e Mais Baixo da Miniatura -->
                    <div id="wrapper-preview-capa" style="display: none; width: 140px; height: 130px; border-radius: 8px; overflow: hidden; border: 1px solid rgba(255,255,255,0.12); background: #1e293b; flex-shrink: 0; box-shadow: 0 4px 12px rgba(0,0,0,0.4);">
                        <img id="preview-capa-livro" src="" alt="Pré-visualização" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    
                    <input type="file" id="input-imagem-capa" name="imagem" accept="image/*" required class="modal-field" style="width: 100%; height: 45px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: #64748b; padding: 10px 14px; box-sizing: border-box; font-family: inherit;">
                </div>
                <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display:block;">Apenas ficheiros de imagem válidos (JPG, PNG, WEBP).</small>
            </div>
            
            <!-- Botões de Ação -->
            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px; border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 20px; flex-shrink: 0;">
                <button type="button" id="cancelAddModalBtn" style="background: transparent; border: 1px solid rgba(255, 255, 255, 0.1); color: #cbd5e1; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 0.9rem;">Cancelar Criação</button>
                <button type="submit" style="background: #3b82f6; border: none; color: white; padding: 10px 22px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: 600;">Confirmar Criação</button>
            </div>
        </form>
    </div>
</div>