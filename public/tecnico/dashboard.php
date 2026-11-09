<?php
require_once __DIR__ . '/../../config/db.php';
check_auth('tecnico');
require_once __DIR__ . '/../../includes/header.php';

$tecnico_id = $_SESSION['user_id'];

$filtro_candidato = trim($_GET['candidato'] ?? '');
$filtro_bolsa = trim($_GET['bolsa'] ?? '');
$filtro_estado = trim($_GET['estado'] ?? '');
$filtro_data = trim($_GET['data'] ?? '');

$sql = "
    SELECT c.*, b.designacao as bolsa_nome, u.nome as candidato_nome
    FROM candidaturas c
    JOIN bolsas b ON c.bolsa_id = b.id
    JOIN users u ON c.user_id = u.id
    WHERE c.tecnico_id = ?
";
$params = [$tecnico_id];

if ($filtro_candidato !== '') {
    $sql .= " AND u.nome LIKE ?";
    $params[] = "%$filtro_candidato%";
}
if ($filtro_bolsa !== '') {
    $sql .= " AND b.designacao LIKE ?";
    $params[] = "%$filtro_bolsa%";
}
if ($filtro_estado !== '') {
    $sql .= " AND c.estado = ?";
    $params[] = $filtro_estado;
}
if ($filtro_data !== '') {
    $sql .= " AND DATE(c.created_at) = ?";
    $params[] = $filtro_data;
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$candidaturas = $stmt->fetchAll();
?>

<div class="card">
    <h2>Painel do Técnico de Avaliação</h2>
    <form method="GET" action="dashboard.php">
        <div class="form-row">
            <div class="form-col">
                <label>Candidato:</label>
                <input type="text" name="candidato" value="<?= sanitize($filtro_candidato) ?>" placeholder="Nome...">
            </div>
            <div class="form-col">
                <label>Bolsa:</label>
                <input type="text" name="bolsa" value="<?= sanitize($filtro_bolsa) ?>" placeholder="Bolsa...">
            </div>
            <div class="form-col">
                <label>Estado:</label>
                <select name="estado">
                    <option value="">-- Todos --</option>
                    <option value="submetida" <?= $filtro_estado === 'submetida' ? 'selected' : '' ?>>Submetida</option>
                    <option value="em analise" <?= $filtro_estado === 'em analise' ? 'selected' : '' ?>>Em Análise</option>
                    <option value="aguardar documentacao" <?= $filtro_estado === 'aguardar documentacao' ? 'selected' : '' ?>>Aguardar Documentação</option>
                    <option value="aprovada" <?= $filtro_estado === 'aprovada' ? 'selected' : '' ?>>Aprovada</option>
                    <option value="rejeitada" <?= $filtro_estado === 'rejeitada' ? 'selected' : '' ?>>Rejeitada</option>
                </select>
            </div>
            <div class="form-col">
                <label>Data:</label>
                <input type="date" name="data" value="<?= sanitize($filtro_data) ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <a href="dashboard.php" class="btn btn-secondary">Limpar</a>
    </form>
</div>

<div class="card">
    <?php if (empty($candidaturas)): ?>
        <p>Nenhuma candidatura atribuída encontrada.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Candidato</th>
                    <th>Bolsa</th>
                    <th>Data Submissão</th>
                    <th>Estado</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($candidaturas as $c): 
                    $badge_class = 'badge-' . str_replace(' ', '-', $c['estado']);
                ?>
                <tr>
                    <td><strong><?= sanitize($c['candidato_nome']) ?></strong></td>
                    <td><?= sanitize($c['bolsa_nome']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                    <td><span class="badge <?= $badge_class ?>"><?= sanitize($c['estado']) ?></span></td>
                    <td><a href="avaliar.php?id=<?= $c['id'] ?>" class="btn btn-primary">Avaliar</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
