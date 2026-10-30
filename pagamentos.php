<?php
require_once __DIR__ . '/includes/init.php';

$erros = [];
$form = [
    'socio_id' => '',
    'valor' => '',
    'data_pagamento' => date('Y-m-d'),
    'estado' => 'Pago',
];

// ---------------------------------------------------------
// Ações (registar / marcar como pago / eliminar)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $acao = texto($_POST['acao'] ?? '');

    if ($acao === 'marcar_pago') {
        $id = inteiro_positivo($_POST['id'] ?? null);
        if ($id === null) {
            flash('warning', 'Pagamento inválido.');
            redirecionar('pagamentos.php');
        }

        $stmt = $conn->prepare("UPDATE pagamentos SET estado = 'Pago' WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $alterados = $stmt->affected_rows;
        $stmt->close();

        flash($alterados > 0 ? 'success' : 'info',
            $alterados > 0 ? 'Pagamento marcado como pago.' : 'O pagamento já se encontrava como pago.');
        redirecionar('pagamentos.php');
    }

    if ($acao === 'eliminar') {
        $id = inteiro_positivo($_POST['id'] ?? null);
        if ($id === null) {
            flash('warning', 'Pagamento inválido.');
            redirecionar('pagamentos.php');
        }

        $stmt = $conn->prepare("DELETE FROM pagamentos WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $eliminados = $stmt->affected_rows;
        $stmt->close();

        flash($eliminados > 0 ? 'success' : 'warning',
            $eliminados > 0 ? 'Pagamento eliminado com sucesso.' : 'O pagamento indicado já não existia.');
        redirecionar('pagamentos.php');
    }

    if ($acao === 'registar') {
        $socioId = inteiro_positivo($_POST['socio_id'] ?? null);
        $valorTexto = texto($_POST['valor'] ?? null);
        $data = texto($_POST['data_pagamento'] ?? null);
        $estado = texto($_POST['estado'] ?? null);
        if ($estado === '') {
            $estado = 'Pago';
        }

        $form = [
            'socio_id' => $socioId ?? '',
            'valor' => $valorTexto,
            'data_pagamento' => $data,
            'estado' => $estado,
        ];

        $socio = null;
        if ($socioId === null) {
            $erros[] = 'Escolhe o sócio que efetuou o pagamento.';
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

        $valorNumero = str_replace(',', '.', $valorTexto);
        if ($valorNumero === '' || !is_numeric($valorNumero)) {
            $erros[] = 'Indica um valor válido.';
        } else {
            $valor = round((float) $valorNumero, 2);
            if ($valor <= 0) {
                $erros[] = 'O valor tem de ser superior a 0 €.';
            } elseif ($valor > 999999.99) {
                $erros[] = 'O valor indicado é demasiado elevado.';
            }
        }

        if (!data_valida($data)) {
            $erros[] = 'Indica uma data de pagamento válida.';
        } elseif ($data > date('Y-m-d')) {
            $erros[] = 'A data do pagamento não pode estar no futuro.';
        }

        if (!in_array($estado, ['Pago', 'Pendente'], true)) {
            $erros[] = 'Estado de pagamento inválido.';
        }

        if ($erros === []) {
            $stmt = $conn->prepare(
                "INSERT INTO pagamentos (socio_id, valor, data_pagamento, estado) VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param('idss', $socioId, $valor, $data, $estado);
            $stmt->execute();
            $stmt->close();

            flash('success', 'Pagamento de ' . euros($valor) . ' de ' . $socio['nome'] . ' registado como ' . $estado . '.');
            redirecionar('pagamentos.php');
        }
    }
}

// ---------------------------------------------------------
// Dados para o formulário
// ---------------------------------------------------------
$listaSocios = [];
$resultadoSocios = $conn->query(
    "SELECT s.id, s.nome, p.nome AS plano, p.preco
     FROM socios s
     LEFT JOIN planos p ON s.plano_id = p.id
     ORDER BY s.nome"
);
while ($socio = $resultadoSocios->fetch_assoc()) {
    $listaSocios[] = $socio;
}

// ---------------------------------------------------------
// Filtros
// ---------------------------------------------------------
$busca = texto($_GET['busca'] ?? null);
$estadoPedido = texto($_GET['estado'] ?? null);
$estadoFiltro = in_array($estadoPedido, ['Pago', 'Pendente'], true) ? $estadoPedido : '';
$meses = meses_recentes(12);
$mesPedido = texto($_GET['mes'] ?? null);
$mesFiltro = $mesPedido !== '' && isset($meses[$mesPedido]) ? $mesPedido : '';

$sql = "SELECT pg.*, s.nome AS socio_nome
        FROM pagamentos pg
        JOIN socios s ON pg.socio_id = s.id
        WHERE 1 = 1";
$parametros = [];
$tipos = '';

if ($busca !== '') {
    $sql .= " AND s.nome LIKE ?";
    $parametros[] = '%' . $busca . '%';
    $tipos .= 's';
}

if ($estadoFiltro !== '') {
    $sql .= " AND pg.estado = ?";
    $parametros[] = $estadoFiltro;
    $tipos .= 's';
}

if ($mesFiltro !== '') {
    $sql .= " AND DATE_FORMAT(pg.data_pagamento, '%Y-%m') = ?";
    $parametros[] = $mesFiltro;
    $tipos .= 's';
}

$sql .= " ORDER BY pg.data_pagamento DESC, pg.id DESC LIMIT 300";

$stmt = $conn->prepare($sql);
if ($parametros !== []) {
    $stmt->bind_param($tipos, ...$parametros);
}
$stmt->execute();
$resultadoPagamentos = $stmt->get_result();

$listaPagamentos = [];
$totalRecebido = 0.0;
$totalPendente = 0.0;
while ($pagamento = $resultadoPagamentos->fetch_assoc()) {
    $listaPagamentos[] = $pagamento;
    if ($pagamento['estado'] === 'Pago') {
        $totalRecebido += (float) $pagamento['valor'];
    } else {
        $totalPendente += (float) $pagamento['valor'];
    }
}

$titulo = 'Pagamentos';
$ativo = 'pagamentos';
include __DIR__ . '/includes/cabecalho.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1"><i class="fa-solid fa-euro-sign text-success me-2"></i>Pagamentos</h1>
        <p class="text-muted mb-0">Registo e acompanhamento das mensalidades</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm h-100 border-start border-4 border-success">
            <div class="card-body">
                <p class="text-muted text-uppercase small fw-semibold mb-1">Recebido (filtro)</p>
                <p class="h3 mb-0 text-success"><?= euros($totalRecebido) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100 border-start border-4 border-warning">
            <div class="card-body">
                <p class="text-muted text-uppercase small fw-semibold mb-1">Pendente (filtro)</p>
                <p class="h3 mb-0 text-warning-emphasis"><?= euros($totalPendente) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100 border-start border-4 border-primary">
            <div class="card-body">
                <p class="text-muted text-uppercase small fw-semibold mb-1">Registos (filtro)</p>
                <p class="h3 mb-0"><?= count($listaPagamentos) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0"><i class="fa-solid fa-plus text-success me-2"></i>Novo pagamento</h2>
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

                <?php if ($listaSocios === []): ?>
                    <div class="alert alert-warning mb-0">
                        Ainda não existem sócios. <a href="adicionar_socio.php" class="alert-link">Regista um sócio</a>
                        antes de lançar pagamentos.
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
                                            data-preco="<?= e($socio['preco'] ?? '') ?>"
                                        <?= (string) $form['socio_id'] === (string) $socio['id'] ? 'selected' : '' ?>>
                                        <?= e($socio['nome']) ?><?= $socio['plano'] ? ' (' . e($socio['plano']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">O valor é sugerido automaticamente com base no plano.</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-7">
                                <label for="valor" class="form-label">Valor (€) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="valor" name="valor" min="0.01" step="0.01"
                                       value="<?= e($form['valor']) ?>" placeholder="40.00" required>
                            </div>
                            <div class="col-5">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="Pago" <?= $form['estado'] === 'Pago' ? 'selected' : '' ?>>Pago</option>
                                    <option value="Pendente" <?= $form['estado'] === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label for="data_pagamento" class="form-label">Data do pagamento <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="data_pagamento" name="data_pagamento"
                                   value="<?= e($form['data_pagamento']) ?>" max="<?= date('Y-m-d') ?>" required>
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            <i class="fa-solid fa-floppy-disk me-1"></i>Registar Pagamento
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0"><i class="fa-solid fa-receipt text-success me-2"></i>Histórico de pagamentos</h2>
            </div>

            <div class="card-body border-bottom">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-sm-4">
                        <label for="busca" class="form-label small mb-1">Sócio</label>
                        <input type="search" class="form-control form-control-sm" id="busca" name="busca"
                               value="<?= e($busca) ?>" placeholder="Nome do sócio...">
                    </div>
                    <div class="col-sm-3">
                        <label for="estado-filtro" class="form-label small mb-1">Estado</label>
                        <select class="form-select form-select-sm" id="estado-filtro" name="estado">
                            <option value="">Todos</option>
                            <option value="Pago" <?= $estadoFiltro === 'Pago' ? 'selected' : '' ?>>Pago</option>
                            <option value="Pendente" <?= $estadoFiltro === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label for="mes" class="form-label small mb-1">Mês</label>
                        <select class="form-select form-select-sm" id="mes" name="mes">
                            <option value="">Todos</option>
                            <?php foreach ($meses as $chave => $rotulo): ?>
                                <option value="<?= e($chave) ?>" <?= $mesFiltro === $chave ? 'selected' : '' ?>>
                                    <?= e($rotulo) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-success flex-fill" title="Filtrar">
                            <i class="fa-solid fa-filter"></i>
                        </button>
                        <?php if ($busca !== '' || $estadoFiltro !== '' || $mesFiltro !== ''): ?>
                            <a href="pagamentos.php" class="btn btn-sm btn-outline-secondary" title="Limpar filtros">
                                <i class="fa-solid fa-xmark"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
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
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($listaPagamentos === []): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="fa-solid fa-rectangle-list fa-2x d-block mb-2"></i>
                                        Nenhum pagamento encontrado para os filtros escolhidos.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listaPagamentos as $pagamento): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="avatar"><?= e(iniciais($pagamento['socio_nome'])) ?></span>
                                                <span class="fw-semibold"><?= e($pagamento['socio_nome']) ?></span>
                                            </div>
                                        </td>
                                        <td class="fw-bold text-success"><?= euros($pagamento['valor']) ?></td>
                                        <td class="text-muted small"><?= data_pt($pagamento['data_pagamento']) ?></td>
                                        <td>
                                            <span class="badge <?= badge_pagamento($pagamento['estado']) ?>">
                                                <?= e($pagamento['estado']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <?php if ($pagamento['estado'] !== 'Pago'): ?>
                                                <form method="POST" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="acao" value="marcar_pago">
                                                    <input type="hidden" name="id" value="<?= (int) $pagamento['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Marcar como pago">
                                                        <i class="fa-solid fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" class="d-inline"
                                                  onsubmit="return confirm('Eliminar este pagamento?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="acao" value="eliminar">
                                                <input type="hidden" name="id" value="<?= (int) $pagamento['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar pagamento">
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
                Os totais apresentados referem-se aos filtros ativos. São mostrados até 300 registos.
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const socioSelect = document.getElementById('socio_id');
        const valorInput = document.getElementById('valor');
        if (!socioSelect || !valorInput) return;

        socioSelect.addEventListener('change', function () {
            const opcao = this.options[this.selectedIndex];
            const preco = opcao ? opcao.dataset.preco : '';
            if (preco) {
                valorInput.value = Number(preco).toFixed(2);
            }
        });
    })();
</script>

<?php include __DIR__ . '/includes/rodape.php'; ?>
