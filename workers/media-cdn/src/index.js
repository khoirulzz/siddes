const ALLOWED_CLOUDS = new Set(['dcf6mkq3q', 'dzrca841f']);
const encoder = new TextEncoder();
const PRIVATE_PDF_MAX_AGE_SECONDS = 360;

function hex(bytes) {
  return Array.from(new Uint8Array(bytes), (byte) => byte.toString(16).padStart(2, '0')).join('');
}

function bytesFromHex(value) {
  return Uint8Array.from(value.match(/.{2}/g), (byte) => Number.parseInt(byte, 16));
}

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

async function privatePdf(request, url, env, assetId) {
  const cloudName = env?.CLOUDINARY_CLOUD_NAME;
  const apiKey = env?.CLOUDINARY_API_KEY;
  const apiSecret = env?.CLOUDINARY_API_SECRET;
  if (!cloudName || !apiKey || !apiSecret || !ALLOWED_CLOUDS.has(cloudName)) {
    return textResponse('Media temporarily unavailable', 503);
  }

  const expires = url.searchParams.getAll('exp');
  const signatures = url.searchParams.getAll('sig');
  if (expires.length !== 1 || signatures.length !== 1 || [...url.searchParams].length !== 2
    || !/^[1-9][0-9]{9}$/.test(expires[0]) || !/^[a-f0-9]{64}$/.test(signatures[0])) {
    return textResponse('Forbidden', 403);
  }

  const now = Math.floor(Date.now() / 1000);
  const expiry = Number(expires[0]);
  if (expiry <= now || expiry > now + PRIVATE_PDF_MAX_AGE_SECONDS) {
    return textResponse('Forbidden', 403);
  }

  const key = await crypto.subtle.importKey(
    'raw', encoder.encode(apiSecret), { name: 'HMAC', hash: 'SHA-256' }, false, ['verify'],
  );
  const message = `siddes-private-pdf:v1\n${cloudName}\n${assetId}\n${expires[0]}`;
  const valid = await crypto.subtle.verify(
    'HMAC', key, bytesFromHex(signatures[0]), encoder.encode(message),
  );
  if (!valid) {
    return textResponse('Forbidden', 403);
  }

  const timestamp = String(now);
  const cloudinarySignature = hex(await crypto.subtle.digest(
    'SHA-1', encoder.encode(`asset_id=${assetId}&timestamp=${timestamp}${apiSecret}`),
  ));
  const downloadUrl = new URL(`https://api.cloudinary.com/v1_1/${cloudName}/asset/download`);
  downloadUrl.searchParams.set('asset_id', assetId);
  downloadUrl.searchParams.set('timestamp', timestamp);
  downloadUrl.searchParams.set('api_key', apiKey);
  downloadUrl.searchParams.set('signature', cloudinarySignature);

  let upstream;
  try {
    upstream = await fetch(downloadUrl, { redirect: 'follow', cache: 'no-store' });
  } catch {
    return textResponse('Media temporarily unavailable', 502);
  }

  if (!upstream.ok) {
    return textResponse(upstream.status === 404 ? 'Not found' : 'Media temporarily unavailable',
      upstream.status === 404 ? 404 : 502);
  }

  if (!(upstream.headers.get('Content-Type') || '').toLowerCase().startsWith('application/pdf')) {
    return textResponse('Invalid media response', 502);
  }

  const responseHeaders = new Headers({
    'Content-Type': 'application/pdf',
    'Content-Disposition': 'inline; filename="dokumen.pdf"',
    'Cache-Control': 'private, no-store, max-age=0',
    'Referrer-Policy': 'no-referrer',
    'X-Robots-Tag': 'noindex, nofollow',
    'X-Content-Type-Options': 'nosniff',
  });
  const contentLength = upstream.headers.get('Content-Length');
  if (contentLength && /^[0-9]+$/.test(contentLength)) {
    responseHeaders.set('Content-Length', contentLength);
  }
  if (request.method === 'HEAD') {
    await upstream.body?.cancel();
  }

  return new Response(request.method === 'HEAD' ? null : upstream.body, {
    status: 200,
    headers: responseHeaders,
  });
}

export default {
  async fetch(request, env) {
    const url = new URL(request.url);

    if (request.method !== 'GET' && request.method !== 'HEAD') {
      return textResponse('Method not allowed', 405);
    }

    if (url.pathname === '/') {
      return textResponse('SIDDes media CDN', 200);
    }

    const privateMatch = url.pathname.match(/^\/private\/pdf\/([A-Za-z0-9_-]{1,128})$/);
    if (privateMatch) {
      return privatePdf(request, url, env, privateMatch[1]);
    }

    // Keep the full Cloudinary path, including its cloud name and transformations.
    const match = url.pathname.match(/^\/([^/]+)\/(image|raw)\/upload\/(.+)$/);
    if (!match || !ALLOWED_CLOUDS.has(match[1])) {
      return textResponse('Not found', 404);
    }

    if (url.pathname.includes('..') || /%2f|%5c|%2e/i.test(url.pathname)) {
      return textResponse('Invalid path', 400);
    }

    const isPdf = /\.pdf$/i.test(url.pathname);
    if (match[2] === 'raw' && !isPdf) {
      return textResponse('Not found', 404);
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

    const contentType = (upstream.headers.get('Content-Type') || '').toLowerCase();
    if (upstream.ok && !(isPdf ? contentType.startsWith('application/pdf')
      : contentType.startsWith('image/'))) {
      return textResponse('Invalid media response', 502);
    }

    const responseHeaders = new Headers(upstream.headers);
    responseHeaders.delete('Set-Cookie');
    responseHeaders.set('X-Content-Type-Options', 'nosniff');
    if (upstream.status >= 400) {
      responseHeaders.set('Cache-Control', 'no-store');
    } else if (isPdf) {
      responseHeaders.set('Content-Disposition', 'inline; filename="dokumen.pdf"');
    }

    return new Response(request.method === 'HEAD' || upstream.status === 304 ? null : upstream.body, {
      status: upstream.status,
      headers: responseHeaders,
    });
  },
};
