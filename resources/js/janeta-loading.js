
import gsap from 'gsap';

function initJanetaLoading() {
    const app = document.getElementById('processingApp');

    if (!app) return;

    const $ = id => document.getElementById(id);

    const requestId = app.dataset.requestId;
    const statusUrl = app.dataset.statusUrl;
    const resultUrl = app.dataset.resultUrl;
    const userId = app.dataset.channelUser;

    const title = $('statusTitle');
    const message = $('statusMessage');
    const systemLabel = $('systemLabel');

    const network = $('janetaNetwork');
    const pulse = document.querySelector('.janeta-pulse');
    const core = document.querySelector('.janeta-core');
    const nodes = gsap.utils.toArray('.janeta-nodes circle');
    const lines = gsap.utils.toArray('.janeta-lines path');
    const signals = gsap.utils.toArray('.janeta-signal');

    const terminal = $('terminalIcon');
    const terminalInner = $('terminalIconInner');
    const successIcon = $('successIcon');
    const failedIcon = $('failedIcon');

    const errorPanel = $('errorPanel');
    const errorMessage = $('errorMessage');

    let currentStatus = null;
    let polling = false;
    let redirected = false;
    let pollingTimer = null;
    let redirectTimer = null;
    let channel = null;

    /*
    |--------------------------------------------------------------------------
    | GSAP animation
    |--------------------------------------------------------------------------
    */

    const animation = gsap.timeline({ repeat: -1 });

    animation
        .to(pulse, {
            attr: { r: 29 },
            opacity: 0.08,
            duration: 1.8,
            ease: 'sine.inOut',
        }, 0)
        .to(pulse, {
            attr: { r: 18 },
            opacity: 0.5,
            duration: 1.8,
            ease: 'sine.inOut',
        }, 1.8)
        .to(nodes, {
            opacity: 0.35,
            scale: 0.7,
            transformOrigin: 'center center',
            duration: 1.2,
            stagger: 0.15,
            ease: 'sine.inOut',
        }, 0)
        .to(nodes, {
            opacity: 1,
            scale: 1,
            duration: 1.2,
            stagger: 0.15,
            ease: 'sine.inOut',
        }, 1.2)
        .to(lines, {
            opacity: 0.2,
            duration: 1.5,
            stagger: 0.1,
        }, 0)
        .to(lines, {
            opacity: 0.65,
            duration: 1.5,
            stagger: 0.1,
        }, 1.5)
        .to(core, {
            opacity: 0.35,
            duration: 0.8,
            yoyo: true,
            repeat: 1,
        }, 0);

    gsap.to(signals, {
        opacity: 0.2,
        duration: 0.7,
        repeat: -1,
        yoyo: true,
        stagger: 0.4,
        ease: 'sine.inOut',
    });

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    function setStatus(status) {
        const states = {
            queued: {
                title: 'Menunggu antrean...',
                message: 'Request sudah masuk ke antrean JANETA.',
                label: 'Waiting for worker',
                color: '#06B6D4',
            },

            running: {
                title: 'Sedang diproses...',
                message: 'JANETA sedang berkomunikasi dengan OLT.',
                label: 'Communicating with OLT',
                color: '#06B6D4',
            },
        };

        const state = states[status];

        if (!state) return;

        title.textContent = state.title;
        message.textContent = state.message;
        systemLabel.textContent = state.label;

        gsap.to(core, {
            fill: state.color,
            duration: 0.4,
        });

        gsap.to(pulse, {
            stroke: state.color,
            duration: 0.4,
        });

        gsap.to($('systemPulse'), {
            backgroundColor: state.color,
            duration: 0.4,
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Stop animation
    |--------------------------------------------------------------------------
    */

    function stopAnimation() {
        animation.pause();

        gsap.killTweensOf([
            ...nodes,
            ...lines,
            ...signals,
            pulse,
            core,
            $('systemPulse'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Terminal state
    |--------------------------------------------------------------------------
    */

    function showTerminal(status) {
        stopAnimation();

        gsap.to(network, {
            opacity: 0,
            scale: 0.85,
            duration: 0.35,
            ease: 'power2.out',
        });

        terminal.classList.remove('hidden');
        terminal.classList.add('flex');

        if (status === 'completed') {
            successIcon.classList.remove('hidden');
            failedIcon.classList.add('hidden');
            terminalInner.classList.add('text-emerald-500');
        } else {
            failedIcon.classList.remove('hidden');
            successIcon.classList.add('hidden');
        }

        gsap.fromTo(
            terminalInner,
            { opacity: 0, scale: 0.7 },
            {
                opacity: 1,
                scale: 1,
                duration: 0.4,
                ease: 'back.out(1.7)',
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Cleanup
    |--------------------------------------------------------------------------
    */

    function cleanup() {
        if (pollingTimer) {
            clearInterval(pollingTimer);
            pollingTimer = null;
        }

        if (redirectTimer) {
            clearTimeout(redirectTimer);
            redirectTimer = null;
        }

        if (channel && window.Echo) {
            window.Echo.leave('users.' + userId);
            channel = null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Completed
    |--------------------------------------------------------------------------
    */

    function completeRequest() {
        if (redirected) return;

        redirected = true;
        currentStatus = 'completed';

        cleanup();

        title.textContent = 'Request berhasil!';
        message.textContent = 'Mengalihkan ke halaman hasil...';
        systemLabel.textContent = 'Completed';

        showTerminal('completed');

        redirectTimer = setTimeout(() => {
            window.location.replace(resultUrl);
        }, 700);
    }

    /*
    |--------------------------------------------------------------------------
    | Failed
    |--------------------------------------------------------------------------
    */

    function failRequest(data) {
        if (redirected || currentStatus === 'failed') return;

        currentStatus = 'failed';

        cleanup();

        title.textContent = 'Request gagal';
        message.textContent = 'JANETA tidak dapat menyelesaikan operasi.';
        systemLabel.textContent = 'Operation failed';

        errorMessage.textContent =
            data.error ||
            data.message ||
            data.data?.error ||
            'Terjadi kesalahan saat memproses request.';

        errorPanel.classList.remove('hidden');

        showTerminal('failed');
    }

    /*
    |--------------------------------------------------------------------------
    | Handle event / API response
    |--------------------------------------------------------------------------
    */

    function handleStatus(data) {
        if (!data || redirected) return;

        const receivedId =
            data.request_id ??
            data.uuid ??
            data.data?.request_id ??
            data.data?.uuid;

        if (receivedId && receivedId !== requestId) return;

        const status = String(
            data.status ?? data.data?.status ?? ''
        ).toLowerCase();

        if (!['queued', 'running', 'completed', 'failed'].includes(status)) {
            return;
        }

        console.log('[JANETA] Status:', status);

        if (status === 'completed') {
            completeRequest();
            return;
        }

        if (status === 'failed') {
            failRequest(data);
            return;
        }

        if (currentStatus === 'failed') return;

        currentStatus = status;
        setStatus(status);

        if (data.message) {
            message.textContent = data.message;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Database polling
    |--------------------------------------------------------------------------
    */

    async function checkStatus() {
        if (polling || redirected || currentStatus === 'failed') return;

        polling = true;

        try {
            const response = await fetch(statusUrl, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            handleStatus(data);

        } catch (error) {
            console.error('[JANETA] Polling:', error);

        } finally {
            polling = false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Reverb
    |--------------------------------------------------------------------------
    */

    if (window.Echo && userId) {
        channel = window.Echo.private('users.' + userId);

        channel.listen('.OltServiceRequestUpdated', event => {
            console.log('[JANETA] Reverb:', event);
            handleStatus(event);
        });
    } else {
        console.warn('[JANETA] Echo tidak tersedia, menggunakan polling.');
    }

    /*
    |--------------------------------------------------------------------------
    | Start
    |--------------------------------------------------------------------------
    */

    pollingTimer = setInterval(checkStatus, 2000);

    checkStatus();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initJanetaLoading, {
        once: true,
    });
} else {
    initJanetaLoading();
}
