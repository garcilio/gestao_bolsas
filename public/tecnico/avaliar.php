<?php
require_once __DIR__ . '/../../config/db.php';
check_auth('tecnico');

$id = (int)($_GET['id'] ?? 0);
$tecnico_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT c.*, b.designacao as bolsa_nome, b.montante as bolsa_montante,
           u.nome as cand_nome, u.email as cand_email, u.telefone as cand_telefone, u.nif as cand_nif, u.morada as cand_morada
    FROM candidaturas c
    JOIN bolsas b ON c.bolsa_id = b.id
    JOIN users u ON c.user_id = u.id
    WHERE c.id = ? AND c.tecnico_id = ?
");
$stmt->execute([$id, $tecnico_id]);
$cand = $stmt->fetch();

if (!$cand) {
    die("Candidatura não encontrada ou não atribuída a si.");
}

$erro = '';
$sucesso = '';

if (isset($_POST['acao']) && $_POST['acao'] === 'atualizar_estado') {
    verify_csrf();
    $novo_estado = $_POST['novo_estado'] ?? '';
    $estados_validos = ['em analise', 'aprovada', 'rejeitada', 'aguardar documentacao'];

    if (!in_array($novo_estado, $estados_validos, true)) {
        $erro = "Estado inválido.";
    } elseif ($novo_estado === $cand['estado']) {
        $erro = "O estado selecionado é igual ao atual.";
    } else {
        $pdo->beginTransaction();
        try {
            $estado_anterior = $cand['estado'];
            
            $up = $pdo->prepare("UPDATE candidaturas SET estado = ? WHERE id = ?");
            $up->execute([$novo_estado, $id]);

            $aud = $pdo->prepare("INSERT INTO historico_estados (candidatura_id, tecnico_id, estado_anterior, estado_novo) VALUES (?, ?, ?, ?)");
            $aud->execute([$id, $tecnico_id, $estado_anterior, $novo_estado]);

            $pdo->commit();
            $sucesso = "Estado atualizado com registo no histórico.";
            $cand['estado'] = $novo_estado;
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro: " . $e->getMessage();
        }
    }
}

if (isset($_POST['acao']) && $_POST['acao'] === 'guardar_observacoes') {
    verify_csrf();
    $obs = trim($_POST['observacoes_internas'] ?? '');
    $up = $pdo->prepare("UPDATE candidaturas SET observacoes_internas = ? WHERE id = ?");
    $up->execute([$obs, $id]);
    $cand['observacoes_internas'] = $obs;
    $sucesso = "Observações internas gravadas.";
}

if (isset($_POST['acao']) && $_POST['acao'] === 'enviar_esclarecimento') {
    verify_csrf();
    $msg = trim($_POST['mensagem'] ?? '');
    if (empty($msg)) {
        $erro = "Mensagem não pode ser vazia.";
    } else {
        $ins = $pdo->prepare("INSERT INTO esclarecimentos (candidatura_id, remetente_id, mensagem) VALUES (?, ?, ?)");
        $ins->execute([$id, $tecnico_id, $msg]);
        $sucesso = "Pedido de esclarecimento enviado.";
    }
}

$docs = $pdo->prepare("SELECT * FROM documentos WHERE candidatura_id = ?");
$docs->execute([$id]);
$documentos = $docs->fetchAll();

