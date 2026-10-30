<?php
require_once __DIR__ . '/includes/init.php';

$busca = texto($_GET['busca'] ?? null);
$planoFiltro = inteiro_positivo($_GET['plano'] ?? null);

$sql = "SELECT s.*, p.nome AS plano, p.preco AS plano_preco
        FROM socios s
        LEFT JOIN planos p ON s.plano_id = p.id
        WHERE 1 = 1";
$parametros = [];
$tipos = '';

if ($busca !== '') {
    $sql .= " AND (s.nome LIKE ? OR s.email LIKE ? OR s.telefone LIKE ?)";
    $termo = '%' . $busca . '%';
    $parametros[] = $termo;
    $parametros[] = $termo;
    $parametros[] = $termo;
    $tipos .= 'sss';
}

if ($planoFiltro !== null) {
    $sql .= " AND s.plano_id = ?";
    $parametros[] = $planoFiltro;
    $tipos .= 'i';
}

$sql .= " ORDER BY s.nome ASC";

$stmt = $conn->prepare($sql);
if ($parametros !== []) {
    $stmt->bind_param($tipos, ...$parametros);
}
$stmt->execute();
$resultado = $stmt->get_result();
$totalEncontrados = $resultado->num_rows;

$planosFiltro = $conn->query("SELECT id, nome FROM planos ORDER BY preco");

$titulo = 'Sócios';
$ativo = 'socios';
include __DIR__ . '/includes/cabecalho.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1"><i class="fa-solid fa-users text-success me-2"></i>Gestão de Sócios</h1>
        <p class="text-muted mb-0"><?= $totalEncontrados ?> sócio(s) encontrado(s)</p>
    </div>
    <a href="adicionar_socio.php" class="btn btn-success">
        <i class="fa-solid fa-user-plus me-1"></i>Novo Sócio
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label" for="busca">Pesquisar</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="search" class="form-control" id="busca" name="busca"
                           placeholder="Nome, email ou telefone..." value="<?= e($busca) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="plano">Plano</label>
                <select class="form-select" id="plano" name="plano">
                    <option value="">Todos os planos</option>
                    <?php while ($plano = $planosFiltro->fetch_assoc()): ?>
                        <option value="<?= (int) $plano['id'] ?>" <?= $planoFiltro === (int) $plano['id'] ? 'selected' : '' ?>>
                            <?= e($plano['nome']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-success flex-fill">
                    <i class="fa-solid fa-magnifying-glass me-1"></i>Filtrar
                </button>
                <?php if ($busca !== '' || $planoFiltro !== null): ?>
                    <a href="socios.php" class="btn btn-outline-secondary" title="Limpar filtros">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Sócio</th>
                        <th>Contacto</th>
                        <th>Plano</th>
                        <th>Registo</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($totalEncontrados === 0): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fa-regular fa-face-frown fa-2x d-block mb-2"></i>
                                Nenhum sócio encontrado com os filtros selecionados.
                                <?php if ($busca === '' && $planoFiltro === null): ?>
                                    <div class="mt-3">
                                        <a href="adicionar_socio.php" class="btn btn-sm btn-success">
                                            <i class="fa-solid fa-user-plus me-1"></i>Registar o primeiro sócio
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($socio = $resultado->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar"><?= e(iniciais($socio['nome'])) ?></span>
                                        <div>
                                            <div class="fw-semibold"><?= e($socio['nome']) ?></div>
                                            <div class="text-muted small"><?= e($socio['email'] ?: 'Sem email') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="small">
                                    <i class="fa-solid fa-phone text-muted me-1"></i><?= e($socio['telefone'] ?: '—') ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= e($socio['plano'] ?? 'Sem plano') ?></span>
                                    <?php if (!empty($socio['plano_preco'])): ?>
                                        <div class="text-muted small mt-1"><?= euros($socio['plano_preco']) ?>/mês</div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small"><?= data_pt($socio['data_registo']) ?></td>
                                <td class="text-end text-nowrap">
                                    <a href="editar_socio.php?id=<?= (int) $socio['id'] ?>"
                                       class="btn btn-sm btn-outline-warning" title="Editar sócio">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <form method="POST" action="eliminar_socio.php" class="d-inline"
                                          onsubmit="return confirm('Eliminar este sócio? Esta ação não pode ser revertida.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $socio['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar sócio">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/rodape.php'; ?>
