<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/jalwa-functions.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['uid'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$token = jalwaGetToken();

if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Jalwa login failed. Check Jalwa environment variables.']);
    exit;
}

$response = jalwaApiPost(
    '/deepanshu/api/webapi/GetNoaverageEmerdList',
    ['pageSize' => 10, 'pageNo' => 1, 'typeId' => 1],
    $token
);

if (empty($response['data']['list'])) {
    // Token may have expired. Re-login and retry once in the same request.
    if (jalwaLogin()) {
        $cached = jalwaCacheGet('jalwa_token');
        $token = $cached['token'] ?? null;

        if ($token) {
            $response = jalwaApiPost(
                '/deepanshu/api/webapi/GetNoaverageEmerdList',
                ['pageSize' => 10, 'pageNo' => 1, 'typeId' => 1],
                $token
            );
        }
    }
}

if (empty($response['data']['list'])) {
    $msg = $response['message'] ?? $response['msg'] ?? 'No data returned from Jalwa API';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

echo json_encode(['success' => true, 'data' => $response['data']['list']]);
