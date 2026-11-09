<?php
require_once __DIR__ . '/../config/db.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nif = trim($_POST['nif'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $morada = trim($_POST['morada'] ?? '');

    if (empty($username) || empty($password) || empty($nome) || empty($email)) {
        $erro = "Preencha todos os campos obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Endereço de email inválido.";
    } elseif (strlen($password) < 8) {
        $erro = "A palavra-passe tem de ter no mínimo 8 caracteres.";
    } else {
        $chk = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $chk->execute([$username, $email]);
        if ($chk->fetch()) {
            $erro = "Nome de utilizador ou email já registado.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, nome, email, nif, telefone, morada) VALUES (?, ?, 'candidato', ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hash, $nome, $email, $nif, $telefone, $morada]);
            $sucesso = "Registo concluído com sucesso!";
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 600px; margin: auto;">
    <h2>Registo de Candidato</h2>
    <?php if ($erro): ?><div class="alert alert-error"><?= sanitize($erro) ?></div><?php endif; ?>
    <?php if ($sucesso): ?><div class="alert alert-success"><?= sanitize($sucesso) ?> <a href="login.php">Entrar</a></div><?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <label>Nome Completo *:</label>
        <input type="text" name="nome" required value="<?= sanitize($_POST['nome'] ?? '') ?>">

        <label>Email *:</label>
        <input type="email" name="email" required value="<?= sanitize($_POST['email'] ?? '') ?>">

        <div class="form-row">
            <div class="form-col">
                <label>Nome de Utilizador *:</label>
                <input type="text" name="username" required value="<?= sanitize($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-col">
                <label>Palavra-passe (mín. 8 caracteres) *:</label>
                <input type="password" name="password" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-col">
                <label>NIF:</label>
                <input type="text" name="nif" maxlength="9" value="<?= sanitize($_POST['nif'] ?? '') ?>">
            </div>
            <div class="form-col">
                <label>Telefone:</label>
                <input type="text" name="telefone" value="<?= sanitize($_POST['telefone'] ?? '') ?>">
            </div>
        </div>

        <label>Morada:</label>
        <input type="text" name="morada" value="<?= sanitize($_POST['morada'] ?? '') ?>">

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Criar Conta</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
