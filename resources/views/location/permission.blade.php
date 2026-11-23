
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Izin Lokasi · JANETA</title>

    <style>
        :root {
            color-scheme: dark;
            --bg: #080808;
            --surface: #0c0c0c;
            --border: #262626;
            --muted: #888;
            --text: #ededed;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            padding: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;

            background:
                radial-gradient(
                    ellipse at 50% 35%,
                    rgba(255,255,255,.055),
                    transparent 55%
                ),
                var(--bg);

            color: var(--text);
            font-family: Geist, Inter, -apple-system,
            BlinkMacSystemFont, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        button {
            font: inherit;
        }

        .page {
            width: 100%;
            max-width: 420px;
            margin: auto;
        }

        /* BRAND */

        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 38px;
        }

        .brand img {
            display: block;
            width: 125px;
            height: auto;
            object-fit: contain;
        }

        .brand-divider {
            height: 23px;
            width: 1px;
            background: #333;
        }

        .brand-label {
            color: #888;
            font-size: 11px;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        /* CARD */

        .card {
            padding: 34px;
            background: rgba(12,12,12,.92);
            border: 1px solid var(--border);
            border-radius: 13px;
            box-shadow:
                0 20px 80px rgba(0,0,0,.35),
                inset 0 1px rgba(255,255,255,.025);
        }

        .icon-wrapper {
            width: 54px;
            height: 54px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;

            border: 1px solid #333;
            border-radius: 12px;
            background: #151515;
            color: #e5e5e5;
        }

        .icon-wrapper svg {
            width: 25px;
            height: 25px;
        }

        .eyebrow {
            margin-bottom: 11px;
            color: #777;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .13em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            color: #fafafa;
            font-size: 25px;
            font-weight: 600;
            letter-spacing: -.8px;
            line-height: 1.3;
        }

        .description {
            margin: 13px 0 0;
            color: #929292;
            font-size: 13px;
            line-height: 1.8;
        }

        /* INFORMATION */

        .info {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-top: 25px;
            padding: 14px;
            border: 1px solid #262626;
            border-radius: 8px;
            background: #101010;
        }

        .info svg {
            width: 17px;
            height: 17px;
            flex-shrink: 0;
            margin-top: 1px;
            color: #a3a3a3;
        }

        .info-content {
            color: #929292;
            font-size: 11px;
            line-height: 1.7;
        }

        .info-content strong {
            display: block;
            margin-bottom: 3px;
            color: #d4d4d4;
            font-size: 12px;
            font-weight: 500;
        }

        /* BUTTON */

        .allow-button {
            width: 100%;
            min-height: 46px;
            margin-top: 24px;
            padding: 12px 18px;

            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;

            border: 1px solid #fff;
            border-radius: 7px;
            background: #fafafa;
            color: #090909;

            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: .2s ease;
        }

        .allow-button:hover {
            background: #dcdcdc;
            border-color: #dcdcdc;
        }

        .allow-button:active {
            transform: scale(.99);
        }

        .allow-button:disabled {
            opacity: .55;
            cursor: not-allowed;
            transform: none;
        }

        .allow-button svg {
            width: 16px;
            height: 16px;
        }

        .spinner {
            width: 15px;
            height: 15px;
            border: 2px solid rgba(0,0,0,.2);
            border-top-color: #000;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* STATUS */

        .status {
            min-height: 20px;
            margin: 15px 0 0;
            text-align: center;
            font-size: 12px;
            line-height: 1.6;
            color: #888;
        }

        .status.error {
            color: #fb7185;
        }

        .status.success {
            color: #4ade80;
        }

        /* FOOTER */

        .footer {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
            margin-top: 25px;
            color: #666;
            font-size: 10px;
        }

        .footer svg {
            width: 13px;
            height: 13px;
        }

        .footer-dot {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: #444;
        }

        @media(max-width: 480px) {
            body {
                padding: 20px;
            }

            .brand {
                margin-bottom: 28px;
            }

            .card {
                padding: 26px 22px;
            }

            h1 {
                font-size: 23px;
            }
        }

        @media(prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .01ms !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>

<body>

<div class="page">

    {{-- BRAND --}}
    <div class="brand">
        <img
            src="{{ asset('images/jangkaunet-logo.png') }}"
            alt="JANETA"
        >

        <span class="brand-divider"></span>

        <span class="brand-label">Network Management</span>
    </div>

    {{-- AUTHORIZATION CARD --}}
    <main class="card">

        <div class="icon-wrapper">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/>
                <circle cx="12" cy="10" r="2.5"/>
            </svg>
        </div>

        <div class="eyebrow">
            Device Authorization
        </div>

        <h1>
            Aktifkan akses lokasi
        </h1>

        <p class="description">
            JANETA memerlukan akses lokasi perangkat untuk
            mendukung pemantauan aktivitas dan pencatatan
            posisi pengguna secara real-time.
        </p>

        {{-- PRIVACY INFORMATION --}}
        <div class="info">

            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.6"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <rect x="3" y="11" width="18" height="11" rx="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>

            <div class="info-content">
                <strong>Informasi privasi</strong>

                Data koordinat digunakan untuk kebutuhan
                operasional JANETA dan pemantauan aktivitas
                pengguna yang telah diotorisasi.
            </div>

        </div>

        {{-- BUTTON --}}
        <button
            type="button"
            id="allowLocation"
            class="allow-button"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/>
                <circle cx="12" cy="10" r="2.5"/>
            </svg>

            Izinkan Akses Lokasi
        </button>

        <p
            id="status"
            class="status"
            role="status"
            aria-live="polite"
        ></p>

    </main>

    {{-- FOOTER --}}
    <footer class="footer">

        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/>
            <path d="m9 12 2 2 4-4"/>
        </svg>

        <span>Secure Connection</span>

        <span class="footer-dot"></span>

        <span>JANETA Network Management</span>

    </footer>

</div>

<script>
    const allowButton = document.getElementById('allowLocation');
    const statusText = document.getElementById('status');

    const authorizeUrl = @json(route('location.authorize'));
    const dashboardUrl = @json(route('dashboard'));

    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute('content');

    function showStatus(message, type = '') {
        statusText.textContent = message;
        statusText.className = 'status ' + type;
    }

    function setLoading(loading, message = 'Memproses...') {
        allowButton.disabled = loading;

        if (loading) {
            allowButton.innerHTML = `
                <span class="spinner"></span>
                ${message}
            `;
        } else {
            allowButton.innerHTML = `
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/>
                    <circle cx="12" cy="10" r="2.5"/>
                </svg>
                Izinkan Akses Lokasi
            `;
        }
    }

    allowButton.addEventListener('click', () => {

        if (!window.isSecureContext) {
            showStatus(
                'Akses lokasi membutuhkan HTTPS atau localhost.',
                'error'
            );
            return;
        }

        if (!navigator.geolocation) {
            showStatus(
                'Browser tidak mendukung layanan lokasi.',
                'error'
            );
            return;
        }

        setLoading(true, 'Meminta izin lokasi...');
        showStatus('Silakan izinkan akses lokasi pada browser.');

        navigator.geolocation.getCurrentPosition(

            async (position) => {

                setLoading(true, 'Mengaktifkan JANETA...');
                showStatus('Mengirim koordinat ke server...');

                try {

                    const response = await fetch(authorizeUrl, {
                        method: 'POST',
                        credentials: 'same-origin',

                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },

                        body: JSON.stringify({
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            accuracy: position.coords.accuracy
                        })
                    });

                    const result = await response.json();

                    if (!response.ok || !result.success) {
                        throw new Error(
                            result.message ||
                            'Gagal mengaktifkan lokasi JANETA.'
                        );
                    }

                    showStatus(
                        'Lokasi berhasil diaktifkan. Mengalihkan...',
                        'success'
                    );

                    allowButton.innerHTML = `
                        <span class="spinner"></span>
                        Berhasil
                    `;

                    window.location.replace(dashboardUrl);

                } catch (error) {

                    console.error(
                        'JANETA location authorization:',
                        error
                    );

                    showStatus(
                        error.message || 'Terjadi kesalahan pada server.',
                        'error'
                    );

                    setLoading(false);
                }

            },

            (error) => {

                setLoading(false);

                if (error.code === error.PERMISSION_DENIED) {

                    showStatus(
                        'Akses lokasi ditolak. Aktifkan izin lokasi melalui pengaturan browser.',
                        'error'
                    );

                } else if (error.code === error.POSITION_UNAVAILABLE) {

                    showStatus(
                        'Lokasi tidak tersedia. Periksa GPS atau layanan lokasi perangkat.',
                        'error'
                    );

                } else if (error.code === error.TIMEOUT) {

                    showStatus(
                        'Waktu permintaan lokasi habis. Silakan coba kembali.',
                        'error'
                    );

                } else {

                    showStatus(
                        'Tidak dapat mengambil lokasi perangkat.',
                        'error'
                    );
                }

            },

            {
                enableHighAccuracy: true,
                timeout: 20000,
                maximumAge: 0
            }
        );

    });
</script>

</body>
</html>
