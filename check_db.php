<?php
/**
 * Garante que as tabelas de aulas e presenças existem.
 * É incluído automaticamente por includes/init.php.
 */

if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/config_db.php';
}

$conn->query("CREATE TABLE IF NOT EXISTS aulas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    instrutor VARCHAR(100) NOT NULL,
    horario VARCHAR(50) NOT NULL,
    capacidade INT NOT NULL DEFAULT 20
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS presencas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    socio_id INT NOT NULL,
    aula_id INT NOT NULL,
    data_presenca DATE NOT NULL,
    UNIQUE KEY uq_presenca (socio_id, aula_id, data_presenca),
    CONSTRAINT fk_presencas_socio FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE CASCADE,
    CONSTRAINT fk_presencas_aula FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
