<?php
$page_title = 'Contact Us';
$active_nav = 'contact';
require_once __DIR__ . '/includes/header.php';

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid session, please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name === '' || $email === '' || $message === '') {
            $error = 'Please fill in your name, email and message.';
        } else {
            $stmt = db()->prepare('INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $email, $phone, $subject, $message]);
            $success = 'Thank you! Your message has been sent. We will get back to you soon.';
        }
    }
}
?>

<div class="page-header">
  <div class="container">
    <h1>Contact Us</h1>
    <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / Contact</div>
  </div>
</div>

<div class="section">
  <div class="container">
    <div class="info-grid">
      <div>
        <span class="eyebrow">Get In Touch</span>
        <h2 style="font-size:26px;font-weight:800;margin-bottom:24px">We'd Love To Hear From You</h2>

        <div class="contact-item">
          <div class="ic" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 4h3l2 5-2.5 1.5a12 12 0 0 0 6 6L15 14l5 2v3a1 1 0 0 1-1 1C10.7 20 4 13.3 4 5a1 1 0 0 1 1-1Z"></path></svg></div>
          <div><h4>Call Us</h4><p><?= e(setting('phone')) ?></p></div>
        </div>
        <div class="contact-item">
          <div class="ic" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m4 7 8 6 8-6"></path></svg></div>
          <div><h4>Email Us</h4><p><?= e(setting('email')) ?></p></div>
        </div>
        <div class="contact-item">
          <div class="ic" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg></div>
          <div><h4>Visit Us</h4><p><?= e(setting('address')) ?></p></div>
        </div>
        <div class="contact-item">
          <div class="ic" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"></circle><path d="M12 7v5l3 2"></path></svg></div>
          <div><h4>Working Hours</h4><p>Everyday: 11:00 AM – 2:00 AM</p></div>
        </div>
      </div>

      <div class="flow-card">
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
          <?= csrf_field() ?>
          <div class="form-row">
            <div class="form-group"><label>Full Name</label><input class="form-control" type="text" name="name" required></div>
            <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Phone</label><input class="form-control" type="text" name="phone"></div>
            <div class="form-group"><label>Subject</label><input class="form-control" type="text" name="subject"></div>
          </div>
          <div class="form-group"><label>Message</label><textarea class="form-control" name="message" rows="5" required></textarea></div>
          <button type="submit" class="btn btn-primary btn-block">Send Message</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
