# Customer Accounts Guide

## Registering

`register.php` asks for: Full Name, Address, Email (optional — an internal placeholder email is generated if left blank), WhatsApp Number, and a Password (minimum 6 characters).

On successful signup, every new customer automatically receives a **10% welcome discount code**:

- Format: `ZB{last 4 digits of phone}W{random}` (e.g. `ZB1234WA1B2C3D4`)
- 10% off, no minimum order amount
- **Expires in 2 days**
- A "Share Discount Code To ZeeBroast" button opens WhatsApp with a pre-filled message containing the code, addressed to the business's WhatsApp number.

This welcome coupon is separate from the [referral program](referral-program.md) — it's a one-time signup incentive, not reusable or shareable for repeat use.

## Logging in

`login.php` accepts either the registered **WhatsApp number or email**, plus the password. Blocked accounts (`status = blocked`, set by an admin) cannot log in.

## My Account page

`account.php` (requires login) shows:

- **Profile**: name, email, phone, and a logout button.
- **Order History**: the customer's last 20 orders with status and a "Track" link to `track-order.php`.
- **Loyalty Points** section (only shown if the [loyalty program](loyalty-points.md) is enabled *and* the admin has enabled it for that specific customer): available points, their monetary value, the current point rate, and a full transaction history table (date, type, order reference, points, value, running balance, description).

## Admin control over customer accounts

Admins manage all customers from **Admin → Customers** — see [Admin: Customer Management](admin-customer-management.md) for blocking/unblocking accounts, enabling loyalty points, and generating referral codes.
