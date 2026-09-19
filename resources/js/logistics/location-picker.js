import { loadGoogleMaps } from './google-maps-loader.js';

/**
 * Interactive location picker for sellers (pickup) and buyers (delivery).
 *
 * Markup lives in resources/views/components/logistics/location-picker.blade.php.
 * Works with or without a Google Maps key — geolocation always works from the
 * phone browser.
 */
export function initLocationPickers() {
    document.querySelectorAll('[data-location-picker]').forEach((root) => {
        new LocationPicker(root);
    });
}

class LocationPicker {
    constructor(root) {
        this.root = root;
        this.mapsKey = root.dataset.mapsKey || '';
        this.latInput = root.querySelector('[data-lat]');
        this.lngInput = root.querySelector('[data-lng]');
        this.coordsDisplay = root.querySelector('[data-coords-display]');
        this.statusEl = root.querySelector('[data-picker-status]');
        this.canvas = root.querySelector('[data-picker-canvas]');
        this.noMapHint = root.querySelector('[data-no-map-hint]');
        this.markerColor = root.dataset.markerColor || '#4f46e5';

        const defaultLat = parseFloat(root.dataset.defaultLat || '-1.286389');
        const defaultLng = parseFloat(root.dataset.defaultLng || '36.817223');
        const initialLat = parseFloat(this.latInput?.value);
        const initialLng = parseFloat(this.lngInput?.value);
        this.position = Number.isFinite(initialLat) && Number.isFinite(initialLng)
            ? { lat: initialLat, lng: initialLng }
            : { lat: defaultLat, lng: defaultLng };

        root.querySelector('[data-use-current]')?.addEventListener('click', () => this.useCurrentLocation());
        root.querySelector('form')?.addEventListener('submit', (e) => this.validateBeforeSubmit(e));

        if (this.mapsKey && this.canvas) {
            this.noMapHint?.classList.add('hidden');
            loadGoogleMaps(this.mapsKey)
                .then((maps) => this.initMap(maps))
                .catch(() => this.showNoMapFallback());
        } else {
            this.showNoMapFallback();
        }

        this.syncDisplay();
    }

    showNoMapFallback() {
        this.canvas?.classList.add('hidden');
        this.noMapHint?.classList.remove('hidden');
    }

    initMap(maps) {
        this.map = new maps.Map(this.canvas, {
            center: this.position,
            zoom: Number.isFinite(parseFloat(this.latInput?.value)) ? 15 : 12,
            mapTypeControl: false,
            streetViewControl: false,
        });

        this.marker = new maps.Marker({
            position: this.position,
            map: this.map,
            draggable: true,
            icon: {
                path: 'M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z',
                fillColor: this.markerColor,
                fillOpacity: 1,
                strokeColor: '#ffffff',
                strokeWeight: 2,
                scale: 1.8,
                anchor: new maps.Point(12, 22),
            },
        });

        this.marker.addListener('dragend', () => {
            const pos = this.marker.getPosition();
            this.setPosition(pos.lat(), pos.lng());
        });

        this.map.addListener('click', (e) => {
            this.marker.setPosition(e.latLng);
            this.setPosition(e.latLng.lat(), e.latLng.lng());
        });
    }

    setPosition(lat, lng) {
        this.position = { lat: Number(lat), lng: Number(lng) };
        if (this.latInput) this.latInput.value = this.position.lat.toFixed(7);
        if (this.lngInput) this.lngInput.value = this.position.lng.toFixed(7);
        this.syncDisplay();
        this.setStatus('Location pinned on the map.');
    }

    syncDisplay() {
        if (this.coordsDisplay) {
            this.coordsDisplay.textContent = `${this.position.lat.toFixed(5)}, ${this.position.lng.toFixed(5)}`;
        }
    }

    useCurrentLocation() {
        if (!('geolocation' in navigator)) {
            this.setStatus('This browser does not support geolocation.', 'error');
            return;
        }

        this.setStatus('Getting your location…');

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const { latitude, longitude } = pos.coords;
                this.setPosition(latitude, longitude);
                if (this.map) {
                    this.map.panTo(this.position);
                    this.map.setZoom(16);
                    this.marker?.setPosition(this.position);
                }
                this.setStatus('Using your current location.', 'success');
            },
            (err) => {
                const msg = err.code === err.PERMISSION_DENIED
                    ? 'Location permission denied. Allow location access or tap the map.'
                    : 'Could not read your location. Tap the map to place a pin.';
                this.setStatus(msg, 'error');
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
        );
    }

    validateBeforeSubmit(e) {
        const lat = parseFloat(this.latInput?.value);
        const lng = parseFloat(this.lngInput?.value);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            e.preventDefault();
            this.setStatus('Please share your location first (use the button or tap the map).', 'error');
        }
    }

    setStatus(text, tone = 'muted') {
        if (!this.statusEl) return;
        this.statusEl.textContent = text;
        this.statusEl.dataset.tone = tone;
    }
}
