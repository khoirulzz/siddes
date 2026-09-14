
<!-- Marked.js for better markdown rendering -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

<div id="ai-chatbot-widget">
    <!-- Toggle Button -->
    <button id="ai-chat-trigger" aria-label="Buka Asisten AI" aria-expanded="false">
        <svg id="chat-icon-open" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <svg id="chat-icon-close" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
        <span id="chat-unread-badge" style="display:none">1</span>
    </button>

    <!-- Chat Panel -->
    <div id="ai-chat-panel" class="collapsed">
        <div class="aichat-header">
            <div class="aichat-header-info">
                <!-- Premium CS Icon -->
                <div class="aichat-avatar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div>
                    <strong>Layanan AI Desa</strong>
                    <span><i class="online-dot"></i> Online</span>
                </div>
            </div>
            <div class="aichat-header-actions">
                <button id="ai-chat-clear" title="Bersihkan obrolan" style="display:none">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
                <!-- Close Button (Mobile primarily) -->
                <button id="ai-chat-close-btn" class="mobile-only" title="Tutup">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>

        <div id="aichat-messages">
            <div class="aichat-bubble aichat-bubble--ai aichat-welcome" id="aichat-welcome">
                <div class="aichat-bubble-inner">
                    <p>Halo! Saya asisten AI Desa Lambanggelun. Silakan tanya tentang layanan desa, surat, PBB, dll.</p>
                </div>
                <div class="aichat-quick-replies">
                    <button class="aichat-quick-btn" data-q="Bagaimana cara buat surat domisili?">Cara surat domisili?</button>
                    <button class="aichat-quick-btn" data-q="Bagaimana cara cek tagihan PBB?">Cek tagihan PBB</button>
                </div>
            </div>
        </div>

        <div class="aichat-input-area">
            <textarea id="aichat-input" rows="1" placeholder="Tanyakan sesuatu..." aria-label="Pesan AI"></textarea>
            <button id="aichat-send" disabled aria-label="Kirim Pesan">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-left:-2px">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
            </button>
        </div>
    </div>
</div>

<style>
/* Reset & Base */
#ai-chatbot-widget {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    font-family: 'Poppins', sans-serif;
    box-sizing: border-box;
}
#ai-chatbot-widget * { box-sizing: inherit; }

/* Toggle Button */
#ai-chat-trigger {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #7c3aed);
    color: #fff;
    border: none;
    box-shadow: 0 4px 16px rgba(99,102,241,.4);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform .2s, box-shadow .2s;
    z-index: 2;
}
#ai-chat-trigger:hover { transform: scale(1.05); box-shadow: 0 6px 20px rgba(99,102,241,.6); }
#chat-unread-badge {
    position: absolute; top: -2px; right: -2px;
    width: 18px; height: 18px; border-radius: 50%;
    background: #ef4444; border: 2px solid #fff;
    color: #fff; font-size: 10px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}

/* Chat Panel */
#ai-chat-panel {
    position: absolute;
    bottom: 76px;
    right: 0;
    width: 360px;
    height: 500px;
    max-height: calc(100vh - 100px);
    background: var(--surface, #fff);
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0,0,0,.15);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transform-origin: bottom right;
    transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.3s;
    visibility: visible;
    opacity: 1;
    transform: scale(1) translateY(0);
}
#ai-chat-panel.collapsed {
    visibility: hidden;
    opacity: 0;
    transform: scale(0.9) translateY(20px);
    pointer-events: none;
}
[data-theme="dark"] #ai-chat-panel { background: #1e293b; box-shadow: 0 8px 32px rgba(0,0,0,.5); }

