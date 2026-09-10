# Loyalty Points Guide

A configurable points system: customers earn points on qualifying purchases and can
redeem them for a discount on a future order. Fully optional — disabled by default and
switched off with one setting if you ever want to turn it off.

## How the math works

Two admin-configurable numbers control everything:

- **Spend Amount for 1 Point** — e.g. Rs. 100 spent = 1 point earned.
- **Point Value** — e.g. 1 point = Rs. 1.50 when redeemed.

```
points_earned = floor(qualifying_amount / spend_amount_for_one_point)
redeemable_value = points × point_value
```

Points are always **whole numbers** (rounded down, never up) — Rs. 450 at a Rs. 100/point
rate earns 4 points, not 4.5. Example: Rs. 1,000 spent → 10 points → 10 × Rs. 1.50 = **Rs. 15** redeemable.

## Configuration (Admin → Site Settings)

Under **Loyalty / Referral Points**:

| Setting | Effect |
|---|---|
| Enable loyalty/referral points program | Master on/off switch for the whole feature |
| Spend Amount for 1 Point | Rs. amount that earns 1 point |
| Value per Point | Rs. value of each point when redeemed |
| Award Points When Order Status Is | Which order status triggers earning (default: Delivered) |
| Maximum Redemption (% of order payable amount) | Caps how much of an order's total can be paid with points (default 100%) |
| Earn points on delivery fee | Whether delivery fee counts toward the qualifying amount |
| Deduct coupon/discount before calculating earned points | Whether a coupon discount reduces the qualifying amount |

A live example calculation updates on the settings page as you change these values.

## Earning points

Points are only awarded once an order reaches the configured status (not at the moment
of placing the order), and never for orders with a `failed` payment status. This
prevents awarding points for orders that are cancelled or never actually paid for.

There are two ways an order can be point-eligible:

1. **The customer's account has "Allow Referral/Loyalty Points" enabled** (Admin → Customers) — their own qualifying purchases earn points.
2. **A [referral code](referral-program.md) was applied at checkout** — in this case the points always go to the *code's owner*, regardless of who placed the order or whether their own checkbox is on.

Points are never awarded twice for the same order — this is enforced at the database
level (an atomic guard flag on the order), not just in the UI, so re-saving a status or
concurrent requests can't double-award.

## Redeeming points at checkout

If the logged-in customer is eligible and has a balance, the Payment page shows:

- Available Points, Point Value, and the maximum currently usable
- A field to choose how many points to redeem, with a live discount preview

The customer can never redeem more than their available balance, and the discount can
never make the order total negative — redemption is capped to the order's payable amount
(times the configured maximum redemption %). **All of this is re-validated server-side**
at the moment the order is placed — the frontend numbers are only a preview, never
trusted directly. Redemption is also protected against double-spending from two
simultaneous requests (e.g. two open tabs) using a database row lock.

## Cancellation / refund handling

If an order that already earned points is later cancelled, or its payment is marked
refunded, the points are automatically reversed with a new offsetting ledger entry — the
original "earned" entry is never deleted, keeping a full audit trail. If those points
were already spent elsewhere, the customer's balance can temporarily go negative to
reflect that; this is intentional so the ledger stays accurate.

## Customer view (My Account)

Logged-in eligible customers see a **Loyalty Points** section on `account.php`:
available points, their Rs. value, the current point rate, and a transaction history
table — date, type (Earn/Redeem/Adjustment/Refund), linked order (clickable), points,
value, running balance after that transaction, and a description.

## Admin management (Admin → Customers → "Points")

From a customer's dedicated Loyalty Points page you can:

- View their current balance and full transaction ledger
- Toggle "Allow Referral/Loyalty Points" for them (also auto-configures their referral code — see the [referral program guide](referral-program.md))
- **Manually add or deduct points**, with a required reason — this always creates an `ADJUSTMENT` ledger entry (e.g. "+100 points — Customer compensation")
- See their **Referral QR Code** for sharing

## Transaction types in the ledger

| Type | Meaning |
|---|---|
| `EARN` | Points credited from a qualifying order |
| `REDEEM` | Points spent as a discount on an order |
| `ADJUSTMENT` | Manually added/deducted by an admin |
| `REFUND` | Reversal of previously-earned points (order cancelled/refunded) |
| `EXPIRATION` | Reserved for future use (not currently implemented) |

A customer's balance is always calculated live as the sum of their ledger — there's no
separate "balance" number that can drift out of sync.

## Troubleshooting

See the [Referral Program troubleshooting checklist](referral-program.md#troubleshooting-checklist) —
the same steps apply whether points are expected from a referral code or from a
customer's own qualifying purchase.
