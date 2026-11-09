<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h3>1. Verificação de Extensões:</h3>";
if (extension_loaded('pdo_mysql')) {
    print "Extensão PDO MySQL está ativa.<br>";
} else {
    print "<strong>A extensão PDO MySQL NÃO está ativa no php.ini!</strong><br>";
}

print "<h3>2. Teste de Conexão:</h3>";
$host = '127.0.0.1';
$port = 3306;
$dbname = 'bolsas_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    print "✅ Conexão PDO criada com sucesso!";
} catch (PDOException $e) {
    print "<strong>Erro ao criar o PDO:</strong> " . $e->getMessage();
}