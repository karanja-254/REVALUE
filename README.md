# ReValue

ReValue is a Kenyan web platform that helps people **sell, donate, or recycle unwanted household items without bargaining, transport headaches, or unreliable strangers**.

The platform handles:

- item listing.
- AI-assisted item classification.
- fixed-price offers
- buyer payments.
- seller payout tracking
- pickup and delivery verification
- logistics tracking
- charity donations.
- recycling
- ratings and reputation

The core principle is simple:

> **No bargaining. No surprise prices. Once a ReValue price is accepted, that is the transaction price.**

---

# 1. Project Goal

Many people in Kenya have furniture, electronics, appliances, mattresses, household goods, or other unwanted items but do not want to:

- bargain with strangers
- arrange transport
- deal with fake buyers
- visit multiple charities
- struggle with recycling
- receive a different price from what was advertised

ReValue acts as the trusted middle layer.

A user can choose:

1. **Sell**
2. **Donate**
3. **Recycle**

---

# 2. Main User Flows

## Sell

A seller uploads an item with photos and details.

Example:

- Samsung 43" Smart TV.
- Good condition

AI helps identify the item and condition.

The ReValue pricing engine provides a suggested price.

Example:

`KSh 10,500`

The seller can:

- accept the offer
- request manual review

Once accepted, the price is fixed.

There is no bargaining chat.

A buyer either buys the item at the listed price or leaves it.

---

## Donate

A user can give an item away instead of selling it.

Examples:

- mattress
- table
- laptop
- chairs
- sofa
- microwave
- clothes
- household items

Verified charities can claim suitable donations.

The donor should not be forced to pay transport simply because they are giving something away.

The charity or approved collection arrangement handles pickup.

Only verified charities can claim donations.

---

## Recycle

Items that are too damaged or unsuitable for resale can be routed to verified recyclers.

Examples:

- broken TVs.
- damaged electronics
- scrap
- e-waste
- unusable appliances
- recyclable materials

---

# 3. Core Product Rules

These rules must not be changed without team agreement.

## No Bargaining

There is no normal buyer-to-seller bargaining chat.

The buyer sees the fixed price.

The buyer can:

- buy
- leave

The buyer cannot negotiate.

---

## Price Lock

Once a seller accepts a ReValue price and the item is published, the price is fixed.

Only authorized administration can override a price.

Every price override must be recorded in the audit log.

---

## AI Does Not Control Final Pricing

AI is used to help identify:

- category
- brand
- model
- visible condition
- possible defects

AI does not freely invent final prices.

Pricing should come from ReValue pricing rules and stored price ranges.

For the hackathon MVP, a mocked AI result is acceptable until the real AI service is connected.

---

# 4. Payment Flow

Paystack is used for buyer payments.

Example:

Item price:

`KSh 10,500`

Delivery:

`KSh 600`

Service fee:

`KSh 300`

Total:

`KSh 11,400`

The buyer pays through Paystack.

The backend must verify the payment before marking the order as paid.

Never trust only the browser success message.

---

# 5. Seller Payout Flow

Paystack is not used as the immediate seller payout system for the MVP.

ReValue maintains an internal seller payout ledger.

After successful pickup verification:

`Seller payout status -> READY`

A ReValue admin can then pay the seller using the business M-Pesa account.

The admin records:

- payout amount
- M-Pesa number
- M-Pesa transaction code
- admin who recorded payment
- date/time

Then:

`Seller payout status -> PAID`

---

# 6. Two-PIN Verification System

ReValue uses two separate 4-digit verification codes.

## Pickup PIN

The seller receives a 4-digit pickup PIN.

The seller only gives this code after the logistics team has:

- arrived
- checked the item
- confirmed that the item matches the listing
- physically taken custody of the item

After a successful Pickup PIN:

`Order -> PICKED_UP`

and

`Seller payout -> READY`

---

## Delivery PIN

The buyer receives another 4-digit PIN.

The buyer gives it to the delivery team after receiving the item.

After a successful Delivery PIN:

`Order -> COMPLETED`

---

# 7. Failed Pickup Verification

If the physical item does not match the listing:

Examples:

- wrong item
- undisclosed damage
- broken item when listing said working
- missing parts
- incorrect model

The logistics team must not negotiate a lower price at the doorstep.

Instead:

`Pickup -> FAILED`

The item remains with the seller.

The buyer refund process begins.

Evidence photos may be uploaded.

---

# 8. Logistics Model

ReValue runs the logistics network.

The collector is not the buyer.

ReValue will batch pickups and deliveries to reduce transport costs.

Planned collection days:

- Wednesday
- Saturday

The logistics team handles:

- pickup
- item verification
- transport
- delivery

Users should be able to view relevant delivery status.

---

# 9. Maps and Tracking

The logistics feature should support:

- seller pickup location
- buyer delivery location
- driver current location
- route display
- delivery progress
- pickup progress

The web app should work on mobile browsers.

