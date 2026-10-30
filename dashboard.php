<?php
require_once __DIR__ . '/includes/init.php';

// Indicadores gerais
$totalSocios = (int) $conn->query("SELECT COUNT(*) AS total FROM socios")->fetch_assoc()['total'];
$totalPlanos = (int) $conn->query("SELECT COUNT(*) AS total FROM planos")->fetch_assoc()['total'];
$totalAulas = (int) $conn->query("SELECT COUNT(*) AS total FROM aulas")->fetch_assoc()['total'];

$presencasHoje = (int) $conn->query(
    "SELECT COUNT(*) AS total FROM presencas WHERE data_presenca = CURDATE()"
)->fetch_assoc()['total'];

$totalPresencas = (int) $conn->query("SELECT COUNT(*) AS total FROM presencas")->fetch_assoc()['total'];

$receitaMes = (float) $conn->query(
    "SELECT COALESCE(SUM(valor), 0) AS total FROM pagamentos
     WHERE estado = 'Pago'
       AND MONTH(data_pagamento) = MONTH(CURDATE())
       AND YEAR(data_pagamento) = YEAR(CURDATE())"
)->fetch_assoc()['total'];

$novosMes = (int) $conn->query(
    "SELECT COUNT(*) AS total FROM socios
     WHERE MONTH(data_registo) = MONTH(CURDATE())
       AND YEAR(data_registo) = YEAR(CURDATE())"
)->fetch_assoc()['total'];

$pendentes = $conn->query(
    "SELECT COUNT(*) AS total, COALESCE(SUM(valor), 0) AS valor
     FROM pagamentos WHERE estado <> 'Pago'"
)->fetch_assoc();

// Receita dos últimos 6 meses
$meses = meses_recentes(6);
$receitaRegistada = [];
$resultadoReceita = $conn->query(
    "SELECT DATE_FORMAT(data_pagamento, '%Y-%m') AS mes, SUM(valor) AS total
     FROM pagamentos
     WHERE estado = 'Pago'
       AND data_pagamento >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH)
     GROUP BY mes"
);
while ($linha = $resultadoReceita->fetch_assoc()) {
    $receitaRegistada[$linha['mes']] = (float) $linha['total'];
}

$receitaRotulos = array_values($meses);
$receitaValores = [];
foreach ($meses as $chave => $rotulo) {
    $receitaValores[] = $receitaRegistada[$chave] ?? 0;
}

// Sócios por plano
$planoRotulos = [];
$planoValores = [];
$resultadoPlanos = $conn->query(
    "SELECT p.nome, COUNT(s.id) AS total
     FROM planos p
     LEFT JOIN socios s ON s.plano_id = p.id
     GROUP BY p.id, p.nome
     ORDER BY p.preco"
);
while ($linha = $resultadoPlanos->fetch_assoc()) {
    $planoRotulos[] = $linha['nome'];
    $planoValores[] = (int) $linha['total'];
}

// Últimos registos
$ultimosSocios = $conn->query(
    "SELECT s.*, p.nome AS plano_nome
     FROM socios s
     LEFT JOIN planos p ON s.plano_id = p.id
     ORDER BY s.id DESC
     LIMIT 5"
);

$ultimosPagamentos = $conn->query(
    "SELECT pg.*, s.nome AS socio_nome
     FROM pagamentos pg
     JOIN socios s ON pg.socio_id = s.id
     ORDER BY pg.data_pagamento DESC, pg.id DESC
     LIMIT 5"
);

$titulo = 'Dashboard';
$ativo = 'dashboard';
include __DIR__ . '/includes/cabecalho.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Painel de Controlo</h1>
        <p class="text-muted mb-0">
            <i class="fa-regular fa-calendar me-1"></i><?= date('d/m/Y') ?>
            &middot; Visão geral do ginásio
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="adicionar_socio.php" class="btn btn-success">
            <i class="fa-solid fa-user-plus me-1"></i>Novo Sócio
        </a>
        <a href="presencas.php" class="btn btn-outline-success">
            <i class="fa-solid fa-check-to-slot me-1"></i>Marcar Presença
        </a>
    </div>
</div>

