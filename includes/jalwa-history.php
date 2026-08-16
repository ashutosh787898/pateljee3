<?php
// Ensure session is active (same session as main site)
if (session_status() === PHP_SESSION_NONE) {
    session_name('wingo_sess');
    session_start();
}

header('Content-Type: application/json');

// Auth check — any logged-in user can view history (VIP not required)
if (empty($_SESSION['uid'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

require_once __DIR__ . '/../includes/jalwa-functions.php';

// Get token (auto-logins if missing/expired)
$token = jalwaGetToken();

if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Jalwa login failed. Check credentials in jalwa-config.php']);
    exit;
}

// Fetch history
$response = jalwaApiPost(
    '/deepanshu/api/webapi/GetNoaverageEmerdList',
    ['pageSize' => 10, 'pageNo' => 1, 'typeId' => 1],
    $token
);

// If token expired mid-session → retry once with fresh login
if (empty($response['data']['list'])) {
    jalwaLogin();
    $td = json_decode(file_get_contents(JALWA_TOKEN_FILE), true);
    $token = $td['token'] ?? null;
    if ($token) {
        $response = jalwaApiPost(
            '/deepanshu/api/webapi/GetNoaverageEmerdList',
            ['pageSize' => 10, 'pageNo' => 1, 'typeId' => 1],
            $token
        );
    }
}

if (empty($response['data']['list'])) {
    // Return the raw error from the API to help debug
    $msg = $response['message'] ?? $response['msg'] ?? 'No data returned from Jalwa API';
    echo json_encode(['success' => false, 'message' => $msg, 'raw' => $response]);
    exit;
}

echo json_encode(['success' => true, 'data' => $response['data']['list']]);
