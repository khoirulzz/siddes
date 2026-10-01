import assert from 'node:assert/strict';
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

test('allows the legacy cloud but rejects private documents and other clouds', async () => {
  let cacheMode;
  globalThis.fetch = async (_url, options) => {
    cacheMode = options.cache;
    return new Response('image', { headers: { 'Content-Type': 'image/webp' } });
  };

  const legacy = await worker.fetch(new Request('https://cdn.desalambanggelun.id/dzrca841f/image/upload/v1/photo.webp'));
  const document = await worker.fetch(new Request('https://cdn.desalambanggelun.id/dcf6mkq3q/raw/upload/v1/akta.pdf'));
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
