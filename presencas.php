<?php
require_once __DIR__ . '/includes/init.php';

$erros = [];
$form = [
    'socio_id' => '',
    'aula_id' => '',
    'data' => date('Y-m-d'),
];

// ---------------------------------------------------------
// Ações (registar / eliminar)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $acao = texto($_POST['acao'] ?? '');

    if ($acao === 'eliminar') {
        $id = inteiro_positivo($_POST['id'] ?? null);
        if ($id === null) {
            flash('warning', 'Presença inválida.');
            redirecionar('presencas.php');
        }

        $stmt = $conn->prepare("DELETE FROM presencas WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $eliminadas = $stmt->affected_rows;
        $stmt->close();

        flash($eliminadas > 0 ? 'success' : 'warning',
            $eliminadas > 0 ? 'Presença eliminada com sucesso.' : 'A presença indicada já não existia.');
        redirecionar('presencas.php');
    }

    if ($acao === 'registar') {
        $socioId = inteiro_positivo($_POST['socio_id'] ?? null);
        $aulaId = inteiro_positivo($_POST['aula_id'] ?? null);
        $data = texto($_POST['data'] ?? null);

        $form = [
            'socio_id' => $socioId ?? '',
            'aula_id' => $aulaId ?? '',
            'data' => $data,
        ];

        $socio = null;
        if ($socioId === null) {
            $erros[] = 'Escolhe um sócio.';
        } else {
            $stmt = $conn->prepare("SELECT nome FROM socios WHERE id = ?");
            $stmt->bind_param('i', $socioId);
            $stmt->execute();
            $socio = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$socio) {
                $erros[] = 'O sócio selecionado não existe.';
            }
        }

        $aula = null;
        if ($aulaId === null) {
            $erros[] = 'Escolhe uma aula.';
        } else {
            $stmt = $conn->prepare("SELECT nome, capacidade FROM aulas WHERE id = ?");
            $stmt->bind_param('i', $aulaId);
            $stmt->execute();
            $aula = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$aula) {
                $erros[] = 'A aula selecionada não existe.';
            }
        }

        if (!data_valida($data)) {
            $erros[] = 'Indica uma data válida.';
        } elseif ($data > date('Y-m-d')) {
            $erros[] = 'Não é possível registar presenças em datas futuras.';
        }

        if ($erros === []) {
            $stmt = $conn->prepare(
                "SELECT id FROM presencas WHERE socio_id = ? AND aula_id = ? AND data_presenca = ? LIMIT 1"
            );
            $stmt->bind_param('iis', $socioId, $aulaId, $data);
            $stmt->execute();
            $duplicada = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($duplicada) {
                $erros[] = 'Este sócio já tem presença registada nesta aula e data.';
            } else {
                $stmt = $conn->prepare(
                    "SELECT COUNT(*) AS total FROM presencas WHERE aula_id = ? AND data_presenca = ?"
                );
                $stmt->bind_param('is', $aulaId, $data);
                $stmt->execute();
                $ocupacao = (int) $stmt->get_result()->fetch_assoc()['total'];
                $stmt->close();

                if ($ocupacao >= (int) $aula['capacidade']) {
                    $erros[] = 'A aula "' . $aula['nome'] . '" já está cheia nesta data (' .
                        $ocupacao . '/' . (int) $aula['capacidade'] . ').';
                } else {
                    $stmt = $conn->prepare(
                        "INSERT INTO presencas (socio_id, aula_id, data_presenca) VALUES (?, ?, ?)"
                    );
                    $stmt->bind_param('iis', $socioId, $aulaId, $data);
                    $stmt->execute();
                    $stmt->close();

                    flash('success', 'Presença de ' . $socio['nome'] . ' em "' . $aula['nome'] . '" registada.');
                    redirecionar('presencas.php?data=' . urlencode($data));
                }
            }
        }
    }
}

