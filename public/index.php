<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$termo = trim($_GET['termo'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');
$area = trim($_GET['area'] ?? '');

$sql = "SELECT * FROM bolsas WHERE 1=1";
$params = [];

if ($termo !== '') {
    $sql .= " AND designacao LIKE ?";
    $params[] = "%$termo%";
}
if ($tipo !== '') {
    $sql .= " AND tipo = ?";
    $params[] = $tipo;
}
if ($area !== '') {
    $sql .= " AND area_estudo LIKE ?";
    $params[] = "%$area%";
}
$sql .= " ORDER BY prazo ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bolsas = $stmt->fetchAll();

$tipos = $pdo->query("SELECT DISTINCT tipo FROM bolsas")->fetchAll(PDO::FETCH_COLUMN);
$areas = $pdo->query("SELECT DISTINCT area_estudo FROM bolsas")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="card">
    <h2>Bolsas e Apoios Financeiros Disponíveis</h2>
    <form method="GET" action="index.php">
        <div class="form-row">
            <div class="form-col">
                <label>Designação:</label>
                <input type="text" name="termo" value="<?= sanitize($termo) ?>" placeholder="Pesquisar por designação...">
            </div>
            <div class="form-col">
                <label>Tipo de Apoio:</label>
                <select name="tipo">
                    <option value="">-- Todos os Tipos --</option>
                    <?php foreach ($tipos as $t): ?>
                        <option value="<?= sanitize($t) ?>" <?= $tipo === $t ? 'selected' : '' ?>><?= sanitize($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-col">
                <label>Área de Estudo:</label>
                <select name="area">
                    <option value="">-- Todas as Áreas --</option>
                    <?php foreach ($areas as $a): ?>
                        <option value="<?= sanitize($a) ?>" <?= $area === $a ? 'selected' : '' ?>><?= sanitize($a) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <a href="index.php" class="btn btn-secondary">Limpar</a>
    </form>
</div>

<div class="card">
    <?php if (empty($bolsas)): ?>
        <p>Não foram encontradas bolsas.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Designação</th>
                    <th>Tipo</th>
                    <th>Área</th>
                    <th>Montante</th>
                    <th>Prazo</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bolsas as $b): 
                    $expirada = strtotime($b['prazo']) < strtotime(date('Y-m-d'));
                ?>
                <tr>
                    <td><strong><?= sanitize($b['designacao']) ?></strong></td>
                    <td><?= sanitize($b['tipo']) ?></td>
                    <td><?= sanitize($b['area_estudo']) ?></td>
                    <td><?= number_format($b['montante'], 2, ',', '.') ?> €</td>
                    <td>
                        <?= date('d/m/Y', strtotime($b['prazo'])) ?>
                        <?php if ($expirada): ?>
                            <span class="badge badge-rejeitada">Expirada</span>
                        <?php else: ?>
                            <span class="badge badge-aprovada">Aberta</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-secondary" onclick="alert('Requisitos:\n<?= addslashes(sanitize($b['requisitos'])) ?>')">Requisitos</button>
                        <?php if (!$expirada): ?>
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'candidato'): ?>
                                <a href="candidato/candidatar.php?bolsa_id=<?= $b['id'] ?>" class="btn btn-primary">Candidatar-se</a>
                            <?php elseif (!isset($_SESSION['user_id'])): ?>
                                <a href="login.php" class="btn btn-primary">Entrar para Submeter</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
