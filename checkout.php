<?php
$page_title = 'Checkout';
require_once __DIR__ . '/includes/header.php';

if (cart_subtotal() <= 0) {
    redirect(base_url('cart.php'));
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid session, please try again.';
    } else {
        $_SESSION['checkout'] = [
            'order_type'    => $_POST['order_type'] === 'takeaway' ? 'takeaway' : 'delivery',
            'house_no'      => trim($_POST['house_no'] ?? ''),
            'street'        => trim($_POST['street'] ?? ''),
            'city'          => trim($_POST['city'] ?? 'Lahore'),
            'instructions'  => trim($_POST['instructions'] ?? ''),
            'time_option'   => $_POST['time_option'] === 'scheduled' ? 'scheduled' : 'asap',
            'scheduled_time'=> trim($_POST['scheduled_time'] ?? ''),
            'name'          => trim($_POST['name'] ?? ''),
            'phone'         => trim($_POST['phone'] ?? ''),
            'email'         => trim($_POST['email'] ?? ''),
        ];

        if ($_SESSION['checkout']['order_type'] === 'delivery' && ($_SESSION['checkout']['house_no'] === '' || $_SESSION['checkout']['street'] === '')) {
            $error = 'Please provide your delivery address.';
        } elseif ($_SESSION['checkout']['name'] === '' || $_SESSION['checkout']['phone'] === '') {
            $error = 'Please provide your name and phone number.';
        } else {
            redirect(base_url('payment.php'));
        }
    }
}

$user = current_user();
$saved = $_SESSION['checkout'] ?? [];
?>

<div class="page-header">
  <div class="container">
    <h1>Checkout</h1>
    <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / <a href="<?= base_url('cart.php') ?>">Cart</a> / Checkout</div>
  </div>
</div>

<div class="section">
  <div class="container flow-wrap">
    <div class="flow-card">
      <div class="flow-title"><a href="<?= base_url('cart.php') ?>">←</a> Checkout</div>

      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

      <form method="post">
        <?= csrf_field() ?>

        <div class="toggle-tabs">
          <button type="button" class="tab-delivery active" onclick="setOrderType('delivery')">Delivery</button>
          <button type="button" class="tab-takeaway" onclick="setOrderType('takeaway')">Takeaway</button>
        </div>
        <input type="hidden" name="order_type" id="order_type" value="<?= e($saved['order_type'] ?? 'delivery') ?>">

        <div class="form-row">
          <div class="form-group"><label>Full Name</label><input class="form-control" name="name" value="<?= e($saved['name'] ?? ($user['full_name'] ?? '')) ?>" required></div>
          <div class="form-group"><label>Phone</label><input class="form-control" name="phone" value="<?= e($saved['phone'] ?? ($user['phone'] ?? '')) ?>" required></div>
        </div>

        <div id="addressFields">
          <div class="form-group"><label>Delivery Address — House / Flat No.</label><input class="form-control" name="house_no" value="<?= e($saved['house_no'] ?? '') ?>" placeholder="House 130, Street 4"></div>
          <div class="form-row">
            <div class="form-group"><label>Street / Area</label><input class="form-control" name="street" value="<?= e($saved['street'] ?? '') ?>" placeholder="DHA Phase 5"></div>
            <div class="form-group"><label>City</label><input class="form-control" name="city" value="<?= e($saved['city'] ?? 'Lahore') ?>"></div>
          </div>
          <div class="form-group"><label>Delivery Instructions (Optional)</label><input class="form-control" name="instructions" value="<?= e($saved['instructions'] ?? '') ?>" placeholder="E.g. Call when you arrive"></div>
        </div>

        <div class="form-group">
          <label>Delivery Time</label>
          <select class="form-control" name="time_option" id="time_option" onchange="document.getElementById('scheduledWrap').style.display=this.value==='scheduled'?'block':'none'">
            <option value="asap" <?= ($saved['time_option'] ?? 'asap') === 'asap' ? 'selected' : '' ?>>ASAP (20-30 mins)</option>
            <option value="scheduled" <?= ($saved['time_option'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Schedule Order</option>
          </select>
        </div>
        <div class="form-group" id="scheduledWrap" style="<?= ($saved['time_option'] ?? '') === 'scheduled' ? '' : 'display:none' ?>">
          <label>Pick Date &amp; Time</label>
          <input class="form-control" type="datetime-local" name="scheduled_time" value="<?= e($saved['scheduled_time'] ?? '') ?>">
        </div>

        <button type="submit" class="btn btn-primary btn-block">Continue</button>
      </form>
    </div>
  </div>
</div>

<script>
function setOrderType(type) {
  document.getElementById('order_type').value = type;
  document.querySelector('.tab-delivery').classList.toggle('active', type === 'delivery');
  document.querySelector('.tab-takeaway').classList.toggle('active', type === 'takeaway');
  document.getElementById('addressFields').style.display = type === 'delivery' ? 'block' : 'none';
}
setOrderType(document.getElementById('order_type').value);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
