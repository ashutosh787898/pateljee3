<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['success' => false, 'message' => 'Invalid request'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {
    case 'login':
        $email    = strtolower(trim($input['email'] ?? ''));
        $password = $input['password'] ?? '';

        if (!$email || !$password) {
            jsonOut(['success' => false, 'message' => 'Email and password required']);
        }

        $s = db()->prepare("SELECT id, password FROM users WHERE email=? LIMIT 1");
        $s->execute([$email]);
        $user = $s->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            jsonOut(['success' => false, 'message' => 'Invalid email or password']);
        }

        session_regenerate_id(true);
        $_SESSION['uid'] = $user['id'];
        jsonOut(['success' => true, 'message' => 'Login successful']);
        break;

    case 'signup':
        $email    = strtolower(trim($input['email'] ?? ''));
        $password = $input['password'] ?? '';

        if (!$email || !$password) {
            jsonOut(['success' => false, 'message' => 'Email and password required']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonOut(['success' => false, 'message' => 'Invalid email address']);
        }
        if (strlen($password) < 6) {
            jsonOut(['success' => false, 'message' => 'Password must be at least 6 characters']);
        }

        // Duplicate check
        $s = db()->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $s->execute([$email]);
        if ($s->fetch()) {
            jsonOut(['success' => false, 'message' => 'This email is already registered']);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $s = db()->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $s->execute([$email, $hash]);
        session_regenerate_id(true);
        $_SESSION['uid'] = db()->lastInsertId();

        jsonOut(['success' => true, 'message' => 'Account created successfully']);
        break;

    case 'logout':
        $_SESSION = [];
        session_destroy();
        jsonOut(['success' => true]);
        break;

    default:
        jsonOut(['success' => false, 'message' => 'Unknown action'], 400);
}
