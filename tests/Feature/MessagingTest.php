<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('t', 32))]);
        config(['messaging.enabled' => true, 'messaging.base_url' => 'https://messaging.test', 'messaging.operator_key' => str_repeat('o', 32), 'messaging.admin_key' => str_repeat('a', 32)]);
        $migration = require database_path('migrations/2026_09_17_000001_create_messaging_submissions_table.php');
        $migration->up();
        Http::preventStrayRequests();
    }

    private function user(string $role): User
    {
        $user = new User(['name' => 'Operator Test', 'role' => $role, 'email' => 'test@example.com']);
        $user->id = 42;
        return $user;
    }

    public function test_guest_and_operator_cannot_manage_connection(): void
    {
        $this->get('/dashboard/messaging')->assertRedirect(route('login'));
        $this->actingAs($this->user('operator'))->post('/dashboard/messaging/connection/connect')->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_admin_connection_uses_admin_key(): void
    {
        Http::fake(['messaging.test/*' => Http::response(['status' => 'CONNECTING'])]);
        $this->actingAs($this->user('admin'))->post('/dashboard/messaging/connection/connect')->assertRedirect();
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer '.str_repeat('a', 32)) && $request->hasHeader('X-SID-Actor-Id', '42'));
    }

    public function test_contact_list_uses_operator_key_and_does_not_expose_secret(): void
    {
        Http::fake(['messaging.test/*' => Http::response(['items' => [], 'pagination' => ['page' => 1, 'perPage' => 20, 'total' => 0, 'lastPage' => 1]])]);
        $this->actingAs($this->user('operator'))->get('/dashboard/messaging/contacts')->assertOk()->assertDontSee(str_repeat('o', 32))->assertDontSee(str_repeat('a', 32));
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer '.str_repeat('o', 32)));
    }

    public function test_backend_offline_is_not_an_empty_successful_list(): void
    {
        Http::fake(['messaging.test/*' => Http::response(['message' => 'internal secret'], 503)]);
        $this->actingAs($this->user('operator'))->get('/dashboard/messaging/contacts')->assertStatus(503)->assertSee('Layanan pesan')->assertDontSee('internal secret');
    }

    public function test_html_form_boolean_fields_are_sent_as_json_booleans(): void
    {
        Http::fake(['messaging.test/*' => Http::response(['id' => 'test'])]);
        $this->actingAs($this->user('operator'))->post('/dashboard/messaging/contacts', ['fullName' => 'Rumah Satu', 'phone' => '081234567890', 'whatsappOptIn' => '1'])->assertRedirect();
        Http::assertSent(fn ($request) => $request['whatsappOptIn'] === true);
        $this->patch('/dashboard/messaging/templates/c53dfbb3-58d3-4e92-bf85-32f39f317064', ['isActive' => '0'])->assertRedirect();
        Http::assertSent(fn ($request) => ($request->data()['isActive'] ?? null) === false);
    }

    public function test_disabled_module_returns_404(): void
    {
        config(['messaging.enabled' => false]);
        $this->actingAs($this->user('operator'))->get('/dashboard/messaging')->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_replay_create_uses_ledger_and_never_posts_twice(): void
    {
        $id = 'c53dfbb3-58d3-4e92-bf85-32f39f317064';
        Http::fake(['messaging.test/*' => Http::response(['id' => $id], 201)]);
        $payload = ['request_uuid' => '7f04a191-7eef-4cef-9970-7e4c7d7fa72e', 'name' => 'Pengumuman', 'content' => 'Halo {{nama}}', 'contactIds' => [$id], 'batchSize' => 10, 'previewToken' => 'signed-preview'];
        $this->actingAs($this->user('operator'))->post('/dashboard/messaging/campaigns', $payload)->assertRedirect('/dashboard/messaging/campaigns/'.$id);
        $this->post('/dashboard/messaging/campaigns', $payload)->assertRedirect('/dashboard/messaging/campaigns/'.$id);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->hasHeader('Idempotency-Key', $payload['request_uuid']));
        $this->assertDatabaseHas('messaging_submissions', ['request_uuid' => $payload['request_uuid'], 'status' => 'CONFIRMED', 'campaign_id' => $id]);
    }

    public function test_mutation_requires_csrf_outside_test_bypass(): void
    {
        $this->actingAs($this->user('admin'));
        $this->app['env'] = 'production';
        $this->post('/dashboard/messaging/connection/connect')->assertStatus(419);
        Http::assertNothingSent();
    }

    public function test_multiline_template_preview_and_html_draft_have_identical_payloads(): void
    {
        $ids = ['c53dfbb3-58d3-4e92-bf85-32f39f317064', '7f04a191-7eef-4cef-9970-7e4c7d7fa72e'];
        $previewPayload = null;
        $createPayload = null;
        Http::fake(function ($request) use (&$previewPayload, &$createPayload, $ids) {
            if (str_ends_with($request->url(), '/preview')) {
                $previewPayload = $request->data();
                return Http::response(['previewToken' => 'signed', 'recipientCount' => 2, 'samples' => []]);
            }
            $createPayload = $request->data();
            unset($createPayload['previewToken']);
            // JSON object field order is not meaningful; keep value types strict.
            ksort($previewPayload);
            ksort($createPayload);
            return $createPayload === $previewPayload
                ? Http::response(['id' => $ids[0]], 201)
                : Http::response(['message' => 'Isi atau penerima berubah. Tinjau campaign kembali.'], 409);
        });
        $payload = ['name' => 'Informasi desa', 'content' => "Halo {{nama}},\n\nInformasi terbaru:\nhttps://desa.test", 'templateId' => $ids[0], 'contactIds' => $ids, 'batchSize' => 10, 'useBanner' => false, 'useInteractiveCta' => false];
        $this->actingAs($this->user('operator'))->postJson('/dashboard/messaging/campaigns/preview', $payload)->assertOk();
        // Native HTML textarea submission uses CRLF, unlike the JSON preview.
        $payload['content'] = str_replace("\n", "\r\n", $payload['content']);
        $payload['contactIds'] = array_reverse($ids);
        $payload['batchSize'] = '10';
        unset($payload['useBanner'], $payload['useInteractiveCta']);
        $response = $this->from('/dashboard/messaging/campaigns/create')->post('/dashboard/messaging/campaigns', [...$payload, 'request_uuid' => '008efbf6-447f-4e6f-bad9-2156f592d86a', 'previewToken' => 'signed']);
        $this->assertSame($previewPayload, $createPayload);
        $response->assertRedirect('/dashboard/messaging/campaigns/'.$ids[0])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('messaging_submissions', ['request_uuid' => '008efbf6-447f-4e6f-bad9-2156f592d86a', 'status' => 'CONFIRMED']);
    }

    public function test_all_module_views_render_without_exposing_keys(): void
    {
        $id = 'c53dfbb3-58d3-4e92-bf85-32f39f317064';
        Http::fake(function ($request) use ($id) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if (str_ends_with($path, '/settings')) return Http::response(['defaultBannerUrl' => 'https://media.test/banner.png', 'interactiveCtaEnabled' => false]);
            if (str_ends_with($path, '/dashboard')) return Http::response(['whatsapp' => ['status' => 'DISCONNECTED']]);
            if (str_ends_with($path, '/status')) return Http::response(['status' => 'DISCONNECTED', 'phoneNumber' => null, 'qrDataUrl' => null]);
            if (str_ends_with($path, '/'.$id)) return Http::response(['id' => $id, 'name' => 'Informasi', 'status' => 'DRAFT', 'recipientCount' => 1, 'batchSize' => 10, 'jobs' => ['QUEUED' => 1], 'contentSnapshot' => 'Halo']);
            return Http::response(['items' => [], 'pagination' => ['page' => 1, 'perPage' => 20, 'total' => 0, 'lastPage' => 1]]);
        });
        $this->actingAs($this->user('admin'));
        foreach (['', '/templates', '/campaigns', '/campaigns/create', '/campaigns/'.$id, '/history', '/connection'] as $path) {
            $response = $this->get('/dashboard/messaging'.$path)->assertOk()->assertDontSee(str_repeat('a', 32));
            foreach (['dispatcher', 'instance API', 'Request yang perlu diperiksa', 'Session tersimpan'] as $label) $response->assertDontSee($label);
        }
    }

    public function test_create_timeout_preserves_key_and_can_be_reconciled(): void
    {
        $id = 'c53dfbb3-58d3-4e92-bf85-32f39f317064';
        $key = '7f04a191-7eef-4cef-9970-7e4c7d7fa72e';
        $offline = true;
        Http::fake(function () use (&$offline, $id) {
            if ($offline) throw new \Illuminate\Http\Client\ConnectionException('timeout');
            return Http::response(['id' => $id]);
        });
        $payload = ['request_uuid' => $key, 'name' => 'Informasi', 'content' => 'Halo', 'contactIds' => [$id], 'batchSize' => 10, 'previewToken' => 'signed'];
        $this->actingAs($this->user('operator'))->from('/dashboard/messaging/campaigns/create')->post('/dashboard/messaging/campaigns', $payload)->assertRedirect()->assertSessionHas('messaging_uncertain_key', $key)->assertSessionHasInput('request_uuid', $key);
        $this->assertDatabaseHas('messaging_submissions', ['request_uuid' => $key, 'status' => 'UNCERTAIN']);
        $offline = false;
        $this->get('/dashboard/messaging/campaigns/reconcile/'.$key)->assertRedirect('/dashboard/messaging/campaigns/'.$id);
        $this->assertDatabaseHas('messaging_submissions', ['request_uuid' => $key, 'status' => 'CONFIRMED']);
    }

    public function test_message_excerpt_is_collapsed_and_full_content_is_escaped(): void
    {
        $content = str_repeat('Informasi untuk warga desa. ', 10)."\n<script>alert('x')</script>";
        $html = view('dashboard.messaging.message-content', ['text' => $content])->render();
        $this->assertStringContainsString('data-message-detail', $html);
        $this->assertStringNotContainsString(' open', $html);
        $this->assertStringContainsString('Informasi untuk warga desa.', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        preg_match('/<summary[^>]*><span>(.*?)<\/span>/s', $html, $excerpt);
        $this->assertLessThanOrEqual(113, mb_strlen(html_entity_decode($excerpt[1])));
    }

    public function test_populated_history_and_templates_use_expandable_messages(): void
    {
        $id = 'c53dfbb3-58d3-4e92-bf85-32f39f317064';
        Http::fake(function ($request) use ($id) {
            $item = str_contains($request->url(), '/templates')
                ? ['id' => $id, 'name' => 'Informasi', 'content' => "Halo {{nama}}\nInformasi desa", 'isActive' => true]
                : ['id' => $id, 'recipient' => '6281234567890', 'renderedMessage' => "Halo warga\nInformasi desa", 'status' => 'SENT', 'deliveryStatus' => 'PENDING', 'attempts' => 1, 'maxAttempts' => 3, 'errorMessage' => null];
            return Http::response(['items' => [$item], 'pagination' => ['page' => 1, 'perPage' => 20, 'total' => 1, 'lastPage' => 1]]);
        });
        $this->actingAs($this->user('operator'));
        foreach (['/history', '/templates'] as $path) $this->get('/dashboard/messaging'.$path)->assertOk()->assertSee('data-message-detail', false)->assertSee('messaging-content-cell', false);
    }

    public function test_rejected_preview_preserves_form_and_can_be_reviewed_again(): void
    {
        Http::fake(['messaging.test/*' => Http::response(['message' => 'Isi atau penerima berubah. Tinjau campaign kembali.'], 409)]);
        $payload = ['request_uuid' => '7f04a191-7eef-4cef-9970-7e4c7d7fa72e', 'name' => 'Informasi', 'content' => "Pesan baru\nBaris kedua", 'contactIds' => ['c53dfbb3-58d3-4e92-bf85-32f39f317064'], 'batchSize' => 10, 'previewToken' => 'old'];
        $this->actingAs($this->user('operator'))->from('/dashboard/messaging/campaigns/create')->post('/dashboard/messaging/campaigns', $payload)
            ->assertRedirect('/dashboard/messaging/campaigns/create')->assertSessionHas('error')->assertSessionHasInput('content', $payload['content'])->assertSessionHasInput('contactIds', $payload['contactIds']);
        $this->assertDatabaseHas('messaging_submissions', ['request_uuid' => $payload['request_uuid'], 'status' => 'REJECTED', 'campaign_id' => null]);
    }

    public function test_connection_visual_matches_each_state_and_hides_internal_labels(): void
    {
        $state = [];
        Http::fake(function () use (&$state) { return Http::response($state); });
        $this->actingAs($this->user('admin'));
        foreach ([
            'CONNECTED' => ['ready', 'WhatsApp terhubung'],
            'CONNECTING' => ['pairing', 'Menghubungkan WhatsApp'],
            'QR_READY' => ['pairing', 'Pindai kode QR'],
            'DISCONNECTED' => ['offline', 'WhatsApp belum terhubung'],
            'NEEDS_REAUTH' => ['warning', 'Hubungkan kembali WhatsApp'],
        ] as $status => [$tone, $title]) {
            $state = ['status' => $status, 'phoneNumber' => '6281234567890', 'authPersistence' => 'healthy', 'reason' => 'SESSION_IN_USE', 'qrDataUrl' => $status === 'QR_READY' ? 'data:image/png;base64,test' : null];
            $response = $this->get('/dashboard/messaging/connection')->assertOk()->assertSee($title)->assertSee('data-connection-state="'.$tone.'"', false)->assertSee('+6281234567890');
            foreach (['SESSION_IN_USE', 'instance API', 'database produksi', 'Session tersimpan'] as $label) $response->assertDontSee($label);
            if ($status === 'QR_READY') $response->assertSee('Tautkan akun dalam tiga langkah')->assertSee('data-refresh-seconds="5"', false);
            if ($status === 'CONNECTED') $response->assertSee('messaging-connection-icon--ready', false)->assertSee('Buat campaign');
        }
    }

    public function test_degraded_and_unknown_connection_never_show_ready_check(): void
    {
        $state = ['status' => 'CONNECTED', 'authPersistence' => 'degraded', 'reason' => 'AUTH_PERSISTENCE_FAILED'];
        Http::fake(function () use (&$state) { return Http::response($state); });
        $this->actingAs($this->user('admin'))->get('/dashboard/messaging/connection')->assertOk()->assertSee('Koneksi perlu diperiksa')->assertSee('data-connection-state="warning"', false)->assertDontSee('messaging-connection-icon--ready', false)->assertDontSee('AUTH_PERSISTENCE_FAILED');
        $state = ['status' => 'UNRECOGNIZED', 'reason' => 'INTERNAL_DATABASE_ERROR'];
        $this->get('/dashboard/messaging/connection')->assertOk()->assertSee('Status belum tersedia')->assertDontSee('messaging-connection-icon--ready', false)->assertDontSee('INTERNAL_DATABASE_ERROR');
    }
}
