<?php
require_once 'includes/config.php';

// Return JSON even on fatal errors
set_exception_handler(function(Throwable $e) {
    jsonOut(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
});

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? ($_GET['action'] ?? '');

// Admin login (no auth required)
if ($action === 'admin_login') {
    $pass   = $input['password'] ?? '';
    $stored = setting('admin_password', '');
    if ($stored && password_verify($pass, $stored)) {
        session_regenerate_id(true);
        $_SESSION['admin_auth'] = true;
        jsonOut(['success' => true]);
    } else {
        jsonOut(['success' => false, 'message' => 'Invalid admin password']);
    }
    exit;
}

requireAdmin();

switch ($action) {

    case 'get_stats':
        $total   = (int) db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $vip     = (int) db()->query("SELECT COUNT(*) FROM users WHERE vip_expires_at > NOW()")->fetchColumn();
        $orders  = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status='success'")->fetchColumn();
        $revenue = (float) db()->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE status='success'")->fetchColumn();
        jsonOut(['success' => true, 'total' => $total, 'vip' => $vip,
                 'orders' => $orders, 'revenue' => round($revenue, 2)]);
        break;

    case 'get_settings':
        $keys = ['payment_amount','allapi_token','telegram_link','youtube_link','whatsapp_link','yaarwin_link','vip_days'];
        $out  = [];
        foreach ($keys as $k) $out[$k] = setting($k);
        jsonOut(['success' => true, 'settings' => $out]);
        break;

    case 'save_settings':
        $allowed = ['payment_amount','allapi_token','telegram_link','youtube_link','whatsapp_link','yaarwin_link','vip_days'];
        $s = db()->prepare("INSERT INTO settings(`key`,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)");
        foreach ($allowed as $k) {
            if (isset($input[$k])) $s->execute([$k, trim($input[$k])]);
        }
        if (!empty($input['admin_password']) && strlen($input['admin_password']) >= 6) {
            $s->execute(['admin_password', password_hash($input['admin_password'], PASSWORD_DEFAULT)]);
        }
        jsonOut(['success' => true, 'message' => 'Settings saved successfully']);
        break;

    case 'search_user':
        $email = strtolower(trim($input['email'] ?? ''));
        if (!$email) jsonOut(['success' => false, 'message' => 'Email required']);
        $s = db()->prepare("SELECT id, email, vip_expires_at, created_at FROM users WHERE email LIKE ? ORDER BY id DESC LIMIT 20");
        $s->execute(['%' . $email . '%']);
        jsonOut(['success' => true, 'users' => $s->fetchAll()]);
        break;

    case 'get_users':
        $page  = max(1, (int)($input['page'] ?? 1));
        $limit = 20;
        $off   = ($page - 1) * $limit;
        $total = (int) db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
        // FIX: bindValue with PARAM_INT prevents PDO LIMIT/OFFSET binding bug
        $s = db()->prepare("SELECT id, email, vip_expires_at, created_at FROM users ORDER BY id DESC LIMIT :lim OFFSET :off");
        $s->bindValue(':lim', $limit, PDO::PARAM_INT);
        $s->bindValue(':off', $off,   PDO::PARAM_INT);
        $s->execute();
        jsonOut(['success' => true, 'users' => $s->fetchAll(), 'total' => $total, 'page' => $page]);
        break;

    case 'activate_vip':
        $email = strtolower(trim($input['email'] ?? ''));
        $days  = max(1, (int)($input['days'] ?? 17));
        if (!$email) jsonOut(['success' => false, 'message' => 'Email required']);
        $s = db()->prepare("SELECT id, vip_expires_at FROM users WHERE email=? LIMIT 1");
        $s->execute([$email]);
        $user = $s->fetch();
        if (!$user) jsonOut(['success' => false, 'message' => 'User not found with that email']);
        $base      = ($user['vip_expires_at'] && strtotime($user['vip_expires_at']) > time())
                     ? strtotime($user['vip_expires_at']) : time();
        $expiresAt = date('Y-m-d H:i:s', $base + ($days * 86400));
        $upd = db()->prepare("UPDATE users SET vip_expires_at=? WHERE email=?");
        $upd->execute([$expiresAt, $email]);
        jsonOut(['success' => true, 'message' => "VIP activated until {$expiresAt}"]);
        break;

    case 'revoke_vip':
        $email = strtolower(trim($input['email'] ?? ''));
        if (!$email) jsonOut(['success' => false, 'message' => 'Email required']);
        $upd = db()->prepare("UPDATE users SET vip_expires_at=NULL WHERE email=?");
        $upd->execute([$email]);
        jsonOut(['success' => true, 'message' => 'VIP revoked for ' . $email]);
        break;

    case 'get_orders':
        $page  = max(1, (int)($input['page'] ?? 1));
        $limit = 20;
        $off   = ($page - 1) * $limit;
        $total = (int) db()->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        // FIX: bindValue with PARAM_INT
        $s = db()->prepare(
            "SELECT o.id, o.order_id, o.amount, o.status, o.txn_id, o.created_at, u.email
             FROM orders o LEFT JOIN users u ON u.id=o.user_id
             ORDER BY o.id DESC LIMIT :lim OFFSET :off"
        );
        $s->bindValue(':lim', $limit, PDO::PARAM_INT);
        $s->bindValue(':off', $off,   PDO::PARAM_INT);
        $s->execute();
        jsonOut(['success' => true, 'orders' => $s->fetchAll(), 'total' => $total]);
        break;

    default:
        jsonOut(['success' => false, 'message' => 'Unknown action: ' . $action], 400);
}
