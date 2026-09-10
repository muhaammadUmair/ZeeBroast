# Admin: Catalog Management (Categories, Products, Deals)

## Categories (Admin → Categories)

- **Fields**: Name, Slug (auto-generated from name), Icon (emoji), optional Image upload, Sort Order, Status (active/inactive).
- List view shows icon, name, slug, how many products are in it, and status.
- Add / Edit / Delete supported. Deleting a category also removes its products (foreign key cascade) — double-check before deleting a category with products in it.

## Products (Admin → Products)

- **Fields**: Name, Category (dropdown), Price, optional Sale Price, Icon (emoji, used when no image is uploaded), Description, Sort Order, Stock Status (In Stock / Out of Stock), Status (active/inactive).
- **Flags**: Featured (⭐, shown on the homepage), Popular (affects default sort order on menu/category pages).
- **Image**: Optional upload (JPG/PNG/WEBP/GIF, max 4MB) — replaces the emoji placeholder everywhere immediately, no code changes needed.
- Slug is auto-generated from the name and must be unique.
- List view supports searching by name and filtering by category; shows the sale price crossed out next to the current price when one is set.

## Deals & Combos (Admin → Deals)

- **Fields**: Title, Icon (emoji), Original Price, Deal Price, Description (e.g. "4 Pcs Broast + 2 Burgers + Fries"), Sort Order, Status.
- **Discount % is calculated automatically**: `(original_price − deal_price) / original_price × 100`.
- Optional image upload, same rules as products.
- Individual deal items (the itemized list shown on the storefront) are managed via the `deal_items` table linked to each deal.

## Uploaded images

All uploaded images are stored under `/uploads/{categories|products|deals}/` with a
randomly generated filename. PHP execution is disabled in that folder for security, so
uploaded files can never be run as scripts even if disguised.
