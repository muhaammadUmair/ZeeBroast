# Admin: Order Management

## Dashboard (Admin → Dashboard)

At a glance: total orders (+ today's count), pending/active orders needing attention,
total revenue (+ today's), and total registered customers — plus a table of the 8 most
recent orders with a quick link to each.

## Orders list (Admin → Orders)

- Search by Order ID, customer name, or phone.
- Filter by status: Pending, Confirmed, Preparing, On The Way, Delivered, Cancelled.
- Paginated, 20 per page.
- Each row shows Order ID, customer, order type, payment method, total, status badge, and placed time, with a **View** link to the full order detail.

## Order detail (Admin → Orders → view)

Shows: full item list with quantities and line totals, subtotal/delivery fee/discount/total
breakdown, customer & delivery details, payment method, and — if the
[loyalty program](loyalty-points.md) is enabled — a **Loyalty Points** panel showing
points earned/redeemed for this specific order and who they were credited to (relevant
when a [referral code](referral-program.md) was used).

### Updating status

The **Update Status** form lets you change the **Order Status** and **Payment Status**
independently. Order statuses: `pending → confirmed → preparing → on_the_way → delivered`,
or `cancelled`. Payment statuses: `pending, paid, failed, refunded`.

> **Important:** always change status **through this form**, not by editing the database
> directly (e.g. in phpMyAdmin). Saving through the admin UI is what triggers side
> effects like awarding or reversing loyalty points — a direct database edit silently
> skips that logic. See the [referral program troubleshooting guide](referral-program.md#troubleshooting-checklist)
> if you've done this and need to re-trigger it.

Setting status to **Cancelled**, or payment status to **Refunded**, automatically
reverses any loyalty points that were previously earned on that order.