/* Header */
.aichat-header {
    background: linear-gradient(135deg, #6366f1, #7c3aed);
    color: #fff;
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.aichat-header-info { display: flex; align-items: center; gap: 12px; }
.aichat-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: rgba(255,255,255,.2);
    display: flex; align-items: center; justify-content: center;
}
.aichat-header-info strong { display: block; font-size: 14px; font-weight: 600; line-height: 1.2; margin-bottom: 2px; }
.aichat-header-info span { display: flex; align-items: center; gap: 4px; font-size: 11px; opacity: .9; }
.online-dot { width: 6px; height: 6px; background: #22c55e; border-radius: 50%; display: inline-block; }
.aichat-header-actions button {
    background: transparent; border: none; color: rgba(255,255,255,.7);
    cursor: pointer; padding: 4px; border-radius: 6px;
    transition: background .2s, color .2s;
    display: flex; align-items: center; justify-content: center;
}
.aichat-header-actions button:hover { background: rgba(255,255,255,.15); color: #fff; }
.mobile-only { display: none !important; }

/* Messages */
#aichat-messages {
    flex: 1; padding: 16px; overflow-y: auto;
    display: flex; flex-direction: column; gap: 12px;
    background: var(--bg, #f8fafc);
}
[data-theme="dark"] #aichat-messages { background: #0f172a; }

.aichat-bubble { display: flex; flex-direction: column; max-width: 85%; }
.aichat-bubble--ai { align-self: flex-start; }
.aichat-bubble--user { align-self: flex-end; }

.aichat-bubble-inner {
    padding: 10px 14px; border-radius: 14px;
    font-size: 13px; line-height: 1.5;
    word-break: break-word;
}
.aichat-bubble--ai .aichat-bubble-inner {
    background: var(--surface, #fff); color: var(--text-1, #1e293b);
    border-bottom-left-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
[data-theme="dark"] .aichat-bubble--ai .aichat-bubble-inner { background: #1e293b; color: #f8fafc; }
.aichat-bubble--user .aichat-bubble-inner {
    background: #6366f1; color: #fff;
    border-bottom-right-radius: 4px;
}

/* Markdown Rendering Styles */
.aichat-bubble-inner p { margin: 0 0 8px; }
.aichat-bubble-inner p:last-child { margin: 0; }
.aichat-bubble-inner a { color: #3b82f6; text-decoration: underline; }
.aichat-bubble-inner ul, .aichat-bubble-inner ol { margin: 4px 0 8px; padding-left: 20px; }
.aichat-bubble--user .aichat-bubble-inner a { color: #fff; font-weight: 500; }
.aichat-bubble-inner strong { font-weight: 600; }
.aichat-bubble-inner code { background: rgba(0,0,0,.05); padding: 2px 4px; border-radius: 4px; font-family: monospace; font-size: 11.5px; }
[data-theme="dark"] .aichat-bubble-inner code { background: rgba(255,255,255,.1); }

/* Quick Replies */
.aichat-quick-replies { display: flex; flex-direction: column; gap: 6px; margin-top: 8px; }
.aichat-quick-btn {
    background: transparent; border: 1px solid #c7d2fe; color: #4f46e5;
    border-radius: 12px; padding: 6px 12px; font-size: 12px; font-weight: 500;
    text-align: left; cursor: pointer; transition: all .2s;
    font-family: inherit;
}
.aichat-quick-btn:hover { background: #e0e7ff; border-color: #a5b4fc; }
[data-theme="dark"] .aichat-quick-btn { border-color: #3730a3; color: #818cf8; }
[data-theme="dark"] .aichat-quick-btn:hover { background: #312e81; border-color: #4f46e5; }

/* Input */
.aichat-input-area {
    display: flex; gap: 8px; padding: 12px;
    border-top: 1px solid var(--border, rgba(0,0,0,.08));
    background: var(--surface, #fff);
}
[data-theme="dark"] .aichat-input-area { background: #1e293b; border-color: rgba(255,255,255,.07); }
#aichat-input {
    flex: 1; border: 1.5px solid var(--border, rgba(0,0,0,.12));
    border-radius: 20px; padding: 8px 14px; font-size: 13px;
    font-family: inherit; resize: none; outline: none;
    background: var(--bg, #f8fafc); color: var(--text-1, #1e293b);
    transition: border-color .2s; line-height: 1.4; max-height: 80px; overflow-y: auto;
}
#aichat-input:focus { border-color: #6366f1; }
[data-theme="dark"] #aichat-input { background: #0f172a; color: #e2e8f0; border-color: rgba(255,255,255,.12); }
#aichat-send {
    width: 38px; height: 38px; flex-shrink: 0;
    border-radius: 50%; border: none; cursor: pointer; color: #fff;
    background: linear-gradient(135deg, #6366f1, #7c3aed);
    display: flex; align-items: center; justify-content: center;
    transition: opacity .2s, transform .2s;
}
#aichat-send:disabled { opacity: .4; cursor: not-allowed; transform: scale(0.9); }
#aichat-send:not(:disabled):hover { transform: scale(1.05); }

/* Loading Dots */
.aichat-loading-dots { display: flex; gap: 4px; padding: 4px; }
.aichat-loading-dots span {
    width: 6px; height: 6px; background: #94a3b8; border-radius: 50%;
    animation: aichat-bounce 1.4s infinite ease-in-out both;
}
.aichat-loading-dots span:nth-child(1) { animation-delay: -0.32s; }
.aichat-loading-dots span:nth-child(2) { animation-delay: -0.16s; }
@keyframes aichat-bounce { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }

/* Mobile Fullscreen Optimization */
@media (max-width: 480px) {
    #ai-chatbot-widget { bottom: 16px; right: 16px; }
    .mobile-only { display: flex !important; }
    
    /* When open, make it cover the screen */
    #ai-chatbot-widget.widget-open {
        bottom: 0; right: 0; width: 100%; height: 100%;
    }
    #ai-chatbot-widget.widget-open #ai-chat-trigger { display: none; }
    
    #ai-chat-panel {
        bottom: 0; right: 0; width: 100%; height: 100%;
        max-height: 100vh; border-radius: 0;
        transform-origin: bottom center;
    }
    #ai-chat-panel.collapsed {
        transform: translateY(100%);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
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
    const closeBtn = document.getElementById('ai-chat-close-btn');

    let history = []; 
    let isOpen  = false;
    let isLoading = false;
    let collapseTimer = null;
    const AUTO_COLLAPSE_MS = 30000; // 30 seconds

    // Initialize Marked.js if available
    const renderContent = (text) => {
        if (typeof marked !== 'undefined') {
            return marked.parse(text);
        }
        return text.replace(/\n/g, '<br>');
    };

    // Load from SessionStorage so it persists across page reloads in same tab
    function loadSession() {
        try {
            const saved = sessionStorage.getItem('sid_ai_chat');
            if (saved) {
                history = JSON.parse(saved);
                if (history.length > 0) {
                    const welcome = document.getElementById('aichat-welcome');
                    if (welcome) welcome.style.display = 'none';
                    clearBtn.style.display = 'flex';
                    
                    history.forEach(msg => {
                        appendBubble(msg.role, msg.content, false, true);
                    });
                }
            }
        } catch (e) {}
    }

    function saveSession() {
        sessionStorage.setItem('sid_ai_chat', JSON.stringify(history));
    }

    // Timer Logic
    function resetCollapseTimer() {
        if (collapseTimer) clearTimeout(collapseTimer);
        if (isOpen) {
            collapseTimer = setTimeout(() => {
                togglePanel(false); // Auto collapse after 30s
            }, AUTO_COLLAPSE_MS);
        }
    }
    
    // Reset timer on any interaction
    panel.addEventListener('mousemove', resetCollapseTimer);
    panel.addEventListener('touchstart', resetCollapseTimer);
    input.addEventListener('keydown', resetCollapseTimer);

    // Open / Close
    function togglePanel(open) {
        isOpen = open ?? !isOpen;
        
        if (isOpen) {
            panel.classList.remove('collapsed');
            widget.classList.add('widget-open');
            iconOpen.style.display  = 'none';
            iconClose.style.display = '';
            badge.style.display = 'none';
            setTimeout(() => { input.focus(); scrollBottom(); }, 300);
            resetCollapseTimer();
        } else {
            panel.classList.add('collapsed');
            widget.classList.remove('widget-open');
            iconOpen.style.display  = '';
            iconClose.style.display = 'none';
            if (collapseTimer) clearTimeout(collapseTimer);
        }
        trigger.setAttribute('aria-expanded', isOpen);
    }
    
    trigger.addEventListener('click', () => togglePanel());
    closeBtn.addEventListener('click', () => togglePanel(false));

    // Force close on load (per request: otomatis menutup tanpa hilang)
    // We don't restore `isOpen` state on navigation, we always start collapsed.
    
    function appendBubble(role, content, isLoading, skipScroll = false) {
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
        if(!skipScroll) scrollBottom();
        return wrap;
    }

    function scrollBottom() {
        messages.scrollTop = messages.scrollHeight;
    }

    // Send Message
    async function sendMessage(text) {
        if (isLoading || !text.trim()) return;
        isLoading = true;
        sendBtn.disabled = true;
        resetCollapseTimer();

        const welcome = document.getElementById('aichat-welcome');
        if (welcome) welcome.style.display = 'none';

        appendBubble('user', text);
        clearBtn.style.display = 'flex';

        history.push({ role: 'user', content: text });
        saveSession();

        const aiBubble = appendBubble('assistant', '', true);
        const bubbleInner = aiBubble.querySelector('.aichat-bubble-inner');

        let fullReply = '';
        let isFirstChunk = true;

        try {
            const res = await fetch('{{ route("ai.public.chat") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'Accept': 'text/event-stream',
                },
                body: JSON.stringify({ message: text, history: history.slice(-6) }),
            });

            if (!res.ok || !res.body) {
                try {
                    const errData = await res.json();
                    fullReply = errData.message || 'Maaf, terjadi kesalahan. Silakan coba lagi.';
                } catch (e) {
                    fullReply = 'Maaf, terjadi kesalahan layanan. Silakan coba lagi.';
                }
                bubbleInner.innerHTML = renderContent(fullReply);
            } else {
                const reader = res.body.getReader();
                const decoder = new TextDecoder('utf-8');
                let buffer = '';

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop(); // keep last incomplete line

                    for (const line of lines) {
                        const trimmed = line.trim();
                        if (!trimmed.startsWith('data: ')) continue;

                        const dataStr = trimmed.substring(6).trim();
                        if (dataStr === '[DONE]') break;

                        try {
                            const parsed = JSON.parse(dataStr);
                            if (parsed.error) {
                                fullReply = parsed.error;
                                bubbleInner.innerHTML = renderContent(fullReply);
                                break;
                            }

                            if (parsed.content) {
                                if (isFirstChunk) {
                                    bubbleInner.innerHTML = '';
                                    isFirstChunk = false;
                                }
                                fullReply += parsed.content;
                                bubbleInner.innerHTML = renderContent(fullReply);
                                scrollBottom();
                            }
                        } catch (e) {
                            // ignore parse errors for incomplete JSON
                        }
                    }
                }
            }

            if (!fullReply) {
                fullReply = 'Maaf, terjadi kesalahan saat memproses jawaban.';
                bubbleInner.innerHTML = renderContent(fullReply);
            }

            history.push({ role: 'assistant', content: fullReply });
            if (history.length > 30) history = history.slice(-30);
            saveSession();

        } catch (err) {
            if (isFirstChunk) {
                bubbleInner.innerHTML = renderContent('Maaf, koneksi bermasalah. Periksa internet Anda dan coba lagi.');
            } else if (!fullReply) {
                bubbleInner.innerHTML = renderContent('Koneksi terputus saat menerima jawaban.');
            } else {
                history.push({ role: 'assistant', content: fullReply });
                saveSession();
            }
        }

        isLoading = false;
        sendBtn.disabled = input.value.trim() === '';
        input.focus();
        resetCollapseTimer();
    }

    // Input events
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

    // Quick questions
    messages.addEventListener('click', function (e) {
        const btn = e.target.closest('.aichat-quick-btn');
        if (!btn) return;
        const q = btn.dataset.q;
        if (q) sendMessage(q);
    });

    // Clear chat
    clearBtn.addEventListener('click', function () {
        history = [];
        sessionStorage.removeItem('sid_ai_chat');
        messages.innerHTML = '';
        const div = document.createElement('div');
        div.className = 'aichat-bubble aichat-bubble--ai aichat-welcome';
        div.id = 'aichat-welcome';
        div.innerHTML = '<div class="aichat-bubble-inner"><p>Chat telah dihapus. Ada yang bisa saya bantu?</p></div>';
        messages.appendChild(div);
        clearBtn.style.display = 'none';
        input.focus();
    });

    // Init
    loadSession();

    // Show badge after 3s if no history
    setTimeout(() => {
        if (!isOpen && history.length === 0) badge.style.display = 'flex';
    }, 3000);
});
</script>
