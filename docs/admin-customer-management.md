# Admin: Customer Management

## Customer list (Admin → Customers)

For every registered customer, the table shows: name, email, phone, number of orders,
total spent (excluding cancelled orders), account status, current loyalty points balance,
a toggle for loyalty eligibility, their referral code (if any), and a Block/Unblock
control plus a link to their dedicated Points page.

### Blocking / unblocking

Toggling a customer's status between **Active** and **Blocked** immediately affects
whether they can log in — blocked accounts are rejected at `login.php`.

### Allow Referral/Loyalty Points

This checkbox is **admin-only** — customers cannot enable it themselves. Turning it on:

- Makes the customer eligible to earn and redeem [loyalty points](loyalty-points.md) from their own purchases.
- Automatically converts their referral code to 0% discount / unlimited-use / never-expiring (or generates one if they don't have one yet) — see the [referral program guide](referral-program.md).

Turning it off stops future earning/redemption for that customer but doesn't touch their
existing code or points balance.

### Referral code column

Shows the customer's current code and its usage status, with a **Generate**/**Regenerate**
button. See the [referral program guide](referral-program.md#generatingregenerating-a-code-manually)
for details.

## Per-customer Points page (Admin → Customers → "Points")

Click **Points** on any customer row to open their dedicated page:

- Current balance and its Rs. value
- Full transaction ledger (type, linked order, points, value, description, timestamp)
- The same "Allow Referral/Loyalty Points" toggle
- **Manual Adjustment** form: add or deduct a specific number of points with a required
  reason — always recorded as an `ADJUSTMENT` entry in the ledger for auditing.
- Their **Referral QR Code**, if they have one, for easy sharing.
