credenciais
a@a.a
12345678

================================================================================
                    LIVRISCORE - SISTEMA DE GESTÃO DE BIBLIOTECA
                              DOCUMENTAÇÃO TÉCNICA
================================================================================

VERSÃO: 1.0
DATA: 2026-06-25
LINGUAGEM: PHP 7.x + MySQL/MariaDB
AMBIENTE: Apache/XAMPP

================================================================================
1. VISÃO GERAL TÉCNICA
================================================================================

LivrisCore é um sistema de gestão de biblioteca web desenvolvido em PHP puro 
(sem frameworks) com MySQL, implementando uma arquitetura em 3 camadas:

  - APRESENTAÇÃO: HTML/CSS/JavaScript (responsivo, mobile-first)
  - LÓGICA DE NEGÓCIO: PHP (controladores em /Processos)
  - DADOS: MySQL/MariaDB (7 tabelas normalizadas, PDO com prepared statements)

PALAVRAS-CHAVE: PHP-MVC, PDO, MySQL, Session-based Auth, CRUD Operations,
                Transações SQL, Uploads Dinâmicos, Validações Server-side

================================================================================
2. ARQUITETURA DE CAMADAS
================================================================================

2.1 CAMADA DE APRESENTAÇÃO
├─ index.php              → Catálogo com grid responsivo e pesquisa avançada
├─ login.php              → Formulário de autenticação (email + password)
├─ registo.php            → Criação de novo utilizador
├─ admin.php              → Painel administrativo (gestão completa)
├─ emprestimos.php        → Dashboard pessoal de empréstimos
├─ perfil.php             → Edição de dados pessoais
├─ sobre.php              → Informações sobre o sistema
├─ navbar.php             → Componente de navegação (reutilizável)
├─ sidebar.php            → Menu lateral (admin)
└─ seccao_*.php           → Componentes modulares (tabelas, formulários)

2.2 CAMADA DE LÓGICA (Controllers em /Processos)
├─ processo_login.php              → Validação credenciais + session
├─ processo_reserva.php            → Criar reserva com código validação
├─ processo_emprestimo.php         → Confirmar empréstimo + devolução
├─ processa_devolucao.php          → Processar devolução física
├─ processa_renovacao.php          → Renovar prazo de empréstimo
├─ inserir_artigo.php              → CRUD livros (admin)
├─ editar_artigo.php               → Editar dados do livro
├─ eliminar_artigo.php             → Soft-delete de livros
├─ criacao_utilizador.php          → Novo utilizador (registo)
├─ editar_utilizadores.php         → Gestão de perfil
├─ eliminar_conta.php              → Remover conta utilizador
├─ atualiza_perfil.php             → Atualizar dados pessoais
├─ cancela_reserva.php             → Cancelar reserva pendente
├─ processa_artigo.php             → Processamento genérico
└─ Uploads/                        → Imagens armazenadas (MD5 renamed)

2.3 CAMADA DE DADOS
├─ config.php                      → Conexão PDO + constantes
├─ Sql/bs.sql                      → Schema inicial + dados seed
└─ [Database: livriscore]

================================================================================
3. BANCO DE DADOS - ESQUEMA RELACIONAL
================================================================================

3.1 TABELA: utilizadores
├─ id (INT, PRIMARY KEY, AUTO_INCREMENT)
├─ nome (VARCHAR 255)
├─ email (VARCHAR 255, UNIQUE)
├─ password (VARCHAR 255, bcrypt hash)
├─ telemóvel (VARCHAR 20)
├─ tipo (INT: 0=utilizador comum, 1=administrador)
├─ data_criacao (TIMESTAMP)
└─ ÍNDICES: PRIMARY KEY, UNIQUE(email)

3.2 TABELA: livros
├─ id (INT, PRIMARY KEY, AUTO_INCREMENT)
├─ titulo (VARCHAR 255, INDEX)
├─ isbn (VARCHAR 20, UNIQUE)
├─ editora (VARCHAR 255)
├─ ano_edicao (YEAR)
├─ descricao (TEXT)
├─ imagem_url (VARCHAR 255)
├─ cdu_codigo (VARCHAR 10, FOREIGN KEY → cdu_classes)
├─ estado (ENUM: 'disponivel', 'reservado', 'emprestado', 'indisponivel')
├─ quem_reservou (INT, FOREIGN KEY → utilizadores, nullable)
├─ data_criacao (TIMESTAMP)
└─ ÍNDICES: PRIMARY KEY, UNIQUE(isbn), INDEX(estado)

3.3 TABELA: autores
├─ id (INT, PRIMARY KEY, AUTO_INCREMENT)
├─ nome (VARCHAR 255, INDEX)
├─ nacionalidade (VARCHAR 100, nullable)
└─ data_criacao (TIMESTAMP)

3.4 TABELA: livro_autores (Relacionamento N:N)
├─ livro_id (INT, FOREIGN KEY → livros)
├─ autor_id (INT, FOREIGN KEY → autores)
└─ PRIMARY KEY: (livro_id, autor_id)

