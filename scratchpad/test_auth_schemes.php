<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$apiKey = env('OLLAMA_API_KEY');
$url = 'https://ollama.com/api/tags'; // Or /api/version or /v1/models

echo "Testing authentication methods for ollama.com with key...\n";

// Method 1: Bearer token
$r1 = Http::withToken($apiKey)->get('https://ollama.com/api/tags');
echo "1. Bearer: " . $r1->status() . " | " . $r1->body() . "\n";

// Method 2: Header Authorization without 'Bearer ' or direct
$r2 = Http::withHeaders(['Authorization' => $apiKey])->get('https://ollama.com/api/tags');
echo "2. Raw Header: " . $r2->status() . " | " . $r2->body() . "\n";

// Method 3: X-API-Key
$r3 = Http::withHeaders(['X-API-Key' => $apiKey])->get('https://ollama.com/api/tags');
echo "3. X-API-Key: " . $r3->status() . " | " . $r3->body() . "\n";

// Method 4: Query param
$r4 = Http::get('https://ollama.com/api/tags?api_key=' . $apiKey);
echo "4. Query param: " . $r4->status() . " | " . $r4->body() . "\n";

// Method 5: /v1/models with Bearer
$r5 = Http::withToken($apiKey)->get('https://ollama.com/v1/models');
echo "5. /v1/models Bearer: " . $r5->status() . " | " . $r5->body() . "\n";

// Method 6: Basic auth with key as username or password
$r6 = Http::withBasicAuth($apiKey, '')->get('https://ollama.com/api/tags');
echo "6. Basic auth: " . $r6->status() . " | " . $r6->body() . "\n";

