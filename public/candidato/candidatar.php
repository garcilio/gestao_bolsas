<?php
require_once __DIR__ . '/../../config/db.php';
check_auth('candidato');

$bolsa_id = (int)($_GET['bolsa_id'] ?? $_POST['bolsa_id'] ?? 0);
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM bolsas WHERE id = ?");
$stmt->execute([$bolsa_id]);
$bolsa = $stmt->fetch();

if (!$bolsa) {
    die("Bolsa não encontrada.");
}

if (strtotime($bolsa['prazo']) < strtotime(date('Y-m-d'))) {
    die("O prazo de candidatura para esta bolsa já expirou.");
}

$chk = $pdo->prepare("SELECT id FROM candidaturas WHERE user_id = ? AND bolsa_id = ?");
$chk->execute([$user_id, $bolsa_id]);
if ($chk->fetch()) {
    die("Já submeteu uma candidatura para esta bolsa.");
}

$tecnicos = $pdo->query("SELECT id FROM users WHERE role = 'tecnico'")->fetchAll(PDO::FETCH_COLUMN);
$tecnico_atribuido = !empty($tecnicos) ? $tecnicos[array_rand($tecnicos)] : null;

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $instituicao = trim($_POST['instituicao'] ?? '');
    $curso = trim($_POST['curso'] ?? '');
    $rendimento = floatval($_POST['rendimento'] ?? 0);
    $iban = strtoupper(trim($_POST['iban'] ?? ''));

    if (empty($instituicao) || empty($curso) || empty($iban) || $rendimento < 0) {
        $erro = "Preencha todos os campos obrigatórios.";
    } elseif (!isset($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
        $erro = "Anexe um documento comprovativo em PDF.";
    } else {
        $file = $_FILES['documento'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);

        if ($mime !== 'application/pdf') {
            $erro = "Apenas ficheiros PDF são autorizados.";
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $erro = "O ficheiro excede 5MB.";
        } else {
            $pdo->beginTransaction();
            try {
                $ins = $pdo->prepare("INSERT INTO candidaturas (user_id, bolsa_id, tecnico_id, instituicao, curso, rendimento, iban, estado) VALUES (?, ?, ?, ?, ?, ?, ?, 'submetida')");
                $ins->execute([$user_id, $bolsa_id, $tecnico_atribuido, $instituicao, $curso, $rendimento, $iban]);
                $candidatura_id = $pdo->lastInsertId();

                $nome_aleatorio = bin2hex(random_bytes(16)) . '.pdf';
                $caminho_destino = __DIR__ . '/../../storage/' . $nome_aleatorio;

                if (!move_uploaded_file($file['tmp_name'], $caminho_destino)) {
                    throw new Exception("Falha ao gravar o ficheiro no servidor.");
                }

                $doc_ins = $pdo->prepare("INSERT INTO documentos (candidatura_id, tipo_documento, nome_ficheiro, nome_original) VALUES (?, ?, ?, ?)");
                $doc_ins->execute([$candidatura_id, 'Comprovativo Inicial', $nome_aleatorio, $file['name']]);

                $pdo->commit();
                header("Location: dashboard.php");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $erro = "Erro ao submeter: " . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width: 650px; margin: auto;">
    <h2>Candidatura: <?= sanitize($bolsa['designacao']) ?></h2>
    <?php if ($erro): ?><div class="alert alert-error"><?= sanitize($erro) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="bolsa_id" value="<?= $bolsa['id'] ?>">

        <label>Instituição de Ensino *:</label>
        <input type="text" name="instituicao" required value="<?= sanitize($_POST['instituicao'] ?? '') ?>">

        <label>Curso *:</label>
        <input type="text" name="curso" required value="<?= sanitize($_POST['curso'] ?? '') ?>">

        <div class="form-row">
            <div class="form-col">
                <label>Rendimento Anual Familiar (€) *:</label>
                <input type="number" step="0.01" name="rendimento" required value="<?= sanitize($_POST['rendimento'] ?? '') ?>">
            </div>
            <div class="form-col">
                <label>IBAN *:</label>
                <input type="text" name="iban" required value="<?= sanitize($_POST['iban'] ?? '') ?>">
            </div>
        </div>

        <label>Documento Comprovativo (PDF, máx. 5MB) *:</label>
        <input type="file" name="documento" accept="application/pdf" required>

        <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Submeter Candidatura</button>
        <a href="/index.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
