{{-- =========================================================
     AI CHATBOT FLOATING WIDGET
     Dirender di semua halaman publik via layouts/public.blade.php
     ========================================================= --}}
<div id="ai-chatbot-widget" role="dialog" aria-label="Asisten AI Desa" aria-modal="true">
    {{-- Trigger Button --}}
    <button id="ai-chat-trigger" type="button" aria-label="Buka asisten AI" aria-expanded="false" aria-controls="ai-chat-panel">
        <svg id="chat-icon-open" viewBox="0 0 24 24" fill="none" width="24" height="24" aria-hidden="true">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
            <circle cx="8" cy="10" r="1" fill="currentColor"/><circle cx="12" cy="10" r="1" fill="currentColor"/><circle cx="16" cy="10" r="1" fill="currentColor"/>
        </svg>
        <svg id="chat-icon-close" viewBox="0 0 24 24" fill="none" width="22" height="22" aria-hidden="true" style="display:none">
            <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
        </svg>
        <span id="chat-unread-badge" style="display:none">1</span>
    </button>

    {{-- Chat Panel --}}
    <div id="ai-chat-panel" hidden>
        <div class="aichat-header">
            <div class="aichat-header-info">
                <div class="aichat-avatar" aria-hidden="true">🤖</div>
                <div>
                    <p class="aichat-name">Asisten AI Desa</p>
                    <p class="aichat-status"><span class="aichat-online-dot"></span> Online</p>
                </div>
            </div>
            <div class="aichat-header-actions">
                <button type="button" id="ai-chat-clear" title="Hapus riwayat chat" aria-label="Hapus riwayat chat" style="display:none">
                    <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button type="button" id="ai-chat-close" aria-label="Tutup chat">
                    <svg viewBox="0 0 24 24" fill="none" width="18" height="18"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
            </div>
        </div>

        <div class="aichat-messages" id="aichat-messages" role="log" aria-live="polite" aria-relevant="additions">
            {{-- Pesan selamat datang default --}}
            <div class="aichat-bubble aichat-bubble--ai aichat-welcome" id="aichat-welcome">
                <div class="aichat-bubble-inner">
                    <p>👋 Halo! Saya asisten AI <strong>{{ config('village.name') }}</strong>.</p>
                    <p>Saya bisa bantu Anda soal layanan desa, cara pengajuan surat, cek PBB, pengaduan, dan lainnya.</p>
                    <div class="aichat-quick-questions" id="aichat-quick">
                        <p class="aichat-quick-label">Pertanyaan populer:</p>
                        <button type="button" class="aichat-quick-btn" data-q="Bagaimana cara mengajukan surat keterangan domisili?">Cara surat domisili?</button>
                        <button type="button" class="aichat-quick-btn" data-q="Bagaimana cara cek tagihan PBB saya?">Cek tagihan PBB</button>
                        <button type="button" class="aichat-quick-btn" data-q="Bagaimana cara melacak status pengajuan surat saya?">Lacak status surat</button>
                        <button type="button" class="aichat-quick-btn" data-q="Apa saja layanan online yang tersedia di desa?">Layanan online apa saja?</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="aichat-input-area">
            <textarea id="aichat-input" placeholder="Tanyakan sesuatu..." rows="1" maxlength="1000" aria-label="Pesan untuk asisten AI"></textarea>
            <button type="button" id="aichat-send" aria-label="Kirim pesan" disabled>
                <svg viewBox="0 0 24 24" fill="none" width="18" height="18"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
    </div>
</div>

<style>
/* ── Floating Trigger ── */
#ai-chatbot-widget { position: fixed; bottom: 24px; right: 24px; z-index: 9999; font-family: 'Poppins', sans-serif; }
#ai-chat-trigger {
    width: 56px; height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #7c3aed);
    border: none; cursor: pointer; color: #fff;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 20px rgba(99,102,241,.5);
    transition: transform .2s, box-shadow .2s;
    position: relative;
}
#ai-chat-trigger:hover { transform: scale(1.08); box-shadow: 0 8px 28px rgba(99,102,241,.6); }
#chat-unread-badge {
    position: absolute; top: -4px; right: -4px;
    width: 18px; height: 18px; border-radius: 50%;
    background: #ef4444; color: #fff; font-size: 10px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}

