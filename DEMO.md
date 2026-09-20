# REVALUE Demo - Full App Setup

## Quick Start (60 seconds)

```bash
cd /path/to/REVALUE
docker-compose up -d --build
sleep 10
open http://127.0.0.1:8000
```

**App ready at:** `http://127.0.0.1:8000`

---

## What You'll See

### 1. **Login Screen**
- **Super Admin Account**
  - Email: `superadmin@revalue.test`
  - Password: `password`

### 2. **Logistics Dashboard** (Maps/Logistics Feature)
Navigate to: **Logistics → Routes**

See:
- Live route list with drivers & stops
- Route status (planned, in progress, completed)
- Collection dates

### 3. **Live Map on Route Details**
Click any route to see:
- **Interactive Google Map** with:
  - Pickup locations (emerald markers)
  - Delivery locations (blue markers)
  - Route line connecting stops
  - Driver position (if assigned)
- **Live driver location sharing** (if assigned driver)
- **Fallback location list** (if Maps API key missing)

### 4. **Order Tracking**
View order pickup/delivery map:
- Single order with pickup & delivery points
- Live driver position from active route
- Real-time location updates (polling)

---

## Google Maps Integration

✅ **Pre-configured with Demo API Key**

The app includes:
- **Maps JavaScript API** for rendering
- **Geometry Library** for distance calculations
- **Route Directions API** support
- **Graceful fallback** when API key unavailable

**Key in `.env`:**
```
GOOGLE_MAPS_API_KEY=your-demo-key-here
```

### For Production:
1. Get a real API key: https://console.cloud.google.com
2. Enable billing
3. Restrict key to:
   - **Maps JavaScript API**
   - **Routes API**
   - **Your production domain**
4. Update `.env` on deployment

---

## Demo Flow for Stage

### Segment 1: Auth (1 min)
```
1. Show login page
2. Login as super admin
3. Show super admin dashboard
```

### Segment 2: Logistics/Maps (3 min)
```
1. Navigate to Logistics → Routes
2. Show list of routes (pre-seeded with demo data)
3. Click a route to open details
4. Highlight:
   - Route progress bar
   - Driver assignment
   - Stop count (pickups + deliveries)
   - LIVE GOOGLE MAP showing:
     * Pickup/delivery stops with markers
     * Route line connecting them
     * Driver position (if live)
     * Zoom/pan controls
5. Toggle "Start Route" button to show status changes
```

### Segment 3: Order Tracking (2 min)
```
1. Navigate to Orders or go to tracking page
2. Select an order with delivery location set
3. Show tracking map with:
   - Pickup location
   - Delivery location
   - Active driver from route
   - Live location updates
```

### Segment 4: Technical Highlights (2 min)
```
1. Show `.env` configuration (GOOGLE_MAPS_API_KEY)
2. Explain graceful fallback (show console if maps fail)
3. Show that no API key = location list (demo fallback)
4. Highlight real-time polling (driver location)
```

---

## Demo Data

Pre-seeded includes:
- **Super Admin** (full permissions)
- **Logistics Drivers** (can see/update routes)
- **Sample Routes** (tomorrow's date by default)
- **Pickup/Delivery Addresses** with coordinates
- **Route Stops** linked to orders

### Modify Demo Data:
Edit `database/seeders/LogisticsDemoSeeder.php` and re-run:
```bash
docker-compose exec app php artisan migrate:fresh --seed
```

---

## Troubleshooting

### Maps not loading?
1. Check `.env` has `GOOGLE_MAPS_API_KEY` set
2. Open browser DevTools → Console (look for errors)
3. Check API key is valid in Google Cloud Console
4. Verify key has Maps JavaScript API enabled

### Container won't start?
```bash
docker-compose logs app
docker-compose logs node
```

### Need to rebuild?
```bash
docker-compose down
docker-compose up -d --build
```

### Reset database?
```bash
docker-compose exec app php artisan migrate:fresh --seed
```

---

## Architecture Overview

```
┌─────────────────────────────────────────┐
│         Browser (http://127.0.0.1:8000) │
├─────────────────────────────────────────┤
│  Vue + Alpine + Tailwind (Vite)         │
│  - Google Maps JavaScript API loaded    │
│  - Real-time location polling           │
└────────┬────────────────────────────────┘
         │
         ├─── API Requests ───┐
         │                    │
    ┌────▼──────────────────┐ │
    │  Laravel 11 Backend   │ │
    │  - Database (SQLite)  │ │
    │  - Route logic        │ │
    │  - Maps data builder  │ │
    └──────────────────────┘ │
         │                    │
         └─────────┬──────────┘
                   │
          ┌────────▼──────────┐
          │ Google Maps API   │
          │ - Map rendering   │
          │ - Directions      │
          │ - Geometry calcs  │
          └───────────────────┘
```

---

## Files to Know

**Maps Integration:**
- `app/Support/Logistics/MapData.php` — Builds map JSON
- `app/Http/Controllers/Logistics/RouteController.php` — Passes data to view
- `resources/views/components/logistics/map.blade.php` — Map component
- `resources/js/logistics/google-maps-loader.js` — Loads API script
- `resources/js/logistics/map.js` — Initializes map + markers
- `config/services.php` — API key configuration

**Database:**
- `database/migrations/*` — Logistics tables (routes, stops, driver locations)
- `database/seeders/LogisticsDemoSeeder.php` — Demo data

**Routes:**
- `routes/web.php` — Logistics routes (requires auth)

---

## Post-Demo Notes

For a production demo or deployment:

1. **Get real API key** (not demo key)
2. **Enable billing** on Google Cloud project
3. **Add domain restrictions** to API key
4. **Configure for your domain** (CORS, referrer restrictions)
5. **Test with real delivery data** if available
6. **Set up monitoring** for API quota usage

Demo key has daily limits — upgrade before going live.

---

**Questions?** Check the code comments or `.env.example` for configuration details.
