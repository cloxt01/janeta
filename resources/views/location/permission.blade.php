<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Izin Lokasi - JANETA</title>

    <style>
        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #08090c;
            color: #f1f5f9;
            font-family: Arial, sans-serif;
        }

        .location-card {
            width: 100%;
            max-width: 430px;
            padding: 36px;
            text-align: center;
            background: #111827;
            border: 1px solid #263244;
            border-radius: 18px;
        }

        .location-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(16, 185, 129, .12);
            color: #34d399;
            font-size: 30px;
        }

        h2 { margin: 0 0 12px; font-size: 23px; }

        p { color: #94a3b8; line-height: 1.7; font-size: 14px; }

        button {
            width: 100%;
            margin-top: 20px;
            padding: 14px;
            border: 0;
            border-radius: 9px;
            background: #059669;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover { background: #047857; }
        button:disabled { opacity: .6; cursor: not-allowed; }

        #status { min-height: 24px; margin-top: 18px; font-size: 13px; }
        .error { color: #fb7185; }
        .success { color: #34d399; }
    </style>
</head>

<body>

<div class="location-card">

    <div class="location-icon">📍</div>

    <h2>Akses Lokasi Diperlukan</h2>

    <p>
        Untuk menggunakan JANETA, kamu wajib mengizinkan
        akses lokasi pada perangkat ini.
    </p>

    <p>
        Lokasi digunakan untuk kebutuhan pemantauan aktivitas
        dan pencatatan lokasi pengguna.
    </p>

    <button id="allowLocation" type="button">
        Izinkan Lokasi
    </button>

    <p id="status"></p>

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
        statusText.className = type;
    }

    allowButton.addEventListener('click', () => {

        if (!navigator.geolocation) {
            showStatus('Browser tidak mendukung akses lokasi.', 'error');
            return;
        }

        allowButton.disabled = true;
        showStatus('Meminta izin lokasi...', '');

        navigator.geolocation.getCurrentPosition(

            async (position) => {

                showStatus('Mengaktifkan lokasi JANETA...', '');

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
                        throw new Error(result.message || 'Gagal mengaktifkan lokasi.');
                    }

                    showStatus('Lokasi berhasil diaktifkan. Mengalihkan ke dashboard...', 'success');

                    // Redirect setelah server berhasil menyimpan session.
                    window.location.replace(dashboardUrl);

                } catch (error) {
                    console.error('Location authorization error:', error);
                    showStatus(error.message, 'error');
                    allowButton.disabled = false;
                }
            },

            (error) => {
                allowButton.disabled = false;

                if (error.code === error.PERMISSION_DENIED) {
                    showStatus('Akses lokasi ditolak. Aktifkan izin lokasi melalui pengaturan browser.', 'error');
                } else if (error.code === error.POSITION_UNAVAILABLE) {
                    showStatus('Lokasi tidak tersedia. Periksa layanan lokasi perangkat.', 'error');
                } else {
                    showStatus('Waktu permintaan lokasi habis. Silakan coba kembali.', 'error');
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
