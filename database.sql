CREATE DATABASE IF NOT EXISTS bolsas_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bolsas_db;

DROP TABLE IF EXISTS historico_estados;
DROP TABLE IF EXISTS esclarecimentos;
DROP TABLE IF EXISTS documentos;
DROP TABLE IF EXISTS candidaturas;
DROP TABLE IF EXISTS bolsas;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('candidato', 'tecnico') NOT NULL DEFAULT 'candidato',
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    nif VARCHAR(9) NULL,
    morada VARCHAR(255) NULL,
    telefone VARCHAR(15) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE bolsas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    designacao VARCHAR(150) NOT NULL,
    tipo VARCHAR(100) NOT NULL,
    area_estudo VARCHAR(100) NOT NULL,
    montante DECIMAL(10,2) NOT NULL,
    requisitos TEXT NOT NULL,
    prazo DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE candidaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bolsa_id INT NOT NULL,
    tecnico_id INT NULL,
    instituicao VARCHAR(150) NOT NULL,
    curso VARCHAR(150) NOT NULL,
    rendimento DECIMAL(10,2) NOT NULL,
    iban VARCHAR(34) NOT NULL,
    estado ENUM('submetida', 'em analise', 'aprovada', 'rejeitada', 'aguardar documentacao') DEFAULT 'submetida',
    observacoes_internas TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_candidato_bolsa (user_id, bolsa_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (bolsa_id) REFERENCES bolsas(id) ON DELETE CASCADE,
    FOREIGN KEY (tecnico_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidatura_id INT NOT NULL,
    tipo_documento VARCHAR(100) NOT NULL,
    nome_ficheiro VARCHAR(255) NOT NULL,
    nome_original VARCHAR(255) NOT NULL,
    data_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidatura_id) REFERENCES candidaturas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE esclarecimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidatura_id INT NOT NULL,
    remetente_id INT NOT NULL,
    mensagem TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidatura_id) REFERENCES candidaturas(id) ON DELETE CASCADE,
    FOREIGN KEY (remetente_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE historico_estados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidatura_id INT NOT NULL,
    tecnico_id INT NOT NULL,
    estado_anterior VARCHAR(50) NOT NULL,
    estado_novo VARCHAR(50) NOT NULL,
    data_alteracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidatura_id) REFERENCES candidaturas(id) ON DELETE CASCADE,
    FOREIGN KEY (tecnico_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO users (username, password_hash, role, nome, email, nif, morada, telefone) VALUES
('tecnico1', '$2y$10$e0MYzXy3tZt5M3k0lYxHmeR7I75q1tJ5yG8d1wA8s7tQy1tA0B2C.', 'tecnico', 'Dra. Maria Técnico', 'maria.tecnico@acores.pt', '123456789', 'Ponta Delgada', '912345678'),
('aluno1', '$2y$10$e0MYzXy3tZt5M3k0lYxHmeR7I75q1tJ5yG8d1wA8s7tQy1tA0B2C.', 'candidato', 'Carlos Estudante', 'carlos.aluno@uac.pt', '234567890', 'Rua da Universidade, 12', '961234567');

INSERT INTO bolsas (designacao, tipo, area_estudo, montante, requisitos, prazo) VALUES
('Bolsa de Mérito Universitário', 'Mérito', 'Informática e Tecnologias', 2500.00, 'Média superior a 16 valores e aproveitamento pleno no ano anterior.', DATE_ADD(CURDATE(), INTERVAL 60 DAY)),
('Apoio Social de Emergência', 'Apoio Social', 'Geral', 1200.00, 'Rendimento do agregado familiar per capita inferior ao salário mínimo.', DATE_ADD(CURDATE(), INTERVAL 30 DAY)),
('Bolsa de Apoio à Insularidade', 'Deslocação', 'Todas as áreas', 1800.00, 'Estudantes deslocados de outras ilhas do arquipélago.', DATE_ADD(CURDATE(), INTERVAL 45 DAY)),
('Bolsa Expirada de Teste', 'Geral', 'Letras', 800.00, 'Bolsa com prazo já encerrado para teste de validação.', DATE_SUB(CURDATE(), INTERVAL 5 DAY));
