<?php
/**
 * Ligação à base de dados MySQL/MariaDB.
 *
 * Pode ser configurada através de variáveis de ambiente (opcional):
 *   FITLIFE_DB_HOST, FITLIFE_DB_USER, FITLIFE_DB_PASS, FITLIFE_DB_NAME
 */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = getenv('FITLIFE_DB_HOST') ?: 'localhost';
$user = getenv('FITLIFE_DB_USER') ?: 'root';
$password = getenv('FITLIFE_DB_PASS') ?: '';
$database = getenv('FITLIFE_DB_NAME') ?: 'fitlife_gym';

try {
    $conn = new mysqli($host, $user, $password, $database);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $erro) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8">';
    echo '<title>Erro de ligação - FitLife Manager</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '</head><body class="bg-light"><div class="db-error shadow-sm">';
    echo '<h1 class="h4 text-danger mb-3">Não foi possível ligar à base de dados</h1>';
    echo '<p>Confirma que o MySQL/MariaDB está a correr e que a base de dados <strong>fitlife_gym</strong> foi criada.';
    echo ' Importa o ficheiro <code>database.sql</code> no phpMyAdmin ou na linha de comandos.</p>';
    echo '<p class="mb-1"><strong>Detalhe do erro:</strong></p>';
    echo '<pre class="bg-light border rounded p-2 small">' . htmlspecialchars($erro->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
    echo '<p class="text-muted small mb-0">Se as credenciais forem diferentes, edita <code>config_db.php</code>.</p>';
    echo '</div></body></html>';
    exit;
}
