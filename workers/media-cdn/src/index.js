const ALLOWED_CLOUDS = new Set(['dcf6mkq3q', 'dzrca841f']);

function textResponse(message, status) {
  return new Response(message, {
    status,
    headers: {
      'Content-Type': 'text/plain; charset=utf-8',
      'Cache-Control': 'no-store',
      'X-Content-Type-Options': 'nosniff',
    },
  });
}

export default {
  async fetch(request) {
    const url = new URL(request.url);

    if (request.method !== 'GET' && request.method !== 'HEAD') {
      return textResponse('Method not allowed', 405);
    }

    if (url.pathname === '/') {
      return textResponse('SIDDes media CDN', 200);
    }

    // Keep the full Cloudinary path, including its cloud name and transformations.
    const match = url.pathname.match(/^\/([^/]+)\/image\/upload\/(.+)$/);
    if (!match || !ALLOWED_CLOUDS.has(match[1])) {
      return textResponse('Not found', 404);
    }

    if (url.pathname.includes('..') || /%2f|%5c|%2e/i.test(url.pathname)) {
      return textResponse('Invalid path', 400);
    }

    const upstreamUrl = new URL(url.pathname + url.search, 'https://res.cloudinary.com');
    const headers = new Headers();

    for (const name of ['Accept', 'If-None-Match', 'If-Modified-Since']) {
      const value = request.headers.get(name);
      if (value !== null) {
        headers.set(name, value);
      }
    }

    const fetchOptions = {
      method: request.method,
      headers,
      redirect: 'manual',
    };

    // Cloudinary can return different formats for a single f_auto URL.
    // Fixed-format images may use Cloudflare's normal cache behavior.
    if (/(?:^|[\/,])f_auto(?:[\/,]|$)/.test(url.pathname)) {
      fetchOptions.cache = 'no-store';
    }

    let upstream;
    try {
      upstream = await fetch(upstreamUrl, fetchOptions);
    } catch {
      return textResponse('Media temporarily unavailable', 502);
    }

    if (upstream.status >= 300 && upstream.status < 400) {
      return textResponse('Media temporarily unavailable', 502);
    }

    const contentType = upstream.headers.get('Content-Type') || '';
    if (upstream.ok && !contentType.toLowerCase().startsWith('image/')) {
      return textResponse('Invalid media response', 502);
    }

    const responseHeaders = new Headers(upstream.headers);
    responseHeaders.delete('Set-Cookie');
    responseHeaders.set('X-Content-Type-Options', 'nosniff');
    if (upstream.status >= 400) {
      responseHeaders.set('Cache-Control', 'no-store');
    }

    return new Response(request.method === 'HEAD' || upstream.status === 304 ? null : upstream.body, {
      status: upstream.status,
      headers: responseHeaders,
    });
  },
};
