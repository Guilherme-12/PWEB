-- ============================================================
--  FitLife Manager - Base de dados
--  MySQL 8 / MariaDB 10.4+
--
--  ATENÇÃO: este script recria as tabelas e apaga os dados atuais.
--  Importa-o no phpMyAdmin (separador "Importar") ou com:
--      mysql -u root -p < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS fitlife_gym
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE fitlife_gym;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS presencas;
DROP TABLE IF EXISTS pagamentos;
DROP TABLE IF EXISTS aulas;
DROP TABLE IF EXISTS socios;
DROP TABLE IF EXISTS planos;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Planos de inscrição
-- ------------------------------------------------------------
CREATE TABLE planos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    UNIQUE KEY uq_planos_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Sócios
-- ------------------------------------------------------------
CREATE TABLE socios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NULL,
    telefone VARCHAR(20) NULL,
    plano_id INT NULL,
    data_registo DATE NOT NULL,
    UNIQUE KEY uq_socios_email (email),
    KEY idx_socios_plano (plano_id),
    CONSTRAINT fk_socios_planos FOREIGN KEY (plano_id)
        REFERENCES planos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Aulas
-- ------------------------------------------------------------
CREATE TABLE aulas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    instrutor VARCHAR(100) NOT NULL,
    horario VARCHAR(50) NOT NULL,
    capacidade INT NOT NULL DEFAULT 20
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Presenças
-- ------------------------------------------------------------
CREATE TABLE presencas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    socio_id INT NOT NULL,
    aula_id INT NOT NULL,
    data_presenca DATE NOT NULL,
    UNIQUE KEY uq_presenca (socio_id, aula_id, data_presenca),
    KEY idx_presencas_data (data_presenca),
    KEY idx_presencas_aula (aula_id),
    CONSTRAINT fk_presencas_socio FOREIGN KEY (socio_id)
        REFERENCES socios(id) ON DELETE CASCADE,
    CONSTRAINT fk_presencas_aula FOREIGN KEY (aula_id)
        REFERENCES aulas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Pagamentos
-- ------------------------------------------------------------
CREATE TABLE pagamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    socio_id INT NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_pagamento DATE NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'Pago',
    KEY idx_pagamentos_data (data_pagamento),
    KEY idx_pagamentos_socio (socio_id),
    CONSTRAINT fk_pagamentos_socio FOREIGN KEY (socio_id)
        REFERENCES socios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  Dados de exemplo
-- ============================================================

INSERT INTO planos (nome, preco) VALUES
('Basic', 25.00),
('Premium', 40.00),
('Gold', 60.00);

