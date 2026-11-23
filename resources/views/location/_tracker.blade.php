{{--
    Tracker GPS sisi user.

    Pasang di layout dashboard (x-layouts.dashboard), sebelum </body>, hanya untuk
    user yang punya permission location.track, mis.:

        @if (auth()->user()->hasPermission('location.track'))
            @include('location._tracker')
        @endif

    Layout harus punya: <meta name="csrf-token" content="{{ csrf_token() }}">
--}}
<script>
    (function () {
        if (window.__locationTracker) return; // cegah dobel jalan
        if (!('geolocation' in navigator)) return;

        const UPDATE_URL = @json(route('location.update'));
        const STOP_URL = @json(route('location.stop'));
        const PERMISSION_URL = @json(route('location.permission'));
        const REVOKE_URL = @json(route('location.revoke'));
        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;

        const SEND_INTERVAL_MS = 5000; // kirim maksimal 1x per 5 detik

        let lastSent = 0;
        let sending = false;
        let watchId = null;

        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF,
        };

        async function send(pos) {
            const now = Date.now();
            if (sending || now - lastSent < SEND_INTERVAL_MS) return;

            sending = true;
            lastSent = now;

            try {
                const res = await fetch(UPDATE_URL, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers,
                    body: JSON.stringify({
                        latitude: pos.coords.latitude,
                        longitude: pos.coords.longitude,
                        accuracy: pos.coords.accuracy,
                    }),
                });

                // Sesi habis -> berhenti, jangan spam server.
                if (res.status === 401 || res.status === 419) stop(false);
            } catch (e) {
                console.error('[tracker] gagal kirim lokasi', e);
            } finally {
                sending = false;
            }
        }

        function onError(err) {
            console.warn('[tracker]', err.message);

            if (err.code === err.PERMISSION_DENIED) {
                stop(false);

                fetch(REVOKE_URL, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers,
                    keepalive: true,
                })
                    .catch(() => {})
                    .finally(() => window.location.replace(PERMISSION_URL));
            }
        }

        function start() {
            if (watchId !== null) return;

            watchId = navigator.geolocation.watchPosition(send, onError, {
                enableHighAccuracy: true,
                maximumAge: 0,
                timeout: 20000,
            });
        }

        // notify=true -> beri tahu server supaya admin langsung melihat user offline.
        function stop(notify = true) {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }

            if (notify) {
                fetch(STOP_URL, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers,
                    keepalive: true,
                }).catch(() => {});
            }
        }

        // Dipanggil dari tombol logout / tombol "Hentikan tracking":
        //   window.__locationTracker.stop();
        window.__locationTracker = { start, stop };

        start();
    })();
</script>
