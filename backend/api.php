<?php
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/supabase.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'games';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// ==========================================
// 1. ROTAS DE USUÁRIO (SUPABASE: usuarios)
// ==========================================
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($input['username'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $password = $input['password'] ?? '';

    if (!$username || !$email || !$password) {
        http_response_code(400);
        echo json_encode(['sucesso' => false, 'erro' => 'Preencha todos os campos.']);
        exit();
    }

    // Verifica se o e-mail já existe
    $check = supabaseRequest('usuarios?email=eq.' . urlencode($email) . '&select=id', 'GET');
    if ($check['sucesso'] && !empty($check['dados'])) {
        http_response_code(409);
        echo json_encode(['sucesso' => false, 'erro' => 'Já existe uma conta com este e-mail.']);
        exit();
    }

    // Insere novo usuário no Supabase
    $novoUsuario = [
        'nome_usuario' => $username,
        'email'        => $email,
        'senha_hash'   => password_hash($password, PASSWORD_DEFAULT)
    ];

    $res = supabaseRequest('usuarios', 'POST', $novoUsuario);
    if ($res['sucesso'] && !empty($res['dados'][0])) {
        $u = $res['dados'][0];
        echo json_encode([
            'sucesso' => true,
            'user' => [
                'id' => $u['id'],
                'username' => $u['nome_usuario'],
                'email' => $u['email'],
                'favorites' => [],
                'library' => []
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['sucesso' => false, 'erro' => 'Erro ao cadastrar no banco.', 'detalhes' => $res['erro']]);
    }
    exit();
}

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($input['email'] ?? ''));
    $password = $input['password'] ?? '';

    $res = supabaseRequest('usuarios?email=eq.' . urlencode($email) . '&select=*', 'GET');
    if (!$res['sucesso'] || empty($res['dados'][0])) {
        http_response_code(401);
        echo json_encode(['sucesso' => false, 'erro' => 'E-mail ou senha incorretos.']);
        exit();
    }

    $u = $res['dados'][0];
    // Valida senha com hash (ou texto simples caso tenha sido inserida manualmente no SQL)
    $senhaValida = password_verify($password, $u['senha_hash'] ?? '') || ($password === ($u['senha_hash'] ?? ''));

    if (!$senhaValida) {
        http_response_code(401);
        echo json_encode(['sucesso' => false, 'erro' => 'E-mail ou senha incorretos.']);
        exit();
    }

    // Busca favoritos e biblioteca do usuário no Supabase
    $favRes = supabaseRequest('favoritos?usuario_id=eq.' . $u['id'] . '&select=jogo_id_api', 'GET');
    $libRes = supabaseRequest('bibliotea?usuario_id=eq.' . $u['id'] . '&select=jogo_id_api', 'GET');

    $favorites = $favRes['sucesso'] ? array_column($favRes['dados'], 'jogo_id_api') : [];
    $library   = $libRes['sucesso'] ? array_column($libRes['dados'], 'jogo_id_api') : [];

    echo json_encode([
        'sucesso' => true,
        'user' => [
            'id'        => $u['id'],
            'username'  => $u['nome_usuario'],
            'email'     => $u['email'],
            'favorites' => $favorites,
            'library'   => $library
        ]
    ]);
    exit();
}

// ==========================================
// 2. FAVORITOS E BIBLIOTECA (SUPABASE)
// ==========================================
if ($action === 'toggle_favorite' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioId = (int)($input['usuario_id'] ?? 0);
    $jogoId    = (int)($input['jogo_id'] ?? 0);

    $check = supabaseRequest("favoritos?usuario_id=eq.$usuarioId&jogo_id_api=eq.$jogoId&select=id", 'GET');
    if ($check['sucesso'] && !empty($check['dados'])) {
        $del = supabaseRequest("favoritos?usuario_id=eq.$usuarioId&jogo_id_api=eq.$jogoId", 'DELETE');
        echo json_encode(['sucesso' => $del['sucesso'], 'acao' => 'removido']);
    } else {
        $ins = supabaseRequest('favoritos', 'POST', ['usuario_id' => $usuarioId, 'jogo_id_api' => $jogoId]);
        echo json_encode(['sucesso' => $ins['sucesso'], 'acao' => 'adicionado']);
    }
    exit();
}

if ($action === 'toggle_library' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioId = (int)($input['usuario_id'] ?? 0);
    $jogoId    = (int)($input['jogo_id'] ?? 0);

    $check = supabaseRequest("bibliotea?usuario_id=eq.$usuarioId&jogo_id_api=eq.$jogoId&select=id", 'GET');
    if ($check['sucesso'] && !empty($check['dados'])) {
        $del = supabaseRequest("bibliotea?usuario_id=eq.$usuarioId&jogo_id_api=eq.$jogoId", 'DELETE');
        echo json_encode(['sucesso' => $del['sucesso'], 'acao' => 'removido']);
    } else {
        $ins = supabaseRequest('bibliotea', 'POST', ['usuario_id' => $usuarioId, 'jogo_id_api' => $jogoId]);
        echo json_encode(['sucesso' => $ins['sucesso'], 'acao' => 'adicionado']);
    }
    exit();
}

// ==========================================
// 3. CATÁLOGO DE JOGOS (FREETOGAME API)
// ==========================================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$gameId = isset($_GET['id']) ? trim($_GET['id']) : '';
$baseUrl = "https://www.freetogame.com/api";

if ($action === 'detail' && !empty($gameId)) {
    $apiUrl = $baseUrl . "/game?id=" . urlencode($gameId);
} else {
    $apiUrl = $baseUrl . "/games";
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && !empty($response)) {
    if ($action === 'games' && !empty($search)) {
        $gamesList = json_decode($response, true);
        if (is_array($gamesList)) {
            $filtered = array_values(array_filter($gamesList, function($game) use ($search) {
                return stripos($game['title'], $search) !== false ||
                       stripos($game['genre'], $search) !== false ||
                       stripos($game['developer'], $search) !== false;
            }));
            echo json_encode($filtered);
            exit();
        }
    }
    echo $response;
} else {
    if ($action === 'detail') {
        echo json_encode([
            "id" => $gameId ? $gameId : "540",
            "title" => "Overwatch 2 (Offline Mock)",
            "thumbnail" => "https://www.freetogame.com/g/540/thumbnail.jpg",
            "short_description" => "A hero-based tactical shooter set in a optimistic future.",
            "description" => "Overwatch 2 is a team-based action game set in the optimistic future.",
            "game_url" => "https://www.freetogame.com/open/overwatch-2",
            "genre" => "Shooter",
            "platform" => "PC (Windows)",
            "publisher" => "Blizzard Entertainment",
            "developer" => "Blizzard Entertainment",
            "release_date" => "2022-10-04",
            "minimum_system_requirements" => [
                "os" => "Windows 10 64-bit",
                "processor" => "Intel Core i3",
                "memory" => "6 GB RAM",
                "graphics" => "NVIDIA GeForce GTX 600",
                "storage" => "50 GB"
            ]
        ]);
    } else {
        echo json_encode([
            [
                "id" => 540,
                "title" => "Overwatch 2",
                "thumbnail" => "https://www.freetogame.com/g/540/thumbnail.jpg",
                "short_description" => "A hero-based tactical shooter set in a optimistic future.",
                "game_url" => "https://www.freetogame.com/open/overwatch-2",
                "genre" => "Shooter",
                "platform" => "PC (Windows)",
                "publisher" => "Blizzard Entertainment",
                "developer" => "Blizzard Entertainment",
                "release_date" => "2022-10-04"
            ]
        ]);
    }
}
?>
