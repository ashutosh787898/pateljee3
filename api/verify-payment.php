<?php
require_once __DIR__ . '/../includes/config.php';

$orderId = trim($_GET['order_id'] ?? '');

if (!$orderId) {
    header('Location: dashboard.php?msg=invalid_order');
    exit;
}

// Find order in DB
$s = db()->prepare("SELECT * FROM orders WHERE order_id=? LIMIT 1");
$s->execute([$orderId]);
$order = $s->fetch();

if (!$order) {
    header('Location: dashboard.php?msg=order_not_found');
    exit;
}

if ($order['status'] === 'success') {
    // Already processed
    header('Location: dashboard.php?msg=already_activated');
    exit;
}

// Poll AllAPI for status
$token = setting('allapi_token', '');

$payload = ['token' => $token, 'order_id' => $orderId];

$ch = curl_init('https://allapi.in/order/status');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

$txnStatus = $data['results']['status'] ?? 'Unknown';
$txnId     = $data['results']['txn_id'] ?? null;

if (($data['status'] ?? false) && $txnStatus === 'Success') {
    // Activate VIP
    $vipDays    = (int) setting('vip_days', '17');
    $expiresAt  = date('Y-m-d H:i:s', strtotime("+{$vipDays} days"));

    $upd = db()->prepare("UPDATE users SET vip_expires_at=? WHERE id=?");
    $upd->execute([$expiresAt, $order['user_id']]);

    $upd2 = db()->prepare("UPDATE orders SET status='success', txn_id=? WHERE order_id=?");
    $upd2->execute([$txnId, $orderId]);

    // Log in user if not already
    if (!isLoggedIn()) {
        $_SESSION['uid'] = $order['user_id'];
    }

    header('Location: dashboard.php?msg=vip_activated');
    exit;

} elseif ($txnStatus === 'Pending') {
    // Mark as still pending, show waiting message
    header('Location: dashboard.php?msg=payment_pending');
    exit;
} else {
    // Mark failed
    $upd = db()->prepare("UPDATE orders SET status='failed' WHERE order_id=?");
    $upd->execute([$orderId]);

    header('Location: dashboard.php?msg=payment_failed');
    exit;
}
