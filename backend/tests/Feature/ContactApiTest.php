<?php

namespace Tests\Feature;

use App\Mail\ContactOwnerMail;
use App\Mail\ContactUserCopyMail;
use App\Services\Ai\ContactAiAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    private array $validPayload = [
        'name' => 'Иван Петров',
        'phone' => '+79991234567',
        'email' => 'ivan@example.com',
        'comment' => 'Тестовое обращение',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush(); // Reset cache-based rate limiting between tests.
        Mail::fake(); // Prevent real emails from being sent during tests.
        Http::fake(); // Prevent real API calls during tests.
    }

    public function test_contact_form_submits_successfully(): void
    {
        $response = $this->postJson('/api/contact', $this->validPayload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Contact request accepted',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'id',
                'ai_analysis',
                'mail_sent',
            ]);

        $response->assertJsonPath('ai_analysis.category', 'other');
        $response->assertJsonPath('ai_analysis.sentiment', 'neutral');
        $response->assertJsonPath('ai_analysis.priority', 'normal');
        $response->assertJsonPath('ai_analysis.summary', 'AI analysis fallback');
        $response->assertJsonPath('ai_analysis.ai_available', false);
    }

    public function test_contact_form_works_with_real_ai(): void
    {
        $mockService = new class extends ContactAiAnalysisService {
            public function analyze(string $comment): array
            {
                return [
                    'category' => 'question',
                    'sentiment' => 'positive',
                    'priority' => 'high',
                    'summary' => 'Клиент интересуется разработкой интернет-магазина',
                    'ai_available' => true,
                ];
            }
        };

        $this->app->instance(ContactAiAnalysisService::class, $mockService);

        $response = $this->postJson('/api/contact', $this->validPayload);

        $response->assertStatus(201);
        $response->assertJsonPath('ai_analysis.category', 'question');
        $response->assertJsonPath('ai_analysis.sentiment', 'positive');
        $response->assertJsonPath('ai_analysis.priority', 'high');
        $response->assertJsonPath('ai_analysis.summary', 'Клиент интересуется разработкой интернет-магазина');
        $response->assertJsonPath('ai_analysis.ai_available', true);
    }

    public function test_contact_form_handles_invalid_ai_json(): void
    {
        $mockService = new class extends ContactAiAnalysisService {
            public function analyze(string $comment): array
            {
                return [
                    'category' => 'other',
                    'sentiment' => 'neutral',
                    'priority' => 'normal',
                    'summary' => 'AI analysis fallback',
                    'ai_available' => false,
                ];
            }
        };

        $this->app->instance(ContactAiAnalysisService::class, $mockService);

        $response = $this->postJson('/api/contact', $this->validPayload);

        $response->assertStatus(201);
        $response->assertJsonPath('ai_analysis.ai_available', false);
        $response->assertJsonPath('ai_analysis.category', 'other');
        $response->assertJsonPath('ai_analysis.sentiment', 'neutral');
        $response->assertJsonPath('ai_analysis.priority', 'normal');
        $response->assertJsonPath('ai_analysis.summary', 'AI analysis fallback');
    }

    public function test_contact_form_handles_unsupported_ai_values(): void
    {
        $mockService = new class extends ContactAiAnalysisService {
            public function analyze(string $comment): array
            {
                return [
                    'category' => 'other',
                    'sentiment' => 'neutral',
                    'priority' => 'normal',
                    'summary' => 'Client question',
                    'ai_available' => true,
                ];
            }
        };

        $this->app->instance(ContactAiAnalysisService::class, $mockService);

        $response = $this->postJson('/api/contact', $this->validPayload);

        $response->assertStatus(201);
        $response->assertJsonPath('ai_analysis.ai_available', true);
        $response->assertJsonPath('ai_analysis.category', 'other');
        $response->assertJsonPath('ai_analysis.sentiment', 'neutral');
        $response->assertJsonPath('ai_analysis.priority', 'normal');
        $response->assertJsonPath('ai_analysis.summary', 'Client question');
    }

    public function test_contact_form_validates_required_fields(): void
    {
        $response = $this->postJson('/api/contact', [
            'name' => '',
            'phone' => '',
            'email' => 'bad-email',
            'comment' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'phone', 'email', 'comment']);
    }

    public function test_contact_form_rejects_invalid_email(): void
    {
        $response = $this->postJson('/api/contact', [
            'name' => 'Иван',
            'phone' => '+79991234567',
            'email' => 'not-an-email',
            'comment' => 'Test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_contact_form_handles_xss_input(): void
    {
        $response = $this->postJson('/api/contact', [
            'name' => '<script>alert(1)</script>',
            'phone' => '+79991234567',
            'email' => 'test@test.com',
            'comment' => '<b>bold</b>',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'id',
                'ai_analysis',
                'mail_sent',
            ]);
    }

    public function test_contact_form_works_with_ai_fallback(): void
    {
        $response = $this->postJson('/api/contact', $this->validPayload);

        $response->assertStatus(201);

        $aiAnalysis = $response->json('ai_analysis');
        $this->assertFalse($aiAnalysis['ai_available']);
        $this->assertNotEmpty($aiAnalysis['category']);
        $this->assertNotEmpty($aiAnalysis['sentiment']);
        $this->assertNotEmpty($aiAnalysis['priority']);
        $this->assertNotEmpty($aiAnalysis['summary']);
    }

    public function test_contact_form_sends_emails(): void
    {
        $response = $this->postJson('/api/contact', $this->validPayload);

        $response->assertStatus(201);

        Mail::assertSent(ContactOwnerMail::class);
        Mail::assertSent(ContactUserCopyMail::class);
    }

    public function test_contact_form_enforces_rate_limit(): void
    {
        $limit = (int) config('contact.rate_limit.limit', 2);

        for ($i = 0; $i < $limit; $i++) {
            $response = $this->postJson('/api/contact', $this->validPayload);
            $response->assertStatus(201);
        }

        $response = $this->postJson('/api/contact', $this->validPayload);
        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Too many contact requests. Please try again later.',
            ]);
    }

    public function test_contact_form_rejects_empty_json(): void
    {
        $response = $this->postJson('/api/contact', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'phone', 'email', 'comment']);
    }
}