3.5 TABELA: reservas
├─ id (INT, PRIMARY KEY, AUTO_INCREMENT)
├─ utilizador_id (INT, FOREIGN KEY → utilizadores)
├─ livro_id (INT, FOREIGN KEY → livros)
├─ codigo_validacao (INT: 000-999, randômico)
├─ data_inicio (TIMESTAMP)
├─ data_expiracao (TIMESTAMP: data_inicio )
├─ status (ENUM: 'pendente', 'concluida', 'cancelada', 'expirada')
└─ ÍNDICES: PRIMARY KEY, INDEX(utilizador_id), INDEX(status)

3.6 TABELA: emprestimos
├─ id (INT, PRIMARY KEY, AUTO_INCREMENT)
├─ utilizador_id (INT, FOREIGN KEY → utilizadores)
├─ livro_id (INT, FOREIGN KEY → livros)
├─ data_inicio (TIMESTAMP: NOW())
├─ data_prevista_devolucao (TIMESTAMP: NOW() + 15 dias)
├─ data_devolucao_real (TIMESTAMP, nullable)
├─ renovacoes (INT: contador de renovações)
└─ ÍNDICES: PRIMARY KEY, INDEX(utilizador_id), INDEX(livro_id)

3.7 TABELA: cdu_classes (Classificação Decimal Universal)
├─ codigo (VARCHAR 10, PRIMARY KEY)
├─ nome (VARCHAR 255)
├─ descricao (TEXT, nullable)
└─ Exemplos: 0-Generalidades, 1-Filosofia, 2-Religião, ... 9-Geografia

RELACIONAMENTOS (ERD):
├─ utilizadores 1:N emprestimos
├─ utilizadores 1:N reservas
├─ livros 1:N emprestimos
├─ livros 1:N reservas
├─ livros N:N autores (via livro_autores)
├─ livros N:1 cdu_classes
└─ cdu_classes 1:N livros

================================================================================
4. FLUXOS DE PROCESSAMENTO PRINCIPAIS
================================================================================

4.1 FLUXO DE AUTENTICAÇÃO (LOGIN)
┌─ index.php (form)
│
├─ POST login.php (email + password)
│
├─ Processos/processo_login.php
│  ├─ Validação: email via FILTER_VALIDATE_EMAIL
│  ├─ Query: SELECT * FROM utilizadores WHERE email = ?
│  ├─ Verify: password_verify($input, $bd_hash)
│  ├─ Session: $_SESSION[id, nome, email, tipo]
│  └─ Redirect: index.php (catálogo)
│
└─ Proteções: Prepared Statements, htmlspecialchars(), password hash bcrypt

KEYWORDS: autenticação, session, bcrypt, prepared statements, FILTER_VALIDATE_EMAIL

---

4.2 FLUXO DE RESERVA (RESERVE BOOK)
┌─ index.php (grid de livros)
│
├─ Utilizador clica "Reservar"
│
├─ Modal confirmação (JavaScript + HTML dinâmico)
│
├─ POST: Processos/processo_reserva.php
│  ├─ Verificações:
│  │  ├─ Utilizador autenticado? (isset($_SESSION['utilizador_id']))
│  │  ├─ Livro existe? (SELECT FROM livros WHERE id = ?)
│  │  ├─ Livro disponível? (estado = 'disponivel')
│  │  └─ Limite 2 reservas? (COUNT WHERE status='pendente' < 2)
│  │
│  ├─ Geração: código 3-dígitos aleatório (000-999)
│  │
│  ├─ TRANSAÇÃO SQL (atomicidade):
│  │  ├─ BEGIN
│  │  ├─ INSERT INTO reservas (utilizador_id, livro_id, codigo, status='pendente')
│  │  ├─ UPDATE livros SET estado='reservado', quem_reservou=user_id
│  │  └─ COMMIT (ou ROLLBACK em erro)
│  │
│  └─ Resposta: $_SESSION['reserva_sucesso_codigo'] = $codigo
│
├─ index_reservas.php (modal)
│  └─ Exibe: Código 3-dígitos 
│
└─ REGRA NEGÓCIO: Máx 2 reservas simultâneas por utilizador

KEYWORDS: transação SQL, CRUD, aleatório, atomicidade, validação

---

4.3 FLUXO DE EMPRÉSTIMO (LOAN CONFIRMATION)
┌─ admin.php (painel admin)
│
├─ Admin acede: "Processar Empréstimos"
│
├─ INPUT: código 3-dígitos (digitado pelo utilizador na biblioteca)
│
├─ POST: Processos/processo_emprestimo.php
│  ├─ Ação: $_POST['acao'] === 'confirmar_emprestimo'
│  │
│  ├─ Validação:
│  │  ├─ Código existe? (SELECT FROM reservas WHERE codigo = ?)
│  │  ├─ Status = 'pendente'?
│  │  └─ Não expirado? (data_expiracao > NOW())
│  │
│  ├─ TRANSAÇÃO:
│  │  ├─ BEGIN
│  │  ├─ UPDATE reservas SET status='concluida'
│  │  ├─ INSERT INTO emprestimos
│  │  │  └─ data_prevista_devolucao = NOW() + INTERVAL 15 DAY
│  │  ├─ UPDATE livros SET estado='emprestado'
│  │  └─ COMMIT
│  │
│  └─ Alerta: "Empréstimo confirmado com sucesso! Boa leitura."
│
└─ PRAZO: 15 dias por padrão (configurável em função)

