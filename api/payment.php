<?php
require_once 'includes/config.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['success' => false, 'message' => 'Invalid request'], 405);
}

$input  = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action !== 'create') {
    jsonOut(['success' => false, 'message' => 'Unknown action'], 400);
}

$user      = currentUser();
$baseAmt   = (float) setting('payment_amount', '798');
$token     = setting('allapi_token', '');

if (!$token) {
    jsonOut(['success' => false, 'message' => 'Payment service not configured. Please contact admin.']);
}

// Add a random decimal value (₹0.01 - ₹0.99) to the base amount.
// This makes every transaction amount unique, which helps avoid
// bank/UPI app duplicate-amount detection issues.
$randomCents = rand(1, 99) / 100;
$amount      = round($baseAmt + $randomCents, 2);

// Generate unique order ID
$orderId = 'WINGO' . date('YmdHis') . rand(100, 999);

$redirectUrl = SITE_URL . '/verify-payment.php?order_id=' . $orderId;

$payload = [
    'token'            => $token,
    'order_id'         => $orderId,
    'txn_amount'       => $amount,
    'txn_note'         => 'WinGo Analyser Pro VIP Activation',
    'product_name'     => 'WinGo Analyser Pro VIP Subscription',
    'customer_name'    => explode('@', $user['email'])[0],
    'customer_mobile'  => '9999999999',
    'customer_email'   => $user['email'],
    'redirect_url'     => $redirectUrl,
];

$ch = curl_init('https://allapi.in/order/create');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$response = curl_exec($ch);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    jsonOut(['success' => false, 'message' => 'Network error. Please try again.']);
}

$data = json_decode($response, true);

if (!$data || !($data['status'] ?? false)) {
    jsonOut(['success' => false, 'message' => $data['message'] ?? 'Payment initialization failed']);
}

$paymentUrl = $data['results']['payment_url'] ?? '';
if (!$paymentUrl) {
    jsonOut(['success' => false, 'message' => 'Could not get payment URL']);
}

// Save order to DB (store the exact randomized amount)
$s = db()->prepare("INSERT INTO orders (user_id, order_id, amount, status) VALUES (?, ?, ?, 'pending')");
$s->execute([$user['id'], $orderId, $amount]);

jsonOut([
    'success'     => true,
    'payment_url' => $paymentUrl,
    'order_id'    => $orderId,
    'amount'      => $amount,
    'upi_intent'  => $data['results']['upi_intent'] ?? [],
]);
