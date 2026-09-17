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
            $this->get('/dashboard/messaging'.$path)->assertOk()->assertDontSee(str_repeat('a', 32));
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
}
