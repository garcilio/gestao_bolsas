<?php
require_once __DIR__ . '/../../config/db.php';
check_auth('candidato');
require_once __DIR__ . '/../../includes/header.php';

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT c.*, b.designacao, b.montante, b.prazo
    FROM candidaturas c
    JOIN bolsas b ON c.bolsa_id = b.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$candidaturas = $stmt->fetchAll();
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>As Minhas Candidaturas</h2>
        <a href="/index.php" class="btn btn-primary">+ Nova Candidatura</a>
    </div>
    
    <?php if (empty($candidaturas)): ?>
        <p style="margin-top: 1rem;">Ainda não submeteu qualquer candidatura. <a href="/index.php">Consulte as bolsas abertas</a>.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Bolsa</th>
                    <th>Montante</th>
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
                    <td><strong><?= sanitize($c['designacao']) ?></strong></td>
                    <td><?= number_format($c['montante'], 2, ',', '.') ?> €</td>
                    <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                    <td><span class="badge <?= $badge_class ?>"><?= sanitize($c['estado']) ?></span></td>
                    <td><a href="detalhes.php?id=<?= $c['id'] ?>" class="btn btn-secondary">Ver Detalhes</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
