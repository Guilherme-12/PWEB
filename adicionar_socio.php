<?php
require_once __DIR__ . '/includes/init.php';

$erros = [];
$dados = ['nome' => '', 'email' => '', 'telefone' => '', 'plano_id' => ''];

$planos = $conn->query("SELECT id, nome, preco FROM planos ORDER BY preco");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $dados['nome'] = texto($_POST['nome'] ?? null);
    $dados['email'] = texto($_POST['email'] ?? null);
    $dados['telefone'] = texto($_POST['telefone'] ?? null);
    $planoId = inteiro_positivo($_POST['plano_id'] ?? null);
    $dados['plano_id'] = $planoId ?? '';

    if ($dados['nome'] === '') {
        $erros[] = 'O nome é obrigatório.';
    } elseif (mb_strlen($dados['nome']) > 100) {
        $erros[] = 'O nome não pode ter mais de 100 caracteres.';
    }

    if ($dados['email'] !== '' && !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'O email indicado não é válido.';
    } elseif ($dados['email'] !== '') {
        $stmt = $conn->prepare("SELECT id FROM socios WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $dados['email']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $erros[] = 'Já existe um sócio com esse email.';
        }
        $stmt->close();
    }

    if ($dados['telefone'] !== '' && mb_strlen($dados['telefone']) > 20) {
        $erros[] = 'O telefone não pode ter mais de 20 caracteres.';
    }

    if ($planoId === null) {
        $erros[] = 'Escolhe um plano de inscrição.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM planos WHERE id = ?");
        $stmt->bind_param('i', $planoId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $erros[] = 'O plano selecionado não existe.';
        }
        $stmt->close();
    }

    if ($erros === []) {
        $email = $dados['email'] !== '' ? $dados['email'] : null;
        $telefone = $dados['telefone'] !== '' ? $dados['telefone'] : null;

        $stmt = $conn->prepare(
            "INSERT INTO socios (nome, email, telefone, plano_id, data_registo)
             VALUES (?, ?, ?, ?, CURDATE())"
        );
        $stmt->bind_param('sssi', $dados['nome'], $email, $telefone, $planoId);
        $stmt->execute();
        $stmt->close();

        flash('success', 'Sócio "' . $dados['nome'] . '" registado com sucesso.');
        redirecionar('socios.php');
    }
}

$titulo = 'Novo Sócio';
$ativo = 'socios';
include __DIR__ . '/includes/cabecalho.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1"><i class="fa-solid fa-user-plus text-success me-2"></i>Adicionar Sócio</h1>
        <p class="text-muted mb-0">Regista um novo membro no ginásio</p>
    </div>
    <a href="socios.php" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Voltar à lista
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0"><i class="fa-solid fa-id-card text-success me-2"></i>Dados do sócio</h2>
            </div>
            <div class="card-body">

                <?php if ($erros !== []): ?>
                    <div class="alert alert-danger">
                        <div class="fw-semibold mb-1"><i class="fa-solid fa-circle-exclamation me-1"></i>Corrige os seguintes pontos:</div>
                        <ul class="mb-0">
                            <?php foreach ($erros as $erro): ?>
                                <li><?= e($erro) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($planos->num_rows === 0): ?>
                    <div class="alert alert-warning">
                        Ainda não existem planos. <a href="planos.php" class="alert-link">Cria um plano</a> antes de registar sócios.
                    </div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="nome" class="form-label">Nome completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nome" name="nome" maxlength="100"
                                   value="<?= e($dados['nome']) ?>" required autofocus>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" maxlength="100"
                                   value="<?= e($dados['email']) ?>" placeholder="nome@exemplo.pt">
                        </div>

                        <div class="col-md-6">
                            <label for="telefone" class="form-label">Telefone</label>
                            <input type="tel" class="form-control" id="telefone" name="telefone" maxlength="20"
                                   value="<?= e($dados['telefone']) ?>" placeholder="912 345 678">
                        </div>

                        <div class="col-12">
                            <label for="plano_id" class="form-label">Plano de inscrição <span class="text-danger">*</span></label>
                            <select class="form-select" id="plano_id" name="plano_id" required>
                                <option value="">— Escolher plano —</option>
                                <?php while ($plano = $planos->fetch_assoc()): ?>
                                    <option value="<?= (int) $plano['id'] ?>"
                                        <?= (string) $dados['plano_id'] === (string) $plano['id'] ? 'selected' : '' ?>>
                                        <?= e($plano['nome']) ?> — <?= euros($plano['preco']) ?>/mês
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-light border mb-0 small text-muted">
                                <i class="fa-regular fa-calendar-check me-1"></i>
                                A data de registo é preenchida automaticamente com a data de hoje.
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between">
                        <a href="socios.php" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-xmark me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-success" <?= $planos->num_rows === 0 ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-floppy-disk me-1"></i>Guardar Sócio
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/rodape.php'; ?>
