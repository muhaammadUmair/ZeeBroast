# Admin: Items & Purchases (Raw Materials)

Admin → Items and Admin → Purchases together let you log day-to-day raw material and
supply spend, separate from customer order data.

## Items (Admin → Items)

Preconfigure each raw material once: Name, optional Category (e.g. Meat, Vegetables,
Packaging), Unit (kg, litre, pcs, dozen, etc. — free text), and Status (active/inactive).
Name/category/unit only need to be set up here — inactive items are hidden from the
purchase form but keep their history.

## Recording a purchase

Fields: Purchase Date, Item (select from the preconfigured list — its category and unit
come along automatically), Quantity, Total Cost (Rs., entered fresh each time since prices
change), optional Supplier, and optional Notes. **Unit Cost is calculated automatically**
as `total cost ÷ quantity` and previewed as you type. Each purchase snapshots the item's
name/category/unit at the time of purchase, so editing or removing an item later doesn't
change past purchase records.

## Purchase history

- Filterable by date range (defaults to the last 30 days) and by a search term matching
  item name, category, or supplier.
- Summary cards show total spend and number of purchase entries for the filtered range.
- Each entry can be edited or deleted.

## What this does not do

This module only records purchase cost — it does not track stock levels, link purchases
to specific menu items, or affect the [profit report](admin-order-management.md#profit-report)
automatically. Use it as a standalone spend log for raw materials and supplies.
