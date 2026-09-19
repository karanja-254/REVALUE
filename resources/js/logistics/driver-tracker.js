/**
 * Reports the driver's GPS position to the server while a route is active.
 *
 * Markup:
 *   <div data-driver-tracker
 *        data-endpoint="/logistics/driver/location"
 *        data-interval-ms="10000">
 *     <button data-tracker-toggle>Start sharing my location</button>
 *     <span data-tracker-status></span>
 *   </div>
 *
 * Uses the browser Geolocation API, so it works on any mobile browser without
 * a native app. Nothing is sent until the driver opts in.
 */
export function initDriverTracker() {
    const root = document.querySelector('[data-driver-tracker]');
    if (!root) return;

    const endpoint = root.dataset.endpoint;
    const intervalMs = parseInt(root.dataset.intervalMs || '10000', 10);
    const toggle = root.querySelector('[data-tracker-toggle]');
    const statusEl = root.querySelector('[data-tracker-status]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    let watchId = null;
    let lastSent = 0;
    let latest = null;

    const setStatus = (text, tone = 'muted') => {
        if (!statusEl) return;
        statusEl.textContent = text;
        statusEl.dataset.tone = tone;
    };

    const send = async (coords) => {
        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    latitude: coords.latitude,
                    longitude: coords.longitude,
                    heading: Number.isFinite(coords.heading) ? coords.heading : null,
                    accuracy: coords.accuracy ?? null,
                    speed: Number.isFinite(coords.speed) ? coords.speed : null,
                }),
            });
            if (res.ok) {
                setStatus(`Sharing live location · updated ${new Date().toLocaleTimeString()}`, 'live');
            } else {
                setStatus('Could not save location (server error).', 'error');
            }
        } catch (e) {
            setStatus('Offline — will retry.', 'error');
        }
    };

    const onPosition = (position) => {
        latest = position.coords;
        const now = Date.now();
        if (now - lastSent >= intervalMs) {
            lastSent = now;
            send(latest);
        }
    };

    const onError = (err) => {
        setStatus(
            err.code === err.PERMISSION_DENIED
                ? 'Location permission denied.'
                : 'Unable to read location.',
            'error'
        );
        stop();
    };

    const start = () => {
        if (!('geolocation' in navigator)) {
            setStatus('This browser does not support geolocation.', 'error');
            return;
        }
        setStatus('Starting…');
        watchId = navigator.geolocation.watchPosition(onPosition, onError, {
            enableHighAccuracy: true,
            maximumAge: 5000,
            timeout: 15000,
        });
        if (toggle) toggle.textContent = 'Stop sharing my location';
        root.dataset.tracking = 'on';
    };

    const stop = () => {
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }
        if (toggle) toggle.textContent = 'Start sharing my location';
        root.dataset.tracking = 'off';
        setStatus('Location sharing off.');
    };

    if (toggle) {
        toggle.addEventListener('click', () => {
            if (root.dataset.tracking === 'on') {
                stop();
            } else {
                start();
            }
        });
    }
}
