<?php

namespace App\Http\Middleware;

use App\Services\VisitorStatisticsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackWebsiteVisitor
{
    public function __construct(private VisitorStatisticsService $statistics) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldTrack($request, $response)) {
            return $response;
        }

        try {
            $cookieName = config('visitor_statistics.cookie_name', 'sid_visitor');
            $token = $request->cookie($cookieName);
            $hasCookie = $this->validToken($token);

            if (! $hasCookie) {
                $token = $request->session()->get('visitor-statistics.token');
                if (! $this->validToken($token)) {
                    $token = bin2hex(random_bytes(32));
                }
                $request->session()->put('visitor-statistics.token', $token);
            }

            $date = $this->statistics->today()->toDateString();
            $identity = $this->statistics->identity($token, $date);
            $marker = $date.':'.$identity;

            if ($request->session()->get('visitor-statistics.counted') !== $marker) {
                $this->statistics->record($date, $identity);
                $request->session()->put('visitor-statistics.counted', $marker);
                $this->statistics->pruneOccasionally();
            }

            if (! $hasCookie) {
                $response->headers->setCookie(Cookie::make(
                    $cookieName, $token, (int) config('visitor_statistics.cookie_minutes', 43200),
                    '/', null, $request->isSecure(), true, false, 'lax'
                ));
            }
        } catch (Throwable $exception) {
            $this->statistics->logFailure('record', $exception);
        }

        return $response;
    }

    private function validToken(mixed $token): bool
    {
        return is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token) === 1;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! config('visitor_statistics.enabled') || $request->method() !== 'GET'
            || ! in_array($request->route()?->getName(), config('visitor_statistics.routes', []), true)
            || $response->getStatusCode() !== 200
            || ! str_contains(strtolower($response->headers->get('Content-Type', '')), 'text/html')
            || $request->expectsJson() || $request->ajax()
            || in_array($request->user()?->role, ['admin', 'operator'], true)) {
            return false;
        }

        $purpose = $request->header('Purpose', '').' '.$request->header('Sec-Purpose', '').' '.$request->header('X-Purpose', '');
        if (preg_match('/prefetch|prerender/i', $purpose)) {
            return false;
        }

        $agent = $request->userAgent() ?? '';

        return $agent !== '' && ! preg_match(
            '/bot|crawler|spider|slurp|facebookexternalhit|facebot|whatsapp|telegrambot|twitterbot|linkedinbot|uptimerobot|pingdom|headless|curl|wget|python-requests/i',
            $agent
        );
    }
}
