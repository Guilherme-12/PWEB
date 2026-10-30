<?php
require_once __DIR__ . '/includes/init.php';

$titulo = 'Início';
$ativo = 'dashboard';
include __DIR__ . '/includes/cabecalho.php';
?>

<div class="container py-5">

    <!-- Hero -->
    <section class="card shadow-sm border-0 overflow-hidden mb-5">
        <div class="row g-0">
            <div class="col-md-7 p-5 text-white" style="background: linear-gradient(135deg, #467f1f 0%, #1f3a2d 100%);">
                <span class="badge bg-white text-success fw-semibold mb-3">FitLife Gym</span>
                <h1 class="display-5 fw-bold">FitLife Manager</h1>
                <p class="lead mb-4">Plataforma web de gestão integrada para o ginásio fictício FitLife Gym.</p>
                <p class="mb-4">
                    Centraliza sócios, planos, aulas, presenças e pagamentos num único local,
                    com estatísticas e indicadores de gestão.
                </p>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="dashboard.php" class="btn btn-success btn-lg">
                        <i class="fa-solid fa-gauge-high me-2"></i>Aceder ao Painel
                    </a>
                    <a href="socios.php" class="btn btn-outline-light btn-lg">
                        <i class="fa-solid fa-users me-2"></i>Gestão de Sócios
                    </a>
                </div>
            </div>
            <div class="col-md-5 bg-white p-5 text-center d-flex flex-column justify-content-center">
                <i class="fa-solid fa-dumbbell display-3 text-success mb-3"></i>
                <h2 class="h4 fw-bold">Sua operação num só local</h2>
                <p class="text-muted mb-0">
                    Sócios, planos, aulas, presenças, pagamentos e indicadores — tudo em cada vez.
                </p>
            </div>
        </div>
    </section>

    <!-- Módulos -->
    <section class="mb-5">
        <h2 class="h3 mb-4 text-center"><i class="fa-solid fa-grid-horizontal text-success me-2"></i>Módulos da Plataforma</h2>
        <div class="row g-4">
            <?php
            $modulos = [
                ['dashboard.php', 'fa-gauge-high', 'Dashboard', 'Indicadores e estatísticas', 'Visão geral do negócio'],
                ['socios.php', 'fa-users', 'Sócios', 'Registo, pesquisa e gestão', 'Gestão de membros'],
                ['planos.php', 'fa-tags', 'Planos', 'Preços e receita potencial', 'Planos de inscrição'],
                ['aulas.php', 'fa-calendar-days', 'Aulas', 'Instrução, horários e capacidade', 'Gestão de aulas'],
                ['presencas.php', 'fa-check-to-slot', 'Presenças', 'Registo e controlos', 'Controlo diário'],
                ['pagamentos.php', 'fa-euro-sign', 'Pagamentos', 'Estados e histórico', 'Gestão financeira'],
            ];
            ?>
            <?php foreach ($modulos as [$url, $icone, $titulo, $desc1, $desc2]): ?>
                <div class="col-md-4 col-lg-4">
                    <div class="card shadow-sm h-100 text-center">
                        <div class="card-body">
                            <i class="fa-solid <?= e($icone) ?> fa-2x text-success mb-3"></i>
                            <h3 class="h5 fw-bold"><?= e($titulo) ?></h3>
                            <p class="text-muted mb-2"><?= e($desc1) ?></p>
                            <p class="small text-muted mb-0"><?= e($desc2) ?></p>
                        </div>
                        <div class="card-footer bg-white text-center py-2">
                            <a class="btn btn-sm btn-outline-success" href="<?= e($url) ?>">
                                <i class="fa-solid fa-arrow-right me-1"></i>Explorar
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- CTA final -->
    <section class="card shadow-sm border-0 mb-5">
        <div class="card-body text-center py-5">
            <h2 class="h4 fw-bold mb-3"><i class="fa-solid fa-rocket text-success me-2"></i>Pronto para gerir o seu ginásio?</h2>
            <p class="text-muted mb-4">
                Importe o esquema, registre os primeiros sócios e comece a usar o painel.
            </p>
            <a href="dashboard.php" class="btn btn-success btn-lg">
                <i class="fa-solid fa-gauge-high me-2"></i>Aceder ao Painel
            </a>
        </div>
    </section>

</div>

<?php include __DIR__ . '/includes/rodape.php'; ?>
