<?php
require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('socios.php');
}

validar_csrf();

$id = inteiro_positivo($_POST['id'] ?? null);
if ($id === null) {
    flash('warning', 'Sócio inválido.');
    redirecionar('socios.php');
}

$stmt = $conn->prepare("SELECT nome FROM socios WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$socio = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$socio) {
    flash('warning', 'O sócio indicado já não existe.');
    redirecionar('socios.php');
}

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM pagamentos WHERE socio_id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$totalPagamentos = (int) $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

if ($totalPagamentos > 0) {
    flash(
        'warning',
        'Não é possível eliminar "' . $socio['nome'] . '" porque tem ' . $totalPagamentos .
        ' pagamento(s) associado(s). Elimina primeiro os pagamentos no histórico.'
    );
    redirecionar('socios.php');
}

try {
    // As presenças são removidas em cascata, mas garantimos a limpeza explicitamente.
    $stmt = $conn->prepare("DELETE FROM presencas WHERE socio_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM socios WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    flash('success', 'Sócio "' . $socio['nome'] . '" eliminado com sucesso.');
} catch (mysqli_sql_exception $erro) {
    flash('danger', 'Não foi possível eliminar o sócio. Tenta novamente.');
}

redirecionar('socios.php');