KEYWORDS: validação de código, transação SQL, prazos, INTERVAL DATE

---

4.4 FLUXO DE DEVOLUÇÃO (RETURN)
┌─ admin.php → "Os Meus Empréstimos" → "Processar Devolução"
│
├─ POST: Processos/processo_emprestimo.php
│  ├─ Ação: $_POST['acao'] === 'entregar_emprestimo'
│  │
│  ├─ Validação:
│  │  ├─ Empréstimo existe? (SELECT FROM emprestimos WHERE id = ?)
│  │  └─ Não devolvido? (data_devolucao_real IS NULL)
│  │
│  ├─ TRANSAÇÃO:
│  │  ├─ BEGIN
│  │  ├─ UPDATE emprestimos SET data_devolucao_real = NOW()
│  │  ├─ UPDATE livros SET estado='disponivel'
│  │  └─ COMMIT
│  │
│  └─ Alerta: "Artigo entregue e devolvido com sucesso! Obrigado."
│
└─ Efeito: Livro volta a ficar disponível para reserva

KEYWORDS: soft-delete, data_devolucao_real, NOW(), estado UPDATE

---

4.5 FLUXO DE RENOVAÇÃO (RENEWAL)
┌─ emprestimos.php (dashboard utilizador)
│
├─ Utilizador clica "Renovar Prazo"
│
├─ POST: Processos/processa_renovacao.php
│  ├─ Validação:
│  │  ├─ Empréstimo ativo? (data_devolucao_real IS NULL)
│  │  └─ Renovações < limite?
│  │
│  ├─ UPDATE emprestimos
│  │  ├─ data_prevista_devolucao = data_prevista_devolucao + INTERVAL 14 DAY
│  │  └─ renovacoes = renovacoes + 1
│  │
│  └─ Alerta: "Prazo renovado com sucesso! Nova data: XX/XX/XXXX"
│
└─ LIMITE: Por configurar (atualmente não há limite hard)

KEYWORDS: INTERVAL DATE, UPDATE múltiplos campos, contador renovações

================================================================================
5. AUTENTICAÇÃO E SEGURANÇA
================================================================================

5.1 SISTEMA DE AUTENTICAÇÃO
├─ Tipo: Session-based (cookies $_SESSION)
├─ Hash: bcrypt via password_hash() e password_verify()
├─ Força: Mínimo 8 caracteres (não há complexidade obrigatória)
├─ Email: Validado com FILTER_VALIDATE_EMAIL + UNIQUE constraint
└─ Sessão: Armazenada no servidor (PHP default files handler)

5.2 VARIÁVEIS DE SESSÃO
├─ $_SESSION['utilizador_id']      → INT ID do utilizador
├─ $_SESSION['utilizador_nome']    → STRING nome completo
├─ $_SESSION['utilizador_email']   → STRING email
├─ $_SESSION['utilizador_tipo']    → INT (0=user, 1=admin)
└─ $_SESSION['alerta']             → ARRAY [tipo, mensagem]

5.3 VERIFICAÇÃO DE PERMISSÕES
├─ Utilizador comum:
│  ├─ Ver catálogo (pesquisa disponível)
│  ├─ Reservar livros (máx 2)
│  ├─ Ver empréstimos pessoais
│  ├─ Renovar empréstimos
│  └─ Editar próprio perfil
│
├─ Administrador (tipo=1):
│  ├─ CRUD completo de livros
│  ├─ CRUD de utilizadores
│  ├─ Gestão de empréstimos (confirmar, devolver, renovar)
│  ├─ Painel admin (dashboard estatísticas)
│  └─ Editar qualquer utilizador
│
└─ Verificação: if ((int)$_SESSION['utilizador_tipo'] === 1)

5.4 PROTEÇÕES IMPLEMENTADAS
✅ SQL Injection: Prepared statements PDO (prepared query patterns)
✅ XSS: htmlspecialchars() em todos os outputs echo
✅ Password: Hashing bcrypt (não reversível)
✅ Email UNIQUE: Constraint UNIQUE na tabela utilizadores
✅ Telemóvel: Validação regex 9 dígitos
✅ Upload: Whitelist extensões (jpg, png, gif), MD5 rename files
✅ Transações: Atomicidade em operações críticas

5.5 LACUNAS DE SEGURANÇA CONHECIDAS
❌ CSRF: Sem tokens CSRF (POST sem validação CSRF)
❌ Rate limiting: Sem limite tentativas login (brute force possível)
❌ Session regeneration: Sem regenerate_id() pós-login
❌ Logging: Sem auditoria de ações sensíveis
❌ HTTPS: Não forçado (transmission data plain em HTTP)
❌ Prepared statements: Incompleto em alguns ficheiros

KEYWORDS: bcrypt, PDO, session, prepared statements, XSS, SQL injection,
          CSRF, rate limiting, HTTPS

================================================================================
6. VALIDAÇÕES E REGRAS DE NEGÓCIO
================================================================================

6.1 CAMPOS DE ENTRADA - VALIDAÇÕES

EMAIL
├─ Validação: FILTER_VALIDATE_EMAIL
├─ Constraint: UNIQUE (não duplicados)
├─ Tamanho: até 255 chars
└─ Exemplo: usuario@email.com

