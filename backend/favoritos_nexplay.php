<?php
require_once 'seguranca_nexplay.php';
require_once 'conexao_nexplay.php';

$action = $_GET['action'] ?? '';
$acoesPublicas = ['report_by_genre', 'report_by_platform'];

if (!isset($_SESSION['usuario_id']) && !in_array($action, $acoesPublicas)) {
    http_response_code(401);
    echo json_encode(["sucesso" => false, "mensagem" => "Acesso negado."]);
    exit();
}

$usuarioId = $_SESSION['usuario_id'] ?? null;

// 1. SALVAR FAVORITO
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $jogoId     = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
    $titulo     = sanitizarEntrada($input['title'] ?? '');
    $genero     = sanitizarEntrada($input['genre'] ?? '');
    $plataforma = sanitizarEntrada($input['platform'] ?? '');
    $url        = filter_var($input['game_url'] ?? '', FILTER_SANITIZE_URL);

    if (!$jogoId || empty($titulo)) {
        http_response_code(400);
        echo json_encode(["sucesso" => false, "mensagem" => "Parâmetros inválidos."]);
        exit();
    }

    $sqlJogo  = "INSERT INTO jogos (id, titulo, genero, plataforma, url) 
                 VALUES (:id, :titulo, :genero, :plataforma, :url)
                 ON CONFLICT (id) DO UPDATE SET titulo = EXCLUDED.titulo";
    $stmtJogo = $pdo->prepare($sqlJogo);
    $stmtJogo->execute([
        ':id'         => $jogoId,
        ':titulo'     => $titulo,
        ':genero'     => $genero,
        ':plataforma' => $plataforma,
        ':url'        => $url
    ]);

    $sqlFav  = "INSERT INTO favoritos (usuario_id, jogo_id) 
                 VALUES (:usuario_id, :jogo_id) 
                 ON CONFLICT (usuario_id, jogo_id) DO NOTHING";
    $stmtFav = $pdo->prepare($sqlFav);
    $stmtFav->execute([
        ':usuario_id' => $usuarioId,
        ':jogo_id'    => $jogoId
    ]);

    echo json_encode(["sucesso" => true, "mensagem" => "Salvo nos favoritos."]);
    exit();
}

// 2. LISTAR FAVORITOS DO USUÁRIO
if ($action === 'list') {
    $sql = "SELECT j.id, j.titulo, j.genero, j.plataforma, j.url, f.adicionado_em 
            FROM favoritos f
            JOIN jogos j ON f.jogo_id = j.id
            WHERE f.usuario_id = :usuario_id
            ORDER BY f.adicionado_em DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':usuario_id' => $usuarioId]);
    $favoritos = $stmt->fetchAll();

    $limpo = array_map(function($item) {
        return [
            'id'            => (int)$item['id'],
            'titulo'        => escaparSaida($item['titulo']),
            'genero'        => escaparSaida($item['genero']),
            'plataforma'    => escaparSaida($item['plataforma']),
            'url'           => escaparSaida($item['url']),
            'adicionado_em' => $item['adicionado_em']
        ];
    }, $favoritos);
<?php
require_once 'seguranca_nexplay.php';
require_once 'conexao_nexplay.php';

$action = $_GET['action'] ?? '';
$acoesPublicas = ['report_by_genre', 'report_by_platform'];

if (!isset($_SESSION['usuario_id']) && !in_array($action, $acoesPublicas)) {
    http_response_code(401);
    echo json_encode(["sucesso" => false, "mensagem" => "Acesso negado."]);
    exit();
}

$usuarioId = $_SESSION['usuario_id'] ?? null;

