# Admin: Admin Users & Access Control

`Admin → Admin Users` is only accessible to **super_admin** accounts.

## Roles

- **super_admin** — full access, including managing other admin accounts.
- **manager** / **staff** — access to the rest of the admin panel (catalog, orders, customers, coupons, settings) but not admin user management.

## Managing admin accounts

- **Add**: Full Name, Email, Password (min. 6 characters), Role.
- Duplicate emails are rejected.
- **Enable/Disable** or **Delete** any other admin — a super_admin cannot modify or delete their own currently logged-in account (to avoid accidentally locking themselves out).
- The list shows each admin's role, last login time (or "Never"), and status.

## Security notes

- Passwords are hashed with PHP's `password_hash()` and verified with `password_verify()` — never stored in plain text.
- Every admin form is protected with a CSRF token.
- All database queries use prepared statements (no raw string concatenation of user input into SQL).
- Change the default admin password (`admin@zeebroast.com` / `Admin@123`) immediately after first login.