PASSWORD
├─ Tamanho: Mínimo 8 caracteres
├─ Hash: bcrypt via password_hash()
├─ Comparação: password_verify($input, $hash)
└─ Complexidade: Não é obrigatória (melhoria recomendada)

TELEMÓVEL
├─ Validação: Regex ^\d{9}$ (exatos 9 dígitos)
├─ Tamanho: 9 caracteres
└─ Exemplo: 912345678

NOME
├─ Tamanho: 1-255 caracteres
├─ Type: VARCHAR
└─ Trim: Aplicado antes de salvar

TÍTULO DO LIVRO
├─ Tamanho: até 255 caracteres
├─ INDEX: Otimizado para pesquisa
└─ Exemplo: "O Senhor dos Anéis"

ISBN
├─ Constraint: UNIQUE (um ISBN por livro)
├─ Validação: Não é validado ISBN checksum
├─ Tamanho: até 20 caracteres
└─ Exemplo: 978-0-13-110362-7

CDU (Classificação Decimal Universal)
├─ Validação: FOREIGN KEY → cdu_classes
├─ Valores: 0-9 (10 categorias principais)
└─ Exemplos: 1=Filosofia, 2=Religião, 3=Ciências Sociais

6.2 REGRAS DE NEGÓCIO PRINCIPAIS

LIMITE DE RESERVAS
├─ Máximo: 2 reservas ativas por utilizador
├─ Status: Apenas 'pendente' conta para limite
├─ Bloqueio: Botão "Reservar" desabilitado ao atingir limite
└─ Query: COUNT(*) WHERE utilizador_id = ? AND status = 'pendente'

PRAZO DE LEVANTAMENTO
├─ Cálculo: data_expiracao = data_inicio + INTERVAL 4 HOUR
├─ Expiração: Automática quando ultrapassa prazo
└─ Cancelamento: Via função cancela_reserva.php

PRAZO DE EMPRÉSTIMO
├─ Duração: 15 dias (configurável)
├─ Cálculo: data_prevista_devolucao = NOW() + INTERVAL 15 DAY
├─ Multas: Não implementadas (TODO)
└─ Renovação: +14 dias por vez

ESTADOS DO LIVRO
├─ 'disponivel': Pronto para reserva
├─ 'reservado': Alguém reservou (quem_reservou = user_id)
├─ 'emprestado': Em posse de utilizador (empréstimo ativo)
├─ 'indisponivel': Danificado ou removido (não disponível)
└─ Transições: disponivel → reservado → emprestado → disponivel

CÓDIGO DE VALIDAÇÃO
├─ Formato: 3 dígitos (000-999)
├─ Geração: rand(0, 999) formatado com sprintf('%03d', $rand)
├─ Unicidade: Não garantida (possível colisão, melhoria: UUID)
├─ Uso: Confirmar empréstimo na biblioteca
└─ Validade: 4 horas

KEYWORDS: constraints, triggers (não implementados), business rules,
          enum states, validação multi-camada

================================================================================
7. ESTRUTURA DE FICHEIROS
================================================================================

Bibliobase/
│
├─ RAIZ (Controllers de primeira linha + config)
│  ├─ config.php                    → PDO connection + db config
│  ├─ index.php                     → Catálogo (GET param pesquisa)
│  ├─ login.php                     → Formulário login
│  ├─ registo.php                   → Formulário criação conta
│  ├─ admin.php                     → Painel administrativo
│  ├─ emprestimos.php               → Dashboard pessoal
│  ├─ perfil.php                    → Editar perfil utilizador
│  ├─ sobre.php                     → Página informativa
│  ├─ navbar.php                    → Component navbar (include)
│  ├─ sidebar.php                   → Component sidebar admin (include)
│  ├─ index_reservas.php            → Modal reservas (include)
│  ├─ seccao_geral.php              → Section tabela utilizadores
│  ├─ seccao_artigos.php            → Section gestão livros
│  ├─ seccao_emprestimos.php        → Section tabela empréstimos
│  ├─ seccao_reservas.php           → Section tabela reservas
│  └─ seccao_utilizadores.php       → Section gestão users
│
├─ Processos/                        → POST handlers (business logic)
│  ├─ processo_login.php             → Autenticação
│  ├─ processo_reserva.php           → Criar reserva
│  ├─ processo_emprestimo.php        → Empréstimo + devolução
│  ├─ processa_devolucao.php         → Devolução (legacy?)
│  ├─ processa_renovacao.php         → Renovar prazo
│  ├─ inserir_artigo.php             → CREATE livro (admin)
│  ├─ editar_artigo.php              → UPDATE livro (admin)
│  ├─ eliminar_artigo.php            → DELETE livro (admin)
│  ├─ processa_artigo.php            → Generic processor
│  ├─ criacao_utilizador.php         → CREATE user (registo)
│  ├─ editar_utilizadores.php        → UPDATE user (admin/self)
│  ├─ eliminar_conta.php             → DELETE user (self)
│  ├─ atualiza_perfil.php            → UPDATE perfil (self)
│  ├─ cancela_reserva.php            → DELETE/cancel reserva
│  ├─ inserir_utilizador.php         → CREATE user (admin)
│  └─ Uploads/                       → Imagens (MD5 renamed)
│
├─ Styles/                           → CSS (7 ficheiros)
│  ├─ StyleNav.css                   → Navbar styling
│  ├─ StylesIndex.css                → Index + catálogo
│  ├─ StylesIndex2.css               → Index (complementos)
│  ├─ StyleAdmin.css                 → Painel admin
│  ├─ Styleempres.css                → Empréstimos
│  ├─ StylePerfil.css                → Perfil
│  ├─ StyleRegistro.css              → Login + registo
│  └─ StylesSobre.css                → Página sobre
│
├─ Sql/
│  └─ bs.sql                         → Schema + seed data
│
├─ Uploads/                          → Storage imagens dinâmicas
│
└─ README.txt                        → Este ficheiro