INSERT INTO socios (nome, email, telefone, plano_id, data_registo) VALUES
('Ana Silva', 'ana.silva@email.pt', '912 345 678', 2, DATE_SUB(CURDATE(), INTERVAL 120 DAY)),
('Bruno Costa', 'bruno.costa@email.pt', '913 456 789', 1, DATE_SUB(CURDATE(), INTERVAL 95 DAY)),
('Carla Mendes', 'carla.mendes@email.pt', '914 567 890', 3, DATE_SUB(CURDATE(), INTERVAL 80 DAY)),
('Diogo Ferreira', 'diogo.ferreira@email.pt', '915 678 901', 2, DATE_SUB(CURDATE(), INTERVAL 65 DAY)),
('Eva Santos', 'eva.santos@email.pt', '916 789 012', 1, DATE_SUB(CURDATE(), INTERVAL 45 DAY)),
('Fábio Rodrigues', 'fabio.rodrigues@email.pt', '917 890 123', 3, DATE_SUB(CURDATE(), INTERVAL 30 DAY)),
('Gabriela Lopes', 'gabriela.lopes@email.pt', '918 901 234', 2, DATE_SUB(CURDATE(), INTERVAL 20 DAY)),
('Hugo Martins', 'hugo.martins@email.pt', '919 012 345', 1, DATE_SUB(CURDATE(), INTERVAL 12 DAY)),
('Inês Carvalho', 'ines.carvalho@email.pt', '910 123 456', 3, DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
('João Pereira', 'joao.pereira@email.pt', '911 234 567', 2, DATE_SUB(CURDATE(), INTERVAL 1 DAY));

INSERT INTO aulas (nome, instrutor, horario, capacidade) VALUES
('Yoga Flow', 'Marta Nunes', 'Seg/Qua 08:00', 15),
('Spinning', 'Rui Almeida', 'Ter/Qui 18:30', 20),
('CrossFit', 'Pedro Sousa', 'Seg/Qua/Sex 19:00', 12),
('Zumba', 'Sofia Lima', 'Ter/Qui 20:00', 25),
('Musculação Acompanhada', 'André Rocha', 'Sábado 10:00', 10),
('Pilates', 'Marta Nunes', 'Sexta 09:00', 12);

INSERT INTO pagamentos (socio_id, valor, data_pagamento, estado) VALUES
(1, 40.00, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Pago'),
(1, 40.00, DATE_SUB(CURDATE(), INTERVAL 33 DAY), 'Pago'),
(2, 25.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Pago'),
(2, 25.00, DATE_SUB(CURDATE(), INTERVAL 36 DAY), 'Pago'),
(3, 60.00, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Pago'),
(3, 60.00, DATE_SUB(CURDATE(), INTERVAL 40 DAY), 'Pago'),
(4, 40.00, DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'Pendente'),
(4, 40.00, DATE_SUB(CURDATE(), INTERVAL 38 DAY), 'Pago'),
(5, 25.00, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'Pago'),
(5, 25.00, DATE_SUB(CURDATE(), INTERVAL 70 DAY), 'Pago'),
(6, 60.00, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Pago'),
(6, 60.00, DATE_SUB(CURDATE(), INTERVAL 75 DAY), 'Pago'),
(7, 40.00, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'Pago'),
(7, 40.00, DATE_SUB(CURDATE(), INTERVAL 68 DAY), 'Pago'),
(8, 25.00, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'Pendente'),
(8, 25.00, DATE_SUB(CURDATE(), INTERVAL 100 DAY), 'Pago'),
(9, 60.00, CURDATE(), 'Pago'),
(9, 60.00, DATE_SUB(CURDATE(), INTERVAL 110 DAY), 'Pago'),
(10, 40.00, CURDATE(), 'Pago'),
(10, 40.00, DATE_SUB(CURDATE(), INTERVAL 130 DAY), 'Pago'),
(2, 25.00, DATE_SUB(CURDATE(), INTERVAL 160 DAY), 'Pago'),
(3, 60.00, DATE_SUB(CURDATE(), INTERVAL 140 DAY), 'Pago'),
(5, 25.00, DATE_SUB(CURDATE(), INTERVAL 165 DAY), 'Pago'),
(7, 40.00, DATE_SUB(CURDATE(), INTERVAL 125 DAY), 'Pago');

INSERT INTO presencas (socio_id, aula_id, data_presenca) VALUES
(1, 1, CURDATE()),
(3, 1, CURDATE()),
(5, 1, CURDATE()),
(7, 1, CURDATE()),
(2, 2, CURDATE()),
(4, 2, CURDATE()),
(6, 2, CURDATE()),
(8, 2, CURDATE()),
(1, 3, CURDATE()),
(6, 3, CURDATE()),
(9, 3, CURDATE()),
(10, 3, CURDATE()),
(3, 4, CURDATE()),
(7, 4, CURDATE()),
(9, 5, CURDATE()),
(1, 2, DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(2, 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(3, 3, DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
(4, 4, DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(5, 5, DATE_SUB(CURDATE(), INTERVAL 6 DAY)),
(6, 1, DATE_SUB(CURDATE(), INTERVAL 7 DAY)),
(7, 2, DATE_SUB(CURDATE(), INTERVAL 8 DAY)),
(8, 3, DATE_SUB(CURDATE(), INTERVAL 9 DAY)),
(9, 4, DATE_SUB(CURDATE(), INTERVAL 12 DAY)),
(10, 5, DATE_SUB(CURDATE(), INTERVAL 14 DAY));
