-- 1. CATEGORIAS
CREATE TABLE categorias (
    id   INT         PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO categorias (nome) VALUES ('Livro'), ('CD'), ('Blu-ray');

-- 2. UTILIZADORES
CREATE TABLE utilizadores (
    id              INT          PRIMARY KEY AUTO_INCREMENT,
    nome            VARCHAR(100) NOT NULL,
    email           VARCHAR(100) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    tipo        TINYINT(1) NOT NULL DEFAULT 0
    ativo           BOOLEAN      NOT NULL DEFAULT TRUE,
    data_registo    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 3. ITENS
CREATE TABLE itens (
    id            INT          PRIMARY KEY AUTO_INCREMENT,
    titulo        VARCHAR(150) NOT NULL,
    autor_artista VARCHAR(100) DEFAULT NULL,
    descricao     TEXT         DEFAULT NULL,
    categoria_id  INT          DEFAULT NULL,
    imagem_url    VARCHAR(300) DEFAULT NULL,
    estado        ENUM('disponivel','reservado','emprestado')
                               NOT NULL DEFAULT 'disponivel',
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- 4. RESERVAS
CREATE TABLE reservas (
    id             INT       PRIMARY KEY AUTO_INCREMENT,
    utilizador_id  INT       NOT NULL,
    item_id        INT       NOT NULL,
    data_reserva   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_expiracao DATETIME  NOT NULL,
    status         ENUM('pendente','concluida','cancelada')
    NOT NULL DEFAULT 'pendente',
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id)       REFERENCES itens(id)        ON DELETE CASCADE
);

-- 5. EMPRÉSTIMOS
CREATE TABLE emprestimos (
    id                       INT       PRIMARY KEY AUTO_INCREMENT,
    utilizador_id            INT       NOT NULL,
    item_id                  INT       NOT NULL,
    reserva_id               INT       DEFAULT NULL,
    data_saida               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_prevista_devolucao  DATE      NOT NULL,
    data_devolucao_real      DATETIME  DEFAULT NULL,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id)       REFERENCES itens(id)        ON DELETE CASCADE,
    FOREIGN KEY (reserva_id)    REFERENCES reservas(id)     ON DELETE SET NULL
);