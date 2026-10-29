@props([
    'proposal',
    'locale' => 'en',
    'slug',
])
@php
    $chatbot = app(\App\Services\AiChatbot\ProposalChatbot::class);
    $chatEnabled = rescue(fn () => $chatbot->isEnabledFor($proposal), false, report: false);

    if ($chatEnabled) {
        $isIndonesian = $locale === 'id';
        $chatConfig = [
            'endpoint' => route('proposal.chat', ['slug' => $slug]),
            'csrf' => csrf_token(),
            'locale' => $locale,
            'storageKey' => 'kanjo-ai-chat:' . $proposal->id,
            'welcome' => $chatbot->welcomeMessage($locale),
            'maxLength' => \App\Services\AiChatbot\ProposalChatbot::MAX_MESSAGE_LENGTH,
            'text' => [
                'error' => $isIndonesian ? 'Pesan gagal dikirim. Silakan coba lagi.' : 'Message failed to send. Please try again.',
                'session' => $isIndonesian ? 'Sesi Anda telah berakhir. Muat ulang halaman ini.' : 'Your session has ended. Please reload this page.',
            ],
        ];
        $quickQuestions = $chatbot->quickQuestions($proposal, $locale);
        $botName = $chatbot->botName();
        $avatarUrl = $chatbot->avatarUrl();
        $accentColor = $chatbot->accentColor($proposal);
    }
