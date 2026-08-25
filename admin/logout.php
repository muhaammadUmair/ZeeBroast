<?php
require_once __DIR__ . '/../config/config.php';
unset($_SESSION['admin_id']);
redirect(base_url('admin/login.php'));
