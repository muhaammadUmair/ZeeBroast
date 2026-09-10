<?php
$page_title = 'Checkout';
require_once __DIR__ . '/includes/header.php';

if (cart_subtotal() <= 0) {
    redirect(base_url('cart.php'));
}

$error = null;

$user = current_user();
$existingAddresses = [];
if ($user) {
    $addressStmt = db()->prepare('SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC');
    $addressStmt->execute([$user['id']]);
    $existingAddresses = $addressStmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid session, please try again.';
    } else {
        $checkoutData = [
            'order_type'    => $_POST['order_type'] === 'takeaway' ? 'takeaway' : 'delivery',
            'house_no'      => trim($_POST['house_no'] ?? ''),
            'street'        => trim($_POST['street'] ?? ''),
            'city'          => trim($_POST['city'] ?? 'Lahore'),
            'instructions'  => trim($_POST['instructions'] ?? ''),
            'time_option'   => $_POST['time_option'] === 'scheduled' ? 'scheduled' : 'asap',
            'scheduled_time'=> trim($_POST['scheduled_time'] ?? ''),
            'name'          => trim($_POST['name'] ?? ''),
            'phone'         => normalize_phone_number($_POST['phone'] ?? ''),
            'email'         => trim($_POST['email'] ?? ''),
            'coupon_code'   => trim(strtoupper((string)($_POST['coupon_code'] ?? ''))),
            'address_id'    => null,
        ];

        if ($checkoutData['order_type'] === 'delivery') {
            $selectedAddressId = isset($_POST['address_id']) ? (int)$_POST['address_id'] : 0;
            if ($user && $selectedAddressId > 0) {
                $selectedAddressStmt = db()->prepare('SELECT * FROM addresses WHERE id = ? AND user_id = ?');
                $selectedAddressStmt->execute([$selectedAddressId, $user['id']]);
                $selectedAddress = $selectedAddressStmt->fetch();
                if ($selectedAddress) {
                    $checkoutData['address_id'] = $selectedAddress['id'];
                    $checkoutData['house_no'] = $selectedAddress['house_no'];
                    $checkoutData['street'] = $selectedAddress['street'];
                    $checkoutData['city'] = $selectedAddress['city'];
                    $checkoutData['instructions'] = $selectedAddress['instructions'] ?? '';
                }
            }

            if ($user && empty($checkoutData['address_id']) && $checkoutData['house_no'] !== '' && $checkoutData['street'] !== '') {
                $addressLabel = trim((string)($_POST['address_label'] ?? ''));
                if ($addressLabel === '') {
                    $addressLabel = 'Address ' . (count($existingAddresses) + 1);
                }

                $insertAddress = db()->prepare('INSERT INTO addresses (user_id, label, house_no, street, city, instructions, is_default) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $insertAddress->execute([
                    $user['id'],
                    $addressLabel,
                    $checkoutData['house_no'],
                    $checkoutData['street'],
                    $checkoutData['city'],
                    $checkoutData['instructions'],
                    0,
                ]);
                $checkoutData['address_id'] = (int)db()->lastInsertId();
            }
        }

        $_SESSION['checkout'] = $checkoutData;

        if (!empty($_SESSION['checkout']['coupon_code'])) {
            $coupon = resolve_coupon_code($_SESSION['checkout']['coupon_code'], $user['id'] ?? null);
            if (!$coupon) {
                $error = 'This discount code is invalid, expired, or already used.';
            } else {
                $_SESSION['coupon_code'] = $_SESSION['checkout']['coupon_code'];
            }
        } else {
            unset($_SESSION['coupon_code']);
            // Customer explicitly cleared the field — don't keep re-applying the QR referral code.
            unset($_SESSION['pending_referral_code']);
        }

        if ($_SESSION['checkout']['order_type'] === 'delivery' && ($_SESSION['checkout']['house_no'] === '' || $_SESSION['checkout']['street'] === '')) {
            $error = 'Please provide your delivery address.';
        } elseif ($_SESSION['checkout']['name'] === '' || $_SESSION['checkout']['phone'] === '') {
            $error = 'Please provide your name and phone number.';
        } elseif ($error === null) {
            redirect(base_url('payment.php'));
        }
    }
}

