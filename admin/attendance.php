<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
ensure_attendance_schema();

$admin_page_title = 'Employee Attendance';
$active_admin = 'attendance';
$pdo = db();
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        flash('attendance_error', 'Your session expired. Please try again.');
    } else {
        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $action = $_POST['form_action'] ?? '';
        $employeeStmt = $pdo->prepare("SELECT id FROM admin_users WHERE id = ? AND status = 'active'");
        $employeeStmt->execute([$employeeId]);
        $employee = $employeeStmt->fetch();

        if (!$employee || !in_array($action, ['check_in', 'check_out', 'start_break', 'end_break'], true)) {
            flash('attendance_error', 'That employee or attendance action is not valid.');
        } else {
            $now = date('Y-m-d H:i:s');
            if ($action === 'check_in') {
                $stmt = $pdo->prepare('INSERT INTO attendance (admin_user_id, attendance_date, check_in) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE check_in = COALESCE(check_in, VALUES(check_in))');
                $stmt->execute([$employeeId, $today, $now]);
                flash('attendance_success', 'Check-in recorded.');
            } elseif ($action === 'start_break' || $action === 'end_break') {
              $attendanceStmt = $pdo->prepare('SELECT id FROM attendance WHERE admin_user_id = ? AND attendance_date = ? AND check_in IS NOT NULL AND check_out IS NULL');
              $attendanceStmt->execute([$employeeId, $today]);
              $attendanceId = (int)$attendanceStmt->fetchColumn();
              if (!$attendanceId) {
                flash('attendance_error', 'The employee must be checked in and not checked out to record a break.');
              } elseif ($action === 'start_break') {
                $openBreakStmt = $pdo->prepare('SELECT id FROM attendance_breaks WHERE attendance_id = ? AND break_end IS NULL LIMIT 1');
                $openBreakStmt->execute([$attendanceId]);
                if ($openBreakStmt->fetchColumn()) {
                  flash('attendance_error', 'This employee is already on a break.');
                } else {
                  $pdo->prepare('INSERT INTO attendance_breaks (attendance_id, break_start) VALUES (?, ?)')->execute([$attendanceId, $now]);
                  flash('attendance_success', 'Break started.');
                }
              } else {
                $stmt = $pdo->prepare('UPDATE attendance_breaks SET break_end = ? WHERE attendance_id = ? AND break_end IS NULL');
                $stmt->execute([$now, $attendanceId]);
                if ($stmt->rowCount() === 1) {
                  flash('attendance_success', 'Break ended.');
                } else {
                  flash('attendance_error', 'This employee is not currently on a break.');
                }
              }
            } else {
              $stmt = $pdo->prepare('UPDATE attendance SET check_out = ? WHERE admin_user_id = ? AND attendance_date = ? AND check_in IS NOT NULL AND check_out IS NULL AND NOT EXISTS (SELECT 1 FROM attendance_breaks WHERE attendance_breaks.attendance_id = attendance.id AND attendance_breaks.break_end IS NULL)');
                $stmt->execute([$now, $employeeId, $today]);
                if ($stmt->rowCount() === 1) {
                    flash('attendance_success', 'Check-out recorded.');
                } else {
                flash('attendance_error', 'The employee must be checked in and have ended any active break before checking out.');
                }
            }
        }
    }

    redirect(base_url('admin/attendance.php'));
}