// 1. SALVAR FAVORITO
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $jogoId     = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
    $titulo     = sanitizarEntrada($input['title'] ?? '');
    $genero     = sanitizarEntrada($input['genre'] ?? '');
    $plataforma = sanitizarEntrada($input['platform'] ?? '');
    $url        = filter_var($input['game_url'] ?? '', FILTER_SANITIZE_URL);

    if (!$jogoId || empty($titulo)) {
        http_response_code(400);
        echo json_encode(["sucesso" => false, "mensagem" => "Parâmetros inválidos."]);
        exit();
    }

    $sqlJogo  = "INSERT INTO jogos (id, titulo, genero, plataforma, url) 
                 VALUES (:id, :titulo, :genero, :plataforma, :url)
                 ON CONFLICT (id) DO UPDATE SET titulo = EXCLUDED.titulo";
    $stmtJogo = $pdo->prepare($sqlJogo);
    $stmtJogo->execute([
        ':id'         => $jogoId,
        ':titulo'     => $titulo,
        ':genero'     => $genero,
        ':plataforma' => $plataforma,
        ':url'        => $url
    ]);

    $sqlFav  = "INSERT INTO favoritos (usuario_id, jogo_id) 
                 VALUES (:usuario_id, :jogo_id) 
                 ON CONFLICT (usuario_id, jogo_id) DO NOTHING";
    $stmtFav = $pdo->prepare($sqlFav);
    $stmtFav->execute([
        ':usuario_id' => $usuarioId,
        ':jogo_id'    => $jogoId
    ]);

    echo json_encode(["sucesso" => true, "mensagem" => "Salvo nos favoritos."]);
    exit();
}

// 2. LISTAR FAVORITOS DO USUÁRIO
if ($action === 'list') {
    $sql = "SELECT j.id, j.titulo, j.genero, j.plataforma, j.url, f.adicionado_em 
            FROM favoritos f
            JOIN jogos j ON f.jogo_id = j.id
            WHERE f.usuario_id = :usuario_id
            ORDER BY f.adicionado_em DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':usuario_id' => $usuarioId]);
    $favoritos = $stmt->fetchAll();

    $limpo = array_map(function($item) {
        return [
            'id'            => (int)$item['id'],
            'titulo'        => escaparSaida($item['titulo']),
            'genero'        => escaparSaida($item['genero']),
            'plataforma'    => escaparSaida($item['plataforma']),
            'url'           => escaparSaida($item['url']),
            'adicionado_em' => $item['adicionado_em']
        ];
    }, $favoritos);

    echo json_encode(["sucesso" => true, "dados" => $limpo]);
    exit();
}

// 3. RELATÓRIO ANALÍTICO 1: Favoritos por Gênero (GROUP BY e COUNT)
if ($action === 'report_by_genre') {
    $sql = "SELECT j.genero, COUNT(f.id) AS total_favoritos
            FROM favoritos f
            JOIN jogos j ON f.jogo_id = j.id
            GROUP BY j.genero
            ORDER BY total_favoritos DESC";
    
    $stmt = $pdo->query($sql);
    $relatorio = $stmt->fetchAll();

    $limpo = array_map(function($item) {
        return [
            'genero'          => escaparSaida($item['genero']),
            'total_favoritos' => (int)$item['total_favoritos']
        ];
    }, $relatorio);

    echo json_encode(["sucesso" => true, "dados" => $limpo]);
    exit();
}

