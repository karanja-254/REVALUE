# ReValue AI Pricing System - API Documentation

## Overview

The AI Pricing System automatically recognizes items using Claude Vision and suggests prices based on Jiji market data. Sellers can accept suggested prices or request manual admin review. Super admins can override published prices with full audit logging.

## Flow Diagram

```
Seller Upload
    ↓
AI Recognition (Claude Vision)
    ↓
Pricing Engine (Jiji median * 0.80-0.90)
    ↓
Has Market Data?
    ├─ YES → Suggest Price
    │         ├─ Seller Accepts → Available
    │         └─ Seller Requests Review → Under Review → Admin Sets Price → Available
    │
    └─ NO → Under Review → Admin Sets Price → Available
    
Available Listing
    ↓
Super Admin Can Override Price (Audit Trail)
```

## API Endpoints

### Create Listing (with AI Processing)

**Endpoint:** `POST /api/listings`

**Auth:** Required (authenticated user)

**Request:**
```json
{
    "type": "sell|donate|recycle",
    "title": "Item title",
    "description": "Item description",
    "image": "<binary image file>"
}
```

**Response (201):**
```json
{
    "message": "Listing created. AI is analyzing your item...",
    "listing_id": 123
}
```

**Flow:**
1. Stores listing in draft status
2. Dispatches async `ProcessListingWithAI` job
3. Job calls Claude Vision to identify category, condition, defects
4. If Jiji market data exists, calculates suggested price
5. If no market data, queues for manual admin review

---

### Get Listing Details

**Endpoint:** `GET /api/listings/{id}`

**Auth:** Required (seller only)

**Response (200):**
```json
{
    "id": 123,
    "user_id": 1,
    "type": "sell",
    "title": "Samsung TV",
    "description": "...",
    "category": "Electronics",
    "condition": "Good",
    "image_path": "listings/...",
    "suggested_price": "28000.00",
    "final_price": null,
    "status": "draft",
    "processing_status": "completed",
    "ai_metadata": {
        "brand": "Samsung",
        "model": "43-inch",
        "detected_defects": [],
        "confidence": 0.95
    },
    "manual_review": null,
    "price_overrides": []
}
```

---

### Accept Suggested Price

**Endpoint:** `PUT /api/listings/{id}/accept-price`

**Auth:** Required (seller only)

**Response (200):**
```json
{
    "message": "Price accepted. Item is now available for purchase.",
    "listing": { ... }
}
```

**Response (422):**
```json
{
    "error": "No suggested price available. Item requires manual review."
}
```

**Effect:**
- Sets `final_price = suggested_price`
- Changes status to `available`
- Item is now buyable

---

### Request Manual Review

**Endpoint:** `PUT /api/listings/{id}/request-review`

**Auth:** Required (seller only)

**Request:**
```json
{
    "reason": "I think the price is too low"
}
```

**Response (200):**
```json
{
    "message": "Your item has been submitted for manual pricing review. An admin will review it shortly."
}
```

**Response (422):**
```json
{
    "error": "Item is already under manual review."
}
```

**Effect:**
- Creates `ManualReview` entry with status = `pending`
- Changes listing status to `under_review`

---

### Admin: View Manual Reviews Queue

**Endpoint:** `GET /api/admin/manual-reviews`

**Auth:** Required (admin or super_admin only)

**Response (200):**
```json
{
    "data": [
        {
            "id": 1,
            "listing_id": 123,
            "status": "pending",
            "notes": "No Jiji market data for RareAntique / Excellent",
            "assigned_admin_id": null,
            "created_at": "2026-09-19T20:40:00Z",
            "updated_at": "2026-09-19T20:40:00Z",
            "listing": { ... },
            "assignedAdmin": null
        }
    ],
    "links": { ... },
    "meta": { ... }
}
```

---

### Admin: Approve Manual Review

**Endpoint:** `PUT /api/admin/manual-reviews/{id}/approve`

**Auth:** Required (admin or super_admin only)

**Request:**
```json
{
    "final_price": 9500.00,
    "notes": "Good condition, below market"
}
```

**Response (200):**
```json
{
    "message": "Item approved and priced at KSh 9500.00",
    "listing_id": 123
}
```

**Effect:**
- Sets `listing.final_price`
- Changes `listing.status` to `available`
- Updates `manual_review.status` to `reviewed`
- Creates audit entry in `price_overrides` (if price differs from original)

---

### Super Admin: Override Published Price

**Endpoint:** `POST /api/admin/listings/{id}/override-price`

**Auth:** Required (super_admin only)

**Request:**
```json
{
    "new_price": 7500.00,
    "reason": "Market adjustment due to bulk order"
}
```

**Response (200):**
```json
{
    "message": "Price overridden from KSh 8000.00 to KSh 7500.00",
    "listing": { ... }
}
```

