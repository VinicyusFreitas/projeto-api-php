<?php
define('SUPABASE_URL', 'https://viitrkysujqunawxgldu.supabase.co'); // Substitui pela tua URL do Supabase
define('SUPABASE_KEY', 'sb_publishable_3XDjsTeLP0zf0emFUhJ1Mw_Ztxjr-h6');           // Substitui pela tua chave do Supabase

function supabaseRequest(string $endpoint, string $method = 'GET', array $dados = null): array {
    $url = SUPABASE_URL . '/rest/v1/' . $endpoint;

    $ch = curl_init();
    
    $options = [
        CURLOPT_SSL_VERIFYPEER => False,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => [
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ],
    ];

    if ($dados !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $options[CURLOPT_POSTFIELDS] = json_encode($dados);
    }

    curl_setopt_array($ch, $options);

    $resposta = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        return ['sucesso' => true, 'dados' => json_decode($resposta, true)];
    } else {
        return ['sucesso' => false, 'erro' => json_decode($resposta, true), 'curl_erro' => $curlError, 'status' => $httpCode];
    }
}
?>
