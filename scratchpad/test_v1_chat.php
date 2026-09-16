<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$apiKey = env('OLLAMA_API_KEY');
$model = env('OLLAMA_MODEL', 'gpt-oss:20b');

echo "Test A: withBasicAuth(\$apiKey, '') on /v1/chat/completions\n";
$r1 = Http::timeout(20)->withBasicAuth($apiKey, '')->post('https://ollama.com/v1/chat/completions', [
    'model' => $model,
    'messages' => [['role' => 'user', 'content' => 'Halo']]
]);
echo "A. Status: " . $r1->status() . " | " . $r1->body() . "\n";

echo "Test B: withBasicAuth(\$apiKey, '') on /api/chat\n";
$r2 = Http::timeout(20)->withBasicAuth($apiKey, '')->post('https://ollama.com/api/chat', [
    'model' => $model,
    'messages' => [['role' => 'user', 'content' => 'Halo']],
    'stream' => false
]);
echo "B. Status: " . $r2->status() . " | " . $r2->body() . "\n";

echo "Test C: withBasicAuth(\$apiKey, '') on /api/generate\n";
$r3 = Http::timeout(20)->withBasicAuth($apiKey, '')->post('https://ollama.com/api/generate', [
    'model' => $model,
    'prompt' => 'Halo',
    'stream' => false
]);
echo "C. Status: " . $r3->status() . " | " . $r3->body() . "\n";
