<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\VisitorStatisticsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VisitorStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['visitor_statistics.enabled' => true]);
        $this->withHeader('User-Agent', 'Mozilla/5.0 Chrome/130.0 Safari/537.36');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-01 12:00:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_cookie_counts_once_across_pages_and_session_loss(): void
    {
        $token = str_repeat('a', 64);
        $this->withCookie('sid_visitor', $token)->get('/')->assertOk()->assertSee('Statistik Kunjungan');
        $this->withCookie('sid_visitor', $token)->get('/berita')->assertOk();
        $this->withSession(['visitor-statistics.counted' => null])
            ->withCookie('sid_visitor', $token)->get('/')->assertOk();

        $this->assertDatabaseCount('website_visitor_days', 1);
        $this->assertDatabaseHas('website_daily_stats', ['visit_date' => '2026-10-01', 'visitors' => 1]);
        $this->assertDatabaseMissing('website_visitor_days', ['visitor_hash' => $token]);

        $this->withCookie('sid_visitor', str_repeat('b', 64))->get('/berita')->assertOk();
        $this->assertDatabaseHas('website_daily_stats', ['visit_date' => '2026-10-01', 'visitors' => 2]);
    }

    public function test_first_visit_issues_encrypted_secure_cookie_and_session_fallback_deduplicates(): void
    {
        $response = $this->get('https://localhost/');
        $response->assertOk()->assertCookie('sid_visitor');
        $cookie = collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === 'sid_visitor');
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertDoesNotMatchRegularExpression('/\A[a-f0-9]{64}\z/', $cookie->getValue());
        $this->get('/berita')->assertOk();
        $this->assertDatabaseCount('website_visitor_days', 1);
    }

    public function test_daily_boundary_and_summary_use_wib_across_months(): void
    {
        $token = str_repeat('a', 64);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-30 16:59:59', 'UTC'));
        $this->withCookie('sid_visitor', $token)->get('/')->assertOk();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-30 17:00:00', 'UTC'));
        $this->withCookie('sid_visitor', $token)->get('/berita')->assertOk();
        Cache::flush();
        $summary = app(VisitorStatisticsService::class)->summary();

        $this->assertSame(1, $summary['today']);
        $this->assertSame(1, $summary['yesterday']);
        $this->assertSame(1, $summary['month']);
        $this->assertSame(2, $summary['total']);
        $this->assertSame('2026-09-30', $summary['started_on']);
        $this->assertNotSame(
            DB::table('website_visitor_days')->where('visit_date', '2026-09-30')->value('visitor_hash'),
            DB::table('website_visitor_days')->where('visit_date', '2026-10-01')->value('visitor_hash')
        );
    }

    public function test_bot_prefetch_head_ajax_and_health_requests_are_excluded(): void
    {
        $this->withHeader('User-Agent', 'Googlebot')->get('/')->assertOk();
        $this->withHeader('User-Agent', 'Mozilla/5.0')->withHeader('Sec-Purpose', 'prefetch')->get('/')->assertOk();
        $this->flushHeaders()->withHeader('User-Agent', 'Mozilla/5.0');
        $this->head('/')->assertOk();
        $this->get('/health')->assertOk();
        $this->get('/up')->assertOk();
        $this->withHeader('X-Requested-With', 'XMLHttpRequest')->get('/berita')->assertOk();
        $this->flushHeaders()->withHeader('User-Agent', 'Mozilla/5.0');
        $this->withHeader('Accept', 'application/json')->get('/berita')->assertOk();

        $this->assertDatabaseCount('website_daily_stats', 0);
        $this->assertDatabaseCount('website_visitor_days', 0);
    }

    public function test_admin_and_non_public_routes_are_excluded(): void
    {
        $this->get('/'.config('security.admin_login_path'))->assertOk();
        $this->get('/berita/tidak-ada')->assertNotFound();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/')->assertOk();
        $this->assertDatabaseCount('website_daily_stats', 0);
    }

    public function test_non_html_redirect_and_post_responses_are_not_counted(): void
    {
        config(['visitor_statistics.routes' => ['visitor-test']]);
        Route::middleware('web')->match(['get', 'post'], '/visitor-test', fn () => response('OK', 200)
            ->header('Content-Type', 'text/plain'))->name('visitor-test');
        $this->get('/visitor-test')->assertOk();
        $this->post('/visitor-test')->assertOk();
        Route::middleware('web')->get('/visitor-redirect', fn () => redirect('/'))->name('visitor-test');
        $this->get('/visitor-redirect')->assertRedirect('/');
        $this->assertDatabaseCount('website_daily_stats', 0);
    }

    public function test_disabled_statistics_hide_widget_and_do_not_record(): void
    {
        config(['visitor_statistics.enabled' => false]);
        $this->get('/')->assertOk()->assertDontSee('Statistik Kunjungan')->assertCookieMissing('sid_visitor');
        $this->assertDatabaseCount('website_daily_stats', 0);
    }

    public function test_missing_tables_do_not_break_public_pages_or_display_fake_zero(): void
    {
        Schema::drop('website_daily_stats');
        $this->get('/')->assertOk()->assertSee('Statistik sementara tidak tersedia.');
        $this->assertDatabaseCount('website_visitor_days', 0);
    }

    public function test_cache_is_reused_and_changes_at_midnight(): void
    {
        DB::table('website_daily_stats')->insert(['visit_date' => '2026-10-01', 'visitors' => 1250]);
        $statistics = app(VisitorStatisticsService::class);
        $this->assertSame(1250, $statistics->summary()['today']);
        DB::table('website_daily_stats')->where('visit_date', '2026-10-01')->increment('visitors');
        $this->assertSame(1250, $statistics->summary()['today']);
        $this->get('/')->assertOk()->assertSee('1.250')->assertSee('01/10/2026');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-02 00:00:00', 'Asia/Jakarta'));
        $this->assertSame(0, $statistics->summary()['today']);
        $this->assertSame(1252, $statistics->summary()['yesterday']);
    }

    public function test_pruning_is_bounded_and_preserves_historical_totals(): void
    {
        $statistics = app(VisitorStatisticsService::class);
        $statistics->record('2026-08-01', $statistics->identity(str_repeat('a', 64), '2026-08-01'));
        $statistics->record('2026-08-01', $statistics->identity(str_repeat('b', 64), '2026-08-01'));
        $statistics->record('2026-10-01', $statistics->identity(str_repeat('a', 64), '2026-10-01'));
        $this->assertSame(1, $statistics->prune(1));
        $this->assertDatabaseCount('website_visitor_days', 2);
        $this->artisan('visitor-statistics:prune')->assertSuccessful();
        $this->assertDatabaseCount('website_visitor_days', 1);
        $this->assertSame(3, $statistics->summary()['total']);
    }
}