// 4. RELATÓRIO ANALÍTICO 2: Plataforma e Média por Usuário (GROUP BY, COUNT e AVG)
if ($action === 'report_by_platform') {
    $sql = "SELECT 
                j.plataforma,
                COUNT(f.id) AS total_favoritos,
                COUNT(DISTINCT f.usuario_id) AS total_usuarios,
                ROUND(AVG(sub.total_user), 2) AS media_por_usuario
            FROM jogos j
            JOIN favoritos f ON j.id = f.jogo_id
            JOIN (
                SELECT usuario_id, COUNT(id) AS total_user 
                FROM favoritos 
                GROUP BY usuario_id
            ) sub ON sub.usuario_id = f.usuario_id
            GROUP BY j.plataforma
            ORDER BY total_favoritos DESC";<?php
require_once 'seguranca_nexplay.php';
require_once 'conexao_nexplay.php';

$action = $_GET['action'] ?? '';
$acoesPublicas = ['report_by_genre', 'report_by_platform'];

if (!isset($_SESSION['usuario_id']) && !in_array($action, $acoesPublicas)) {
    http_response_code(401);
    echo json_encode(["sucesso" => false, "mensagem" => "Acesso negado."]);
    exit();
}

$usuarioId = $_SESSION['usuario_id'] ?? null;

// 1. SALVAR FAVORITO
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $jogoId     = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
    $titulo     = sanitizarEntrada($input['title'] ?? '');
    $genero     = sanitizarEntrada($input['genre'] ?? '');
    $plataforma = sanitizarEntrada($input['platform'] ?? '');
    $url        = filter_var($input['game_url'] ?? '', FILTER_SANITIZE_URL);

    if (!$jogoId || empty($titulo)) {
        http_response_code(400);
        echo json_encode(["sucesso" => false, "mensagem" => "Parâmetros inválidos."]);
        exit();
    }

    $sqlJogo  = "INSERT INTO jogos (id, titulo, genero, plataforma, url) 
                 VALUES (:id, :titulo, :genero, :plataforma, :url)
                 ON CONFLICT (id) DO UPDATE SET titulo = EXCLUDED.titulo";
    $stmtJogo = $pdo->prepare($sqlJogo);
    $stmtJogo->execute([
        ':id'         => $jogoId,
        ':titulo'     => $titulo,
        ':genero'     => $genero,
        ':plataforma' => $plataforma,
        ':url'        => $url
    ]);

    $sqlFav  = "INSERT INTO favoritos (usuario_id, jogo_id) 
                 VALUES (:usuario_id, :jogo_id) 
                 ON CONFLICT (usuario_id, jogo_id) DO NOTHING";
    $stmtFav = $pdo->prepare($sqlFav);
    $stmtFav->execute([
        ':usuario_id' => $usuarioId,
        ':jogo_id'    => $jogoId
    ]);

    echo json_encode(["sucesso" => true, "mensagem" => "Salvo nos favoritos."]);
    exit();
}

// 2. LISTAR FAVORITOS DO USUÁRIO
if ($action === 'list') {
    $sql = "SELECT j.id, j.titulo, j.genero, j.plataforma, j.url, f.adicionado_em 
            FROM favoritos f
            JOIN jogos j ON f.jogo_id = j.id
            WHERE f.usuario_id = :usuario_id
            ORDER BY f.adicionado_em DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':usuario_id' => $usuarioId]);
    $favoritos = $stmt->fetchAll();

    $limpo = array_map(function($item) {
        return [
            'id'            => (int)$item['id'],
            'titulo'        => escaparSaida($item['titulo']),
            'genero'        => escaparSaida($item['genero']),
            'plataforma'    => escaparSaida($item['plataforma']),
            'url'           => escaparSaida($item['url']),
            'adicionado_em' => $item['adicionado_em']
        ];
    }, $favoritos);

    echo json_encode(["sucesso" => true, "dados" => $limpo]);
    exit();
}

// 3. RELATÓRIO ANALÍTICO 1: Favoritos por Gênero (GROUP BY e COUNT)
if ($action === 'report_by_genre') {
    $sql = "SELECT j.genero, COUNT(f.id) AS total_favoritos
            FROM favoritos f
            JOIN jogos j ON f.jogo_id = j.id
            GROUP BY j.genero
            ORDER BY total_favoritos DESC";
    
    $stmt = $pdo->query($sql);
    $relatorio = $stmt->fetchAll();

    $limpo = array_map(function($item) {
        return [
            'genero'          => escaparSaida($item['genero']),
            'total_favoritos' => (int)$item['total_favoritos']
        ];
    }, $relatorio);

    echo json_encode(["sucesso" => true, "dados" => $limpo]);
    exit();
}

