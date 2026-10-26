# Admin: Employee Attendance

Open **Admin → Attendance** to record daily attendance for active admin employees. The list includes staff, managers, and super admins whose accounts are active.

## Marking attendance

- Select **Check In** when an employee starts work. The current server time is saved.
- The same button then changes to **Check Out**. Select it when the employee finishes their shift.
- Use **Start Break** and **End Break** to record breaks during the shift. Multiple breaks are supported, and the page totals the break time.
- An employee must end an active break before checking out.
- After check-out, the row shows **Completed** and cannot be checked in again that day.
- The page shows today's active employee, currently checked-in, and checked-out counts.

Attendance is recorded once per employee per calendar day. Inactive admin accounts are hidden from the daily list; their existing attendance records remain in the database.

## Database setup

For an existing installation, run `database/migration_safe_update.sql` once to create the attendance table. Fresh installations get the table from `database/schema.sql`.
