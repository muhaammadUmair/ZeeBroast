<?php
require_once __DIR__ . '/../config/config.php';
require_admin();

$admin_page_title = 'Contact Messages';
$active_admin = 'messages';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && ($_POST['form_action'] ?? '') === 'delete') {
    $pdo->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([(int)$_POST['id']]);
}

if (isset($_GET['read'])) {
    $pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([(int)$_GET['read']]);
    redirect(base_url('admin/messages.php'));
}

$messages = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>

<div class="panel">
  <div class="panel-head"><h2>Contact Messages</h2></div>
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Subject</th><th>Message</th><th>Received</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($messages)): ?><tr><td colspan="6">No messages yet.</td></tr><?php endif; ?>
      <?php foreach ($messages as $m): ?>
      <tr style="<?= $m['is_read'] ? '' : 'background:rgba(227,28,37,.05)' ?>">
        <td><?= e($m['name']) ?><br><span style="color:var(--muted);font-size:11.5px"><?= e($m['phone']) ?></span></td>
        <td><?= e($m['email']) ?></td>
        <td><?= e($m['subject'] ?: '—') ?></td>
        <td style="max-width:280px"><?= nl2br(e($m['message'])) ?></td>
        <td><?= e(date('d M, h:i A', strtotime($m['created_at']))) ?></td>
        <td style="display:flex;gap:8px">
          <?php if (!$m['is_read']): ?><a href="?read=<?= (int)$m['id'] ?>" class="btn btn-outline btn-sm">Mark Read</a><?php endif; ?>
          <form method="post" onsubmit="return confirm('Delete this message?');">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
