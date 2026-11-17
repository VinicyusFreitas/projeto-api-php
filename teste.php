<?php
require_once 'supabase.php';
/**
 * Consome a API do FreeToGame para buscar jogos gratuitos.
 *
 * @param string|null $category Opcional. Ex: 'shooter', 'anime', 'pvp', 'mmorpg'
 * @param int $timeout Tempo limite de resposta em segundos
 * @return array
 */
function fetchFreeToGames(?string $category = null, int $timeout = 10): array {
    // Define a URL com ou sem o filtro de categoria
    $baseUrl = 'https://www.freetogame.com/api/games';
    $url = $category ? $baseUrl . '?category=' . urlencode($category) : $baseUrl;

    $ch = curl_init();

    // Configurações cURL
    curl_setopt_array($ch, [
        CURLOPT_SSL_VERIFYPEER => False,
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'User-Agent: MeuBackendPHP/1.0'
        ],
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    // 1. Tratamento de erro de rede ou timeout
    if ($response === false) {
        return [
            'success' => false,
            'error'   => 'Erro de conexão/timeout: ' . $curlError
        ];
    }

    // 2. Tratamento de respostas HTTP divergentes de 200 OK
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'status'  => $httpCode,
            'error'   => 'A API retornou o código HTTP ' . $httpCode
        ];
    }

    // 3. Decodificação e validação do JSON
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'error'   => 'Falha ao decodificar JSON: ' . json_last_error_msg()
        ];
    }

    return [
        'success' => true,
        'status'  => $httpCode,
        'data'    => $data
    ];
}

// ============================================================================
// Exemplo de Uso Prático
// ============================================================================

// Buscando jogos da categoria "shooter" (Tiro)
$result = fetchFreeToGames('shooter');

if ($result['success']) {
    echo "Sucesso! Foram encontrados " . count($result['data']) . " jogos.\n\n";

    // Exibe os 5 primeiros resultados
    $topGames = array_slice($result['data'], 0, 5);

    foreach ($topGames as $game) {
        echo "----------------------------------------\n";
        echo "Título: " . $game['title'] . "\n";
        echo "Gênero: " . $game['genre'] . "\n";
        echo "Plataforma: " . $game['platform'] . "\n";
        echo "Desenvolvedora: " . $game['developer'] . "\n";
        echo "Link: " . $game['game_url'] . "\n";
    }
} else {
    echo "Erro na requisição: " . $result['error'] . "\n";
}

// Exemplo: Função para salvar um jogo vindo da FreeToGame no Supabase
function salvarJogoNoSupabase(array $jogo, int $usuarioId =1): array {
    $dadosParaInserir = [
        'usuario_id' => $usuarioId,
        'jogo_id_api' => $jogo['id']
    ];

    // Faz um POST para a tabela 'jogos' do Supabase
    return supabaseRequest('favoritos', 'POST', $dadosParaInserir);
}

// Exemplo: Função para buscar todos os jogos salvos no Supabase
function buscarJogosDoSupabase(): array {
    // Faz um GET na tabela 'jogos' (selecionando todas as colunas)
    return supabaseRequest('favoritos?select=*', 'GET');
}
echo "\n=== TESTE SUPABASE ===\n";
print_r(buscarJogosDoSupabase());

echo "\n=== TESTE INSERT FAVORITOS ===\n";
print_r(salvarJogoNoSupabase($topGames[0]));
    

?>
