import { loadGoogleMaps } from './google-maps-loader.js';

/**
 * Renders a logistics map (pickup + delivery stops, live driver marker, and a
 * route line) into any element carrying [data-logistics-map].
 *
 * Expected markup:
 *   <div data-logistics-map
 *        data-maps-key="..."
 *        data-locations-url="/logistics/routes/1/locations"   (optional polling)
 *        data-poll-ms="10000">
 *     <script type="application/json" data-map-payload>{ ...MapData... }</script>
 *     <div data-map-canvas class="h-96"></div>
 *     <div data-map-fallback> ...server-rendered list... </div>
 *   </div>
 *
 * When no API key is configured, the canvas is hidden and the server-rendered
 * fallback list remains visible so the feature still works on any browser.
 */

const STOP_COLORS = {
    pickup: '#059669', // emerald-600
    delivery: '#2563eb', // blue-600
};

function readPayload(root) {
    const node = root.querySelector('[data-map-payload]');
    if (!node) return null;
    try {
        return JSON.parse(node.textContent);
    } catch (e) {
        return null;
    }
}

function stopSvgMarker(color, label) {
    return {
        path: 'M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z',
        fillColor: color,
        fillOpacity: 1,
        strokeColor: '#ffffff',
        strokeWeight: 2,
        scale: 1.6,
        labelOrigin: { x: 12, y: 9 },
        anchor: { x: 12, y: 22 },
    };
}

class LogisticsMap {
    constructor(root, maps) {
        this.root = root;
        this.maps = maps;
        this.markers = [];
        this.driverMarker = null;
        this.routeLine = null;
        this.locationsUrl = root.dataset.locationsUrl || null;
        this.pollMs = parseInt(root.dataset.pollMs || '12000', 10);

        const canvas = root.querySelector('[data-map-canvas]');
        const fallback = root.querySelector('[data-map-fallback]');
        if (fallback) fallback.classList.add('hidden');
        canvas.classList.remove('hidden');

        const payload = readPayload(root);
        const center = payload?.center || { lat: -1.286389, lng: 36.817223 };

        this.map = new maps.Map(canvas, {
            center,
            zoom: 12,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
        });

        this.render(payload);

        if (this.locationsUrl) {
            this.startPolling();
        }
    }

    clear() {
        this.markers.forEach((m) => m.setMap(null));
        this.markers = [];
        if (this.routeLine) {
            this.routeLine.setMap(null);
            this.routeLine = null;
        }
    }

    render(payload) {
        if (!payload) return;
        this.clear();

        const bounds = new this.maps.LatLngBounds();
        const stops = (payload.stops || []).filter((s) => s.lat != null && s.lng != null);

        stops.forEach((stop) => {
            const position = { lat: Number(stop.lat), lng: Number(stop.lng) };
            const color = STOP_COLORS[stop.type] || '#6b7280';
            const done = ['completed', 'failed', 'skipped'].includes(stop.status);

            const marker = new this.maps.Marker({
                position,
                map: this.map,
                title: `${stop.label || ''}${stop.address ? ' — ' + stop.address : ''}`,
                opacity: done ? 0.5 : 1,
                icon: stopSvgMarker(color),
                label: stop.sequence
                    ? { text: String(stop.sequence), color: '#fff', fontSize: '11px', fontWeight: '700' }
                    : undefined,
            });

            const info = new this.maps.InfoWindow({
                content: `<div style="font-size:13px">
                    <strong>${stop.label || stop.type}</strong><br>
                    ${stop.address ? stop.address + '<br>' : ''}
                    ${stop.contact ? '👤 ' + stop.contact + '<br>' : ''}
                    <em>${stop.status || ''}</em>
                </div>`,
            });
            marker.addListener('click', () => info.open(this.map, marker));

            this.markers.push(marker);
            bounds.extend(position);
        });

        // Route line through the stops in sequence.
        if (stops.length > 1) {
            this.routeLine = new this.maps.Polyline({
                path: stops.map((s) => ({ lat: Number(s.lat), lng: Number(s.lng) })),
                geodesic: true,
                strokeColor: '#4f46e5',
                strokeOpacity: 0.8,
                strokeWeight: 3,
                map: this.map,
            });
        }

        this.renderDriver(payload.driver, bounds);

        if (!bounds.isEmpty()) {
            this.map.fitBounds(bounds, 60);
            if (stops.length + (payload.driver ? 1 : 0) === 1) {
                this.map.setZoom(15);
            }
        }
    }