$saved = $_SESSION['checkout'] ?? [];
// Pre-fill from a QR/share-link referral code if the customer hasn't already typed one of their own.
$prefilledCouponCode = !empty($saved['coupon_code']) ? $saved['coupon_code'] : ($_SESSION['pending_referral_code'] ?? '');
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
          <!-- <button type="button" class="tab-takeaway" onclick="setOrderType('takeaway')">Takeaway</button> -->
        </div>
        <input type="hidden" name="order_type" id="order_type" value="<?= e($saved['order_type'] ?? 'delivery') ?>">

        <div class="form-row">
          <div class="form-group"><label>Full Name</label><input class="form-control" name="name" value="<?= e($saved['name'] ?? ($user['full_name'] ?? '')) ?>" required></div>
          <div class="form-group"><label>Phone</label><input class="form-control" name="phone" value="<?= e($saved['phone'] ?? ($user['phone'] ?? '')) ?>" required></div>
        </div>

        <div class="form-group">
          <label>Discount / Referral Code (Optional)</label>
          <input class="form-control" type="text" name="coupon_code" value="<?= e($prefilledCouponCode) ?>" placeholder="Enter 10% welcome code" style="text-transform:uppercase">
          <?php if ($prefilledCouponCode !== '' && empty($saved['coupon_code'])): ?>
            <p class="muted" style="font-size:12px;margin-top:4px">✓ Applied automatically from your referral link. You can change or clear it.</p>
          <?php endif; ?>
        </div>

        <?php if ($user && !empty($existingAddresses)): ?>
        <div class="form-group">
          <label>Saved Addresses</label>
          <select class="form-control" name="address_id" id="addressIdSelect">
            <option value="" <?= empty($saved['address_id'] ?? null) ? 'selected' : '' ?>>Add new address</option>
            <?php foreach ($existingAddresses as $address): ?>
              <option
                value="<?= (int)$address['id'] ?>"
                data-label="<?= e($address['label'] ?? '') ?>"
                data-house-no="<?= e($address['house_no'] ?? '') ?>"
                data-street="<?= e($address['street'] ?? '') ?>"
                data-city="<?= e($address['city'] ?? 'Lahore') ?>"
                data-instructions="<?= e($address['instructions'] ?? '') ?>"
                <?= ((string)($saved['address_id'] ?? '') === (string)$address['id']) ? 'selected' : '' ?>>
                <?= e($address['label'] ?: 'Saved Address') ?> — <?= e($address['house_no']) ?>, <?= e($address['street']) ?>, <?= e($address['city']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <div id="addressFields">
          <?php if ($user && !empty($existingAddresses)): ?>
          <div class="form-group"><label>Address Label (Optional)</label><input class="form-control" name="address_label" value="<?= e($saved['address_label'] ?? '') ?>" placeholder="Home, Office, etc."></div>
          <?php endif; ?>
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

function fillSelectedAddress() {
  const select = document.getElementById('addressIdSelect');
  if (!select) return;

  const selectedOption = select.options[select.selectedIndex];
  if (!selectedOption || !selectedOption.value) {
    return;
  }

  const addressLabelField = document.querySelector('input[name="address_label"]');
  if (addressLabelField) {
    addressLabelField.value = selectedOption.dataset.label || '';
  }

  document.querySelector('input[name="house_no"]').value = selectedOption.dataset.houseNo || '';
  document.querySelector('input[name="street"]').value = selectedOption.dataset.street || '';
  document.querySelector('input[name="city"]').value = selectedOption.dataset.city || 'Lahore';
  document.querySelector('input[name="instructions"]').value = selectedOption.dataset.instructions || '';
}

const addressSelect = document.getElementById('addressIdSelect');
if (addressSelect) {
  addressSelect.addEventListener('change', fillSelectedAddress);
  fillSelectedAddress();
}

setOrderType(document.getElementById('order_type').value);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
