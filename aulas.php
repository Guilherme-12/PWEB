<?php
require_once __DIR__ . '/includes/init.php';

$erros = [];
$form = ['id' => 0, 'nome' => '', 'instrutor' => '', 'horario' => '', 'capacidade' => '20'];

$editarId = inteiro_positivo($_GET['editar'] ?? null);
if ($editarId !== null) {
    $stmt = $conn->prepare("SELECT * FROM aulas WHERE id = ?");
    $stmt->bind_param('i', $editarId);
    $stmt->execute();
    $aulaEditar = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($aulaEditar) {
        $form = [
            'id' => (int) $aulaEditar['id'],
            'nome' => $aulaEditar['nome'],
            'instrutor' => $aulaEditar['instrutor'],
            'horario' => $aulaEditar['horario'],
            'capacidade' => (string) $aulaEditar['capacidade'],
        ];
    } else {
        flash('warning', 'A aula indicada não existe.');
        redirecionar('aulas.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $acao = texto($_POST['acao'] ?? '');

    if ($acao === 'eliminar') {
        $id = inteiro_positivo($_POST['id'] ?? null);
        if ($id === null) {
            flash('warning', 'Aula inválida.');
            redirecionar('aulas.php');
        }

        $stmt = $conn->prepare("SELECT nome FROM aulas WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $aula = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$aula) {
            flash('warning', 'A aula indicada já não existe.');
            redirecionar('aulas.php');
        }

        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM presencas WHERE aula_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $totalPresencas = (int) $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        if ($totalPresencas > 0) {
            flash('warning', 'Não é possível eliminar a aula "' . $aula['nome'] . '" porque tem ' . $totalPresencas . ' presença(s) registada(s).');
        } else {
            $stmt = $conn->prepare("DELETE FROM aulas WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            flash('success', 'Aula eliminada com sucesso.');
        }

        redirecionar('aulas.php');
    }

    if ($acao === 'guardar' || $acao === 'atualizar') {
        $id = $acao === 'atualizar' ? inteiro_positivo($_POST['id'] ?? null) : 0;
        $form['id'] = $id ?? 0;
        $form['nome'] = texto($_POST['nome'] ?? null);
        $form['instrutor'] = texto($_POST['instrutor'] ?? null);
        $form['horario'] = texto($_POST['horario'] ?? null);
        $form['capacidade'] = texto($_POST['capacidade'] ?? null);

        if ($acao === 'atualizar' && $id === null) {
            flash('warning', 'Aula inválida.');
            redirecionar('aulas.php');
        }

        if ($form['nome'] === '') {
            $erros[] = 'O nome da aula é obrigatório.';
        } elseif (mb_strlen($form['nome']) > 100) {
            $erros[] = 'O nome não pode ter mais de 100 caracteres.';
        }

        if ($form['instrutor'] === '') {
            $erros[] = 'O nome do instrutor é obrigatório.';
        } elseif (mb_strlen($form['instrutor']) > 100) {
            $erros[] = 'O instrutor não pode ter mais de 100 caracteres.';
        }

        if ($form['horario'] === '') {
            $erros[] = 'Indica o horário da aula.';
        } elseif (mb_strlen($form['horario']) > 50) {
            $erros[] = 'O horário não pode ter mais de 50 caracteres.';
        }

        $capacidade = inteiro_positivo($form['capacidade']);
        if ($capacidade === null || $capacidade > 500) {
            $erros[] = 'A capacidade deve ser um número entre 1 e 500.';
        }

        if ($erros === []) {
            if ($acao === 'atualizar') {
                $stmt = $conn->prepare(
                    "UPDATE aulas SET nome = ?, instrutor = ?, horario = ?, capacidade = ? WHERE id = ?"
                );
                $stmt->bind_param('sssii', $form['nome'], $form['instrutor'], $form['horario'], $capacidade, $id);
                $stmt->execute();
                $stmt->close();
                flash('success', 'Aula atualizada com sucesso.');
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO aulas (nome, instrutor, horario, capacidade) VALUES (?, ?, ?, ?)"
                );
                $stmt->bind_param('sssi', $form['nome'], $form['instrutor'], $form['horario'], $capacidade);
                $stmt->execute();
                $stmt->close();
                flash('success', 'Aula "' . $form['nome'] . '" criada com sucesso.');
            }
            redirecionar('aulas.php');
        }
    }
}

$resultadoAulas = $conn->query(
    "SELECT a.*,
            (SELECT COUNT(*) FROM presencas pr WHERE pr.aula_id = a.id) AS total_presencas,
            (SELECT COUNT(*) FROM presencas pr WHERE pr.aula_id = a.id AND pr.data_presenca = CURDATE()) AS presencas_hoje
     FROM aulas a
     ORDER BY a.nome"
);

$listaAulas = [];
$totalAulas = 0;
$presencasHoje = 0;
while ($aula = $resultadoAulas->fetch_assoc()) {
    $listaAulas[] = $aula;
    $totalAulas++;
    $presencasHoje += (int) $aula['presencas_hoje'];
}

$titulo = 'Aulas';
$ativo = 'aulas';
include __DIR__ . '/includes/cabecalho.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1"><i class="fa-solid fa-calendar-days text-success me-2"></i>Gestão de Aulas</h1>
        <p class="text-muted mb-0"><?= $totalAulas ?> aula(s) &middot; <?= $presencasHoje ?> presença(s) hoje</p>
    </div>
    <a href="presencas.php" class="btn btn-outline-success">
        <i class="fa-solid fa-check-to-slot me-1"></i>Registar Presença
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">
                    <i class="fa-solid <?= $form['id'] > 0 ? 'fa-pen' : 'fa-plus' ?> text-success me-2"></i>
                    <?= $form['id'] > 0 ? 'Editar aula' : 'Nova aula' ?>
                </h2>
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

                <form method="POST" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="acao" value="<?= $form['id'] > 0 ? 'atualizar' : 'guardar' ?>">
                    <?php if ($form['id'] > 0): ?>
                        <input type="hidden" name="id" value="<?= (int) $form['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome da aula <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nome" name="nome" maxlength="100"
                               value="<?= e($form['nome']) ?>" placeholder="Ex.: Spinning" required>
                    </div>

                    <div class="mb-3">
                        <label for="instrutor" class="form-label">Instrutor <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="instrutor" name="instrutor" maxlength="100"
                               value="<?= e($form['instrutor']) ?>" placeholder="Ex.: Rui Almeida" required>
                    </div>

                    <div class="mb-3">
                        <label for="horario" class="form-label">Horário <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="horario" name="horario" maxlength="50"
                               value="<?= e($form['horario']) ?>" placeholder="Ex.: Ter/Qui 18:30" required>
                    </div>

                    <div class="mb-3">
                        <label for="capacidade" class="form-label">Capacidade <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="capacidade" name="capacidade" min="1" max="500"
                                   value="<?= e($form['capacidade']) ?>" required>
                            <span class="input-group-text">vagas</span>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success flex-fill">
                            <i class="fa-solid fa-floppy-disk me-1"></i>
                            <?= $form['id'] > 0 ? 'Atualizar aula' : 'Criar aula' ?>
                        </button>
                        <?php if ($form['id'] > 0): ?>
                            <a href="aulas.php" class="btn btn-outline-secondary" title="Cancelar edição">
                                <i class="fa-solid fa-xmark"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0"><i class="fa-solid fa-dumbbell text-success me-2"></i>Aulas disponíveis</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Aula</th>
                                <th>Horário</th>
                                <th>Hoje</th>
                                <th>Total</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($listaAulas === []): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="fa-solid fa-dumbbell fa-2x d-block mb-2"></i>
                                        Ainda não existem aulas. Cria a primeira ao lado.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listaAulas as $aula): ?>
                                    <?php
                                    $capacidade = max(1, (int) $aula['capacidade']);
                                    $hoje = (int) $aula['presencas_hoje'];
                                    $percentagem = min(100, (int) round($hoje / $capacidade * 100));
                                    $corBarra = $percentagem >= 90 ? 'bg-danger' : ($percentagem >= 70 ? 'bg-warning' : 'bg-success');
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= e($aula['nome']) ?></div>
                                            <div class="text-muted small">
                                                <i class="fa-solid fa-user-tie me-1"></i><?= e($aula['instrutor']) ?>
                                            </div>
                                        </td>
                                        <td class="small text-muted"><?= e($aula['horario']) ?></td>
                                        <td style="min-width: 120px;">
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span><?= $hoje ?>/<?= $capacidade ?></span>
                                                <span class="text-muted"><?= $percentagem ?>%</span>
                                            </div>
                                            <div class="progress" style="height: 6px;" role="progressbar"
                                                 aria-label="Ocupação de <?= e($aula['nome']) ?>"
                                                 aria-valuenow="<?= $hoje ?>" aria-valuemin="0" aria-valuemax="<?= $capacidade ?>">
                                                <div class="progress-bar <?= $corBarra ?>" style="width: <?= $percentagem ?>%;"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="presencas.php?aula=<?= (int) $aula['id'] ?>&amp;data="
                                               class="badge bg-secondary text-decoration-none" title="Ver presenças desta aula">
                                                <?= (int) $aula['total_presencas'] ?>
                                            </a>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <a href="aulas.php?editar=<?= (int) $aula['id'] ?>"
                                               class="btn btn-sm btn-outline-warning" title="Editar aula">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <form method="POST" class="d-inline"
                                                  onsubmit="return confirm('Eliminar esta aula?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="acao" value="eliminar">
                                                <input type="hidden" name="id" value="<?= (int) $aula['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar aula">
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
                Só é possível eliminar aulas sem presenças registadas.
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/rodape.php'; ?>
