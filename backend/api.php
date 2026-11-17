<?php
error_reporting(0);
ini_set('display_errors', 0);

// Liberação total de CORS para permitir acesso remoto do front-end
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$action=isset($_GET['action']) ? $_GET['action'] : 'games';
$search=isset($_GET['search']) ? trim($_GET['search']) : '';
$gameId=isset($_GET['id']) ? trim($_GET['id']) : '';

$baseUrl = "https://www.freetogame.com/api";

// Define a URL de destino da API FreeToGame
if ($action==='detail'&&!empty($gameId)) {
    $apiUrl = $baseUrl."/game?id=".urlencode($gameId);
} else {
    $apiUrl = $baseUrl . "/games";
}

// Requisição cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

$response=curl_exec($ch);
$httpCode=curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Se o servidor conectar com sucesso à FreeToGame
if ($httpCode===200&&!empty($response)) {
    
    // Se for busca por texto na ação 'games', filtra os resultados localmente no PHP
    if ($action==='games'&&!empty($search)) {
        $gamesList=jsonde_code($response, true);
        if (is_array($gamesList)) {
            $filtered=array_values(array_filter($gamesList, function($game)use($search) {
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
    // FALLBACK: Retorno local simulado se a hospedagem bloquear conexões de saída
    if ($action === 'detail') {
        echo json_encode([
            "id" => $gameId ? $gameId : "540",
            "title" => "Overwatch 2 (Offline Mock)",
            "thumbnail" => "https://www.freetogame.com/g/540/thumbnail.jpg",
            "short_description" => "A hero-based tactical shooter set in a optimistic future.",
            "description" => "Overwatch 2 is a team-based action game set in the optimistic future, where every match is the ultimate 5v5 battlefield brawl.",
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
            ],
            [
                "id" => 521,
                "title" => "Diablo Immortal",
                "thumbnail" => "https://www.freetogame.com/g/521/thumbnail.jpg",
                "short_description" => "A brand-new game in Blizzard's genre-defining action-roleplaying game series.",
                "game_url" => "https://www.freetogame.com/open/diablo-immortal",
                "genre" => "ARPG",
                "platform" => "PC (Windows)",
                "publisher" => "Blizzard Entertainment",
                "developer" => "Blizzard Entertainment",
                "release_date" => "2022-06-02"
            ],
            [
                "id" => 517,
                "title" => "Lost Ark",
                "thumbnail" => "https://www.freetogame.com/g/517/thumbnail.jpg",
                "short_description" => "Embark on an odyssey for the Lost Ark in a vast, vibrant world.",
                "game_url" => "https://www.freetogame.com/open/lost-ark",
                "genre" => "ARPG",
                "platform" => "PC (Windows)",
                "publisher" => "Amazon Games",
                "developer" => "Smilegate RPG",
                "release_date" => "2022-02-11"
            ]
        ]);
    }
}
?>
