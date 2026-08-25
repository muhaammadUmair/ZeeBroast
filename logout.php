<?php
require_once __DIR__ . '/config/config.php';
unset($_SESSION['user_id']);
redirect(base_url('index.php'));