// ---------------------------------------------------------
// Dados para os formulários
// ---------------------------------------------------------
$listaSocios = [];
$resultadoSocios = $conn->query(
    "SELECT s.id, s.nome, p.nome AS plano
     FROM socios s
     LEFT JOIN planos p ON s.plano_id = p.id
     ORDER BY s.nome"
);
while ($socio = $resultadoSocios->fetch_assoc()) {
    $listaSocios[] = $socio;
}

$listaAulas = [];
$resultadoAulas = $conn->query(
    "SELECT id, nome, horario, capacidade FROM aulas ORDER BY nome"
);
while ($aula = $resultadoAulas->fetch_assoc()) {
    $listaAulas[] = $aula;
}

// ---------------------------------------------------------
// Filtros da listagem
// ---------------------------------------------------------
if (array_key_exists('data', $_GET)) {
    $dataBruta = texto($_GET['data'] ?? null);
    $dataFiltro = data_valida($dataBruta) ? $dataBruta : '';
} else {
    $dataFiltro = date('Y-m-d');
}
$aulaFiltro = inteiro_positivo($_GET['aula'] ?? null);

$sql = "SELECT pr.*, s.nome AS socio_nome, a.nome AS aula_nome, a.horario
        FROM presencas pr
        JOIN socios s ON pr.socio_id = s.id
        JOIN aulas a ON pr.aula_id = a.id
        WHERE 1 = 1";
$parametros = [];
$tipos = '';

if ($dataFiltro !== '') {
    $sql .= " AND pr.data_presenca = ?";
    $parametros[] = $dataFiltro;
    $tipos .= 's';
}

if ($aulaFiltro !== null) {
    $sql .= " AND pr.aula_id = ?";
    $parametros[] = $aulaFiltro;
    $tipos .= 'i';
}

$sql .= " ORDER BY pr.data_presenca DESC, s.nome ASC LIMIT 300";

$stmt = $conn->prepare($sql);
if ($parametros !== []) {
    $stmt->bind_param($tipos, ...$parametros);
}
$stmt->execute();
$resultadoPresencas = $stmt->get_result();

$listaPresencas = [];
$sociosDistintos = [];
while ($presenca = $resultadoPresencas->fetch_assoc()) {
    $listaPresencas[] = $presenca;
    $sociosDistintos[$presenca['socio_id']] = true;
}

$totalPresencasHoje = (int) $conn->query(
    "SELECT COUNT(*) AS total FROM presencas WHERE data_presenca = CURDATE()"
)->fetch_assoc()['total'];

