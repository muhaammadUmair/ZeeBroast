<?php
/**
 * Shared admin layout header. Expects $admin_page_title and $active_admin to be set,
 * and require_admin() to already have been called by the including page.
 */
$admin = current_admin();
$active_admin = $active_admin ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($admin_page_title ?? 'Admin') ?> — ZeeBroast Admin</title>
<link rel="stylesheet" href="<?= base_url('admin/assets/css/admin.css') ?>">
</head>
<body>

<aside class="admin-sidebar">
  <div class="brand"><span class="mark">Z</span> ZeeBroast Admin</div>
  <nav>
    <div class="group-label">Overview</div>
    <a href="<?= base_url('admin/index.php') ?>" class="<?= $active_admin === 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a>

    <div class="group-label">Catalog</div>
    <a href="<?= base_url('admin/categories.php') ?>" class="<?= $active_admin === 'categories' ? 'active' : '' ?>">🗂️ Categories</a>
    <a href="<?= base_url('admin/products.php') ?>" class="<?= $active_admin === 'products' ? 'active' : '' ?>">🍗 Products</a>
    <a href="<?= base_url('admin/deals.php') ?>" class="<?= $active_admin === 'deals' ? 'active' : '' ?>">🎉 Deals &amp; Combos</a>
    <a href="<?= base_url('admin/coupons.php') ?>" class="<?= $active_admin === 'coupons' ? 'active' : '' ?>">🏷️ Coupons</a>

    <div class="group-label">Sales</div>
    <a href="<?= base_url('admin/orders.php') ?>" class="<?= $active_admin === 'orders' ? 'active' : '' ?>">🧾 Orders</a>
    <a href="<?= base_url('admin/customers.php') ?>" class="<?= $active_admin === 'customers' ? 'active' : '' ?>">👥 Customers</a>
    <a href="<?= base_url('admin/messages.php') ?>" class="<?= $active_admin === 'messages' ? 'active' : '' ?>">✉️ Messages</a>

    <div class="group-label">System</div>
    <a href="<?= base_url('admin/settings.php') ?>" class="<?= $active_admin === 'settings' ? 'active' : '' ?>">⚙️ Site Settings</a>
    <a href="<?= base_url('admin/admin-users.php') ?>" class="<?= $active_admin === 'admin-users' ? 'active' : '' ?>">🛡️ Admin Users</a>
    <a href="<?= base_url('index.php') ?>" target="_blank">🌐 View Website</a>
    <a href="<?= base_url('admin/logout.php') ?>">🚪 Logout</a>
  </nav>
</aside>

<div class="admin-main">
  <div class="admin-topbar">
    <h1><?= e($admin_page_title ?? 'Dashboard') ?></h1>
    <div class="admin-user">👤 <?= e($admin['full_name'] ?? '') ?> <span class="badge badge-gray"><?= e($admin['role'] ?? '') ?></span></div>
  </div>
  <div class="admin-content">