================================================================================
8. ENDPOINTS PRINCIPAIS (ROTAS)
================================================================================

PÚBLICOS (Sem autenticação)
├─ GET  /index.php                  → Catálogo (read-only)
├─ GET  /login.php                  → Formulário login
├─ POST /Processos/processo_login.php    → Processar login
├─ GET  /registo.php                → Formulário criação conta
├─ POST /Processos/criacao_utilizador.php → Processar registo
├─ GET  /sobre.php                  → Página informativa
└─ GET  /logout.php                 → Terminar sessão

AUTENTICADOS (Utilizador comum)
├─ POST /Processos/processo_reserva.php → Reservar livro
├─ POST /Processos/cancela_reserva.php  → Cancelar reserva
├─ GET  /emprestimos.php            → Ver empréstimos pessoais
├─ POST /Processos/processa_renovacao.php → Renovar empréstimo
├─ GET  /perfil.php                 → Ver perfil
├─ POST /Processos/atualiza_perfil.php   → Atualizar perfil
└─ POST /Processos/eliminar_conta.php    → Remover conta

ADMIN (Utilizador tipo=1)
├─ GET  /admin.php                  → Painel administrativo
├─ POST /Processos/inserir_artigo.php    → Criar livro
├─ POST /Processos/editar_artigo.php     → Editar livro
├─ POST /Processos/eliminar_artigo.php   → Eliminar livro
├─ POST /Processos/processo_emprestimo.php    → Confirmar empréstimo
├─ POST /Processos/processa_devolucao.php     → Processar devolução
├─ POST /Processos/inserir_utilizador.php     → Criar utilizador
├─ POST /Processos/editar_utilizadores.php    → Editar utilizador
└─ POST /Processos/eliminar_conta.php    → Eliminar utilizador

METHOD: Maioria POST (estado no BD é alterado)
REDIRECT: Após POST, redireciona para página anterior
ALERTA: Via $_SESSION['alerta'] [tipo, mensagem]

KEYWORDS: MVC-like, controllers, stateful session, redirect pattern

================================================================================
9. SISTEMA DE ALERTAS
================================================================================

IMPLEMENTAÇÃO
├─ Storage: $_SESSION['alerta'] = ['tipo' => ?, 'mensagem' => ?]
├─ Display: Toast notification (CSS classe .alert-toast)
├─ Tipos: 'sucesso', 'erro', 'info', 'aviso'
├─ Durabilidade: Exibida 1x, depois unsset() automaticamente
└─ Triggers: 1. Criação  2. Atualização  3. Deleção  4. Expiração

EXEMPLOS
├─ ✅ "Empréstimo confirmado com sucesso! Boa leitura."
├─ ❌ "Erro ao processar empréstimo: [mensagem exceção]"
├─ ⚠️  "Limite de 2 reservas atingido!"
├─ ℹ️  "Código expirado. Tente reservar novamente."
└─ 📅 "Prazo renovado com sucesso! Nova data: XX/XX/XXXX"

