# Referral Program Guide

The referral program lets a customer share a personal code so that when someone else
(or they themselves) places an order with it, **that customer earns loyalty points** —
without giving anyone a discount. It reuses the same `coupons`/`user_coupon_codes`
tables as regular discount coupons, with a dedicated `is_referral` flag.

## How it's different from a normal discount coupon

| | Regular coupon | Referral code |
|---|---|---|
| Discount | Percent or flat amount | Always **Rs. 0** |
| Reuse | Once per customer | **Unlimited**, by anyone, including the owner |
| Effect | Reduces order total | Triggers [loyalty points](loyalty-points.md) for its owner |
| Linked to a customer? | No | Yes — via `user_coupon_codes` |

A code only awards points if it's **linked to a specific customer** (created via the
"Generate" flow described below). A coupon manually marked "Referral Code" in
Admin → Coupons but never linked to anyone has nobody to credit.

## Enabling a customer for the referral/points program

In **Admin → Customers** (or a customer's dedicated **Admin → Loyalty Points** page),
toggle **Allow Referral/Loyalty Points** on. This automatically:

- Converts that customer's existing code (if any) to 0% discount, unlimited use, **never expires**.
- Or, if they don't have a code yet, generates a brand-new one for them immediately.

You never need a separate "make this code a referral code" step — enabling the toggle handles it.

## Generating/regenerating a code manually

From **Admin → Customers**, each row has a **Generate** / **Regenerate** button under
"Referral Code". Clicking it creates a fresh code for that customer:

- Format: `ZB{last 4 phone digits}R{random}`
- 0% discount, unlimited use, **no expiry**
- Regenerating creates a **new** code (the old one is left as-is in the database, not deleted)

## Sharing a code — QR codes

Open a customer's **Admin → Loyalty Points** page. If they have a referral code, you'll
see a **Referral QR Code** panel with:

- The code itself
- A scannable QR image (generated via the public `api.qrserver.com` service — only the
  public share URL is sent, no customer data)
- The full share link, e.g. `https://yoursite.com/index.php?ref=CODE`

## What happens when someone scans the QR / opens the link

1. The `?ref=CODE` link works on **any** storefront page (home, menu, deals, etc.).
2. If the code is valid, active, and flagged as a referral code, it's stored in the visitor's session and a one-time confirmation banner appears: *"Referral code X applied — it'll be used automatically at checkout."*
3. The customer shops normally. At checkout, the **Discount / Referral Code** field is pre-filled automatically — no typing needed.
4. If they clear the field and submit anyway, the auto-fill won't reappear for that session (their choice is respected).
5. Whether the person checking out is **logged in, a different logged-in customer, or a guest**, the points always go to the code's **owner** — never to whoever happens to be placing the order.

## Earning points from a referral order

Points are **not** awarded the moment the order is placed. They're awarded only once
the order reaches the status configured in [Admin → Site Settings → Loyalty / Referral
Points → "Award Points When Order Status Is"](loyalty-points.md#configuration-admin--site-settings)
(default: **Delivered**), and the order's `payment_status` isn't `failed`.

Important: points are only awarded when the status is changed **through the admin
panel's "Update Status" form** (Admin → Orders → order detail). Editing the `status`
column directly in the database (e.g. via phpMyAdmin) bypasses the award logic entirely,
since that logic lives in the PHP code, not the database. If you ever edit an order's
status directly in the database, re-trigger it by changing the status again through the
admin UI (e.g. set it to something else, save, then set it back to Delivered, save).

## Cancellation / refund handling

If a referral order is later cancelled, or its payment is marked `refunded`, the
previously-awarded points are automatically reversed with an offsetting ledger entry
(the original entry is never deleted — both stay visible for auditing).

## Troubleshooting checklist

If points aren't showing up after marking an order Delivered:

1. Is the **loyalty program globally enabled**? (Admin → Site Settings → Loyalty / Referral Points → "Enable loyalty/referral points program")
2. Is the code actually **linked to a customer**? Check the customer's Admin → Loyalty Points page — if there's no "Referral QR Code" panel, it's not linked.
3. Did you update the status **through the admin UI**, not directly in the database?
4. Does the order's status **exactly match** the configured "Award Points When Order Status Is" setting?
5. Is the qualifying amount (subtotal, ± delivery fee / discount depending on settings) **at least as much as** the configured "Spend Amount for 1 Point"? Below that threshold, 0 points is correct, not a bug.
6. Check the order detail page (Admin → Orders → order) — it shows a "Loyalty Points" panel with exactly what happened: who was credited, how many points, and whether it's still pending.
