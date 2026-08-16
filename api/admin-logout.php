<?php
require_once __DIR__ . '/../includes/config.php';
unset($_SESSION['admin_auth']);
header('Location: admin.php');
exit;
