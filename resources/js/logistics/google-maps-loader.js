/**
 * Loads the Google Maps JavaScript API exactly once and resolves when ready.
 * Safe to call from multiple components on the same page.
 */
let loaderPromise = null;

export function loadGoogleMaps(apiKey) {
    if (!apiKey) {
        return Promise.reject(new Error('missing-api-key'));
    }

    if (window.google && window.google.maps) {
        return Promise.resolve(window.google.maps);
    }

    if (loaderPromise) {
        return loaderPromise;
    }

    loaderPromise = new Promise((resolve, reject) => {
        const callbackName = '__revalueGmapsReady';
        window[callbackName] = () => resolve(window.google.maps);

        // Google calls this when the key is rejected (invalid key, API not
        // enabled, billing off, referrer blocked). It can fire *after* the
        // script loads, so components also listen for the event below.
        window.gm_authFailure = () => {
            window.__revalueMapsAuthFailed = true;
            window.dispatchEvent(new CustomEvent('revalue:maps-auth-failed'));
            reject(new Error('google-maps-auth-failed'));
        };

        const script = document.createElement('script');
        const params = new URLSearchParams({
            key: apiKey,
            libraries: 'geometry',
            callback: callbackName,
            loading: 'async',
        });
        script.src = `https://maps.googleapis.com/maps/api/js?${params.toString()}`;
        script.async = true;
        script.defer = true;
        script.onerror = () => reject(new Error('google-maps-load-failed'));
        document.head.appendChild(script);
    });

    return loaderPromise;
}