/* ── Panel ── */
#ai-chat-panel {
    position: absolute; bottom: 68px; right: 0;
    width: 340px;
    background: var(--surface-alt, #fff);
    border-radius: 20px;
    box-shadow: 0 12px 48px rgba(0,0,0,.18), 0 0 0 1px rgba(0,0,0,.06);
    display: flex; flex-direction: column;
    overflow: hidden;
    max-height: 520px;
    animation: aichat-pop .22s cubic-bezier(.34,1.56,.64,1);
}
[data-theme="dark"] #ai-chat-panel { background: #1e293b; box-shadow: 0 12px 48px rgba(0,0,0,.4), 0 0 0 1px rgba(255,255,255,.07); }
@keyframes aichat-pop { from { opacity:0; transform: scale(.92) translateY(12px); } to { opacity:1; transform: scale(1) translateY(0); } }

/* ── Header ── */
.aichat-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 16px;
    background: linear-gradient(135deg, #6366f1, #7c3aed);
    color: #fff;
}
.aichat-header-info { display: flex; align-items: center; gap: 10px; }
.aichat-avatar { font-size: 24px; line-height: 1; }
.aichat-name { font-size: 13px; font-weight: 700; margin: 0; color: #fff; }
.aichat-status { font-size: 11px; margin: 2px 0 0; color: rgba(255,255,255,.75); display: flex; align-items: center; gap: 4px; }
.aichat-online-dot { width: 7px; height: 7px; border-radius: 50%; background: #4ade80; display: inline-block; }
.aichat-header-actions { display: flex; gap: 6px; }
.aichat-header-actions button { background: rgba(255,255,255,.15); border: none; color: #fff; width: 28px; height: 28px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background .15s; }
.aichat-header-actions button:hover { background: rgba(255,255,255,.28); }

/* ── Messages ── */
.aichat-messages {
    flex: 1; overflow-y: auto; padding: 12px;
    display: flex; flex-direction: column; gap: 10px;
    scroll-behavior: smooth;
    min-height: 240px; max-height: 320px;
}
.aichat-messages::-webkit-scrollbar { width: 4px; }
.aichat-messages::-webkit-scrollbar-thumb { background: rgba(0,0,0,.15); border-radius: 4px; }
.aichat-bubble { display: flex; }
.aichat-bubble--ai { justify-content: flex-start; }
.aichat-bubble--user { justify-content: flex-end; }
.aichat-bubble-inner {
    max-width: 85%; padding: 9px 13px;
    border-radius: 16px; font-size: 12.5px; line-height: 1.65;
}
.aichat-bubble--ai .aichat-bubble-inner {
    background: var(--surface, #f1f5f9);
    color: var(--text-1, #1e293b);
    border-top-left-radius: 4px;
}
[data-theme="dark"] .aichat-bubble--ai .aichat-bubble-inner { background: #334155; color: #e2e8f0; }
.aichat-bubble--user .aichat-bubble-inner {
    background: linear-gradient(135deg, #6366f1, #7c3aed);
    color: #fff; border-top-right-radius: 4px;
}
.aichat-bubble-inner p { margin: 0 0 6px; }
.aichat-bubble-inner p:last-child { margin-bottom: 0; }
.aichat-bubble-inner a { color: #6366f1; text-decoration: underline; word-break: break-all; }
.aichat-bubble--user .aichat-bubble-inner a { color: #c7d2fe; }
.aichat-bubble-inner strong { font-weight: 700; }
.aichat-bubble-inner ul, .aichat-bubble-inner ol { padding-left: 18px; margin: 4px 0; }
.aichat-bubble-inner li { margin-bottom: 2px; }

/* Quick questions */
.aichat-quick-label { font-size: 11px; color: var(--text-2, #64748b); margin-bottom: 6px !important; font-weight: 600; }
.aichat-quick-questions { margin-top: 10px; display: flex; flex-direction: column; gap: 5px; }
.aichat-quick-btn {
    background: rgba(99,102,241,.1); border: 1px solid rgba(99,102,241,.2);
    color: #6366f1; font-size: 11.5px; font-weight: 500; padding: 6px 10px;
    border-radius: 8px; cursor: pointer; text-align: left;
    transition: background .15s, border-color .15s;
    font-family: inherit;
}
.aichat-quick-btn:hover { background: rgba(99,102,241,.18); border-color: rgba(99,102,241,.4); }
[data-theme="dark"] .aichat-quick-btn { color: #a5b4fc; background: rgba(99,102,241,.12); }

/* Loading dots */
.aichat-loading-dots { display: flex; gap: 4px; align-items: center; padding: 4px 0; }
.aichat-loading-dots span {
    width: 6px; height: 6px; border-radius: 50%;
    background: var(--text-2, #94a3b8);
    animation: aichat-bounce .8s ease-in-out infinite;
}
.aichat-loading-dots span:nth-child(2) { animation-delay: .15s; }
.aichat-loading-dots span:nth-child(3) { animation-delay: .3s; }
@keyframes aichat-bounce { 0%,80%,100% { transform: scale(.6); opacity:.4; } 40% { transform: scale(1); opacity:1; } }

/* ── Input ── */
.aichat-input-area {
    display: flex; align-items: flex-end; gap: 8px;
    padding: 10px 12px;
    border-top: 1px solid var(--border, rgba(0,0,0,.08));
    background: var(--surface-alt, #fff);
}
[data-theme="dark"] .aichat-input-area { background: #1e293b; border-color: rgba(255,255,255,.07); }
#aichat-input {
    flex: 1; border: 1.5px solid var(--border, rgba(0,0,0,.12));
    border-radius: 12px; padding: 8px 12px; font-size: 12.5px;
    font-family: inherit; resize: none; outline: none;
    background: var(--surface, #f8fafc); color: var(--text-1, #1e293b);
    transition: border-color .15s; line-height: 1.5; max-height: 80px; overflow-y: auto;
}
#aichat-input:focus { border-color: #6366f1; }
[data-theme="dark"] #aichat-input { background: #334155; color: #e2e8f0; border-color: rgba(255,255,255,.12); }
#aichat-send {
    width: 36px; height: 36px; flex-shrink: 0;
    border-radius: 10px; border: none; cursor: pointer; color: #fff;
    background: linear-gradient(135deg, #6366f1, #7c3aed);
    display: flex; align-items: center; justify-content: center;
    transition: opacity .2s, transform .15s;
}
#aichat-send:disabled { opacity: .4; cursor: not-allowed; }
#aichat-send:not(:disabled):hover { transform: scale(1.08); }

@media (max-width: 420px) {
    #ai-chatbot-widget { bottom: 16px; right: 12px; }
    #ai-chat-panel { width: calc(100vw - 24px); right: -12px; }
}
</style>

<script>
(function () {
    const widget   = document.getElementById('ai-chatbot-widget');
    const trigger  = document.getElementById('ai-chat-trigger');
    const panel    = document.getElementById('ai-chat-panel');
    const iconOpen = document.getElementById('chat-icon-open');
    const iconClose= document.getElementById('chat-icon-close');
    const badge    = document.getElementById('chat-unread-badge');
    const messages = document.getElementById('aichat-messages');
    const input    = document.getElementById('aichat-input');
    const sendBtn  = document.getElementById('aichat-send');
    const clearBtn = document.getElementById('ai-chat-clear');
    const closeBtn = document.getElementById('ai-chat-close');

    let history = []; // {role, content}
    let isOpen  = false;
    let isLoading = false;

    // ── Open / Close ──
    function togglePanel(open) {
        isOpen = open ?? !isOpen;
        panel.hidden = !isOpen;
        iconOpen.style.display  = isOpen ? 'none' : '';
        iconClose.style.display = isOpen ? '' : 'none';
        trigger.setAttribute('aria-expanded', isOpen);
        if (isOpen) {
            badge.style.display = 'none';
            input.focus();
            scrollBottom();
        }
    }
    trigger.addEventListener('click', () => togglePanel());
    closeBtn.addEventListener('click', () => togglePanel(false));

    // ── Render markdown-light & linkify ──
    function renderContent(text) {
        // Bold: **text**
        text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        // Links: [label](url) or bare https://...
        text = text.replace(/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/g,
            '<a href="$2" target="_blank" rel="noopener">$1</a>');
        text = text.replace(/(^|[\s(])(https?:\/\/[^\s<>"')\]]+)/g,
            '$1<a href="$2" target="_blank" rel="noopener">$2</a>');
        // Bullet: lines starting with - or *
        text = text.replace(/^[-*]\s(.+)/gm, '<li>$1</li>');
        text = text.replace(/(<li>.*<\/li>\n?)+/gs, '<ul>$&</ul>');
        // Paragraphs: double newline
        text = text.replace(/\n{2,}/g, '</p><p>');
        text = text.replace(/\n/g, '<br>');
        return '<p>' + text + '</p>';
    }

    function appendBubble(role, content, isLoading) {
        const wrap = document.createElement('div');
        wrap.className = 'aichat-bubble aichat-bubble--' + (role === 'user' ? 'user' : 'ai');

        const inner = document.createElement('div');
        inner.className = 'aichat-bubble-inner';

        if (isLoading) {
            inner.innerHTML = '<div class="aichat-loading-dots"><span></span><span></span><span></span></div>';
            wrap.id = 'aichat-loading-bubble';
        } else {
            inner.innerHTML = renderContent(content);
        }

        wrap.appendChild(inner);
        messages.appendChild(wrap);
        scrollBottom();
        return wrap;
    }

    function scrollBottom() {
        messages.scrollTop = messages.scrollHeight;
    }

    // ── Send message ──
    async function sendMessage(text) {
        if (isLoading || !text.trim()) return;
        isLoading = true;
        sendBtn.disabled = true;

        // Hide welcome message & quick questions after first send
        const welcome = document.getElementById('aichat-welcome');
        if (welcome) welcome.style.display = 'none';

        appendBubble('user', text);
        const loadingBubble = appendBubble('assistant', '', true);

        clearBtn.style.display = 'flex';

        try {
            const res = await fetch('{{ route("ai.public.chat") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message: text, history: history.slice(-6) }),
            });

            const data = await res.json();
            loadingBubble.remove();

            const reply = data.ok
                ? data.message
                : (data.message || 'Maaf, terjadi kesalahan. Silakan coba lagi.');

            appendBubble('assistant', reply);
            history.push({ role: 'user', content: text });
            history.push({ role: 'assistant', content: reply });

            if (history.length > 20) history = history.slice(-20);

        } catch (err) {
            loadingBubble.remove();
            appendBubble('assistant', 'Maaf, koneksi bermasalah. Periksa internet Anda dan coba lagi.');
        }

        isLoading = false;
        sendBtn.disabled = input.value.trim() === '';
        input.focus();
    }

    // ── Input events ──
    input.addEventListener('input', function () {
        sendBtn.disabled = this.value.trim() === '' || isLoading;
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 80) + 'px';
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            const val = this.value.trim();
            if (val) { this.value = ''; this.style.height = 'auto'; sendMessage(val); }
        }
    });

    sendBtn.addEventListener('click', function () {
        const val = input.value.trim();
        if (val) { input.value = ''; input.style.height = 'auto'; sendMessage(val); }
    });

    // ── Quick questions ──
    messages.addEventListener('click', function (e) {
        const btn = e.target.closest('.aichat-quick-btn');
        if (!btn) return;
        const q = btn.dataset.q;
        if (q) sendMessage(q);
    });

    // ── Clear chat ──
    clearBtn.addEventListener('click', function () {
        history = [];
        messages.innerHTML = '';
        const welcome = document.getElementById('aichat-welcome');
        // Re-create welcome bubble
        const div = document.createElement('div');
        div.className = 'aichat-bubble aichat-bubble--ai aichat-welcome';
        div.id = 'aichat-welcome';
        div.innerHTML = messages.querySelector('.aichat-welcome')?.innerHTML || '<div class="aichat-bubble-inner"><p>👋 Chat telah dihapus. Ada yang bisa saya bantu?</p></div>';
        messages.appendChild(div);
        clearBtn.style.display = 'none';
    });

    // ── Show unread badge after 3s if panel not opened ──
    setTimeout(() => {
        if (!isOpen) badge.style.display = 'flex';
    }, 3000);
})();
</script>
