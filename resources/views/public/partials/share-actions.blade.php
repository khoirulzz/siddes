@php
    $shareTitle = trim((string) ($shareTitle ?? 'Informasi Resmi Desa'));
    $shareUrl = (string) ($shareUrl ?? request()->fullUrl());
    $shareLabel = trim((string) ($shareLabel ?? 'Bagikan artikel'));
    $whatsAppText = rawurlencode($shareTitle . "\n" . $shareUrl);
    $facebookUrl = rawurlencode($shareUrl);
    $xText = rawurlencode($shareTitle);
    $emailSubject = rawurlencode($shareTitle);
    $emailBody = rawurlencode($shareTitle . "\n\nLihat selengkapnya di:\n" . $shareUrl);
@endphp

<section class="reader-share-panel" aria-label="Bagikan halaman ini" data-share-panel>
    <div class="reader-share-shell">
        <span class="reader-share-inline-label">
            {{ $shareLabel }}
        </span>
        <button
            type="button"
            class="reader-share-trigger"
            data-share-toggle
            aria-expanded="false"
            aria-haspopup="true"
            aria-label="Buka menu bagikan artikel"
            title="Bagikan artikel"
        >
            <i class="bi bi-share-fill" aria-hidden="true"></i>
        </button>

        <div class="reader-share-popover" data-share-popover hidden>
            <div class="reader-share-header">
                <p class="reader-share-kicker">{{ $shareLabel }}</p>
            </div>
            <div class="reader-share-actions">
                <a
                    class="reader-share-btn share-whatsapp"
                    href="https://wa.me/?text={{ $whatsAppText }}"
                    target="_blank"
                    rel="noopener"
                    data-share-action
                    aria-label="Bagikan ke WhatsApp"
                    title="WhatsApp"
                >
                    <i class="bi bi-whatsapp" aria-hidden="true"></i>
                    <span>WA</span>
                </a>
                <a
                    class="reader-share-btn share-facebook"
                    href="https://www.facebook.com/sharer/sharer.php?u={{ $facebookUrl }}"
                    target="_blank"
                    rel="noopener"
                    data-share-action
                    aria-label="Bagikan ke Facebook"
                    title="Facebook"
                >
                    <i class="bi bi-facebook" aria-hidden="true"></i>
                    <span>FB</span>
                </a>
                <a
                    class="reader-share-btn share-x"
                    href="https://twitter.com/intent/tweet?text={{ $xText }}&url={{ $facebookUrl }}"
                    target="_blank"
                    rel="noopener"
                    data-share-action
                    aria-label="Bagikan ke X"
                    title="X"
                >
                    <i class="bi bi-twitter-x" aria-hidden="true"></i>
                    <span>X</span>
                </a>
                <a
                    class="reader-share-btn share-email"
                    href="mailto:?subject={{ $emailSubject }}&body={{ $emailBody }}"
                    data-share-action
                    aria-label="Bagikan lewat email"
                    title="Email"
                >
                    <i class="bi bi-envelope-paper" aria-hidden="true"></i>
                    <span>Email</span>
                </a>
                <button
                    type="button"
                    class="reader-share-btn share-copy"
                    data-copy-value="{{ $shareUrl }}"
                    data-share-action
                    aria-label="Salin tautan halaman"
                    title="Copy Link"
                >
                    <i class="bi bi-link-45deg" aria-hidden="true"></i>
                    <span>Copy Link</span>
                </button>
            </div>
        </div>
    </div>
</section>