@endphp
@if($chatEnabled)
    <div class="ai-chat no-print" data-ai-chat style="--chat-accent: {{ $accentColor }}">
        <script type="application/json" data-ai-chat-config>@json($chatConfig)</script>

        <section class="ai-chat-panel" data-ai-chat-panel hidden role="dialog" aria-label="{{ $botName }}">
            <header class="ai-chat-header">
                <div class="flex min-w-0 items-center gap-3">
                    @if($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="" class="ai-chat-avatar object-cover">
                    @else
                        <span class="ai-chat-avatar" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5"><path d="M12 3l1.9 4.6L18.5 9.5l-4.6 1.9L12 16l-1.9-4.6L5.5 9.5l4.6-1.9L12 3z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M18.5 15.5l.8 1.9 1.9.8-1.9.8-.8 1.9-.8-1.9-1.9-.8 1.9-.8.8-1.9z" fill="currentColor"/></svg>
                        </span>
                    @endif
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">{{ $botName }}</p>
                        <p class="truncate text-[11px] opacity-80">{{ $proposal->document_number }}</p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-1">
                    <button type="button" class="ai-chat-icon-button" data-ai-chat-reset
                        title="{{ $isIndonesian ? 'Chat baru' : 'New chat' }}" aria-label="{{ $isIndonesian ? 'Chat baru' : 'New chat' }}">
                        <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4"><path d="M4 12a8 8 0 0113.7-5.7L20 8.5M20 4v4.5h-4.5M20 12a8 8 0 01-13.7 5.7L4 15.5M4 20v-4.5h4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <button type="button" class="ai-chat-icon-button" data-ai-chat-close
                        title="{{ $isIndonesian ? 'Tutup' : 'Close' }}" aria-label="{{ $isIndonesian ? 'Tutup' : 'Close' }}">
                        <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </button>
                </div>
            </header>

            <div class="ai-chat-messages" data-ai-chat-messages aria-live="polite"></div>

            @if(count($quickQuestions) > 0)
                <div class="ai-chat-quick" data-ai-chat-quick>
                    @foreach($quickQuestions as $question)
                        <button type="button" class="ai-chat-chip" data-ai-chat-question>{{ $question }}</button>
                    @endforeach
                </div>
            @endif

            <form class="ai-chat-form" data-ai-chat-form>
                <textarea class="ai-chat-input" data-ai-chat-input rows="1" maxlength="{{ $chatConfig['maxLength'] }}"
                    placeholder="{{ $isIndonesian ? 'Tulis pertanyaan Anda…' : 'Type your question…' }}"
                    aria-label="{{ $isIndonesian ? 'Pesan' : 'Message' }}"></textarea>
                <button type="submit" class="ai-chat-send" data-ai-chat-send aria-label="{{ $isIndonesian ? 'Kirim' : 'Send' }}">
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4"><path d="M5 12h13M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </form>
            <p class="px-4 pb-3 text-center text-[10px] text-slate-400">
                {{ $isIndonesian ? 'Jawaban dibuat oleh AI dan bisa saja keliru.' : 'Answers are AI-generated and may be inaccurate.' }}
            </p>
        </section>

        <button type="button" class="ai-chat-launcher" data-ai-chat-toggle aria-expanded="false"
            aria-label="{{ $isIndonesian ? 'Tanya tentang proposal ini' : 'Ask about this proposal' }}">
            <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" data-ai-chat-icon-open><path d="M4 5.5A1.5 1.5 0 015.5 4h13A1.5 1.5 0 0120 5.5v9a1.5 1.5 0 01-1.5 1.5H9l-4 4v-4h0A1 1 0 014 15V5.5z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M8 9h8M8 12h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
            <svg viewBox="0 0 24 24" fill="none" class="hidden h-6 w-6" data-ai-chat-icon-close><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </div>

    <script>
        (() => {
            const root = document.querySelector('[data-ai-chat]');

            if (!root) {
                return;
            }

            const config = JSON.parse(root.querySelector('[data-ai-chat-config]').textContent);
            const panel = root.querySelector('[data-ai-chat-panel]');
            const toggle = root.querySelector('[data-ai-chat-toggle]');
            const list = root.querySelector('[data-ai-chat-messages]');
            const form = root.querySelector('[data-ai-chat-form]');
            const input = root.querySelector('[data-ai-chat-input]');
            const send = root.querySelector('[data-ai-chat-send]');
            const quick = root.querySelector('[data-ai-chat-quick]');

            // Chat lives only in this tab's sessionStorage: closing the tab starts over.
            const emptyState = () => ({ conversation: null, messages: [], open: false });
            const load = () => {
                try {
                    return { ...emptyState(), ...JSON.parse(sessionStorage.getItem(config.storageKey) || '{}') };
                } catch (error) {
                    return emptyState();
                }
            };
            let state = load();
            let busy = false;

            const save = () => {
                try {
                    sessionStorage.setItem(config.storageKey, JSON.stringify(state));
                } catch (error) {
                    // Storage full or blocked: chat still works for this page view.
                }
            };

            const bubble = (role, content, isHtml = false, extraClass = '') => {
                const element = document.createElement('div');
                element.className = `ai-chat-bubble ai-chat-bubble-${role} ${extraClass}`.trim();

                if (isHtml) {
                    element.innerHTML = content;
                } else {
                    element.textContent = content;
                }

                list.appendChild(element);

                return element;
            };

            const render = () => {
                list.innerHTML = '';

                if (config.welcome) {
                    bubble('assistant', config.welcome);
                }

                state.messages.forEach((message) => {
                    if (message.role === 'user') {
                        bubble('user', message.text);
                    } else {
                        bubble('assistant', message.html, true, message.failed ? 'ai-chat-bubble-error' : '');
                    }
                });

                list.scrollTop = list.scrollHeight;
            };

            const setOpen = (open) => {
                state.open = open;
                panel.hidden = !open;
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                toggle.querySelector('[data-ai-chat-icon-open]').classList.toggle('hidden', open);
                toggle.querySelector('[data-ai-chat-icon-close]').classList.toggle('hidden', !open);
                save();

                if (open) {
                    list.scrollTop = list.scrollHeight;
                    input.focus();
                }
            };

            const setBusy = (value) => {
                busy = value;
                send.disabled = value;
                input.disabled = value;
                quick?.querySelectorAll('button').forEach((button) => { button.disabled = value; });
            };

            const resizeInput = () => {
                input.style.height = 'auto';
                input.style.height = `${Math.min(input.scrollHeight, 120)}px`;
            };

            const ask = async (rawText) => {
                const text = rawText.trim().slice(0, config.maxLength);

                if (!text || busy) {
                    return;
                }

                state.messages.push({ role: 'user', text });
                save();
                bubble('user', text);
                input.value = '';
                resizeInput();

                const typing = bubble('assistant', '<span class="ai-chat-typing"><span></span><span></span><span></span></span>', true);
                list.scrollTop = list.scrollHeight;
                setBusy(true);

                try {
                    const response = await fetch(config.endpoint, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': config.csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ message: text, conversation: state.conversation, locale: config.locale }),
                    });
                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        if (payload.code === 'conversation_limit') {
                            state.conversation = null;
                        }

                        const message = [401, 419].includes(response.status)
                            ? config.text.session
                            : (payload.message || config.text.error);

                        throw new Error(message);
                    }

                    state.conversation = payload.conversation;
                    state.messages.push({ role: 'assistant', html: payload.reply.html, failed: !!payload.reply.failed });
                } catch (error) {
                    const message = document.createElement('p');
                    message.textContent = error.message || config.text.error;
                    state.messages.push({ role: 'assistant', html: message.outerHTML, failed: true });
                } finally {
                    typing.remove();
                    save();
                    render();
                    setBusy(false);
                    input.focus();
                }
            };

            toggle.addEventListener('click', () => setOpen(panel.hidden));
            root.querySelector('[data-ai-chat-close]').addEventListener('click', () => setOpen(false));
            root.querySelector('[data-ai-chat-reset]').addEventListener('click', () => {
                if (busy) {
                    return;
                }

                state = { ...emptyState(), open: true };
                save();
                render();
                input.focus();
            });

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                ask(input.value);
            });

            input.addEventListener('input', resizeInput);
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
                    event.preventDefault();
                    ask(input.value);
                }
            });

            quick?.querySelectorAll('[data-ai-chat-question]').forEach((button) => {
                button.addEventListener('click', () => ask(button.textContent));
            });

            render();
            setOpen(!!state.open);
        })();
    </script>
@endif