$titulo = 'Presenças';
$ativo = 'presencas';
include __DIR__ . '/includes/cabecalho.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1"><i class="fa-solid fa-check-to-slot text-success me-2"></i>Presenças</h1>
        <p class="text-muted mb-0"><?= $totalPresencasHoje ?> presença(s) registada(s) hoje</p>
    </div>
    <a href="aulas.php" class="btn btn-outline-success">
        <i class="fa-solid fa-calendar-days me-1"></i>Gerir Aulas
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0"><i class="fa-solid fa-user-check text-success me-2"></i>Registar presença</h2>
            </div>
            <div class="card-body">

                <?php if ($erros !== []): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($erros as $erro): ?>
                                <li><?= e($erro) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($listaSocios === [] || $listaAulas === []): ?>
                    <div class="alert alert-warning mb-0">
                        Para registar presenças é necessário ter pelo menos
                        <?= $listaSocios === [] ? '<a href="adicionar_socio.php" class="alert-link">um sócio</a>' : 'sócios' ?>
                        e
                        <?= $listaAulas === [] ? '<a href="aulas.php" class="alert-link">uma aula</a>' : 'aulas' ?>.
                    </div>
                <?php else: ?>
                    <form method="POST" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="acao" value="registar">

                        <div class="mb-3">
                            <label for="socio_id" class="form-label">Sócio <span class="text-danger">*</span></label>
                            <select class="form-select" id="socio_id" name="socio_id" required>
                                <option value="">— Escolher sócio —</option>
                                <?php foreach ($listaSocios as $socio): ?>
                                    <option value="<?= (int) $socio['id'] ?>"
                                        <?= (string) $form['socio_id'] === (string) $socio['id'] ? 'selected' : '' ?>>
                                        <?= e($socio['nome']) ?><?= $socio['plano'] ? ' (' . e($socio['plano']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="aula_id" class="form-label">Aula <span class="text-danger">*</span></label>
                            <select class="form-select" id="aula_id" name="aula_id" required>
                                <option value="">— Escolher aula —</option>
                                <?php foreach ($listaAulas as $aula): ?>
                                    <option value="<?= (int) $aula['id'] ?>"
                                        <?= (string) $form['aula_id'] === (string) $aula['id'] ? 'selected' : '' ?>>
                                        <?= e($aula['nome']) ?> — <?= e($aula['horario']) ?> (<?= (int) $aula['capacidade'] ?> vagas)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="data" class="form-label">Data <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="data" name="data"
                                   value="<?= e($form['data']) ?>" max="<?= date('Y-m-d') ?>" required>
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            <i class="fa-solid fa-check me-1"></i>Registar Presença
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="h5 mb-0"><i class="fa-solid fa-clipboard-list text-success me-2"></i>Listagem de presenças</h2>
                    <span class="badge bg-secondary">
                        <?= count($listaPresencas) ?> registo(s) &middot; <?= count($sociosDistintos) ?> sócio(s)
                    </span>
                </div>
            </div>

            <div class="card-body border-bottom">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-sm-5">
                        <label for="filtro-data" class="form-label small mb-1">Data</label>
                        <input type="date" class="form-control form-control-sm" id="filtro-data" name="data"
                               value="<?= e($dataFiltro) ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-sm-4">
                        <label for="filtro-aula" class="form-label small mb-1">Aula</label>
                        <select class="form-select form-select-sm" id="filtro-aula" name="aula">
                            <option value="">Todas as aulas</option>
                            <?php foreach ($listaAulas as $aula): ?>
                                <option value="<?= (int) $aula['id'] ?>" <?= $aulaFiltro === (int) $aula['id'] ? 'selected' : '' ?>>
                                    <?= e($aula['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-3 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-success flex-fill">
                            <i class="fa-solid fa-filter me-1"></i>Filtrar
                        </button>
                        <?php if ($dataFiltro !== date('Y-m-d') || $aulaFiltro !== null): ?>
                            <a href="presencas.php" class="btn btn-sm btn-outline-secondary" title="Hoje">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
                <?php if ($dataFiltro === ''): ?>
                    <div class="small text-muted mt-2 mb-0">
                        <i class="fa-solid fa-circle-info me-1"></i>A mostrar todas as datas.
                    </div>
                <?php endif; ?>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Sócio</th>
                                <th>Aula</th>
                                <th>Data</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($listaPresencas === []): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-5">
                                        <i class="fa-solid fa-calendar-xmark fa-2x d-block mb-2"></i>
                                        Nenhuma presença registada para os filtros escolhidos.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listaPresencas as $presenca): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="avatar"><?= e(iniciais($presenca['socio_nome'])) ?></span>
                                                <span class="fw-semibold"><?= e($presenca['socio_nome']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div><?= e($presenca['aula_nome']) ?></div>
                                            <div class="text-muted small"><?= e($presenca['horario']) ?></div>
                                        </td>
                                        <td class="small"><?= data_pt($presenca['data_presenca']) ?></td>
                                        <td class="text-end">
                                            <form method="POST" class="d-inline"
                                                  onsubmit="return confirm('Eliminar esta presença?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="acao" value="eliminar">
                                                <input type="hidden" name="id" value="<?= (int) $presenca['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar presença">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white small text-muted py-2">
                <i class="fa-solid fa-circle-info me-1"></i>
                A capacidade de cada aula é validada automaticamente. São mostrados até 300 registos.
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/rodape.php'; ?>
