<?php
require_once __DIR__ . '/../config/db.php';
check_auth();

$doc_id = (int)($_GET['id'] ?? 0);
if ($doc_id <= 0) {
    http_response_code(400);
    die("Pedido inválido.");
}

$stmt = $pdo->prepare("
    SELECT d.*, c.user_id, c.tecnico_id 
    FROM documentos d
    JOIN candidaturas c ON d.candidatura_id = c.id
    WHERE d.id = ?
");
$stmt->execute([$doc_id]);
$doc = $stmt->fetch();

if (!$doc) {
    http_response_code(404);
    die("Documento não encontrado.");
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$is_candidato_dono = ($role === 'candidato' && $doc['user_id'] == $user_id);
$is_tecnico_atribuido = ($role === 'tecnico' && $doc['tecnico_id'] == $user_id);

if (!$is_candidato_dono && !$is_tecnico_atribuido) {
    http_response_code(403);
    die("Acesso Proibido: Não tem permissão para aceder a este documento.");
}

$ficheiro_disco = __DIR__ . '/../storage/' . basename($doc['nome_ficheiro']);

if (!file_exists($ficheiro_disco)) {
    http_response_code(404);
    die("Ficheiro em falta no servidor.");
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($doc['nome_original']) . '"');
header('Content-Length: ' . filesize($ficheiro_disco));
header('Cache-Control: private, max-age=0, must-revalidate');

readfile($ficheiro_disco);
exit;