$hist = $pdo->prepare("
    SELECT h.*, u.nome as tecnico_nome
    FROM historico_estados h
    JOIN users u ON h.tecnico_id = u.id
    WHERE h.candidatura_id = ?
    ORDER BY h.data_alteracao DESC
");
$hist->execute([$id]);
$historico = $hist->fetchAll();

$escl = $pdo->prepare("
    SELECT e.*, u.nome as remetente_nome, u.role as remetente_role
    FROM esclarecimentos e
    JOIN users u ON e.remetente_id = u.id
    WHERE e.candidatura_id = ?
    ORDER BY e.created_at ASC
");
$escl->execute([$id]);
$mensagens = $escl->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Avaliação: Candidatura #<?= $cand['id'] ?> (<?= sanitize($cand['bolsa_nome']) ?>)</h2>
        <span class="badge badge-<?= str_replace(' ', '-', $cand['estado']) ?>"><?= sanitize($cand['estado']) ?></span>
    </div>

    <?php if ($erro): ?><div class="alert alert-error"><?= sanitize($erro) ?></div><?php endif; ?>
    <?php if ($sucesso): ?><div class="alert alert-success"><?= sanitize($sucesso) ?></div><?php endif; ?>

    <div class="form-row" style="margin-top: 1rem;">
        <div class="form-col">
            <h4>Dados do Candidato</h4>
            <p><strong>Nome:</strong> <?= sanitize($cand['cand_nome']) ?><br>
            <strong>Email:</strong> <?= sanitize($cand['cand_email']) ?><br>
            <strong>Telefone:</strong> <?= sanitize($cand['cand_telefone'] ?: '-') ?><br>
            <strong>NIF:</strong> <?= sanitize($cand['cand_nif'] ?: '-') ?><br>
            <strong>Morada:</strong> <?= sanitize($cand['cand_morada'] ?: '-') ?></p>
        </div>
        <div class="form-col">
            <h4>Dados da Candidatura</h4>
            <p><strong>Instituição:</strong> <?= sanitize($cand['instituicao']) ?><br>
            <strong>Curso:</strong> <?= sanitize($cand['curso']) ?><br>
            <strong>Rendimento:</strong> <?= number_format($cand['rendimento'], 2, ',', '.') ?> €<br>
            <strong>IBAN:</strong> <?= sanitize($cand['iban']) ?><br>
            <strong>Submissão:</strong> <?= date('d/m/Y H:i', strtotime($cand['created_at'])) ?></p>
        </div>
    </div>
</div>

<div class="card">
    <h3>Atualizar Estado da Candidatura</h3>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="acao" value="atualizar_estado">
        
        <label>Novo Estado:</label>
        <select name="novo_estado" required>
            <option value="em analise" <?= $cand['estado'] === 'em analise' ? 'selected' : '' ?>>Em Análise</option>
            <option value="aguardar documentacao" <?= $cand['estado'] === 'aguardar documentacao' ? 'selected' : '' ?>>Aguardar Documentação</option>
            <option value="aprovada" <?= $cand['estado'] === 'aprovada' ? 'selected' : '' ?>>Aprovada</option>
            <option value="rejeitada" <?= $cand['estado'] === 'rejeitada' ? 'selected' : '' ?>>Rejeitada</option>
        </select>
        
        <button type="submit" class="btn btn-primary">Registar Alteração</button>
    </form>
</div>

<div class="card">
    <h3>Observações Internas (Confidenciais)</h3>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="acao" value="guardar_observacoes">
        
        <textarea name="observacoes_internas" rows="4"><?= sanitize($cand['observacoes_internas']) ?></textarea>
        
        <button type="submit" class="btn btn-primary">Guardar Observações</button>
    </form>
</div>

<div class="card">
    <h3>Documentos</h3>
    <table>
        <thead><tr><th>Tipo</th><th>Nome</th><th>Data</th><th>Ação</th></tr></thead>
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
    <h3>Histórico de Alterações de Estado (Auditoria)</h3>
    <?php if (empty($historico)): ?>
        <p>Sem histórico de transições.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>Data</th><th>Técnico</th><th>Anterior</th><th>Novo</th></tr></thead>
            <tbody>
                <?php foreach ($historico as $h): ?>
                <tr>
                    <td><?= date('d/m/Y H:i:s', strtotime($h['data_alteracao'])) ?></td>
                    <td><?= sanitize($h['tecnico_nome']) ?></td>
                    <td><span class="badge badge-<?= str_replace(' ', '-', $h['estado_anterior']) ?>"><?= sanitize($h['estado_anterior']) ?></span></td>
                    <td><span class="badge badge-<?= str_replace(' ', '-', $h['estado_novo']) ?>"><?= sanitize($h['estado_novo']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Esclarecimentos</h3>
    <?php if (!empty($mensagens)): ?>
        <?php foreach ($mensagens as $m): ?>
            <div style="border-left: 4px solid <?= $m['remetente_role'] === 'tecnico' ? '#0056b3' : '#28a745' ?>; padding-left: 1rem; margin-bottom: 1rem;">
                <p><strong><?= sanitize($m['remetente_nome']) ?></strong> em <em><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></em>:</p>
                <p><?= nl2br(sanitize($m['mensagem'])) ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h4 style="margin-top: 1rem;">Solicitar Esclarecimento / Documentos ao Candidato:</h4>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="acao" value="enviar_esclarecimento">
        <textarea name="mensagem" rows="3" required placeholder="Indique o esclarecimento necessário..."></textarea>
        <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;">Enviar Mensagem</button>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