**Response (422):**
```json
{
    "error": "Can only override prices for available listings."
}
```

**Response (403):**
```json
{
    "error": "Unauthorized"
}
```

**Effect:**
- Creates audit entry in `price_overrides` with old/new prices, reason, admin ID
- Updates `listing.final_price`

---

### Super Admin: View Price Override History

**Endpoint:** `GET /api/admin/listings/{id}/price-history`

**Auth:** Required (admin or super_admin only)

**Response (200):**
```json
[
    {
        "id": 1,
        "listing_id": 123,
        "admin_id": 2,
        "old_price": "8000.00",
        "new_price": "7500.00",
        "reason": "Market adjustment due to bulk order",
        "override_at": "2026-09-19T21:00:00Z",
        "created_at": "2026-09-19T21:00:00Z",
        "updated_at": "2026-09-19T21:00:00Z",
        "admin": { "id": 2, "name": "Admin Name", ... }
    }
]
```

---

## Data Models

### Listing
- `id` (primary key)
- `user_id` (foreign key → users)
- `type` (enum: sell, donate, recycle)
- `title` (string)
- `description` (text)
- `category` (string, auto-detected by AI)
- `condition` (string, auto-detected by AI)
- `image_path` (string, storage path or URL)
- `suggested_price` (decimal, system-calculated)
- `final_price` (decimal, set by seller or admin)
- `status` (enum: draft, under_review, available, sold, donated, recycled, cancelled)
- `processing_status` (enum: pending, processing, completed, failed)
- `ai_metadata` (JSON, raw Claude Vision response)

### ManualReview
- `id` (primary key)
- `listing_id` (foreign key → listings, unique)
- `assigned_admin_id` (foreign key → users, nullable)
- `status` (enum: pending, reviewed)
- `notes` (text)
- `created_at`, `updated_at`

### PriceOverride (Audit Log)
- `id` (primary key)
- `listing_id` (foreign key → listings)
- `admin_id` (foreign key → users, who made the override)
- `old_price` (decimal, price before override)
- `new_price` (decimal, new price)
- `reason` (text, why the override was made)
- `override_at` (timestamp, when override occurred)
- `created_at`, `updated_at`

### JijiMarketData
- `id` (primary key)
- `category` (string)
- `condition` (string)
- `price` (decimal, market price from Jiji)
- `source_url` (string)
- `scraped_at` (timestamp)
- `created_at`, `updated_at`

---

## Pricing Formula

For items with Jiji market data:

```
suggested_price = jiji_median_price * random(0.80, 0.90)
```

This ensures ReValue undercuts Jiji slightly to be competitive.

**Example:**
- Jiji median for Electronics/Good condition: KSh 28,000
- Random multiplier: 0.85
- ReValue suggested price: KSh 23,800

---

## Jiji Market Data

Seeded with sample data on system startup. To refresh:

```bash
php artisan jiji:scrape
```

Scheduled daily via Laravel scheduler. For production, implement actual Jiji.ke web scraper.

Sample categories and conditions:
- **Electronics:** Mint, Excellent, Good, Fair
- **Furniture:** Mint, Good, Fair
- **Appliances:** Good, Fair

---

## Error Handling

### 400 Bad Request
Missing or invalid required fields.

### 401 Unauthorized
Authentication token missing or invalid.

### 403 Forbidden
Insufficient permissions (e.g., non-admin trying to approve review).

### 404 Not Found
Listing or review not found.

### 422 Unprocessable Entity
Business logic validation failed (e.g., no suggested price to accept).

### 500 Server Error
AI processing failed. Listing queued for manual review.

---

## Authorization Rules

| Endpoint | Required Role | Ownership Check |
|----------|---------------|----|
| POST /api/listings | User | N/A |
| GET /api/listings/{id} | User | Seller only |
| PUT /api/listings/{id}/accept-price | User | Seller only |
| PUT /api/listings/{id}/request-review | User | Seller only |
| GET /api/admin/manual-reviews | Admin/SuperAdmin | N/A |
| PUT /api/admin/manual-reviews/{id}/approve | Admin/SuperAdmin | N/A |
| POST /api/admin/listings/{id}/override-price | SuperAdmin | N/A |
| GET /api/admin/listings/{id}/price-history | Admin/SuperAdmin | N/A |

---

## Testing

Run all tests:
```bash
php artisan test
```

Run AI Pricing tests only:
```bash
php artisan test tests/Feature/EndToEndAiPricingTest.php
```

---

## Environment Variables

```
ANTHROPIC_API_KEY=sk-ant-...
```

---

## Future Enhancements

1. Real Jiji.ke web scraper (replaces seed data)
2. Cloudinary/S3 image storage (replaces local)
3. Paystack payment integration
4. Logistics tracking (pickup/delivery)
5. Native mobile app
6. Real-time notifications
