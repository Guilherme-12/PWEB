<?php
require_once __DIR__ . '/includes/init.php';

$erros = [];
$form = ['id' => 0, 'nome' => '', 'preco' => ''];

// Edição pedida por GET
$editarId = inteiro_positivo($_GET['editar'] ?? null);
if ($editarId !== null) {
    $stmt = $conn->prepare("SELECT id, nome, preco FROM planos WHERE id = ?");
    $stmt->bind_param('i', $editarId);
    $stmt->execute();
    $planoEditar = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($planoEditar) {
        $form = [
            'id' => (int) $planoEditar['id'],
            'nome' => $planoEditar['nome'],
            'preco' => number_format((float) $planoEditar['preco'], 2, '.', ''),
        ];
    } else {
        flash('warning', 'O plano indicado não existe.');
        redirecionar('planos.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $acao = texto($_POST['acao'] ?? '');

    if ($acao === 'eliminar') {
        $id = inteiro_positivo($_POST['id'] ?? null);
        if ($id === null) {
            flash('warning', 'Plano inválido.');
            redirecionar('planos.php');
        }

        $stmt = $conn->prepare("SELECT nome FROM planos WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $plano = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$plano) {
            flash('warning', 'O plano indicado já não existe.');
            redirecionar('planos.php');
        }

        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM socios WHERE plano_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $totalSocios = (int) $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        if ($totalSocios > 0) {
            flash('warning', 'Não é possível eliminar o plano "' . $plano['nome'] . '" porque tem ' . $totalSocios . ' sócio(s) associado(s).');
        } else {
            $stmt = $conn->prepare("DELETE FROM planos WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            flash('success', 'Plano eliminado com sucesso.');
        }

        redirecionar('planos.php');
    }

    if ($acao === 'guardar' || $acao === 'atualizar') {
        $id = $acao === 'atualizar' ? inteiro_positivo($_POST['id'] ?? null) : 0;
        $form['id'] = $id ?? 0;
        $form['nome'] = texto($_POST['nome'] ?? null);
        $form['preco'] = texto($_POST['preco'] ?? null);

        if ($acao === 'atualizar' && $id === null) {
            flash('warning', 'Plano inválido.');
            redirecionar('planos.php');
        }

        if ($form['nome'] === '') {
            $erros[] = 'O nome do plano é obrigatório.';
        } elseif (mb_strlen($form['nome']) > 50) {
            $erros[] = 'O nome não pode ter mais de 50 caracteres.';
        }

        $precoTexto = str_replace(',', '.', $form['preco']);
        if ($precoTexto === '' || !is_numeric($precoTexto)) {
            $erros[] = 'Indica um preço mensal válido.';
        } else {
            $preco = round((float) $precoTexto, 2);
            if ($preco <= 0) {
                $erros[] = 'O preço tem de ser superior a 0 €.';
            } elseif ($preco > 999999.99) {
                $erros[] = 'O preço indicado é demasiado elevado.';
            }
        }

        if ($erros === []) {
            $sql = "SELECT id FROM planos WHERE nome = ?" . ($acao === 'atualizar' ? " AND id <> ?" : "");
            $stmt = $conn->prepare($sql);
            if ($acao === 'atualizar') {
                $stmt->bind_param('si', $form['nome'], $id);
            } else {
                $stmt->bind_param('s', $form['nome']);
            }
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $erros[] = 'Já existe um plano com esse nome.';
            }
            $stmt->close();
        }

        if ($erros === []) {
            if ($acao === 'atualizar') {
                $stmt = $conn->prepare("UPDATE planos SET nome = ?, preco = ? WHERE id = ?");
                $stmt->bind_param('sdi', $form['nome'], $preco, $id);
                $stmt->execute();
                $stmt->close();
                flash('success', 'Plano atualizado com sucesso.');
            } else {
                $stmt = $conn->prepare("INSERT INTO planos (nome, preco) VALUES (?, ?)");
                $stmt->bind_param('sd', $form['nome'], $preco);
                $stmt->execute();
                $stmt->close();
                flash('success', 'Plano "' . $form['nome'] . '" criado com sucesso.');
            }
            redirecionar('planos.php');
        }
    }
}

$planos = $conn->query(
    "SELECT p.*, COUNT(s.id) AS total_socios
     FROM planos p
     LEFT JOIN socios s ON s.plano_id = p.id
     GROUP BY p.id, p.nome, p.preco
     ORDER BY p.preco"
);

$totalPlanos = 0;
$receitaPotencial = 0.0;
$listaPlanos = [];
while ($plano = $planos->fetch_assoc()) {
    $listaPlanos[] = $plano;
    $totalPlanos++;
    $receitaPotencial += (float) $plano['preco'] * (int) $plano['total_socios'];
}

$titulo = 'Planos';
$ativo = 'planos';
include __DIR__ . '/includes/cabecalho.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1"><i class="fa-solid fa-tags text-success me-2"></i>Planos de Inscrição</h1>
        <p class="text-muted mb-0"><?= $totalPlanos ?> plano(s) &middot; receita mensal potencial <?= euros($receitaPotencial) ?></p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">
                    <i class="fa-solid <?= $form['id'] > 0 ? 'fa-pen' : 'fa-plus' ?> text-success me-2"></i>
                    <?= $form['id'] > 0 ? 'Editar plano' : 'Novo plano' ?>
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
                        <label for="nome" class="form-label">Nome do plano <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nome" name="nome" maxlength="50"
                               value="<?= e($form['nome']) ?>" placeholder="Ex.: Premium" required>
                    </div>

                    <div class="mb-3">
                        <label for="preco" class="form-label">Preço mensal (€) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="preco" name="preco" min="0.01" step="0.01"
                                   value="<?= e($form['preco']) ?>" placeholder="40.00" required>
                            <span class="input-group-text">€</span>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success flex-fill">
                            <i class="fa-solid fa-floppy-disk me-1"></i>
                            <?= $form['id'] > 0 ? 'Atualizar plano' : 'Criar plano' ?>
                        </button>
                        <?php if ($form['id'] > 0): ?>
                            <a href="planos.php" class="btn btn-outline-secondary" title="Cancelar edição">
                                <i class="fa-solid fa-xmark"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0"><i class="fa-solid fa-list-check text-success me-2"></i>Planos existentes</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Plano</th>
                                <th>Preço</th>
                                <th>Sócios</th>
                                <th>Receita</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($listaPlanos === []): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="fa-solid fa-tags fa-2x d-block mb-2"></i>
                                        Ainda não existem planos. Cria o primeiro ao lado.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listaPlanos as $plano): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= e($plano['nome']) ?></td>
                                        <td><?= euros($plano['preco']) ?><span class="text-muted small">/mês</span></td>
                                        <td>
                                            <a href="socios.php?plano=<?= (int) $plano['id'] ?>" class="badge bg-secondary text-decoration-none"
                                               title="Ver sócios deste plano">
                                                <?= (int) $plano['total_socios'] ?>
                                            </a>
                                        </td>
                                        <td class="text-success fw-semibold">
                                            <?= euros((float) $plano['preco'] * (int) $plano['total_socios']) ?>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <a href="planos.php?editar=<?= (int) $plano['id'] ?>"
                                               class="btn btn-sm btn-outline-warning" title="Editar plano">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <form method="POST" class="d-inline"
                                                  onsubmit="return confirm('Eliminar este plano?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="acao" value="eliminar">
                                                <input type="hidden" name="id" value="<?= (int) $plano['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar plano">
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
                Só é possível eliminar planos sem sócios associados.
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/rodape.php'; ?>
