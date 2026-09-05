<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$baseUrl = rtrim(env('OLLAMA_BASE_URL', 'https://ollama.com/api'), '/');
$apiKey = env('OLLAMA_API_KEY');
$model = env('OLLAMA_MODEL', 'gpt-oss:20b');

echo "Testing Ollama config:\n";
echo "Base URL: {$baseUrl}\n";
echo "Model: {$model}\n";
echo "API Key set: " . (!empty($apiKey) ? "YES" : "NO") . "\n";

// Test 1: Try native Ollama /chat (if URL ends in /api, append /chat; else /api/chat)
$chatUrl = str_ends_with($baseUrl, '/api') ? "{$baseUrl}/chat" : "{$baseUrl}/api/chat";
echo "\n--- TEST 1: POST {$chatUrl} ---\n";
try {
    $client = Http::timeout(15);
    if (!empty($apiKey)) {
        $client = $client->withToken($apiKey);
    }
    
    $response = $client->post($chatUrl, [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => 'Halo, ini uji coba koneksi. Balas singkat "OK".']
        ],
        'stream' => false,
    ]);
    
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . substr($response->body(), 0, 500) . "\n";
} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

// Test 2: Try /api/generate or /generate
$genUrl = str_ends_with($baseUrl, '/api') ? "{$baseUrl}/generate" : "{$baseUrl}/api/generate";
echo "\n--- TEST 2: POST {$genUrl} ---\n";
try {
    $client = Http::timeout(15);
    if (!empty($apiKey)) {
        $client = $client->withToken($apiKey);
    }
    
    $response = $client->post($genUrl, [
        'model' => $model,
        'prompt' => 'Halo, ini uji coba koneksi. Balas singkat "OK".',
        'stream' => false,
    ]);
    
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . substr($response->body(), 0, 500) . "\n";
} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

// Test 3: Try /v1/chat/completions (OpenAI compatible)
$v1Base = str_ends_with($baseUrl, '/api') ? substr($baseUrl, 0, -4) : $baseUrl;
$openAiUrl = "{$v1Base}/v1/chat/completions";
echo "\n--- TEST 3: POST {$openAiUrl} ---\n";
try {
    $client = Http::timeout(15);
    if (!empty($apiKey)) {
        $client = $client->withToken($apiKey);
    }
    
    $response = $client->post($openAiUrl, [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => 'Halo, ini uji coba koneksi. Balas singkat "OK".']
        ],
    ]);
    
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . substr($response->body(), 0, 500) . "\n";
} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
