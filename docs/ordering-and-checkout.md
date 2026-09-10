# Ordering & Checkout Guide

How customers browse the menu, build a cart, and complete an order on the ZeeBroast storefront.

## Browsing the menu

- **Menu page** (`menu.php`): filter by price range (Rs. 100–2000, Rs. 50 steps), category, and sort by Popularity, Price (Low→High), Price (High→Low), or Newest First. Results are paginated 8 per page.
- **Categories page** (`category.php`): shows all active categories with item counts; clicking one lists its products with the same sort options.
- **Product page** (`product.php`): shows name, description, price (sale price if set), a quantity selector, an "Add to Cart" button, and up to 4 related products from the same category.
- **Deals page** (`deals.php`): shows active combo deals with their included items, original price (crossed out), deal price, and discount badge.

Adding an item to the cart (from any page) sends an AJAX request to `api/cart_action.php` and updates the cart icon count without a page reload.

## Cart

On `cart.php` each line shows a thumbnail, name, price, quantity controls (+/−), and a remove button — all updated live via AJAX. The page shows a running subtotal and a button to proceed to checkout. Discount codes are **not** entered here — that happens on the checkout/payment step.

## Checkout flow

1. **Checkout** (`checkout.php`) — customer chooses delivery or takeaway, enters/selects a delivery address, sets delivery time (ASAP or scheduled), enters name/phone, and optionally enters a **Discount / Referral Code**.
   - If the customer arrived via a [referral QR/link](referral-program.md), this field is pre-filled automatically.
   - Logged-in customers can pick a saved address or add a new one (saved for next time).
2. **Payment** (`payment.php`) — shows the order summary (subtotal, delivery fee, discount, [loyalty points redemption](loyalty-points.md) if eligible) and lets the customer choose a payment method: Cash on Delivery, JazzCash, EasyPaisa, or Card. All totals and discounts are recalculated server-side here — nothing from the previous page is trusted blindly.
3. **Order confirmation** (`order-confirmation.php`) — shows the final order code, totals, and estimated delivery time.

## Delivery fee & minimum order

Delivery fee and free-delivery threshold are configurable in [Admin → Site Settings](admin-site-settings.md). If the cart subtotal is at or above the free-delivery threshold, delivery is free.

## Tracking an order

`track-order.php` looks up an order by its **Order ID** (e.g. `ZB1234AB`) and shows a step-by-step progress indicator: Order Received → Confirmed → Preparing → On The Way → Delivered. Cancelled orders show a cancellation notice instead of the progress bar. The order total and estimated delivery time are also displayed.

Logged-in customers can also see their full order history and a **Track** shortcut from [My Account](customer-accounts.md).
