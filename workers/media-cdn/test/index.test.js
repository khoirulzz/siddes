import assert from 'node:assert/strict';
import { createHash, createHmac } from 'node:crypto';
import { afterEach, test } from 'node:test';
import worker from '../src/index.js';

const originalFetch = globalThis.fetch;

afterEach(() => {
  globalThis.fetch = originalFetch;
});

test('proxies a public image while preserving path, query and browser format', async () => {
  let outbound;
  globalThis.fetch = async (url, options) => {
    outbound = { url: url.toString(), options };
    return new Response('image bytes', {
      headers: { 'Content-Type': 'image/webp', 'Cache-Control': 'public, max-age=3600', 'Set-Cookie': 'ignored=1' },
    });
  };

  const response = await worker.fetch(new Request(
    'https://cdn.desalambanggelun.id/dcf6mkq3q/image/upload/f_auto/v123/photo.jpg?x=1',
    { headers: { Accept: 'image/avif,image/webp' } },
  ));

  assert.equal(outbound.url, 'https://res.cloudinary.com/dcf6mkq3q/image/upload/f_auto/v123/photo.jpg?x=1');
  assert.equal(outbound.options.headers.get('Accept'), 'image/avif,image/webp');
  assert.equal(outbound.options.cache, 'no-store');
  assert.equal(response.headers.get('Content-Type'), 'image/webp');
  assert.equal(response.headers.get('Set-Cookie'), null);
  assert.equal(await response.text(), 'image bytes');
});

test('allows the legacy cloud but rejects unsupported raw files and other clouds', async () => {
  let cacheMode;
  globalThis.fetch = async (_url, options) => {
    cacheMode = options.cache;
    return new Response('image', { headers: { 'Content-Type': 'image/webp' } });
  };

  const legacy = await worker.fetch(new Request('https://cdn.desalambanggelun.id/dzrca841f/image/upload/v1/photo.webp'));
  const document = await worker.fetch(new Request('https://cdn.desalambanggelun.id/dcf6mkq3q/raw/upload/v1/akta.docx'));
  const unknown = await worker.fetch(new Request('https://cdn.desalambanggelun.id/other/image/upload/v1/photo.webp'));

  assert.equal(legacy.status, 200);
  assert.equal(cacheMode, undefined);
  assert.equal(document.status, 404);
  assert.equal(unknown.status, 404);
});

test('rejects redirects instead of exposing the Cloudinary destination', async () => {
  globalThis.fetch = async () => Response.redirect('https://res.cloudinary.com/other/image/upload/x.jpg');

  const response = await worker.fetch(new Request('https://cdn.desalambanggelun.id/dcf6mkq3q/image/upload/v1/photo.webp'));

  assert.equal(response.status, 502);
  assert.equal(response.headers.get('Location'), null);
});

test('serves public PDF files inline but rejects other raw files', async () => {
  let requests = 0;
  globalThis.fetch = async () => {
    requests += 1;
    return new Response('%PDF-1.4\n', {
      headers: { 'Content-Type': 'application/pdf', 'Content-Disposition': 'attachment' },
    });
  };

  const pdf = await worker.fetch(new Request('https://cdn.desalambanggelun.id/dcf6mkq3q/raw/upload/v1/public.pdf'));
  const other = await worker.fetch(new Request('https://cdn.desalambanggelun.id/dcf6mkq3q/raw/upload/v1/data.xlsx'));

  assert.equal(pdf.status, 200);
  assert.match(pdf.headers.get('Content-Disposition'), /^inline;/);
  assert.equal(other.status, 404);
  assert.equal(requests, 1);
});

test('requires a valid short-lived signature before downloading a private PDF', async () => {
  const env = {
    CLOUDINARY_CLOUD_NAME: 'dcf6mkq3q',
    CLOUDINARY_API_KEY: 'key',
    CLOUDINARY_API_SECRET: 'secret',
  };
  const assetId = 'resident-asset-1';
  const expiry = String(Math.floor(Date.now() / 1000) + 300);
  const message = `siddes-private-pdf:v1\n${env.CLOUDINARY_CLOUD_NAME}\n${assetId}\n${expiry}`;
  const signature = createHmac('sha256', env.CLOUDINARY_API_SECRET).update(message).digest('hex');
  const base = `https://cdn.desalambanggelun.id/private/pdf/${assetId}`;
  let called = 0;
  globalThis.fetch = async (url) => {
    called += 1;
    const apiUrl = new URL(url);
    const timestamp = apiUrl.searchParams.get('timestamp');
    const expected = createHash('sha1')
      .update(`asset_id=${assetId}&timestamp=${timestamp}${env.CLOUDINARY_API_SECRET}`)
      .digest('hex');
    assert.equal(apiUrl.hostname, 'api.cloudinary.com');
    assert.equal(apiUrl.searchParams.get('signature'), expected);
    return new Response('%PDF-1.4\nprivate', { headers: { 'Content-Type': 'application/pdf' } });
  };

  assert.equal((await worker.fetch(new Request(base), env)).status, 403);
  assert.equal((await worker.fetch(new Request(`${base}?exp=${expiry}&sig=${'0'.repeat(64)}`), env)).status, 403);
  assert.equal(called, 0);

  const response = await worker.fetch(new Request(`${base}?exp=${expiry}&sig=${signature}`), env);
  assert.equal(response.status, 200);
  assert.equal(response.headers.get('Content-Type'), 'application/pdf');
  assert.match(response.headers.get('Content-Disposition'), /^inline;/);
  assert.match(response.headers.get('Cache-Control'), /no-store/);
  assert.equal(await response.text(), '%PDF-1.4\nprivate');
  assert.equal(called, 1);
});
