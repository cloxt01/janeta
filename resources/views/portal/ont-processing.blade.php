<x-layouts.dashboard title="Processing Request">

    <div
        id="processingApp"
        class="mx-auto flex min-h-[65vh] w-full max-w-4xl flex-col items-center justify-center px-4 py-12"
        data-request-id="{{ $request->uuid }}"
        data-status-url="{{ route('portal.ont.request-status', $request->uuid) }}"
        data-result-url="{{ route('portal.ont.result', $request->uuid) }}"
        data-channel-user="{{ auth()->id() }}"
    >

        <div class="w-full max-w-md rounded-2xl border border-slate-200/80 bg-white px-6 py-10 shadow-sm sm:px-10">
            <div class="flex flex-col items-center text-center" role="status" aria-live="polite">

                {{-- Robot --}}
                <div id="janetaStage" class="jb-stage relative h-40 w-40 sm:h-44 sm:w-44">

                    <div class="jb-robot absolute inset-0">
                        <div class="jb-glow"></div>

                        <svg
                            id="janetaNetwork"
                            viewBox="0 0 160 160"
                            class="relative h-full w-full overflow-visible"
                            aria-hidden="true"
                        >
                            <defs>
                                <linearGradient id="jbBody" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0" stop-color="#ffffff"/>
                                    <stop offset="1" stop-color="#e2e8f0"/>
                                </linearGradient>
                                <linearGradient id="jbArm" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0" stop-color="#cbd5e1"/>
                                    <stop offset="1" stop-color="#94a3b8"/>
                                </linearGradient>
                                <linearGradient id="jbScan" x1="0" y1="0" x2="1" y2="0">
                                    <stop offset="0" stop-color="#06b6d4" stop-opacity="0"/>
                                    <stop offset="0.5" stop-color="#22d3ee" stop-opacity="0.6"/>
                                    <stop offset="1" stop-color="#06b6d4" stop-opacity="0"/>
                                </linearGradient>
                                <clipPath id="jbVisorClip"><rect x="46" y="50" width="68" height="32" rx="16"/></clipPath>
                            </defs>

                            {{-- bayangan --}}
                            <ellipse class="jb-shadow" cx="80" cy="150" rx="40" ry="6" fill="#06b6d4" opacity=".18"/>

                            <g class="jb-float">
                                {{-- tentakel --}}
                                <g fill="none" stroke="url(#jbArm)" stroke-width="7" stroke-linecap="round">
                                    <path class="jb-arm" style="--d:-0.2s;--t:2.0s" d="M46 86 C32 102 50 116 38 136"/>
                                    <path class="jb-arm" style="--d:-0.9s;--t:1.8s" d="M59 88 C50 104 66 118 55 138"/>
                                    <path class="jb-arm" style="--d:-0.5s;--t:2.2s" d="M72 90 C66 106 78 120 70 140"/>
                                    <path class="jb-arm" style="--d:-1.3s;--t:2.0s" d="M88 90 C94 106 82 120 90 140"/>
                                    <path class="jb-arm" style="--d:-0.7s;--t:1.9s" d="M101 88 C110 104 94 118 105 138"/>
                                    <path class="jb-arm" style="--d:-1.1s;--t:2.1s" d="M114 86 C128 102 110 116 122 136"/>
                                </g>

                                {{-- antena --}}
                                <line x1="80" y1="25" x2="80" y2="13" stroke="#cbd5e1" stroke-width="3" stroke-linecap="round"/>
                                <circle class="jb-ping" cx="80" cy="10" r="4" fill="#06b6d4"/>

                                {{-- kepala --}}
                                <path d="M34 86 V64 C34 40 54 24 80 24 C106 24 126 40 126 64 V86 Q126 93 119 93 H41 Q34 93 34 86 Z"
                                      fill="url(#jbBody)" stroke="#cbd5e1" stroke-width="1.5"/>
                                <path d="M46 52 C52 38 66 31 80 31" fill="none" stroke="#fff" stroke-opacity=".9" stroke-width="3" stroke-linecap="round"/>

                                {{-- visor --}}
                                <rect x="46" y="50" width="68" height="32" rx="16" fill="#0f172a" stroke="#1e293b"/>
                                <g clip-path="url(#jbVisorClip)">
                                    <rect class="jb-scan" x="30" y="50" width="22" height="32" fill="url(#jbScan)"/>
                                </g>
                                <ellipse class="jb-eye" cx="66" cy="66" rx="5" ry="6.5" fill="#22d3ee"/>
                                <ellipse class="jb-eye" cx="94" cy="66" rx="5" ry="6.5" fill="#22d3ee"/>
                            </g>
                        </svg>
                    </div>

                    {{-- Success / failed icon --}}
                    <div
                        id="terminalIcon"
                        class="absolute inset-0 hidden items-center justify-center"
                    >
                        <div
                            id="terminalIconInner"
                            class="flex h-14 w-14 items-center justify-center rounded-full bg-white shadow-sm ring-1 ring-slate-200"
                        >
                            <svg
                                id="successIcon"
                                class="hidden h-7 w-7 text-emerald-500"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M5 12l5 5L20 7" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>

                            <svg
                                id="failedIcon"
                                class="hidden h-7 w-7 text-rose-500"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M18 6L6 18M6 6l12 12" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Status --}}
                <h2
                    id="statusTitle"
                    class="jb-fade mt-6 text-base font-semibold tracking-tight text-slate-800 sm:text-lg"
                >
                    Menunggu antrean...
                </h2>

                <p
                    id="statusMessage"
                    class="jb-fade mt-1.5 max-w-xs text-sm leading-relaxed text-slate-500"
                >
                    JANETA sedang mempersiapkan request.
                </p>

                {{-- Progress --}}
                <div class="mt-6 h-1 w-full max-w-xs overflow-hidden rounded-full bg-slate-100">
                    <div data-jb-bar class="jb-bar h-full rounded-full bg-cyan-500" style="width:8%"></div>
                </div>

                {{-- Tahapan --}}
                <ol class="mt-6 w-full max-w-xs space-y-2.5 text-left">
                    <li data-jb-step data-state="active" class="jb-step"><span class="jb-mark"></span><span>Menunggu antrean</span></li>
                    <li data-jb-step data-state="pending" class="jb-step"><span class="jb-mark"></span><span>Menghubungi OLT</span></li>
                    <li data-jb-step data-state="pending" class="jb-step"><span class="jb-mark"></span><span>Membaca data perangkat</span></li>
                    <li data-jb-step data-state="pending" class="jb-step"><span class="jb-mark"></span><span>Menyiapkan hasil</span></li>
                </ol>

                <div class="mt-7 flex items-center justify-center gap-2">
                    <span
                        id="systemPulse"
                        class="h-1.5 w-1.5 animate-pulse rounded-full bg-cyan-500"
                    ></span>

                    <span
                        id="systemLabel"
                        class="text-[11px] font-medium text-slate-400"
                    >
                        System active
                    </span>
                </div>

            </div>
        </div>

        {{-- Error --}}
        <div
            id="errorPanel"
            class="mt-6 hidden w-full max-w-md rounded-xl border border-rose-200 bg-rose-50 px-4 py-3"
        >
            <div class="text-sm font-semibold text-rose-700">
                Request gagal
            </div>

            <p
                id="errorMessage"
                class="mt-1 break-words text-sm text-rose-600"
            ></p>

            <a
                href="{{ route('dashboard') }}"
                class="mt-3 inline-flex text-xs font-semibold text-rose-700 hover:underline"
            >
                Kembali ke dashboard
            </a>
        </div>

        {{-- Request information, understated --}}
        <div class="mt-8 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-[10px] text-slate-400">

            <span class="font-mono">
                {{ $request->uuid }}
            </span>

            <span class="hidden h-1 w-1 rounded-full bg-slate-300 sm:block"></span>

            <span class="font-medium uppercase tracking-wider">
                {{ strtoupper(str_replace('.', ' · ', $request->operation)) }}
            </span>

        </div>

    </div>

    <style>
        .jb-glow{position:absolute;inset:14% 14% 20%;border-radius:9999px;background:radial-gradient(closest-side,rgba(6,182,212,.28),transparent 70%);filter:blur(14px);animation:jb-glow 3.2s ease-in-out infinite}
        .jb-float{animation:jb-float 3.2s ease-in-out infinite}
        .jb-shadow{transform-box:fill-box;transform-origin:center;animation:jb-shadow 3.2s ease-in-out infinite}
        .jb-arm{transform-box:fill-box;transform-origin:50% 0;animation:jb-sway var(--t,2s) ease-in-out var(--d,0s) infinite alternate}
        .jb-eye{transform-box:fill-box;transform-origin:center;animation:jb-blink 4s ease-in-out infinite}
        .jb-scan{animation:jb-scan 2.4s cubic-bezier(.45,0,.55,1) infinite}
        .jb-ping{transform-box:fill-box;transform-origin:center;animation:jb-ping 1.6s ease-in-out infinite}

        /* Robot memudar saat request selesai, ikon hasil tampil sendirian */
        .jb-robot{transition:opacity .45s ease,transform .45s ease}
        .jb-stage.jb-done .jb-robot{opacity:0;transform:scale(.92)}
        #terminalIcon:not(.hidden){animation:jb-fade .5s cubic-bezier(.22,1,.36,1)}

        .jb-bar{position:relative;transition:width .9s cubic-bezier(.22,1,.36,1),background-color .4s}
        .jb-bar::after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.55),transparent);transform:translateX(-100%);animation:jb-shimmer 1.6s linear infinite}
        .jb-bar[data-state="failed"]{background-color:#f43f5e}
        .jb-bar[data-state="failed"]::after,.jb-bar[data-state="done"]::after{animation:none;display:none}

        .jb-fade{animation:jb-fade .5s cubic-bezier(.22,1,.36,1)}

        .jb-step{display:flex;align-items:center;gap:.75rem;font-size:12px;color:#94a3b8;transition:color .4s}
        .jb-mark{position:relative;flex:none;width:16px;height:16px;border-radius:9999px;border:1.5px solid #e2e8f0;transition:all .4s}
        .jb-step[data-state="active"]{color:#1e293b;font-weight:500}
        .jb-step[data-state="active"] .jb-mark{border-color:rgba(6,182,212,.3);border-top-color:#06b6d4;animation:jb-spin .8s linear infinite}
        .jb-step[data-state="done"]{color:#64748b}
        .jb-step[data-state="done"] .jb-mark{background:#06b6d4;border-color:#06b6d4}
        .jb-step[data-state="done"] .jb-mark::after{content:"";position:absolute;left:4.5px;top:2px;width:4px;height:7px;border:solid #fff;border-width:0 1.5px 1.5px 0;transform:rotate(45deg)}
        .jb-step[data-state="failed"]{color:#be123c;font-weight:500}
        .jb-step[data-state="failed"] .jb-mark{background:#f43f5e;border-color:#f43f5e}
        .jb-step[data-state="failed"] .jb-mark::before,.jb-step[data-state="failed"] .jb-mark::after{content:"";position:absolute;left:50%;top:50%;width:8px;height:1.5px;background:#fff;border-radius:1px}
        .jb-step[data-state="failed"] .jb-mark::before{transform:translate(-50%,-50%) rotate(45deg)}
        .jb-step[data-state="failed"] .jb-mark::after{transform:translate(-50%,-50%) rotate(-45deg)}

        @keyframes jb-float{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
        @keyframes jb-shadow{0%,100%{transform:scale(1);opacity:.18}50%{transform:scale(.85);opacity:.1}}
        @keyframes jb-glow{0%,100%{opacity:.7;transform:scale(1)}50%{opacity:1;transform:scale(1.08)}}
        @keyframes jb-sway{from{transform:rotate(-7deg)}to{transform:rotate(7deg)}}
        @keyframes jb-blink{0%,92%,100%{transform:scaleY(1)}95%{transform:scaleY(.1)}}
        @keyframes jb-scan{0%{transform:translateX(0)}100%{transform:translateX(70px)}}
        @keyframes jb-ping{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.5);opacity:.5}}
        @keyframes jb-shimmer{to{transform:translateX(100%)}}
        @keyframes jb-fade{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
        @keyframes jb-spin{to{transform:rotate(360deg)}}

        @media (prefers-reduced-motion:reduce){
            .jb-glow,.jb-float,.jb-shadow,.jb-arm,.jb-eye,.jb-scan,.jb-ping,.jb-bar::after,.jb-fade,#terminalIcon:not(.hidden){animation:none}
            .jb-step[data-state="active"] .jb-mark{animation-duration:2.4s}
        }
    </style>

    <script>
        /*
         * Loader bertahap untuk halaman Processing.
         * Tidak mengubah ID yang dipakai script status yang sudah ada
         * (statusTitle, statusMessage, terminalIcon, successIcon, failedIcon, dst).
         * Tahapan mengikuti teks status asli: tetap di "Menunggu antrean" selama
         * teks masih menyebut antrean, lalu berjalan maju sampai hasil datang.
         */
        (() => {
            const root = document.getElementById('processingApp');
            if (!root) return;

            const stage = document.getElementById('janetaStage');
            const steps = [...root.querySelectorAll('[data-jb-step]')];
            const bar = root.querySelector('[data-jb-bar]');
            const title = document.getElementById('statusTitle');
            const message = document.getElementById('statusMessage');
            const terminal = document.getElementById('terminalIcon');
            const successIcon = document.getElementById('successIcon');
            const failedIcon = document.getElementById('failedIcon');

            // Lama tiap tahap (ms) setelah antrean: tahap 2 → 3, lalu 3 → 4.
            // Tahap terakhir bertahan sampai hasil datang.
            const holds = [2200, 3800];

            let index = 0;
            let timer = null;
            let finished = false;

            const visible = (el) => el && !el.classList.contains('hidden');

            function render() {
                steps.forEach((el, i) => {
                    el.dataset.state = i < index ? 'done' : i === index ? 'active' : 'pending';
                });
                bar.style.width = `${Math.round(((index + 0.6) / steps.length) * 100)}%`;
            }

            function advance() {
                clearTimeout(timer);
                if (finished || index >= steps.length - 1) return;

                timer = setTimeout(() => {
                    index++;
                    render();
                    advance();
                }, holds[index - 1] ?? 3000);
            }

            function syncWithStatus() {
                if (finished || index > 0) return;

                if (!/antre|menunggu/i.test(title.textContent || '')) {
                    index = 1;
                    render();
                    advance();
                }
            }

            function finish() {
                if (finished) return;
                finished = true;
                clearTimeout(timer);

                stage.classList.add('jb-done');

                if (visible(failedIcon) && !visible(successIcon)) {
                    steps[index].dataset.state = 'failed';
                    bar.dataset.state = 'failed';
                } else {
                    steps.forEach((el) => { el.dataset.state = 'done'; });
                    bar.style.width = '100%';
                    bar.dataset.state = 'done';
                }
            }

            function checkTerminal() {
                if (visible(terminal) && (visible(successIcon) || visible(failedIcon))) {
                    finish();
                }
            }

            // Teks status berubah → animasi fade ulang + sinkronkan tahapan.
            function restartFade(el) {
                el.classList.remove('jb-fade');
                void el.offsetWidth;
                el.classList.add('jb-fade');
            }

            const textObserver = new MutationObserver((records) => {
                records.forEach((r) => restartFade(r.target.nodeType === 1 ? r.target : r.target.parentElement));
                syncWithStatus();
            });

            [title, message].forEach((el) => {
                textObserver.observe(el, { childList: true, characterData: true, subtree: true });
            });

            // Hasil akhir muncul (sukses / gagal).
            new MutationObserver(checkTerminal).observe(terminal, {
                attributes: true,
                attributeFilter: ['class'],
                subtree: true,
            });

            render();
            syncWithStatus();
            checkTerminal();
        })();
    </script>

</x-layouts.dashboard>