    renderDriver(driver, bounds) {
        if (!driver || driver.lat == null) {
            if (this.driverMarker) {
                this.driverMarker.setMap(null);
                this.driverMarker = null;
            }
            return;
        }

        const position = { lat: Number(driver.lat), lng: Number(driver.lng) };
        const icon = {
            path: this.maps.SymbolPath.FORWARD_CLOSED_ARROW,
            fillColor: driver.stale ? '#9ca3af' : '#f59e0b',
            fillOpacity: 1,
            strokeColor: '#ffffff',
            strokeWeight: 2,
            scale: 6,
            rotation: driver.heading || 0,
        };

        if (this.driverMarker) {
            this.driverMarker.setPosition(position);
            this.driverMarker.setIcon(icon);
        } else {
            this.driverMarker = new this.maps.Marker({
                position,
                map: this.map,
                icon,
                title: driver.stale ? 'Driver (last known position)' : 'Driver (live)',
                zIndex: 999,
            });
        }

        if (bounds) bounds.extend(position);
    }

    startPolling() {
        const tick = async () => {
            try {
                const res = await fetch(this.locationsUrl, {
                    headers: { Accept: 'application/json' },
                });
                if (res.ok) {
                    const data = await res.json();
                    // Only the driver moves frequently; refresh the whole payload
                    // so stop statuses update too.
                    this.render(data);
                }
            } catch (e) {
                /* keep the last good state on transient errors */
            }
        };
        this.pollTimer = setInterval(tick, this.pollMs);
    }
}

/**
 * Drop back to the server-rendered stop list. Used when the key is missing,
 * rejected by Google, or the script cannot load — the driver still gets every
 * address instead of Google's red "something went wrong" box.
 */
function showFallback(root, message = null) {
    const fallback = root.querySelector('[data-map-fallback]');
    const canvas = root.querySelector('[data-map-canvas]');

    if (canvas) {
        canvas.classList.add('hidden');
        canvas.innerHTML = '';
    }

    if (fallback) {
        fallback.classList.remove('hidden');

        if (message && !fallback.querySelector('[data-map-error]')) {
            const note = document.createElement('p');
            note.dataset.mapError = '';
            note.className = 'mb-3 text-xs text-amber-700';
            note.textContent = message;
            fallback.prepend(note);
        }
    }
}

export function initLogisticsMaps() {
    const roots = document.querySelectorAll('[data-logistics-map]');
    roots.forEach((root) => {
        const key = root.dataset.mapsKey;
        const fallback = root.querySelector('[data-map-fallback]');

        if (!key) {
            // No key: leave the server-rendered fallback list visible.
            if (fallback) fallback.classList.remove('hidden');
            return;
        }

        const authMessage = 'Live map unavailable (Google rejected the API key). Showing stop locations as a list.';

        // Google can reject the key after the script has already loaded.
        window.addEventListener('revalue:maps-auth-failed', () => showFallback(root, authMessage), { once: true });

        loadGoogleMaps(key)
            .then((maps) => {
                if (window.__revalueMapsAuthFailed) {
                    showFallback(root, authMessage);

                    return;
                }

                new LogisticsMap(root, maps);
            })
            .catch((error) => {
                showFallback(
                    root,
                    error?.message === 'google-maps-auth-failed'
                        ? authMessage
                        : 'Live map could not load. Showing stop locations as a list.'
                );
            });
    });
}
