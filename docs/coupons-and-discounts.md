# Coupons & Discounts Guide

ZeeBroast has one underlying `coupons` table that powers **two different features**:
regular discount coupons, and [referral codes](referral-program.md) (which are a special
type of coupon — see that guide for the points-trigger behavior). This page covers
regular discount coupons.

## How a customer applies a coupon

At checkout, the customer types a code into the **Discount / Referral Code** field. When
they submit the checkout form, the code is validated:

- Must exist, be `active`, and not expired.
- The order subtotal must meet the coupon's minimum order amount.
- If it's a normal (non-referral) coupon, it can only be used **once per customer** — reusing an already-used code is rejected.

If valid, the discount is shown and recalculated on the payment page (percent-off or a flat Rs. amount, depending on the coupon type) — never trusted from the checkout page alone.

## Managing coupons (Admin → Coupons)

From **Admin → Coupons** you can:

- **Add a coupon**: Code, Discount Type (Percentage or Flat Amount), Discount Value, Minimum Order Amount, Expiry Date, Status (active/inactive).
- **Edit an existing coupon**: click **Edit** on any row — the form pre-fills with its current values; save to update in place (you can change the code, discount, min order, expiry, or status).
- **Delete a coupon**.
- Codes must be unique — trying to save a duplicate code shows an error.
- Discount value can be `0` (useful when marking a code as **Referral Code** — see below).

### The "Referral Code" checkbox

Checking **Referral Code** on a coupon changes its behavior completely:

- Discount is forced to **0%** — it never reduces the order total.
- It becomes **unlimited-use** for everyone (no "already used" restriction).
- Using it on an order becomes the trigger that awards [loyalty points](loyalty-points.md) to the code's linked customer.

See the [Referral Program guide](referral-program.md) for the full picture, including how codes get linked to a specific customer and how to generate one automatically from the Customers screen.
