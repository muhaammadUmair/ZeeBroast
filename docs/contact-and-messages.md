# Contact Form & Message Inbox

## Customer-facing contact form (`contact.php`)

Fields: Name, Email, Phone (optional), Subject (optional), Message. On submit, it's
saved to the `contact_messages` table and the customer sees a confirmation. The page
also displays business phone, email, address, and working hours (pulled from
[Site Settings](admin-site-settings.md)).

## Admin inbox (Admin → Messages)

- Lists every submitted message with name, phone, email, subject, and a preview of the message body, newest first.
- **Unread messages** are highlighted so they stand out.
- **Mark Read** clears the highlight.
- **Delete** removes a message permanently (with a confirmation prompt).

## About page (`about.php`)

A static informational page — "Our Story", value highlights (100% Halal, Freshly
Prepared, Fast Delivery, Best Quality), and an "Order Now" call-to-action linking to the
menu. No admin-configurable content beyond the site name/branding pulled from settings.
