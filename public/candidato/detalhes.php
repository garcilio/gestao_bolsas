<?php
require_once __DIR__ . '/../../config/db.php';
check_auth('candidato');

$id = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT c.*, b.designacao, b.montante 
    FROM candidaturas c
    JOIN bolsas b ON c.bolsa_id = b.id
    WHERE c.id = ? AND c.user_id = ?
");
$stmt->execute([$id, $user_id]);
$cand = $stmt->fetch();

if (!$cand) {
    die("Candidatura não encontrada ou sem permissão de acesso.");
}

$erro = '';
$sucesso = '';

if (isset($_POST['acao']) && $_POST['acao'] === 'cancelar') {
    verify_csrf();
    if ($cand['estado'] !== 'submetida') {
        $erro = "Não pode cancelar uma candidatura que já entrou em análise.";
    } else {
        $del = $pdo->prepare("DELETE FROM candidaturas WHERE id = ?");
        $del->execute([$id]);
        header("Location: dashboard.php");
        exit;
    }
}

if (isset($_POST['acao']) && $_POST['acao'] === 'editar') {
    verify_csrf();
    if ($cand['estado'] !== 'submetida') {
        $erro = "Não pode editar uma candidatura já em análise.";
    } else {
        $inst = trim($_POST['instituicao'] ?? '');
        $curs = trim($_POST['curso'] ?? '');
        $rend = floatval($_POST['rendimento'] ?? 0);
        $iban = strtoupper(trim($_POST['iban'] ?? ''));

        $up = $pdo->prepare("UPDATE candidaturas SET instituicao = ?, curso = ?, rendimento = ?, iban = ? WHERE id = ?");
        $up->execute([$inst, $curs, $rend, $iban, $id]);
        $sucesso = "Dados atualizados com sucesso.";
        $stmt->execute([$id, $user_id]);
        $cand = $stmt->fetch();
    }
}

if (isset($_POST['acao']) && $_POST['acao'] === 'responder_esclarecimento') {
    verify_csrf();
    $msg = trim($_POST['mensagem'] ?? '');
    if (empty($msg)) {
        $erro = "A mensagem não pode estar vazia.";
    } else {
        $pdo->beginTransaction();
        try {
            $escl = $pdo->prepare("INSERT INTO esclarecimentos (candidatura_id, remetente_id, mensagem) VALUES (?, ?, ?)");
            $escl->execute([$id, $user_id, $msg]);

            if (isset($_FILES['documento_extra']) && $_FILES['documento_extra']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['documento_extra'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);

                if ($mime === 'application/pdf') {
                    $nome_aleatorio = bin2hex(random_bytes(16)) . '.pdf';
                    $destino = __DIR__ . '/../../storage/' . $nome_aleatorio;
                    if (move_uploaded_file($file['tmp_name'], $destino)) {
                        $dins = $pdo->prepare("INSERT INTO documentos (candidatura_id, tipo_documento, nome_ficheiro, nome_original) VALUES (?, ?, ?, ?)");
                        $dins->execute([$id, 'Documento Adicional', $nome_aleatorio, $file['name']]);
                    }
                }
            }

            $pdo->commit();
            $sucesso = "Resposta e esclarecimento enviados com sucesso.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro: " . $e->getMessage();
        }
    }
}

$docs = $pdo->prepare("SELECT * FROM documentos WHERE candidatura_id = ?");
$docs->execute([$id]);
$documentos = $docs->fetchAll();

$msgs = $pdo->prepare("
    SELECT e.*, u.nome as remetente_nome, u.role as remetente_role
    FROM esclarecimentos e
    JOIN users u ON e.remetente_id = u.id
    WHERE e.candidatura_id = ?
    ORDER BY e.created_at ASC
");
$msgs->execute([$id]);
$esclarecimentos = $msgs->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Candidatura: <?= sanitize($cand['designacao']) ?></h2>
        <span class="badge badge-<?= str_replace(' ', '-', $cand['estado']) ?>"><?= sanitize($cand['estado']) ?></span>
    </div>

    <?php if ($erro): ?><div class="alert alert-error"><?= sanitize($erro) ?></div><?php endif; ?>
    <?php if ($sucesso): ?><div class="alert alert-success"><?= sanitize($sucesso) ?></div><?php endif; ?>

    <?php if ($cand['estado'] === 'submetida'): ?>
        <p><em>Pode editar ou cancelar esta candidatura porque ainda não entrou em análise.</em></p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="editar">
            <div class="form-row">
                <div class="form-col">
                    <label>Instituição:</label>
                    <input type="text" name="instituicao" value="<?= sanitize($cand['instituicao']) ?>" required>
                </div>
                <div class="form-col">
                    <label>Curso:</label>
                    <input type="text" name="curso" value="<?= sanitize($cand['curso']) ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-col">
                    <label>Rendimento Anual (€):</label>
                    <input type="number" step="0.01" name="rendimento" value="<?= sanitize($cand['rendimento']) ?>" required>
                </div>
                <div class="form-col">
                    <label>IBAN:</label>
                    <input type="text" name="iban" value="<?= sanitize($cand['iban']) ?>" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Guardar Alterações</button>
        </form>

        <form method="POST" style="margin-top: 1rem;" onsubmit="return confirm('Tem a certeza que quer cancelar?');">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="cancelar">
            <button type="submit" class="btn btn-danger">Cancelar Candidatura</button>
        </form>
    <?php else: ?>
        <table>
            <tr><th>Instituição:</th><td><?= sanitize($cand['instituicao']) ?></td></tr>
            <tr><th>Curso:</th><td><?= sanitize($cand['curso']) ?></td></tr>
            <tr><th>Rendimento Anual:</th><td><?= number_format($cand['rendimento'], 2, ',', '.') ?> €</td></tr>
            <tr><th>IBAN:</th><td><?= sanitize($cand['iban']) ?></td></tr>
            <tr><th>Submissão:</th><td><?= date('d/m/Y H:i', strtotime($cand['created_at'])) ?></td></tr>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Documentos Anexados</h3>
    <table>
        <thead>
            <tr><th>Tipo</th><th>Nome Original</th><th>Data</th><th>Ação</th></tr>
        </thead>
        <tbody>
            <?php foreach ($documentos as $d): ?>
            <tr>
                <td><?= sanitize($d['tipo_documento']) ?></td>
                <td><?= sanitize($d['nome_original']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($d['data_upload'])) ?></td>
                <td><a href="/download.php?id=<?= $d['id'] ?>" target="_blank" class="btn btn-secondary">Ver PDF</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h3>Esclarecimentos e Mensagens</h3>
    <?php if (empty($esclarecimentos)): ?>
        <p>Não existem pedidos de esclarecimento.</p>
    <?php else: ?>
        <?php foreach ($esclarecimentos as $m): ?>
            <div style="border-left: 4px solid <?= $m['remetente_role'] === 'tecnico' ? '#0056b3' : '#28a745' ?>; padding-left: 1rem; margin-bottom: 1rem;">
                <p><strong><?= sanitize($m['remetente_nome']) ?></strong> em <em><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></em>:</p>
                <p><?= nl2br(sanitize($m['mensagem'])) ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h4 style="margin-top: 1.5rem;">Responder ao Técnico:</h4>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="acao" value="responder_esclarecimento">
        <textarea name="mensagem" rows="3" required placeholder="Escreva aqui a sua resposta..."></textarea>
        
        <label>Anexar Documento Solicitado (PDF):</label>
        <input type="file" name="documento_extra" accept="application/pdf">

        <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;">Enviar Mensagem</button>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