<div class="row g-3 g-lg-4 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card stat-card shadow-sm h-100 border-start border-4 border-primary">
            <div class="card-body d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted text-uppercase small fw-semibold mb-1">Total de Sócios</p>
                    <p class="display-6 mb-1"><?= $totalSocios ?></p>
                    <small class="text-muted"><?= $novosMes ?> novo(s) este mês</small>
                </div>
                <i class="fa-solid fa-users text-primary stat-icon"></i>
            </div>
            <a href="socios.php" class="card-footer bg-white text-decoration-none small py-2">
                Gerir sócios <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card stat-card shadow-sm h-100 border-start border-4 border-success">
            <div class="card-body d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted text-uppercase small fw-semibold mb-1">Receita Mensal</p>
                    <p class="display-6 mb-1"><?= euros($receitaMes) ?></p>
                    <small class="text-muted">
                        <?= (int) $pendentes['total'] ?> pendente(s): <?= euros($pendentes['valor']) ?>
                    </small>
                </div>
                <i class="fa-solid fa-wallet text-success stat-icon"></i>
            </div>
            <a href="pagamentos.php" class="card-footer bg-white text-decoration-none small py-2">
                Ver pagamentos <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card stat-card shadow-sm h-100 border-start border-4 border-info">
            <div class="card-body d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted text-uppercase small fw-semibold mb-1">Aulas</p>
                    <p class="display-6 mb-1"><?= $totalAulas ?></p>
                    <small class="text-muted"><?= $totalPlanos ?> planos disponíveis</small>
                </div>
                <i class="fa-solid fa-dumbbell text-info stat-icon"></i>
            </div>
            <a href="aulas.php" class="card-footer bg-white text-decoration-none small py-2">
                Gerir aulas <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card stat-card shadow-sm h-100 border-start border-4 border-warning">
            <div class="card-body d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted text-uppercase small fw-semibold mb-1">Presenças Hoje</p>
                    <p class="display-6 mb-1"><?= $presencasHoje ?></p>
                    <small class="text-muted"><?= $totalPresencas ?> no total</small>
                </div>
                <i class="fa-solid fa-clipboard-check text-warning stat-icon"></i>
            </div>
            <a href="presencas.php" class="card-footer bg-white text-decoration-none small py-2">
                Registar presenças <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0"><i class="fa-solid fa-chart-column text-success me-2"></i>Receita dos últimos 6 meses</h2>
                <span class="badge bg-success"><?= euros(array_sum($receitaValores)) ?></span>
            </div>
            <div class="card-body">
                <div style="height: 280px;">
                    <canvas id="graficoReceita" aria-label="Gráfico da receita mensal" role="img"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0"><i class="fa-solid fa-tags text-success me-2"></i>Sócios por plano</h2>
            </div>
            <div class="card-body">
                <div style="height: 280px;">
                    <canvas id="graficoPlanos" aria-label="Gráfico de sócios por plano" role="img"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0"><i class="fa-solid fa-user-plus text-success me-2"></i>Últimos sócios</h2>
                <a href="socios.php" class="btn btn-sm btn-outline-success">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Sócio</th>
                                <th>Plano</th>
                                <th>Registo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($ultimosSocios->num_rows === 0): ?>
                                <tr><td colspan="3" class="text-center text-muted py-4">Ainda não existem sócios.</td></tr>
                            <?php else: ?>
                                <?php while ($socio = $ultimosSocios->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="avatar"><?= e(iniciais($socio['nome'])) ?></span>
                                                <span class="fw-semibold"><?= e($socio['nome']) ?></span>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-secondary"><?= e($socio['plano_nome'] ?? 'Sem plano') ?></span></td>
                                        <td class="text-muted small"><?= data_pt($socio['data_registo']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0"><i class="fa-solid fa-receipt text-success me-2"></i>Últimos pagamentos</h2>
                <a href="pagamentos.php" class="btn btn-sm btn-outline-success">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Sócio</th>
                                <th>Valor</th>
                                <th>Data</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($ultimosPagamentos->num_rows === 0): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">Ainda não existem pagamentos.</td></tr>
                            <?php else: ?>
                                <?php while ($pagamento = $ultimosPagamentos->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= e($pagamento['socio_nome']) ?></td>
                                        <td class="fw-bold text-success"><?= euros($pagamento['valor']) ?></td>
                                        <td class="text-muted small"><?= data_pt($pagamento['data_pagamento']) ?></td>
                                        <td><span class="badge <?= badge_pagamento($pagamento['estado']) ?>"><?= e($pagamento['estado']) ?></span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const receitaRotulos = <?= json_encode($receitaRotulos, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const receitaValores = <?= json_encode($receitaValores, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const planoRotulos = <?= json_encode($planoRotulos, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const planoValores = <?= json_encode($planoValores, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    new Chart(document.getElementById('graficoReceita'), {
        type: 'bar',
        data: {
            labels: receitaRotulos,
            datasets: [{
                label: 'Receita (€)',
                data: receitaValores,
                backgroundColor: 'rgba(70, 127, 31, 0.75)',
                borderColor: '#35631a',
                borderWidth: 1,
                borderRadius: 6,
                maxBarThickness: 48
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (contexto) => new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'EUR' }).format(contexto.parsed.y)
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (valor) => valor + ' €',
                        color: '#6b7a6e'
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: { grid: { display: false }, ticks: { color: '#6b7a6e' } }
            }
        }
    });

    new Chart(document.getElementById('graficoPlanos'), {
        type: 'doughnut',
        data: {
            labels: planoRotulos,
            datasets: [{
                data: planoValores,
                backgroundColor: ['#467f1f', '#6fb43f', '#a8d47f', '#d99a1f'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16 } }
            }
        }
    });
</script>

<?php include __DIR__ . '/includes/rodape.php'; ?>
