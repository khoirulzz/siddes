@php
    $messageText = (string) ($text ?? '');
    $messageExcerpt = \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', $messageText) ?? $messageText, 110);
@endphp
<details class="messaging-message-detail" data-message-detail>
    <summary aria-label="Buka isi pesan"><span>{{ $messageExcerpt ?: 'Pesan kosong' }}</span><span class="messaging-expand-label" aria-hidden="true"></span></summary>
    <div class="messaging-message">{{ $messageText }}</div>
</details>
