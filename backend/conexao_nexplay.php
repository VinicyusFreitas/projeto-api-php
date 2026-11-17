<?php
require_once 'seguranca_nexplay.php';

$host = 'db.viitrkysujqunawxgldu.supabase.co';
$port = '5432';
$db   = 'postgres';
$user = 'postgres';
$pass = 'VVV8*_!@VB';

$dsn = "pgsql:host=$host;port=$port;dbname=$db;";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log("Erro na conexão: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["sucesso" => false, "mensagem" => "Erro de conexão com o banco de dados."]);
    exit();
}
