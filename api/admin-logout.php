<?php
require_once 'includes/config.php';
unset($_SESSION['admin_auth']);
header('Location: admin.php');
exit;
