<?php

namespace Tests\Unit;

use App\Exceptions\AiDisabledException;
use App\Exceptions\AiNotConfiguredException;
use App\Filament\Resources\AiTokens\AiTokenResource;
use App\Filament\Resources\Surats\Actions\AiDraftAction;
use App\Models\AiToken;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AiDraftManager;
use App\Services\GeminiService;
use App\Services\OllamaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AiDraftManagerTest extends TestCase
{
    use RefreshDatabase;

    protected AiDraftManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new AiDraftManager();
    }

    public function test_global_toggle_disabled_throws_ai_disabled_exception(): void
    {
        AiDraftManager::setFeatureEnabled(false);
        $this->assertFalse(AiDraftManager::isFeatureEnabled());

        $this->expectException(AiDisabledException::class);
        $this->manager->resolveActiveService();
    }

    public function test_active_db_token_resolves_gemini_service(): void
    {
        AiDraftManager::setFeatureEnabled(true);

        AiToken::create([
            'provider' => 'gemini',
            'label' => 'Gemini Test Token',
            'api_key' => 'AIzaSyTestGeminiKey12345',
            'model' => 'gemini-2.5-flash',
            'is_active' => true,
        ]);

        $service = $this->manager->resolveActiveService();

        $this->assertInstanceOf(GeminiService::class, $service);
        $this->assertEquals('gemini-2.5-flash', $service->getModel());
    }

    public function test_active_db_token_resolves_ollama_service(): void
    {
        AiDraftManager::setFeatureEnabled(true);

        AiToken::create([
            'provider' => 'ollama',
            'label' => 'Ollama Local Token',
            'base_url' => 'http://192.168.1.100:11434',
            'model' => 'llama3.2',
            'is_active' => true,
        ]);

        $service = $this->manager->resolveActiveService();

        $this->assertInstanceOf(OllamaService::class, $service);
        $this->assertEquals('http://192.168.1.100:11434', $service->getBaseUrl());
        $this->assertEquals('llama3.2', $service->getModel());
    }

    public function test_fallback_to_config_gemini_when_no_active_db_token(): void
    {
        AiDraftManager::setFeatureEnabled(true);
        AiToken::query()->delete();

        Config::set('services.gemini.api_key', 'config_gemini_key_abc');

        $service = $this->manager->resolveActiveService();

        $this->assertInstanceOf(GeminiService::class, $service);
    }

    public function test_fallback_to_config_ollama_when_no_active_db_token_and_no_gemini_key(): void
    {
        AiDraftManager::setFeatureEnabled(true);
        AiToken::query()->delete();

        Config::set('services.gemini.api_key', null);
        Config::set('services.ollama.api_key', 'ollama_secret_key');

        $service = $this->manager->resolveActiveService();

        $this->assertInstanceOf(OllamaService::class, $service);
    }

    public function test_zero_token_throws_ai_not_configured_exception(): void
    {
        AiDraftManager::setFeatureEnabled(true);
        AiToken::query()->delete();

        Config::set('services.gemini.api_key', null);
        Config::set('services.ollama.api_key', null);
        Config::set('services.ollama.base_url', 'http://localhost:11434');
        if (class_exists(\Illuminate\Support\Env::class)) {
            \Illuminate\Support\Env::getRepository()->set('OLLAMA_BASE_URL', 'http://localhost:11434');
        }
        putenv('OLLAMA_BASE_URL=http://localhost:11434');
        $_ENV['OLLAMA_BASE_URL'] = 'http://localhost:11434';

        $this->expectException(AiNotConfiguredException::class);
        $this->manager->resolveActiveService();
    }

    public function test_single_active_token_enforcement_on_save(): void
    {
        $token1 = AiToken::create([
            'provider' => 'gemini',
            'label' => 'Token 1',
            'api_key' => 'key1',
            'is_active' => true,
        ]);

        $this->assertTrue($token1->fresh()->is_active);

        $token2 = AiToken::create([
            'provider' => 'ollama',
            'label' => 'Token 2',
            'base_url' => 'http://localhost:11434',
            'is_active' => true,
        ]);

        $this->assertFalse($token1->fresh()->is_active);
        $this->assertTrue($token2->fresh()->is_active);

        $token1->activate();

        $this->assertTrue($token1->fresh()->is_active);
        $this->assertFalse($token2->fresh()->is_active);
    }

    public function test_masked_api_key_attribute(): void
    {
        $token = new AiToken([
            'provider' => 'gemini',
            'label' => 'Masked Test',
            'api_key' => 'AIzaSy1234567890abcdef',
        ]);

        $this->assertEquals('AIzaSy12••••••••', $token->masked_api_key);

        $emptyToken = new AiToken(['provider' => 'ollama', 'label' => 'Empty Key']);
        $this->assertEquals('-', $emptyToken->masked_api_key);
    }

    public function test_system_setting_helpers(): void
    {
        SystemSetting::set('test_key', 'some_value');
        $this->assertEquals('some_value', SystemSetting::get('test_key'));

        SystemSetting::set('bool_flag', true);
        $this->assertTrue(SystemSetting::isTrue('bool_flag'));

        SystemSetting::set('bool_flag', false);
        $this->assertFalse(SystemSetting::isTrue('bool_flag'));

        $this->assertFalse(SystemSetting::isTrue('non_existent_key', false));
        $this->assertTrue(SystemSetting::isTrue('non_existent_key', true));
    }

    public function test_ai_token_resource_can_only_be_accessed_by_admin(): void
    {
        Auth::logout();
        $this->assertFalse(AiTokenResource::canAccess());

        $admin = new User(['tipe_entitas' => 'ADMIN']);
        Auth::setUser($admin);
        $this->assertTrue(AiTokenResource::canAccess());

        $staf = new User(['tipe_entitas' => 'STAF']);
        Auth::setUser($staf);
        $this->assertFalse(AiTokenResource::canAccess());
    }

    public function test_ai_draft_action_is_accessible_reflects_global_toggle_and_roles(): void
    {
        Auth::logout();
        AiDraftManager::setFeatureEnabled(true);
        $this->assertFalse(AiDraftAction::isAccessible());

        $admin = new User(['tipe_entitas' => 'ADMIN']);
        Auth::setUser($admin);
        $this->assertFalse(AiDraftAction::isAccessible());

        $staf = new User(['tipe_entitas' => 'STAF']);
        Auth::setUser($staf);
        $this->assertTrue(AiDraftAction::isAccessible());

        AiDraftManager::setFeatureEnabled(false);
        $this->assertFalse(AiDraftAction::isAccessible());
    }

    public function test_test_connection_helper_returns_structured_result(): void
    {
        $mockService = new class extends \App\Services\BaseAiDraftService {
            public function chat(array $messages, ?string $model = null): string
            {
                return 'OK';
            }
        };

        $result = $this->manager->testConnection($mockService);
        $this->assertTrue($result['success']);
        $this->assertEquals('OK', $result['response']);

        $failingService = new class extends \App\Services\BaseAiDraftService {
            public function chat(array $messages, ?string $model = null): string
            {
                throw new \RuntimeException('Koneksi timeout');
            }
        };

        $resultFail = $this->manager->testConnection($failingService);
        $this->assertFalse($resultFail['success']);
        $this->assertEquals('Koneksi timeout', $resultFail['message']);
    }
}