$employees = $pdo->prepare("SELECT au.id, au.full_name, au.email, au.role, a.id AS attendance_id, a.check_in, a.check_out,
  (SELECT ab.id FROM attendance_breaks ab WHERE ab.attendance_id = a.id AND ab.break_end IS NULL ORDER BY ab.break_start DESC LIMIT 1) AS active_break_id,
  (SELECT COALESCE(SUM(TIMESTAMPDIFF(SECOND, ab.break_start, COALESCE(ab.break_end, NOW()))), 0) FROM attendance_breaks ab WHERE ab.attendance_id = a.id) AS break_seconds
    FROM admin_users au
    LEFT JOIN attendance a ON a.admin_user_id = au.id AND a.attendance_date = ?
    WHERE au.status = 'active'
    ORDER BY au.full_name");
$employees->execute([$today]);
$employees = $employees->fetchAll();

$checkedInCount = 0;
$checkedOutCount = 0;
foreach ($employees as $employee) {
    if (!empty($employee['check_in']) && empty($employee['check_out'])) {
        $checkedInCount++;
    } elseif (!empty($employee['check_out'])) {
        $checkedOutCount++;
    }
}

$success = flash('attendance_success');
$error = flash('attendance_error');
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="stat-grid">
  <div class="stat-card"><div class="label">Active Employees</div><div class="value"><?= count($employees) ?></div><div class="sub">Admin accounts included</div></div>
  <div class="stat-card"><div class="label">Currently Checked In</div><div class="value"><?= $checkedInCount ?></div><div class="sub">On shift today</div></div>
  <div class="stat-card"><div class="label">Checked Out</div><div class="value"><?= $checkedOutCount ?></div><div class="sub">Shift completed today</div></div>
</div>

<div class="panel">
  <div class="panel-head">
    <h2>Today's Attendance</h2>
    <span class="badge badge-gray"><?= e(date('l, d M Y', strtotime($today))) ?></span>
  </div>
  <div style="overflow-x:auto">
    <table>
      <thead><tr><th>Employee</th><th>Role</th><th>Check-in</th><th>Break time</th><th>Check-out</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
        <?php if (!$employees): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--muted)">No active admin employees found.</td></tr>
        <?php endif; ?>
        <?php foreach ($employees as $employee): ?>
        <?php
          $hasCheckedIn = !empty($employee['check_in']);
          $hasCheckedOut = !empty($employee['check_out']);
          $onBreak = !empty($employee['active_break_id']);
          $status = !$hasCheckedIn ? 'Not checked in' : ($hasCheckedOut ? 'Completed' : ($onBreak ? 'On break' : 'On shift'));
          $statusClass = !$hasCheckedIn ? 'badge-gray' : ($hasCheckedOut ? 'badge-blue' : ($onBreak ? 'badge-yellow' : 'badge-green'));
          $breakMinutes = (int)floor((int)$employee['break_seconds'] / 60);
        ?>
        <tr>
          <td><strong><?= e($employee['full_name']) ?></strong><br><span style="color:var(--muted);font-size:12px"><?= e($employee['email']) ?></span></td>
          <td><?= e(ucwords(str_replace('_', ' ', $employee['role']))) ?></td>
          <td><?= $hasCheckedIn ? e(date('h:i A', strtotime($employee['check_in']))) : '—' ?></td>
          <td><?= $breakMinutes > 0 ? e((int)floor($breakMinutes / 60) . 'h ' . ($breakMinutes % 60) . 'm') : '—' ?></td>
          <td><?= $hasCheckedOut ? e(date('h:i A', strtotime($employee['check_out']))) : '—' ?></td>
          <td><span class="badge <?= $statusClass ?>"><?= e($status) ?></span></td>
          <td>
            <?php if (!$hasCheckedIn): ?>
            <form method="post" style="display:inline-block">
              <?= csrf_field() ?><input type="hidden" name="employee_id" value="<?= (int)$employee['id'] ?>"><input type="hidden" name="form_action" value="check_in">
              <button type="submit" class="btn btn-sm btn-check-in">Check In</button>
            </form>
            <?php elseif (!$hasCheckedOut): ?>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              <form method="post">
                <?= csrf_field() ?><input type="hidden" name="employee_id" value="<?= (int)$employee['id'] ?>"><input type="hidden" name="form_action" value="<?= $onBreak ? 'end_break' : 'start_break' ?>">
                <button type="submit" class="btn btn-sm <?= $onBreak ? 'btn-check-in' : 'btn-outline' ?>"><?= $onBreak ? 'End Break' : 'Start Break' ?></button>
              </form>
              <?php if (!$onBreak): ?>
              <form method="post">
                <?= csrf_field() ?><input type="hidden" name="employee_id" value="<?= (int)$employee['id'] ?>"><input type="hidden" name="form_action" value="check_out">
                <button type="submit" class="btn btn-sm btn-check-out">Check Out</button>
              </form>
              <?php endif; ?>
            </div>
            <?php else: ?>
            <span style="color:var(--muted);font-size:12px">Shift complete</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>