A native Android/iOS app is not required for the hackathon.

---

# 10. User Roles

## User

A normal user can:

- sell
- buy
- donate
- recycle
- view orders
- view payouts
- rate completed transactions

A single normal account can be both a buyer and seller.

---

## Logistics

Logistics users can:

- see assigned pickups
- see assigned deliveries
- view required addresses
- update route status
- verify Pickup PINs
- verify Delivery PINs
- record item verification result

They must not have access to:

- price configuration
- platform financial reports
- admin creation
- charity approval
- super-admin settings

---

## Admin

Admins can manage:

- listings
- orders
- seller payouts
- logistics
- charities
- recycling businesses
- manual reviews

---

## Super Admin

Super Admin has full control.

Super Admin can:

- manage admins
- manage pricing
- override prices
- verify charities
- verify businesses
- view payments
- view payouts
- manage platform settings
- view audit logs

---

# 11. Charity Verification

Charities must be verified before they can claim donations.

A charity application may contain:

- organization name
- contact person
- email
- phone
- physical location
- registration details
- supporting documents

Possible verification statuses:

- pending
- verified
- rejected

Only verified charities appear to donors.

---

# 12. Charity Needs

Verified charities may publish requested items.

Example:

Hope Children's Home needs:

- 5 mattresses
- 2 laptops
- 10 chairs

This allows ReValue to match donations with actual demand.

---

# 13. Ratings

After successful transactions, users can leave ratings.

Possible rating categories:

- accurate description
- smooth delivery
- professional handling
- punctual pickup

Users can also build a history showing:

- successful transactions
- donations completed
- items recycled

---

# 14. Technology Stack

## Backend

- PHP
- Laravel 12

## Frontend

- Laravel Blade
- Tailwind CSS
- Alpine.js or lightweight JavaScript where necessary

## Database

For the shared development architecture:

- MySQL

SQLite may be used temporarily during local MVP development if necessary.

## Payments

- Paystack

## Seller Payouts

- ReValue internal payout ledger
- Manual M-Pesa payout record for MVP

## Image Storage

- Cloudinary

## AI

- Vision-capable AI API

## Maps

- Google Maps API

## Email

- Resend

## Version Control

- GitHub
- Private repository

---

# 15. MVP Shared Tables

The main shared tables are expected to include:

- users
- listings
- orders
- seller_payouts
- organizations
- charity_needs
- payments
- routes
- route_stops
- driver_locations
- reviews
- audit_logs

Team members must NOT independently create duplicate versions of shared tables.

Shared database changes should be agreed with the Core/Integration lead first.

---

# 16. Important Status Values

## Listing Types

- sell
- donate
- recycle

## Listing Statuses

- draft
- under_review
- available
- sold
- donated
- recycled
- cancelled

## Payment Statuses

- pending
- paid
- failed
- refunded

## Order Statuses

- pending_payment
- paid
- scheduled
- picked_up
- out_for_delivery
- completed
- cancelled
- pickup_failed

## Seller Payout Statuses

- pending
- ready
- paid
- failed
- cancelled

## Organization Verification Statuses

- pending
- verified
- rejected

---

# 17. Team Structure

There are 5 developers working on this project.

## Person 1 - Core Laravel / Integration Lead

Responsibilities:

- Laravel architecture
- shared database migrations
- shared Eloquent models
- authentication foundation
- roles and permissions foundation
- listings backbone
- orders backbone
- integration
- merging pull requests
- final debugging

Branch:

`feature/core`

---

## Person 2 - Payments / Paystack / Payout Ledger

Responsibilities:

- Paystack checkout
- payment initialization
- payment verification
- webhook handling
- payment status updates
- seller payout ledger
- manual M-Pesa payout recording
- basic refund status

Branch:

`feature/paystack`

---

## Person 3 - AI / Pricing

Responsibilities:

- AI item recognition
- category detection
- condition detection
- mock AI fallback
- pricing engine
- manual price review
- suggested price logic
- super-admin price override logic

Branch:

`feature/ai-pricing`

---

## Person 4 - Maps / Logistics

Responsibilities:

- pickup location
- delivery location
- driver location
- route tracking
- logistics dashboard
- Wednesday/Saturday route flow
- Pickup PIN UI
- Delivery PIN UI
- status updates

Branch:

`feature/maps-logistics`

---

## Person 5 - UI / Charity / Demo Polish

Responsibilities:

- homepage
- responsive Blade layouts
- Tailwind UI
- Sell / Donate / Recycle forms
- charity application UI
- charity verification UI
- donation claim flow
- ratings UI
- demo polish

Branch:

`feature/ui-charity`

---

# 18. Branch Rules

Nobody should code directly on `main`.

Each developer works on their own branch.

Main branches:

- `main`
- `feature/core`
- `feature/paystack`
- `feature/ai-pricing`
- `feature/maps-logistics`
- `feature/ui-charity`

Before starting work:

```bash
git checkout main
git pull origin main
