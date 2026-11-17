<?php
require_once 'seguranca_nexplay.php';
require_once 'conexao_nexplay.php';

$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// 1. REGISTRO DE USUÁRIo
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = sanitizarEntrada($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $senha = $input['password'] ?? '';

    if (empty($nome) || !validarEmail($email) || strlen($senha) < 8) {
        http_response_code(400);
        echo json_encode(["sucesso" => false, "mensagem" => "Dados inválidos ou senha inferior a 8 caracteres."]);
        exit();
    }

    $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
    $stmtCheck->execute([':email' => $email]);

    if ($stmtCheck->fetch()) {
        http_response_code(409);
        echo json_encode(["sucesso" => false, "mensagem" => "Este e-mail já está cadastrado."]);
        exit();
    }

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    $sql  = "INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha)";
    $stmt = $pdo->prepare($sql);
    $exec = $stmt->execute([
        ':nome'  => $nome,
        ':email' => $email,
        ':senha' => $senhaHash
    ]);

    if ($exec) {
        echo json_encode(["sucesso" => true, "mensagem" => "Conta criada com segurança."]);
    } else {
        http_response_code(500);
        echo json_encode(["sucesso" => false, "mensagem" => "Erro ao registrar usuário."]);
    }
    exit();
}

// 2. LOGIN DE USUÁRIO
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($input['email'] ?? '');
    $senha = $input['password'] ?? '';

    if (!validarEmail($email) || empty($senha)) {
        http_response_code(400);
        echo json_encode(["sucesso" => false, "mensagem" => "Preencha e-mail e senha corretamente."]);
        exit();
    }

    $stmt = $pdo->prepare("SELECT id, nome, email, senha FROM usuarios WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        session_regenerate_id(true);

        $_SESSION['usuario_id']   = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];

        echo json_encode([
            "sucesso" => true,
            "usuario" => [
                "id"    => $usuario['id'],
                "nome"  => escaparSaida($usuario['nome']),
                "email" => escaparSaida($usuario['email'])
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["sucesso" => false, "mensagem" => "E-mail ou senha incorretos."]);
    }
    exit();
}

// 3. VERIFICAÇÃO DE SESSÃO
if ($action === 'check') {
    if (isset($_SESSION['usuario_id'])) {
        echo json_encode([
            "logado"  => true,
            "usuario" => [
                "id"   => $_SESSION['usuario_id'],
                "nome" => escaparSaida($_SESSION['usuario_nome'])
            ]
        ]);
    } else {
        echo json_encode(["logado" => false]);
    }
    exit();
}

// 4. LOGOUT SEGURO
if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    echo json_encode(["sucesso" => true]);
    exit();
}
