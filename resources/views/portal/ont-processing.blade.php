
<x-layouts.dashboard title="Processing Request">

    <div
        id="processingApp"
        class="mx-auto flex min-h-[65vh] w-full max-w-4xl flex-col items-center justify-center px-4 py-12"
        data-request-id="{{ $request->uuid }}"
        data-status-url="{{ route('portal.ont.request-status', $request->uuid) }}"
        data-result-url="{{ route('portal.ont.result', $request->uuid) }}"
        data-channel-user="{{ auth()->id() }}"
    >

        {{-- Minimal network animation --}}
        <div class="relative flex h-36 w-52 items-center justify-center">

            <svg
                id="janetaNetwork"
                viewBox="0 0 200 130"
                class="h-full w-full overflow-visible"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <defs>
                    <radialGradient id="networkGlow">
                        <stop offset="0%" stop-color="#06B6D4" stop-opacity=".15"/>
                        <stop offset="100%" stop-color="#06B6D4" stop-opacity="0"/>
                    </radialGradient>
                </defs>

                <circle cx="100" cy="65" r="45" fill="url(#networkGlow)"/>

                {{-- Network lines --}}
                <g
                    class="janeta-lines"
                    stroke="#94A3B8"
                    stroke-width="1.3"
                    stroke-linecap="round"
                    opacity=".55"
                >
                    <path d="M100 65 L42 28"/>
                    <path d="M100 65 L158 28"/>
                    <path d="M100 65 L42 102"/>
                    <path d="M100 65 L158 102"/>
                    <path d="M42 28 L158 28"/>
                    <path d="M42 102 L158 102"/>
                </g>

                {{-- Data signals --}}
                <g fill="#06B6D4">
                    <circle class="janeta-signal" r="2.5">
                        <animateMotion
                            dur="2.8s"
                            repeatCount="indefinite"
                            path="M42 28 L100 65 L158 102"
                        />
                    </circle>

                    <circle class="janeta-signal" r="2.5">
                        <animateMotion
                            dur="3.2s"
                            begin="1s"
                            repeatCount="indefinite"
                            path="M158 28 L100 65 L42 102"
                        />
                    </circle>
                </g>

                {{-- Outer nodes --}}
                <g class="janeta-nodes" fill="#06B6D4">
                    <circle cx="42" cy="28" r="3.5"/>
                    <circle cx="158" cy="28" r="3.5"/>
                    <circle cx="42" cy="102" r="3.5"/>
                    <circle cx="158" cy="102" r="3.5"/>
                </g>

                {{-- Center --}}
                <circle
                    class="janeta-pulse"
                    cx="100"
                    cy="65"
                    r="18"
                    stroke="#06B6D4"
                    stroke-width="1.2"
                    opacity=".5"
                />

                <circle
                    cx="100"
                    cy="65"
                    r="10"
                    fill="#ECFEFF"
                    stroke="#06B6D4"
                    stroke-width="1.3"
                />

                <circle
                    class="janeta-core"
                    cx="100"
                    cy="65"
                    r="4"
                    fill="#06B6D4"
                />
            </svg>

            {{-- Success / failed icon --}}
            <div
                id="terminalIcon"
                class="absolute inset-0 hidden items-center justify-center"
            >
                <div
                    id="terminalIconInner"
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-sm"
                >
                    <svg
                        id="successIcon"
                        class="hidden h-6 w-6 text-emerald-500"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M5 12l5 5L20 7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>

                    <svg
                        id="failedIcon"
                        class="hidden h-6 w-6 text-rose-500"
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
        <div class="mt-5 text-center">

            <div
                id="statusTitle"
                class="text-lg font-semibold tracking-tight text-slate-800"
            >
                Menunggu antrean...
            </div>

            <p
                id="statusMessage"
                class="mt-2 text-sm text-slate-500"
            >
                JANETA sedang mempersiapkan request.
            </p>

            <div class="mt-4 flex items-center justify-center gap-2">
                <span
                    id="systemPulse"
                    class="h-1.5 w-1.5 rounded-full bg-cyan-500"
                ></span>

                <span
                    id="systemLabel"
                    class="text-[10px] font-semibold uppercase tracking-[.2em] text-slate-400"
                >
                    System active
                </span>
            </div>

        </div>

        {{-- Error --}}
        <div
            id="errorPanel"
            class="mt-7 hidden w-full max-w-md rounded-xl border border-rose-200 bg-rose-50 px-4 py-3"
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
        <div class="mt-10 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-[10px] text-slate-400">

            <span class="font-mono">
                {{ $request->uuid }}
            </span>

            <span class="hidden h-1 w-1 rounded-full bg-slate-300 sm:block"></span>

            <span class="font-medium uppercase tracking-wider">
                {{ strtoupper(str_replace('.', ' · ', $request->operation)) }}
            </span>

        </div>

    </div>

</x-layouts.dashboard>