STYLING
├─ Cor sucesso: Verde (#10b981)
├─ Cor erro: Vermelho (#ef4444)
├─ Cor aviso: Amarelo (#eab308)
├─ Cor info: Azul (#3b82f6)
└─ Posição: Topo da página (fixed ou sticky)

KEYWORDS: user feedback, session-based messages, toast patterns

================================================================================
10. UPLOADS E GESTÃO DE FICHEIROS
================================================================================

IMAGENS DE LIVROS
├─ Local: /Bibliobase/Uploads/
├─ Nomes: MD5(original_name + timestamp) + extensão
│  └─ Exemplo: a3f8e9b2c1d4e5f6g7h8i9j0.jpg
├─ Extensões permitidas: jpg, jpeg, png, gif (whitelist)
├─ Tamanho máximo: Não limitado explicitamente (TODO)
├─ Validação: mime_content_type() ou finfo_file()
└─ Referência BD: imagem_url VARCHAR (apenas filename)

FLUXO DE UPLOAD
├─ 1. Usuario carrega ficheiro via $_FILES['imagem']
├─ 2. Validação: extensão whitelist + mime type
├─ 3. Geração nome: MD5(original + time) + ext
├─ 4. Move: move_uploaded_file($tmp, $destino)
├─ 5. Storage BD: INSERT livros(imagem_url) = filename
└─ 6. Exibição: <img src="Uploads/<?= $filename ?>" />

SEGURANÇA UPLOADS
├─ ✅ Whitelist extensões (não blacklist)
├─ ✅ MD5 rename (previne path traversal)
├─ ✅ Armazenado fora do webroot (TODO: melhoria)
├─ ⚠️  Sem verificação de dimensões (TODO)
├─ ⚠️  Sem limite size (TODO)
└─ ⚠️  Sem antivírus scanning (TODO)

KEYWORDS: file upload, whitelist, MD5, mime-type, move_uploaded_file

================================================================================
11. PERFORMANCE E OTIMIZAÇÕES
================================================================================

ÍNDICES NA BD
├─ PRIMARY KEY: id (todas as tabelas)
├─ UNIQUE: email, isbn
├─ INDEX: titulo (pesquisa), estado, utilizador_id, livro_id
└─ FOREIGN KEYS: Automaticamente indexed

QUERIES OTIMIZADAS
├─ Pesquisa: LIKE '%termo%' em INDEX(titulo)
├─ Contagem: COUNT(*) com WHERE clauses
├─ Joins: N:N via table intermediária livro_autores
└─ Paginação: LIMIT + OFFSET (não implementado)

CACHING
├─ Session cache: $_SESSION (utilizador logado)
├─ Query cache: MySQL default (configurável)
└─ Browser cache: Cache-Control headers (não configurado)

PROBLEMAS CONHECIDOS
├─ Pesquisa N:N autores sem INDEX (performance em BD grande)
├─ Sem paginação (carrega todos os livros em memory)
├─ Sem lazy loading (imagens)
├─ PDO fetch em loop (memória)
└─ Sem query profiling (slow log MySQL)

KEYWORDS: índices, execution plan, query optimization, caching,
          pagination, lazy loading

================================================================================
12. PADRÕES DE DESENVOLVIMENTO
================================================================================

12.1 PADRÃO MVC-LIKE (Simplificado)
├─ Model: SQL queries em Processos/*.php
├─ View: HTML ficheiros individuais (index.php, admin.php, etc)
├─ Controller: Processos/*.php (POST handlers)
└─ Router: Nenhum (GET/POST diretos para ficheiros)

12.2 CONVENÇÕES DE CÓDIGO
├─ Variáveis: snake_case ($_SESSION, $user_id, $livro_id)
├─ Constantes: UPPER_CASE (não existem)
├─ Classes: PascalCase (não usadas, PHP puro)
├─ Funções: snake_case (pouquíssimas custom)
├─ SQL: UPPER_CASE keywords (SELECT, FROM, WHERE)
└─ Encoding: UTF-8 (header meta charset)

12.3 PADRÕES DE ERRO
├─ Try-Catch: Exceções PDO em transações críticas
├─ Logging: Via $_SESSION['alerta'] (não em ficheiro)
├─ Stack trace: Exibida em desenvolvimento (riscos segurança)
└─ User feedback: Mensagens genéricas (melhor UX)

12.4 SEGURANÇA DE URL
├─ GET parameters: ?pesquisa=termo (read-only, safe)
├─ POST actions: Via hidden inputs e $_POST['acao']
├─ Redirect: Pós-login para index.php
└─ No pretty URLs (URLs feias, sem rewriting)

KEYWORDS: MVC, conventions, error handling, POST-redirect-GET pattern

================================================================================
13. FLUXO DE AUTENTICAÇÃO E AUTORIZAÇÃO
================================================================================

FLUXO COMPLETO (LOGIN → AÇÃO → LOGOUT)

1. VISITANTE ACEDE /index.php
   ├─ Verifica: isset($_SESSION['utilizador_id'])
   ├─ Resultado: Não existe → Exibe catálogo read-only
   └─ Ações bloqueadas: Reservar (botão desabilitado)

2. VISITANTE CLICA "Entrar"
   ├─ Vai: /login.php
   ├─ Submit: Formulário POST email + password
   └─ Destino: /Processos/processo_login.php

3. PROCESSO_LOGIN.PHP EXECUTA
   ├─ Email validado: FILTER_VALIDATE_EMAIL
   ├─ Query: SELECT * FROM utilizadores WHERE email = ?
   ├─ Verify: password_verify($input, bcrypt_hash)
   ├─ Success:
   │  ├─ $_SESSION['utilizador_id'] = $user->id
   │  ├─ $_SESSION['utilizador_tipo'] = $user->tipo
   │  └─ header("Location: index.php")
   ├─ Fail:
   │  ├─ Mensagem erro genérica (não reveal user exists)
   │  └─ Redirect: /login.php?erro=...
   └─ Proteção: Sem rate limiting (TODO)

4. UTILIZADOR AUTENTICADO
   ├─ Acessa: /emprestimos.php
   ├─ Acesso: ✅ Próprios empréstimos (WHERE utilizador_id = SESSION_ID)
   ├─ Acesso negado: ❌ Empréstimos de outros (403-like sem redirect)
   └─ Admin acesso: ✅ Todos (tipo=1)

5. AÇÃO ADMIN (Exemplo: Confirmar empréstimo)
   ├─ Admin clica: "Confirmar Empréstimo"
   ├─ POST: /Processos/processo_emprestimo.php?acao=confirmar_emprestimo
   ├─ Verificação:
   │  ├─ isset($_SESSION['utilizador_id'])? → Autenticado
   │  ├─ (int)$_SESSION['utilizador_tipo'] === 1? → Admin
   │  └─ else → Redirect ou blank output (sem permissão)
   ├─ Executa:
   │  ├─ BEGIN TRANSACTION
   │  ├─ UPDATE reservas SET status='concluida'
   │  ├─ INSERT emprestimos
   │  ├─ UPDATE livros SET estado='emprestado'
   │  └─ COMMIT
   └─ Feedback: $_SESSION['alerta'] = ['tipo' => 'sucesso', ...]

6. LOGOUT
   ├─ Usuario clica: "Sair"
   ├─ GET: /logout.php
   ├─ Executa:
   │  ├─ session_destroy()
   │  ├─ unset($_SESSION)
   │  └─ header("Location: /login.php")
   └─ Resultado: Volta à página login (session apagada)

KEYWORDS: session lifecycle, authentication flow, authorization checks,
          middleware-like guards

================================================================================
14. ESTRUTURA DE RESPOSTA (JSON IMPLÍCITO)
================================================================================

Na maioria dos endpoints POST, a resposta é:

SUCESSO
├─ $_SESSION['alerta'] = [
│  ├─ 'tipo' => 'sucesso'
│  ├─ 'mensagem' => 'Operação realizada com sucesso!'
│  └─ 'dados' => [] (opcional, estrutura variável)
│
└─ Redirect: header("Location: " . $_SERVER['HTTP_REFERER'])

ERRO
├─ $_SESSION['alerta'] = [
│  ├─ 'tipo' => 'erro'
│  ├─ 'mensagem' => 'Erro ao processar: detalhes...'
│  └─ 'debug' => $e->getMessage() (apenas dev)
│
└─ Redirect ou Die: exit() em caso crítico

ESTRUTURA ALERTA (SESSION)
├─ tipo: 'sucesso' | 'erro' | 'aviso' | 'info'
├─ mensagem: STRING (exibida ao utilizador)
└─ dados: ARRAY (opcional, para processamento JS)

KEYWORDS: response patterns, error handling, session messaging

================================================================================
15. PALAVRAS-CHAVE TÉCNICAS RESUMO
================================================================================

ARQUITETURA & PADRÕES:
├─ MVC-like / Controllers in Processos/
├─ Session-based Authentication
├─ Transaction-oriented (SQL BEGIN/COMMIT/ROLLBACK)
├─ Prepared Statements (PDO parameterized queries)
├─ Component-based Templates (includes navbar, sidebar)
└─ POST-Redirect-GET pattern

BANCO DE DADOS:
├─ MySQL/MariaDB (7 tabelas normalizadas)
├─ Foreign Keys + Constraints
├─ ENUM states (disponivel, reservado, emprestado, indisponivel)
├─ Transações ACID (atomicidade operações críticas)
├─ Índices em colunas de pesquisa/filtragem
├─ Soft-delete via data_devolucao_real (IS NULL)
└─ N:N relationships (livro_autores)

AUTENTICAÇÃO & SEGURANÇA:
├─ bcrypt password hashing
├─ FILTER_VALIDATE_EMAIL
├─ htmlspecialchars() para XSS
├─ Prepared statements para SQL injection
├─ Session timeout (PHP default 24min)
├─ Permissões baseadas em utilizador_tipo
├─ Whitelist file uploads
└─ MD5 renamed files (path traversal prevention)

VALIDAÇÕES:
├─ Email: FILTER_VALIDATE_EMAIL + UNIQUE
├─ Password: Min 8 chars (sem complexidade obrigatória)
├─ Telemóvel: Regex ^\d{9}$
├─ Código validação: rand(0,999) → sprintf('%03d')
├─ ISBN: UNIQUE constraint
├─ Upload extensões: whitelist jpg, png, gif, jpeg
└─ Estado livro: ENUM validation

PROCESSOS CRÍTICOS:
├─ Reserva: Validação limite 2 + gerar código + transação
├─ Empréstimo: Validar código + transação (reserva→empréstimo)
├─ Devolução: UPDATE data_devolucao_real + reset estado
├─ Renovação: +14 dias + contador renovações
├─ Expiração: 4 horas para levantamento (data_expiracao)
└─ Cancelamento: DELETE reserva + reset estado livro

OTIMIZAÇÕES:
├─ Índices: PRIMARY, UNIQUE, INDEX em colunas pesquisa
├─ Prepared statements (reutilização query plans)
├─ JOIN otimizado (N:N via tabela intermediária)
├─ COUNT(*) com WHERE clauses (não SELECT *)
└─ Cache session utilizador autenticado

FERRAMENTAS:
├─ PHP 7.x (sessions, file uploads, PDO)
├─ MySQL/MariaDB (stored procedures não usadas)
├─ HTML5 (semantic markup)
├─ CSS3 (responsive design, flexbox, grid)
├─ JavaScript Vanilla (modais, validação client-side)
├─ Apache (rewrite rules não configurados)
└─ PDO (database abstraction layer)

KEYWORDS FINAIS:
PHP, MySQL, PDO, prepared statements, session-based auth, bcrypt,
CRUD operations, transactions, ACID, normalization, indexes,
foreign keys, constraints, enum, timestamps, soft-delete,
file uploads, validation, XSS prevention, SQL injection prevention,
MVC-like, controllers, routing, alerting system, responsive design,
modal windows, grid layout, toast notifications

================================================================================
16. ROADMAP DE MELHORIAS
================================================================================

SEGURANÇA (ALTA PRIORIDADE)
├─ [ ] Implementar CSRF tokens (double submit cookies)
├─ [ ] Rate limiting em login (max 5 tentativas / 15 min)
├─ [ ] Session regeneration pós-login (session_regenerate_id())
├─ [ ] HTTPS obrigatório (force redirect http → https)
├─ [ ] Audit logging (tabela logs com ID utilizador + ação + timestamp)
├─ [ ] Validação password complexity (uppercase, lowercase, numbers)
├─ [ ] 2FA (two-factor authentication via SMS ou email)
└─ [ ] API keys para acesso programático (futuro mobile app)

FUNCIONALIDADES
├─ [ ] Paginação (LIMIT + OFFSET, select2.js)
├─ [ ] Pesquisa avançada (filtros CDU, ano, estado)
├─ [ ] Sistema de multas (atrasos em devoluções)
├─ [ ] Notificações email (prazo expirando, reserva pronta)
├─ [ ] Wishlist (desejar livros)
├─ [ ] Ratings e reviews de utilizadores
├─ [ ] Recomendações (baseado em histórico)
├─ [ ] Renovação automática (cron job)
└─ [ ] QR codes nos livros (ISBN scanning)

PERFORMANCE & SCALING
├─ [ ] Caching (Redis para sessions + queries frequentes)
├─ [ ] Query optimization (profiling com slow log MySQL)
├─ [ ] Lazy loading imagens (IntersectionObserver API)
├─ [ ] Compressão gzip (Apache mod_deflate)
├─ [ ] CDN para assets estáticos (CSS, JS, imagens)
├─ [ ] Database replication (master-slave MySQL)
├─ [ ] Sharding de data (se crescer muito)
└─ [ ] Search engine (Elasticsearch para pesquisa full-text)

ARQUITETURA & CÓDIGO
├─ [ ] Migrar para framework (Laravel, Symfony, modern PHP)
├─ [ ] API REST (separar backend frontend)
├─ [ ] Testes unitários + integração (PHPUnit)
├─ [ ] Versionamento API (v1, v2, backward compatibility)
├─ [ ] Docker containerization (docker-compose.yml)
├─ [ ] CI/CD pipeline (GitHub Actions, GitLab CI)
├─ [ ] Type hints PHP 7.4 (strict_types=1)
└─ [ ] PSR standards (PSR-12 coding standard)

INTERFACE
├─ [ ] Dark mode toggle
├─ [ ] Accessibility audit (WCAG 2.1 AA)
├─ [ ] Mobile app native (React Native, Flutter)
├─ [ ] Progressive Web App (PWA, service workers)
├─ [ ] Internacionalização i18n (PT, EN, ES, FR)
└─ [ ] Admin dashboard analytics (gráficos, estatísticas)

DADOS & QUALIDADE
├─ [ ] Data migration tools (CSV import/export)
├─ [ ] Backup automation (mysqldump scheduled)
├─ [ ] Database versioning (Liquibase, Flyway)
├─ [ ] Soft-delete universalmente (deleted_at field)
├─ [ ] Audit trail completo (who, what, when, why)
└─ [ ] GDPR compliance (data export, right to be forgotten)

================================================================================
17. SUMÁRIO FINAL
================================================================================

LivrisCore é uma aplicação de gestão de biblioteca single-page (sem SPA
framework como React/Vue, mas com JavaScript vanilla para modais) desenvolvida
em PHP vanilla com MySQL.

STACK TECNOLÓGICO:
├─ Backend: PHP 7.x + MySQL/MariaDB
├─ Frontend: HTML5 + CSS3 (Flexbox, Grid) + JavaScript vanilla
├─ Database: PDO (prepared statements)
├─ Autenticação: Session-based + bcrypt
├─ Hosting: Apache + XAMPP
└─ Deployment: Manual (FTP/SSH) ou CI/CD (TODO)

PRINCIPAIS CARACTERÍSTICAS:
├─ Catálogo pesquisável com filtros CDU
├─ Sistema de reservas com código validação 3-dígitos
├─ Gestão empréstimos com prazos (15 dias default)
├─ Painel admin completo (CRUD livros, utilizadores, empréstimos)
├─ Autenticação segura (bcrypt) com 2 níveis acesso
├─ Armazenamento dinâmico imagens (MD5 renamed)
├─ Alertas contextuais (sucesso, erro, aviso)
├─ Responsive design (mobile-friendly)
└─ Transações SQL (atomicidade operações críticas)

LIMITAÇÕES CONHECIDAS:
├─ Sem CSRF protection
├─ Sem rate limiting
├─ Sem paginação (carrega tudo em memory)
├─ Sem caching (cada request query DB)
├─ Sem logging/auditoria
├─ Sem 2FA
├─ Sem notificações email
├─ Sem teste automatizados
└─ Sem CI/CD pipeline

QUALIDADE DO CÓDIGO:
├─ Boa: SQL injection prevention (prepared statements)
├─ Boa: XSS prevention (htmlspecialchars)
├─ Média: Separação concerns (MVC-like, mas incompleto)
├─ Média: DRY principle (componentes reutilizáveis)


