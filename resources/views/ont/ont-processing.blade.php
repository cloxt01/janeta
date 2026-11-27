
<x-layouts.dashboard title="Processing Request">

    <div
        id="processingApp"
        class="pr-hero mx-auto flex min-h-[70vh] w-full max-w-4xl flex-col items-center justify-center px-4 py-10 sm:px-8"
        data-request-id="{{ $request->uuid }}"
        data-status-url="{{ route('ont.request-status', $request->uuid) }}"
        data-result-url="{{ route('ont.result', $request->uuid) }}"
        data-channel-user="{{ auth()->id() }}"
        data-created-ms="{{ $request->created_at ? $request->created_at->timestamp * 1000 : '' }}"
    >

        {{-- STATUS --}}
        <div class="mb-6 flex flex-wrap items-center justify-center gap-3 sm:mb-7">

            <span id="pillWrap" class="pr-pill" data-tone="amber">
                <i class="pr-pulse-dot"></i>
                <span id="pillText">SENDING</span>
            </span>

            <span class="text-[10px] font-medium uppercase tracking-widest text-[#777]">
                {{ strtoupper(str_replace('.', ' · ', $request->operation)) }}
            </span>

        </div>

        {{-- HERO --}}
        <div class="flex w-full flex-col items-center text-center">

            <div id="janetaStage" class="jb-stage relative">

                {{-- ROBOT --}}
                <div class="jb-robot absolute inset-0">

                    <div class="jb-glow"></div>

                    <svg id="janetaNetwork"
                         viewBox="0 0 160 160"
                         class="relative h-full w-full overflow-visible"
                         aria-hidden="true">

                        <defs>
                            <linearGradient id="jbBody" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#f2f4f6"/>
                                <stop offset="1" stop-color="#b8c0c9"/>
                            </linearGradient>

                            <linearGradient id="jbArm" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#8a9099"/>
                                <stop offset="1" stop-color="#4f545c"/>
                            </linearGradient>

                            <linearGradient id="jbScan" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0" stop-color="#00a34a" stop-opacity="0"/>
                                <stop offset=".5" stop-color="#4cd286" stop-opacity=".6"/>
                                <stop offset="1" stop-color="#00a34a" stop-opacity="0"/>
                            </linearGradient>

                            <clipPath id="jbVisorClip">
                                <rect x="46" y="50" width="68" height="32" rx="16"/>
                            </clipPath>
                        </defs>

                        <ellipse class="jb-shadow"
                                 cx="80" cy="150" rx="40" ry="6"
                                 fill="#00a34a" opacity=".22"/>

                        <g class="jb-float">

                            <g fill="none"
                               stroke="url(#jbArm)"
                               stroke-width="7"
                               stroke-linecap="round">

                                <path class="jb-arm" style="--d:-.2s;--t:2s"
                                      d="M46 86 C32 102 50 116 38 136"/>

                                <path class="jb-arm" style="--d:-.9s;--t:1.8s"
                                      d="M59 88 C50 104 66 118 55 138"/>

                                <path class="jb-arm" style="--d:-.5s;--t:2.2s"
                                      d="M72 90 C66 106 78 120 70 140"/>

                                <path class="jb-arm" style="--d:-1.3s;--t:2s"
                                      d="M88 90 C94 106 82 120 90 140"/>

                                <path class="jb-arm" style="--d:-.7s;--t:1.9s"
                                      d="M101 88 C110 104 94 118 105 138"/>

                                <path class="jb-arm" style="--d:-1.1s;--t:2.1s"
                                      d="M114 86 C128 102 110 116 122 136"/>

                            </g>

                            <line x1="80" y1="25" x2="80" y2="13"
                                  stroke="#8a9099"
                                  stroke-width="3"
                                  stroke-linecap="round"/>

                            <circle class="jb-ping"
                                    cx="80" cy="10" r="4"
                                    fill="#00a34a"/>

                            <path d="M34 86 V64 C34 40 54 24 80 24 C106 24 126 40 126 64 V86 Q126 93 119 93 H41 Q34 93 34 86 Z"
                                  fill="url(#jbBody)"
                                  stroke="#6b717a"
                                  stroke-width="1.5"/>

                            <path d="M46 52 C52 38 66 31 80 31"
                                  fill="none"
                                  stroke="#fff"
                                  stroke-opacity=".9"
                                  stroke-width="3"
                                  stroke-linecap="round"/>

                            <rect x="46" y="50" width="68" height="32" rx="16"
                                  fill="#0b0d10"
                                  stroke="#1e2126"/>

                            <g clip-path="url(#jbVisorClip)">
                                <rect class="jb-scan"
                                      x="30" y="50" width="22" height="32"
                                      fill="url(#jbScan)"/>
                            </g>

                            <ellipse class="jb-eye"
                                     cx="66" cy="66" rx="5" ry="6.5"
                                     fill="#4cd286"/>

                            <ellipse class="jb-eye"
                                     cx="94" cy="66" rx="5" ry="6.5"
                                     fill="#4cd286"/>

                        </g>
                    </svg>

                </div>

                {{-- TERMINAL ICON --}}
                <div id="terminalIcon"
                     class="absolute inset-0 hidden items-center justify-center">

                    <div id="terminalIconInner"
                         class="flex h-[70px] w-[70px] items-center justify-center rounded-full bg-[#202020] ring-1 ring-[#484848]">

                        <svg id="successIcon"
                             class="hidden h-8 w-8 text-[#4cd286]"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">

                            <path d="M5 12l5 5L20 7"
                                  stroke-linecap="round"
                                  stroke-linejoin="round"/>

                        </svg>

                        <svg id="failedIcon"
                             class="hidden h-8 w-8 text-[#f08a8a]"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">

                            <path d="M18 6L6 18M6 6l12 12"
                                  stroke-linecap="round"/>

                        </svg>

                    </div>
                </div>

            </div>

            {{-- TITLE --}}
            <h2 id="stageTitle"
                class="jb-fade mt-4 text-xl font-semibold tracking-tight text-[#f2f2f2] sm:text-2xl">
                Mengirim request ke JANETA
            </h2>

            {{-- DESCRIPTION --}}
            <p id="stageMessage"
               class="jb-fade mt-2 max-w-lg px-3 text-center text-sm leading-relaxed text-[#858585]">
                Request sedang dikirim dan divalidasi.
            </p>

            {{-- HINT --}}
            <p id="hintText"
               class="mt-2 min-h-[16px] text-[11px] text-[#666]">
                Menghubungi JANETA…
            </p>

        </div>


        {{-- PIPELINE --}}
        <div class="mt-10 w-full max-w-4xl sm:mt-12">

            <div class="pr-pipeline" id="pipeline">

                {{-- STEP 1 --}}
                <div class="pr-node"
                     data-node
                     style="--c:#4cd286"
                     data-state="active">

                    <div class="pr-dot">

                        <i data-lucide="send"></i>

                        <svg class="pr-check"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2.6">

                            <path d="M5 12l5 5L20 7"/>

                        </svg>

                    </div>

                    <div class="pr-node-label">Kirim</div>

                    <div class="pr-node-sub" data-sub>
                        Mengirim…
                    </div>

                </div>

                <div class="pr-seg" data-seg>
                    <i class="pr-fill"></i>
                </div>


                {{-- STEP 2 --}}
                <div class="pr-node"
                     data-node
                     style="--c:#e0b04c"
                     data-state="pending">

                    <div class="pr-dot">

                        <i data-lucide="layers"></i>

                        <svg class="pr-check"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2.6">

                            <path d="M5 12l5 5L20 7"/>

                        </svg>

                    </div>

                    <div class="pr-node-label">Antrean</div>

                    <div class="pr-node-sub" data-sub>
                        Menunggu
                    </div>

                </div>

                <div class="pr-seg" data-seg>
                    <i class="pr-fill"></i>
                </div>


                {{-- STEP 3 --}}
                <div class="pr-node"
                     data-node
                     style="--c:#4cd286"
                     data-state="pending">

                    <div class="pr-dot">

                        <i data-lucide="server"></i>

                        <svg class="pr-check"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2.6">

                            <path d="M5 12l5 5L20 7"/>

                        </svg>

                    </div>

                    <div class="pr-node-label">Proses OLT</div>

                    <div class="pr-node-sub" data-sub>
                        Menunggu
                    </div>

                </div>

                <div class="pr-seg" data-seg>
                    <i class="pr-fill"></i>
                </div>


                {{-- STEP 4 --}}
                <div class="pr-node"
                     data-node
                     style="--c:#4cd286"
                     data-state="pending">

                    <div class="pr-dot">

                        <i data-lucide="clipboard-check"></i>

                        <svg class="pr-check"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2.6">

                            <path d="M5 12l5 5L20 7"/>

                        </svg>

                    </div>

                    <div class="pr-node-label">Hasil</div>

                    <div class="pr-node-sub" data-sub>
                        Menunggu
                    </div>

                </div>

            </div>


            {{-- PROGRESS --}}
            <div class="mt-8 w-full">

                <div class="mb-2 flex items-center justify-between text-[11px]">

                    <span id="progressLabel"
                          class="font-medium text-[#aaa]">
                        Mengirim ke JANETA
                    </span>

                    <span id="progressPct"
                          class="font-mono tabular-nums text-[#777]">
                        8%
                    </span>

                </div>

                <div class="h-1 w-full overflow-hidden rounded-full bg-[#292929]">

                    <div id="progressBar"
                         class="jb-bar h-full rounded-full bg-[#00a34a]"
                         style="width:8%">
                    </div>

                </div>

            </div>

        </div>


        {{-- REQUEST ID --}}
        <div class="mt-10 flex w-full max-w-2xl flex-wrap items-center justify-center gap-x-3 gap-y-2 border-t border-[#2a2a2a] pt-5 sm:mt-12">

            <span class="text-[10px] font-semibold uppercase tracking-widest text-[#777]">
                Request ID :
            </span>

            <span id="requestIdText"
                  class="max-w-full break-all font-mono text-[11px] text-[#999]">
                {{ $request->uuid }}
            </span>

            <button type="button"
                    id="copyId"
                    class="inline-flex shrink-0 items-center gap-1.5 text-[11px] font-medium text-[#999] transition hover:text-[#4cd286]">

                <i data-lucide="copy" class="h-3 w-3"></i>

                <span id="copyLabel">Salin</span>

            </button>

        </div>


        {{-- ERROR PANEL --}}
        <div id="errorPanel"
             class="mt-7 hidden w-full max-w-2xl rounded-xl border border-[#5e2b2b] bg-[#2a1717]/40 p-5">

            <div class="flex items-start gap-3">

                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#5e2b2b]/40">

                    <i data-lucide="alert-circle"
                       class="h-5 w-5 text-[#f08a8a]">
                    </i>

                </div>

                <div class="min-w-0 flex-1">

                    <h3 class="text-sm font-semibold text-[#f08a8a]">
                        Request gagal diproses
                    </h3>

                    <p id="errorMessage"
                       class="mt-2 break-words text-xs leading-relaxed text-[#d99b9b]">
                    </p>

                    <div class="mt-4 rounded-lg border border-[#5e2b2b]/70 bg-black/20 p-3">

                        <p class="text-[10px] font-semibold uppercase tracking-wider text-[#999]">
                            Informasi untuk laporan
                        </p>

                        <div class="mt-2 flex flex-wrap items-center justify-between gap-3">

                            <span class="break-all font-mono text-[11px] text-[#aaa]">
                                {{ $request->uuid }}
                            </span>

                            <button type="button"
                                    id="copyErrorInfo"
                                    class="inline-flex shrink-0 items-center gap-1.5 text-[11px] font-medium text-[#f08a8a] transition hover:text-white">

                                <i data-lucide="copy" class="h-3 w-3"></i>

                                <span id="copyErrorLabel">
                                    Salin informasi
                                </span>

                            </button>

                        </div>

                    </div>

                    <p class="mt-3 text-[11px] leading-relaxed text-[#999]">
                        Silakan salin informasi di atas dan laporkan kepada administrator JANETA untuk pemeriksaan lebih lanjut.
                    </p>

                    <a href="{{ route('dashboard') }}"
                       class="mt-4 inline-flex items-center gap-2 text-xs font-semibold text-[#f08a8a] transition hover:text-white">

                        <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>

                        Kembali ke dashboard

                    </a>

                </div>

            </div>

        </div>

    </div>


    {{-- =====================================================
         STYLES
    ====================================================== --}}

    <style>

        /* MAIN */

        #processingApp {
            width: 100%;
            max-width: 960px;
            margin-inline: auto;
        }

        .pr-hero {
            background:
                radial-gradient(
                    ellipse 65% 35% at 50% 0%,
                    rgba(0, 163, 74, .055),
                    transparent 100%
                );
        }


        /* ROBOT */

        .jb-stage {
            width: 128px;
            height: 128px;
            flex: 0 0 128px;
            margin-inline: auto;
        }

        .jb-glow {
            position: absolute;
            inset: 14% 14% 20%;
            border-radius: 9999px;
            background: radial-gradient(
                closest-side,
                rgba(0,163,74,.30),
                transparent 70%
            );
            filter: blur(14px);
            animation: jb-glow 3.2s ease-in-out infinite;
        }

        .jb-float {
            animation: jb-float 3.2s ease-in-out infinite;
        }

        .jb-shadow {
            transform-box: fill-box;
            transform-origin: center;
            animation: jb-shadow 3.2s ease-in-out infinite;
        }

        .jb-arm {
            transform-box: fill-box;
            transform-origin: 50% 0;
            animation: jb-sway var(--t,2s) ease-in-out var(--d,0s) infinite alternate;
        }

        .jb-eye {
            transform-box: fill-box;
            transform-origin: center;
            animation: jb-blink 4s ease-in-out infinite;
        }

        .jb-scan {
            animation: jb-scan 2.4s cubic-bezier(.45,0,.55,1) infinite;
        }

        .jb-ping {
            transform-box: fill-box;
            transform-origin: center;
            animation: jb-ping 1.6s ease-in-out infinite;
        }

        .jb-robot {
            transition: opacity .45s ease, transform .45s ease;
        }

        .jb-stage.jb-done .jb-robot {
            opacity: 0;
            transform: scale(.92);
            pointer-events: none;
        }

        #terminalIcon:not(.hidden) {
            animation: jb-fade .5s cubic-bezier(.22,1,.36,1);
        }

        .jb-fade {
            animation: jb-fade .5s cubic-bezier(.22,1,.36,1);
        }


        /* STATUS PILL */

        .pr-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: 1px solid #3a3a3a;
            border-radius: 999px;
            background: #242424;
            padding: 5px 12px;
            color: #8a8a8a;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            transition: all .3s;
        }

        .pr-pill[data-tone="amber"] {
            background: #2a2314;
            border-color: #5a4a1f;
            color: #e0b04c;
        }

        .pr-pill[data-tone="green"],
        .pr-pill[data-tone="done"] {
            background: #14281d;
            border-color: #1f5a38;
            color: #4cd286;
        }

        .pr-pill[data-tone="red"] {
            background: #2a1717;
            border-color: #5e2b2b;
            color: #f08a8a;
        }

        .pr-pulse-dot {
            position: relative;
            width: 6px;
            height: 6px;
            flex: 0 0 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .pr-pulse-dot::after {
            content: "";
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            border: 1px solid currentColor;
            animation: pr-ripple 1.8s ease-out infinite;
        }

        .pr-pill[data-tone="done"] .pr-pulse-dot::after,
        .pr-pill[data-tone="red"] .pr-pulse-dot::after {
            animation: none;
            opacity: 0;
        }


        /* PIPELINE */

        .pr-pipeline {
            display: grid;
            width: 100%;
            grid-template-columns:
                minmax(56px, 76px)
                minmax(16px, 1fr)
                minmax(56px, 76px)
                minmax(16px, 1fr)
                minmax(56px, 76px)
                minmax(16px, 1fr)
                minmax(56px, 76px);
            align-items: start;
            justify-content: center;
            column-gap: 0;
        }

        .pr-node {
            display: flex;
            min-width: 0;
            width: 100%;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .pr-dot {
            position: relative;
            display: grid;
            place-items: center;
            width: 52px;
            height: 52px;
            flex-shrink: 0;
            border: 1px solid #3a3a3a;
            border-radius: 999px;
            background: #242424;
            color: #6f6f6f;
            transition:
                background .4s,
                border-color .4s,
                color .4s,
                box-shadow .4s;
        }

        .pr-dot > svg,
        .pr-dot > i {
            width: 19px;
            height: 19px;
        }

        .pr-dot .pr-check {
            position: absolute;
            inset: 0;
            display: none;
            width: 19px;
            height: 19px;
            margin: auto;
        }

        .pr-node-label {
            margin-top: 12px;
            color: #6f6f6f;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.4;
            white-space: nowrap;
            transition: color .3s;
        }

        .pr-node-sub {
            margin-top: 5px;
            color: #555;
            font-size: 10px;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            line-height: 1.4;
            white-space: nowrap;
            transition: color .3s;
        }


        /* ACTIVE */

        .pr-node[data-state="active"] .pr-dot {
            border-color: var(--c);
            color: var(--c);
            background: color-mix(in srgb, var(--c) 13%, #1c1c1c);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--c) 12%, transparent);
        }

        .pr-node[data-state="active"] .pr-dot::before,
        .pr-node[data-state="active"] .pr-dot::after {
            content: "";
            position: absolute;
            inset: -1px;
            border: 1px solid var(--c);
            border-radius: 999px;
            animation: pr-node-ripple 2.2s ease-out infinite;
        }

        .pr-node[data-state="active"] .pr-dot::after {
            animation-delay: 1.1s;
        }

        .pr-node[data-state="active"] .pr-node-label {
            color: #f2f2f2;
        }

        .pr-node[data-state="active"] .pr-node-sub {
            color: var(--c);
        }


        /* DONE */

        .pr-node[data-state="done"] .pr-dot {
            border-color: #0b8a3e;
            background: #0b8a3e;
            color: #fff;
        }

        .pr-node[data-state="done"] .pr-dot > svg:not(.pr-check),
        .pr-node[data-state="done"] .pr-dot > i {
            display: none;
        }

        .pr-node[data-state="done"] .pr-dot .pr-check {
            display: block;
            animation: pr-pop .35s cubic-bezier(.22,1,.36,1);
        }

        .pr-node[data-state="done"] .pr-node-label {
            color: #c4c4c4;
        }

        .pr-node[data-state="done"] .pr-node-sub {
            color: #6f9f84;
        }


        /* FAILED */

        .pr-node[data-state="failed"] .pr-dot {
            border-color: #ef4444;
            background: #2a1717;
            color: #f08a8a;
        }

        .pr-node[data-state="failed"] .pr-node-label,
        .pr-node[data-state="failed"] .pr-node-sub {
            color: #f08a8a;
        }


        /* CONNECTOR */

        .pr-seg {
            position: relative;
            width: 100%;
            height: 2px;
            min-width: 0;
            margin-top: 25px;
            overflow: hidden;
            border-radius: 2px;
            background: #2c2c2c;
        }

        .pr-fill {
            position: absolute;
            inset: 0;
            transform: scaleX(var(--f,0));
            transform-origin: left;
            background: linear-gradient(90deg,#00a34a,#4cd286);
            transition: transform .9s cubic-bezier(.22,1,.36,1);
        }

        .pr-seg[data-flow="on"]::before,
        .pr-seg[data-flow="on"]::after {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 36px;
            background: linear-gradient(
                90deg,
                transparent,
                #4cd286,
                transparent
            );
            animation: pr-flow 1.5s linear infinite;
        }

        .pr-seg[data-flow="on"]::after {
            animation-delay: -.75s;
        }

        .pr-seg[data-flow="on"][data-tone="amber"]::before,
        .pr-seg[data-flow="on"][data-tone="amber"]::after {
            background: linear-gradient(
                90deg,
                transparent,
                #e0b04c,
                transparent
            );
        }


        /* PROGRESS */

        .jb-bar {
            position: relative;
            min-width: 0;
            transition:
                width .9s cubic-bezier(.22,1,.36,1),
                background-color .4s;
        }

        .jb-bar::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255,255,255,.35),
                transparent
            );
            transform: translateX(-100%);
            animation: jb-shimmer 1.6s linear infinite;
        }

        .jb-bar[data-state="failed"] {
            background-color: #ef4444;
        }

        .jb-bar[data-state="failed"]::after,
        .jb-bar[data-state="done"]::after {
            display: none;
            animation: none;
        }


        /* ERROR */

        #errorPanel {
            animation: jb-fade .4s ease-out;
        }


        /* ANIMATIONS */

        @keyframes jb-float {
            0%,100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }

        @keyframes jb-shadow {
            0%,100% {
                transform: scale(1);
                opacity: .22;
            }
            50% {
                transform: scale(.85);
                opacity: .12;
            }
        }

        @keyframes jb-glow {
            0%,100% {
                opacity: .7;
                transform: scale(1);
            }
            50% {
                opacity: 1;
                transform: scale(1.08);
            }
        }

        @keyframes jb-sway {
            from { transform: rotate(-7deg); }
            to { transform: rotate(7deg); }
        }

        @keyframes jb-blink {
            0%,92%,100% { transform: scaleY(1); }
            95% { transform: scaleY(.1); }
        }

        @keyframes jb-scan {
            0% { transform: translateX(0); }
            100% { transform: translateX(70px); }
        }

        @keyframes jb-ping {
            0%,100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.5);
                opacity: .5;
            }
        }

        @keyframes jb-shimmer {
            to { transform: translateX(100%); }
        }

        @keyframes jb-fade {
            from {
                opacity: 0;
                transform: translateY(6px);
            }
            to {
                opacity: 1;
                transform: none;
            }
        }

        @keyframes pr-ripple {
            0% {
                transform: scale(.6);
                opacity: .9;
            }
            100% {
                transform: scale(2.2);
                opacity: 0;
            }
        }

        @keyframes pr-node-ripple {
            0% {
                transform: scale(1);
                opacity: .7;
            }
            100% {
                transform: scale(1.7);
                opacity: 0;
            }
        }

        @keyframes pr-flow {
            from { left: -40px; }
            to { left: 100%; }
        }

        @keyframes pr-pop {
            from {
                transform: scale(.4);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }


        /* RESPONSIVE */

        @media (max-width: 640px) {

            #processingApp {
                min-height: 65vh;
                padding-inline: 16px;
            }

            .jb-stage {
                width: 112px;
                height: 112px;
                flex-basis: 112px;
            }

            .pr-pipeline {
                grid-template-columns:
                    minmax(48px, 60px)
                    minmax(10px, 1fr)
                    minmax(48px, 60px)
                    minmax(10px, 1fr)
                    minmax(48px, 60px)
                    minmax(10px, 1fr)
                    minmax(48px, 60px);
            }

            .pr-dot {
                width: 42px;
                height: 42px;
            }

            .pr-dot > svg,
            .pr-dot > i {
                width: 16px;
                height: 16px;
            }

            .pr-dot .pr-check {
                width: 17px;
                height: 17px;
            }

            .pr-seg {
                margin-top: 20px;
            }

            .pr-node-label {
                margin-top: 9px;
                font-size: 10px;
                white-space: normal;
            }

            .pr-node-sub {
                font-size: 9px;
                white-space: normal;
            }
        }

        @media (max-width: 380px) {

            .pr-pipeline {
                grid-template-columns:
                    minmax(42px, 50px)
                    minmax(8px, 1fr)
                    minmax(42px, 50px)
                    minmax(8px, 1fr)
                    minmax(42px, 50px)
                    minmax(8px, 1fr)
                    minmax(42px, 50px);
            }

            .pr-dot {
                width: 38px;
                height: 38px;
            }

            .pr-seg {
                margin-top: 18px;
            }

            .pr-node-label {
                font-size: 9px;
            }

            .pr-node-sub {
                font-size: 8px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .jb-glow,
            .jb-float,
            .jb-shadow,
            .jb-arm,
            .jb-eye,
            .jb-scan,
            .jb-ping,
            .jb-bar::after,
            .jb-fade,
            .pr-pulse-dot::after,
            .pr-dot::before,
            .pr-dot::after,
            .pr-seg::before,
            .pr-seg::after,
            #terminalIcon:not(.hidden) {
                animation: none !important;
            }
        }

    </style>


    {{-- =====================================================
         JAVASCRIPT
    ====================================================== --}}

    <script>
        (() => {
            'use strict';

            const root = document.getElementById('processingApp');

            if (!root || root.dataset.initialized === '1') return;

            root.dataset.initialized = '1';

            const $ = id => document.getElementById(id);

            const requestId = root.dataset.requestId;
            const statusUrl = root.dataset.statusUrl;
            const resultUrl = root.dataset.resultUrl;
            const userId = root.dataset.channelUser;

            const nodes = [...root.querySelectorAll('[data-node]')];
            const segs = [...root.querySelectorAll('[data-seg]')];

            const el = {
                stage: $('janetaStage'),
                title: $('stageTitle'),
                message: $('stageMessage'),
                hint: $('hintText'),

                pill: $('pillWrap'),
                pillText: $('pillText'),

                bar: $('progressBar'),
                pct: $('progressPct'),
                label: $('progressLabel'),

                terminal: $('terminalIcon'),
                ok: $('successIcon'),
                bad: $('failedIcon'),

                errorPanel: $('errorPanel'),
                errorMessage: $('errorMessage'),

                copyId: $('copyId'),
                copyLabel: $('copyLabel'),

                copyError: $('copyErrorInfo'),
                copyErrorLabel: $('copyErrorLabel'),
            };


            const STAGES = [
                {
                    key: 'Kirim',
                    tone: 'amber',
                    pill: 'SENDING',
                    label: 'Mengirim ke JANETA',
                    title: 'Mengirim request ke JANETA',
                    msg: 'Request sedang dikirim dan divalidasi.',
                    hints: [
                        'Menghubungi JANETA…',
                        'Memvalidasi request…'
                    ],
                    cap: 14
                },
                {
                    key: 'Antrean',
                    tone: 'amber',
                    pill: 'QUEUED',
                    label: 'Dalam antrean',
                    title: 'Request masuk antrean',
                    msg: 'JANETA sudah menerima request Anda dan menunggu giliran diproses oleh OLT.',
                    hints: [
                        'Request tersimpan di antrean JANETA',
                        'Menunggu worker mengambil request…'
                    ],
                    cap: 50
                },
                {
                    key: 'Proses OLT',
                    tone: 'green',
                    pill: 'RUNNING',
                    label: 'Diproses OLT',
                    title: 'OLT sedang memproses',
                    msg: 'Perintah sedang dijalankan di perangkat. Hasil akan ditampilkan otomatis.',
                    hints: [
                        'Perintah dikirim ke OLT',
                        'Menunggu respons perangkat…',
                        'Membaca data dari OLT…'
                    ],
                    cap: 92
                },
                {
                    key: 'Hasil',
                    tone: 'green',
                    pill: 'DONE',
                    label: 'Selesai',
                    title: 'Request selesai',
                    msg: 'Data berhasil diterima. Membuka hasil…',
                    hints: [
                        'Menyiapkan hasil…'
                    ],
                    cap: 100
                }
            ];

            const PCT_START = [8, 24, 56, 100];

            let stage = 0;
            let target = 0;

            let ready = false;
            let finished = false;
            let failed = false;
            let redirected = false;

            let pct = 8;
            let hintIndex = 0;

            let pollTimer = null;
            let tickerTimer = null;
            let hintTimer = null;
            let redirectTimer = null;

            let channel = null;
            let reverbConnected = false;
            let fallbackActive = false;
            let pollRunning = false;

            const t0 = Date.now();
            const created = Number(root.dataset.createdMs);

            const startedAt =
                created &&
                Math.abs(t0 - created) < 6 * 3600 * 1000 &&
                created <= t0
                    ? created
                    : t0;

            const enteredAt = [t0];
            const spent = [];

            const pad = n => String(n).padStart(2, '0');

            const secs = ms => {
                return (
                    ms < 10000
                        ? (ms / 1000).toFixed(1)
                        : Math.round(ms / 1000)
                ) + 's';
            };


            // --------------------------------------------------
            // HELPERS
            // --------------------------------------------------

            function log(message) {
                console.log(`[JANETA] ${message}`);
            }

            function setText(element, value) {
                if (element) {
                    element.textContent = value;
                }
            }

            function replay(element) {
                if (!element) return;

                element.classList.remove('jb-fade');

                void element.offsetWidth;

                element.classList.add('jb-fade');
            }

            function setCopy(title, message) {

                if (el.title && el.title.textContent !== title) {
                    setText(el.title, title);
                    replay(el.title);
                }

                if (el.message && el.message.textContent !== message) {
                    setText(el.message, message);
                    replay(el.message);
                }
            }


            // --------------------------------------------------
            // RENDER
            // --------------------------------------------------

            function render() {

                nodes.forEach((node, i) => {

                    let state =
                        i < stage
                            ? 'done'
                            : i === stage
                                ? 'active'
                                : 'pending';

                    if (finished && !failed) {
                        state = 'done';
                    }

                    if (failed && i === stage) {
                        state = 'failed';
                    }

                    node.dataset.state = state;

                    const sub = node.querySelector('[data-sub]');

                    if (!sub) return;

                    if (state === 'done') {

                        sub.textContent =
                            spent[i] != null
                                ? `✓ ${secs(spent[i])}`
                                : '✓ Selesai';

                    } else if (state === 'active') {

                        sub.textContent = [
                            'Mengirim…',
                            'Menunggu…',
                            'Berjalan…',
                            'Menyiapkan…'
                        ][i];

                    } else if (state === 'failed') {

                        sub.textContent = 'Gagal';

                    } else {

                        sub.textContent = 'Menunggu';

                    }

                });


                // CONNECTORS

                segs.forEach((seg, i) => {

                    const fill = seg.querySelector('.pr-fill');

                    const filled =
                        finished && !failed
                            ? true
                            : i < stage;

                    if (fill) {
                        fill.style.setProperty(
                            '--f',
                            filled ? 1 : 0
                        );
                    }

                    const flowing =
                        !finished &&
                        !failed &&
                        i === stage;

                    seg.dataset.flow = flowing ? 'on' : 'off';

                    seg.dataset.tone =
                        STAGES[Math.min(stage, 3)].tone;

                });


                // STATUS

                const cfg = STAGES[Math.min(stage, 3)];

                if (failed) {

                    if (el.pill) {
                        el.pill.dataset.tone = 'red';
                    }

                    setText(el.pillText, 'FAILED');
                    setText(el.label, 'Request gagal');

                } else if (finished) {

                    if (el.pill) {
                        el.pill.dataset.tone = 'done';
                    }

                    setText(el.pillText, 'DONE');
                    setText(el.label, 'Selesai');

                } else {

                    if (el.pill) {
                        el.pill.dataset.tone = cfg.tone;
                    }

                    setText(el.pillText, cfg.pill);
                    setText(el.label, cfg.label);

                }


                // PROGRESS

                if (el.bar) {

                    el.bar.style.width = pct + '%';

                    el.bar.dataset.state =
                        failed
                            ? 'failed'
                            : finished
                                ? 'done'
                                : 'running';

                }

                setText(el.pct, Math.round(pct) + '%');

            }


            // --------------------------------------------------
            // STAGE MANAGEMENT
            // --------------------------------------------------

            function goTo(nextStage) {

                if (finished || nextStage <= stage) return;

                nextStage = Math.min(nextStage, 3);

                const now = Date.now();

                for (let i = stage; i < nextStage; i++) {
                    spent[i] = now - enteredAt[i];
                }

                stage = nextStage;
                enteredAt[stage] = now;
                hintIndex = 0;

                pct = Math.max(pct, PCT_START[stage]);

                const cfg = STAGES[stage];

                setCopy(cfg.title, cfg.msg);

                setText(el.hint, cfg.hints[0]);

                if (stage === 1) {
                    log('Request diterima JANETA, masuk antrean');
                }

                if (stage === 2) {
                    log('OLT mulai memproses request');
                }

                render();

            }

            function request(nextStage) {

                if (finished) return;

                target = Math.max(target, nextStage);

                if (ready) {
                    goTo(target);
                }

            }


            // --------------------------------------------------
            // CLEANUP
            // --------------------------------------------------

            function stopTimers() {

                clearTimeout(pollTimer);
                clearInterval(tickerTimer);
                clearInterval(hintTimer);

                clearTimeout(redirectTimer);

                pollTimer = null;
                tickerTimer = null;
                hintTimer = null;
                redirectTimer = null;

            }

            function redirectResult() {

                if (redirected || failed || !resultUrl) return;

                redirected = true;

                redirectTimer = setTimeout(() => {
                    window.location.assign(resultUrl);
                }, 1200);

            }


            // --------------------------------------------------
            // SUCCESS
            // --------------------------------------------------

            function succeed() {

                if (finished) return;

                const now = Date.now();

                for (let i = stage; i < 3; i++) {
                    spent[i] = now - enteredAt[i];
                }

                stage = 3;
                finished = true;
                pct = 100;

                setCopy(
                    STAGES[3].title,
                    STAGES[3].msg
                );

                setText(el.hint, 'Selesai · membuka hasil');

                if (el.stage) {
                    el.stage.classList.add('jb-done');
                }

                if (el.terminal) {
                    el.terminal.classList.remove('hidden');
                    el.terminal.classList.add('flex');
                }

                if (el.ok) {
                    el.ok.classList.remove('hidden');
                }

                if (el.bad) {
                    el.bad.classList.add('hidden');
                }

                log('Request selesai');

                render();

                stopTimers();

                redirectResult();

            }


            // --------------------------------------------------
            // FAILED
            // --------------------------------------------------

            function fail(reason) {

                if (finished) return;

                finished = true;
                failed = true;

                reason =
                    typeof reason === 'string' && reason.trim()
                        ? reason
                        : 'Terjadi kesalahan saat memproses request.';

                setCopy('Request gagal', reason);

                setText(el.hint, 'Proses dihentikan');

                if (el.stage) {
                    el.stage.classList.add('jb-done');
                }

                if (el.terminal) {
                    el.terminal.classList.remove('hidden');
                    el.terminal.classList.add('flex');
                }

                if (el.bad) {
                    el.bad.classList.remove('hidden');
                }

                if (el.ok) {
                    el.ok.classList.add('hidden');
                }

                if (el.errorPanel) {
                    el.errorPanel.classList.remove('hidden');
                }

                setText(el.errorMessage, reason);

                log('Request gagal: ' + reason);

                render();

                stopTimers();

            }


            // --------------------------------------------------
            // STATUS PARSER
            // --------------------------------------------------

            function readStatus(json) {

                return String(
                    json?.status ??
                    json?.data?.status ??
                    json?.request?.status ??
                    json?.result?.status ??
                    ''
                ).toLowerCase();

            }

            function applyStatus(json) {

                if (finished || !json) return;

                const status = readStatus(json);

                log('Status: ' + status);

                if (
                    /^(queued|pending|waiting|created|dispatched)$/.test(status)
                ) {

                    request(1);

                    return;
                }

                if (
                    /^(running|processing|in_progress|started|executing)$/.test(status)
                ) {

                    request(2);

                    return;
                }

                if (
                    /^(completed|complete|success|succeeded|done|finished)$/.test(status)
                ) {

                    request(3);

                    if (ready) {

                        succeed();

                    } else {

                        setTimeout(() => {

                            if (!finished) {
                                succeed();
                            }

                        }, 1000);

                    }

                    return;
                }

                if (
                    /^(failed|error|timeout|timed_out|cancelled|canceled)$/.test(status)
                ) {

                    const reason =
                        json?.error ??
                        json?.message ??
                        json?.data?.error ??
                        'Request gagal diproses.';

                    fail(
                        typeof reason === 'string'
                            ? reason
                            : 'Request gagal diproses.'
                    );

                }

            }


            // --------------------------------------------------
            // REVERB
            // --------------------------------------------------

            function setupReverb() {

                if (!window.Echo) {

                    log('Echo tidak tersedia');

                    return false;
                }

                if (!userId) {

                    log('User ID tidak tersedia');

                    return false;
                }

                try {

                    const channelName = `users.${userId}`;

                    channel = window.Echo.private(channelName);

                    channel.listen(
                        '.OltServiceRequestUpdated',
                        event => {

                            if (finished) return;

                            const eventRequestId =
                                event.request_id ??
                                event.uuid ??
                                event.request?.uuid;

                            if (
                                eventRequestId &&
                                eventRequestId !== requestId
                            ) {
                                return;
                            }

                            log('Reverb event diterima');

                            applyStatus(event);

                        }
                    );

                    log('Reverb listener aktif');

                    const connection =
                        window.Echo.connector?.pusher?.connection;

                    if (connection) {

                        connection.bind('connected', () => {

                            reverbConnected = true;

                            log('Reverb connected');

                            stopFallback();

                        });

                        connection.bind('disconnected', () => {

                            reverbConnected = false;

                            log('Reverb disconnected');

                            startFallback();

                        });

                        connection.bind('error', () => {

                            reverbConnected = false;

                            log('Reverb connection error');

                            startFallback();

                        });

                        reverbConnected =
                            connection.state === 'connected';

                    }

                    return true;

                } catch (error) {

                    log('Reverb error: ' + error.message);

                    return false;

                }

            }


            // --------------------------------------------------
            // FALLBACK POLLING
            // Only when WebSocket is disconnected.
            // --------------------------------------------------

            async function poll() {

                if (
                    finished ||
                    reverbConnected ||
                    pollRunning ||
                    !fallbackActive
                ) {
                    return;
                }

                pollRunning = true;

                try {

                    const response = await fetch(statusUrl, {

                        method: 'GET',

                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },

                        credentials: 'same-origin',
                        cache: 'no-store'

                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const json = await response.json();

                    applyStatus(json);

                } catch (error) {

                    log('Fallback polling: ' + error.message);

                } finally {

                    pollRunning = false;

                    if (
                        fallbackActive &&
                        !finished &&
                        !reverbConnected
                    ) {

                        pollTimer = setTimeout(poll, 5000);

                    }

                }

            }

            function startFallback() {

                if (finished || fallbackActive) return;

                fallbackActive = true;

                log('Fallback polling aktif');

                clearTimeout(pollTimer);

                poll();

            }

            function stopFallback() {

                fallbackActive = false;

                clearTimeout(pollTimer);

                pollTimer = null;

                log('Fallback polling dihentikan');

            }


            // --------------------------------------------------
            // TICKER
            // --------------------------------------------------

            function startTicker() {

                tickerTimer = setInterval(() => {

                    if (finished) return;

                    const cap = STAGES[stage].cap;

                    pct += (cap - pct) * 0.035;

                    if (el.bar) {
                        el.bar.style.width = pct + '%';
                    }

                    setText(el.pct, Math.round(pct) + '%');

                }, 1000);


                hintTimer = setInterval(() => {

                    if (finished) return;

                    const hints = STAGES[stage].hints;

                    hintIndex = (hintIndex + 1) % hints.length;

                    setText(el.hint, hints[hintIndex]);

                }, 3200);

            }


            // --------------------------------------------------
            // COPY REQUEST INFORMATION
            // --------------------------------------------------

            async function copyText(text, buttonLabel, originalLabel) {

                try {

                    if (navigator.clipboard?.writeText) {

                        await navigator.clipboard.writeText(text);

                    } else {

                        const textarea = document.createElement('textarea');

                        textarea.value = text;
                        textarea.style.position = 'fixed';
                        textarea.style.opacity = '0';

                        document.body.appendChild(textarea);

                        textarea.select();

                        const copied = document.execCommand('copy');

                        textarea.remove();

                        if (!copied) {
                            throw new Error('Clipboard tidak tersedia');
                        }

                    }

                    setText(buttonLabel, 'Tersalin');

                    setTimeout(() => {
                        setText(buttonLabel, originalLabel);
                    }, 1600);

                } catch (error) {

                    log('Gagal menyalin informasi');

                    setText(buttonLabel, 'Gagal menyalin');

                    setTimeout(() => {
                        setText(buttonLabel, originalLabel);
                    }, 1600);

                }

            }

            el.copyId?.addEventListener('click', () => {

                copyText(
                    requestId,
                    el.copyLabel,
                    'Salin'
                );

            });

            el.copyError?.addEventListener('click', () => {

                const errorText =
                    el.errorMessage?.textContent ||
                    'Tidak ada detail error';

                const report = [
                    'JANETA - ERROR REPORT',
                    '-------------------------',
                    'Request ID: ' + requestId,
                    'Operation: {{ $request->operation }}',
                    'Error: ' + errorText,
                    '-------------------------'
                ].join('\n');

                copyText(
                    report,
                    el.copyErrorLabel,
                    'Salin informasi'
                );

            });


            // --------------------------------------------------
            // START
            // --------------------------------------------------

            log('Request dibuat');

            setText(el.hint, STAGES[0].hints[0]);

            render();

            const reverbReady = setupReverb();

            if (!reverbReady || !reverbConnected) {
                startFallback();
            }

            startTicker();


            // Berikan waktu agar tahap pengiriman terlihat.

            setTimeout(() => {

                if (finished) return;

                ready = true;

                goTo(target);

            }, 1100);


            // --------------------------------------------------
            // CLEANUP WHEN LEAVING PAGE
            // --------------------------------------------------

            window.addEventListener('pagehide', () => {

                stopTimers();

                if (channel && window.Echo) {

                    try {
                        window.Echo.leave(`users.${userId}`);
                    } catch (error) {
                        log('Channel cleanup error');
                    }

                }

            });

        })();
    </script>

</x-layouts.dashboard>
