<?php
require_once __DIR__ . '/../config/db.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nome'] = $user['nome'];

        if ($user['role'] === 'candidato') {
            header("Location: candidato/dashboard.php");
        } else {
            header("Location: tecnico/dashboard.php");
        }
        exit;
    } else {
        $erro = "Credenciais inválidas. Verifique os dados.";
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 450px; margin: auto;">
    <h2>Autenticação</h2>
    <?php if ($erro): ?><div class="alert alert-error"><?= sanitize($erro) ?></div><?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <label>Utilizador:</label>
        <input type="text" name="username" required>

        <label>Palavra-passe:</label>
        <input type="password" name="password" required>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Entrar</button>
    </form>
    
    <div style="margin-top: 1.5rem; font-size: 0.9rem; text-align: center; color: #64748b;">
        <p>Ainda não tem conta? <a href="registo.php">Registe-se aqui</a>.</p>
        <p><strong>Contas de demonstração:</strong><br>
        Técnico: <code>tecnico1</code> / <code>Password123!</code><br>
        Candidato: <code>aluno1</code> / <code>Password123!</code></p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