// 4. RELATÓRIO ANALÍTICO 2: Plataforma e Média por Usuário (GROUP BY, COUNT e AVG)
if ($action === 'report_by_platform') {
    $sql = "SELECT 
                j.plataforma,
                COUNT(f.id) AS total_favoritos,
                COUNT(DISTINCT f.usuario_id) AS total_usuarios,
                ROUND(AVG(sub.total_user), 2) AS media_por_usuario
            FROM jogos j
            JOIN favoritos f ON j.id = f.jogo_id
            JOIN (
                SELECT usuario_id, COUNT(id) AS total_user 
                FROM favoritos 
                GROUP BY usuario_id
            ) sub ON sub.usuario_id = f.usuario_id
            GROUP BY j.plataforma
            ORDER BY total_favoritos DESC";

    $stmt = $pdo->query($sql);
    $relatorio = $stmt->fetchAll();

    $limpo = array_map(function($item) {
        return [
            'plataforma'        => escaparSaida($item['plataforma']),
            'total_favoritos'   => (int)$item['total_favoritos'],
            'total_usuarios'    => (int)$item['total_usuarios'],
            'media_por_usuario' => (float)$item['media_por_usuario']
        ];
    }, $relatorio);

    echo json_encode(["sucesso" => true, "dados" => $limpo]);
    exit();
}

    $stmt = $pdo->query($sql);
    $relatorio = $stmt->fetchAll();

    $limpo = array_map(function($item) {
        return [
            'plataforma'        => escaparSaida($item['plataforma']),
            'total_favoritos'   => (int)$item['total_favoritos'],
            'total_usuarios'    => (int)$item['total_usuarios'],
            'media_por_usuario' => (float)$item['media_por_usuario']
        ];
    }, $relatorio);

    echo json_encode(["sucesso" => true, "dados" => $limpo]);
    exit();
}
    echo json_encode(["sucesso" => true, "dados" => $limpo]);
    exit();
}

// 3. RELATÓRIO ANALÍTICO 1: Favoritos por Gênero (GROUP BY e COUNT)
if ($action === 'report_by_genre') {
    $sql = "SELECT j.genero, COUNT(f.id) AS total_favoritos
            FROM favoritos f
            JOIN jogos j ON f.jogo_id = j.id
            GROUP BY j.genero
            ORDER BY total_favoritos DESC";
    
    $stmt = $pdo->query($sql);
    $relatorio = $stmt->fetchAll();

    $limpo = array_map(function($item) {
        return [
            'genero'          => escaparSaida($item['genero']),
            'total_favoritos' => (int)$item['total_favoritos']
        ];
    }, $relatorio);

    echo json_encode(["sucesso" => true, "dados" => $limpo]);
    exit();
}

// 4. RELATÓRIO ANALÍTICO 2: Plataforma e Média por Usuário (GROUP BY, COUNT e AVG)
if ($action === 'report_by_platform') {
    $sql = "SELECT 
                j.plataforma,
                COUNT(f.id) AS total_favoritos,
                COUNT(DISTINCT f.usuario_id) AS total_usuarios,
                ROUND(AVG(sub.total_user), 2) AS media_por_usuario
            FROM jogos j
            JOIN favoritos f ON j.id = f.jogo_id
            JOIN (
                SELECT usuario_id, COUNT(id) AS total_user 
                FROM favoritos 
                GROUP BY usuario_id
            ) sub ON sub.usuario_id = f.usuario_id
            GROUP BY j.plataforma
            ORDER BY total_favoritos DESC";

    $stmt = $pdo->query($sql);
    $relatorio = $stmt->fetchAll();

    $limpo = array_map(function($item) {
        return [
            'plataforma'        => escaparSaida($item['plataforma']),
            'total_favoritos'   => (int)$item['total_favoritos'],
            'total_usuarios'    => (int)$item['total_usuarios'],
            'media_por_usuario' => (float)$item['media_por_usuario']
        ];
    }, $relatorio);

    echo json_encode(["sucesso" => true, "dados" => $limpo]);
    exit();
}
