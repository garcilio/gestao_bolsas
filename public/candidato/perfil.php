<?php
require_once __DIR__ . '/../../config/db.php';
check_auth('candidato');

$user_id = $_SESSION['user_id'];
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'dados') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $morada = trim($_POST['morada'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $nif = trim($_POST['nif'] ?? '');

        if (empty($nome) || empty($email)) {
            $erro = "Nome e Email são obrigatórios.";
        } else {
            $up = $pdo->prepare("UPDATE users SET nome = ?, email = ?, morada = ?, telefone = ?, nif = ? WHERE id = ?");
            $up->execute([$nome, $email, $morada, $telefone, $nif, $user_id]);
            $_SESSION['nome'] = $nome;
            $sucesso = "Perfil atualizado.";
        }
    } elseif ($acao === 'password') {
        $pass_atual = $_POST['pass_atual'] ?? '';
        $nova_pass = $_POST['nova_pass'] ?? '';
        $conf_pass = $_POST['conf_pass'] ?? '';

        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $u = $stmt->fetch();

        if (!password_verify($pass_atual, $u['password_hash'])) {
            $erro = "A palavra-passe atual está incorreta.";
        } elseif (strlen($nova_pass) < 8) {
            $erro = "A nova palavra-passe tem de ter no mínimo 8 caracteres.";
        } elseif ($nova_pass !== $conf_pass) {
            $erro = "As novas palavras-passe não coincidem.";
        } else {
            $novo_hash = password_hash($nova_pass, PASSWORD_DEFAULT);
            $up = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $up->execute([$novo_hash, $user_id]);
            $sucesso = "Palavra-passe alterada.";
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$perfil = $stmt->fetch();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width: 650px; margin: auto;">
    <h2>Perfil Pessoal</h2>
    <?php if ($erro): ?><div class="alert alert-error"><?= sanitize($erro) ?></div><?php endif; ?>
    <?php if ($sucesso): ?><div class="alert alert-success"><?= sanitize($sucesso) ?></div><?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="acao" value="dados">

        <label>Nome Completo:</label>
        <input type="text" name="nome" value="<?= sanitize($perfil['nome']) ?>" required>

        <label>Email:</label>
        <input type="email" name="email" value="<?= sanitize($perfil['email']) ?>" required>

        <div class="form-row">
            <div class="form-col">
                <label>NIF:</label>
                <input type="text" name="nif" maxlength="9" value="<?= sanitize($perfil['nif']) ?>">
            </div>
            <div class="form-col">
                <label>Telefone:</label>
                <input type="text" name="telefone" value="<?= sanitize($perfil['telefone']) ?>">
            </div>
        </div>

        <label>Morada:</label>
        <input type="text" name="morada" value="<?= sanitize($perfil['morada']) ?>">

        <button type="submit" class="btn btn-primary">Guardar Dados</button>
    </form>
</div>

<div class="card" style="max-width: 650px; margin: 2rem auto;">
    <h3>Alterar Palavra-passe</h3>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="acao" value="password">

        <label>Palavra-passe Atual:</label>
        <input type="password" name="pass_atual" required>

        <label>Nova Palavra-passe:</label>
        <input type="password" name="nova_pass" required>

        <label>Confirmar Nova Palavra-passe:</label>
        <input type="password" name="conf_pass" required>

        <button type="submit" class="btn btn-secondary">Alterar Palavra-passe</button>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
