<?php

/*
|--------------------------------------------------------------------------
| Session Configuration
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_name('wingo_sess');
}


/*
|--------------------------------------------------------------------------
| Database Configuration
|--------------------------------------------------------------------------
| These values come from Vercel Environment Variables.
|
| DB_HOST
| DB_PORT
| DB_NAME
| DB_USER
| DB_PASS
|--------------------------------------------------------------------------
*/

define('DB_HOST', getenv('DB_HOST') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: '');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');


/*
|--------------------------------------------------------------------------
| Site Configuration
|--------------------------------------------------------------------------
*/




/*
|--------------------------------------------------------------------------
| Database Connection
|--------------------------------------------------------------------------
*/

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {

        if (
            DB_HOST === '' ||
            DB_NAME === '' ||
            DB_USER === '' ||
            DB_PASS === ''
        ) {
            throw new RuntimeException(
                'Database environment variables are missing.'
            );
        }

        $dsn = 'mysql:host=' . DB_HOST .
               ';port=' . DB_PORT .
               ';dbname=' . DB_NAME .
               ';charset=utf8mb4';

        $pdo = new PDO(
            $dsn,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,

                // Keep connection alive for the current request only
                PDO::ATTR_PERSISTENT => false
            ]
        );
    }

    return $pdo;
}


/*
|--------------------------------------------------------------------------
| MySQL Session Handler
|--------------------------------------------------------------------------
| Vercel is serverless, so don't depend on local session files.
| Sessions are stored in the MySQL `sessions` table.
|--------------------------------------------------------------------------
*/

class MySQLSessionHandler implements SessionHandlerInterface
{
    private ?PDO $pdo = null;

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        try {

            $this->pdo = db();

            $stmt = $this->pdo->prepare(
                "SELECT data
                 FROM sessions
                 WHERE id = ?
                 AND last_activity > ?
                 LIMIT 1"
            );

            $stmt->execute([
                $id,
                time() - 86400
            ]);

            $data = $stmt->fetchColumn();

            return $data !== false ? $data : '';

        } catch (Throwable $e) {
            error_log('Session read error: ' . $e->getMessage());
            return '';
        }
    }

    public function write(string $id, string $data): bool
    {
        try {

            $this->pdo = db();

            $stmt = $this->pdo->prepare(
                "INSERT INTO sessions
                    (id, data, last_activity)
                 VALUES
                    (?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    data = VALUES(data),
                    last_activity = VALUES(last_activity)"
            );

            return $stmt->execute([
                $id,
                $data,
                time()
            ]);

        } catch (Throwable $e) {
            error_log('Session write error: ' . $e->getMessage());
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {

            $this->pdo = db();

            $stmt = $this->pdo->prepare(
                "DELETE FROM sessions WHERE id = ?"
            );

            return $stmt->execute([$id]);

        } catch (Throwable $e) {
            error_log('Session destroy error: ' . $e->getMessage());
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false
    {
        try {

            $this->pdo = db();

            $stmt = $this->pdo->prepare(
                "DELETE FROM sessions
                 WHERE last_activity < ?"
            );

            $stmt->execute([
                time() - $max_lifetime
            ]);

            return $stmt->rowCount();

        } catch (Throwable $e) {
            error_log('Session GC error: ' . $e->getMessage());
            return false;
        }
    }
}


/*
|--------------------------------------------------------------------------
| Start Database-backed Session
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    $sessionHandler = new MySQLSessionHandler();

    session_set_save_handler(
        $sessionHandler,
        true
    );

    session_start();
}


/*
|--------------------------------------------------------------------------
| Website Settings
|--------------------------------------------------------------------------
*/

function setting(string $key, string $default = ''): string
{
    try {

        $stmt = db()->prepare(
            "SELECT value
             FROM settings
             WHERE `key` = ?
             LIMIT 1"
        );

        $stmt->execute([$key]);

        $value = $stmt->fetchColumn();

        return $value !== false
            ? (string) $value
            : $default;

    } catch (Throwable $e) {

        return $default;
    }
}


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

function isLoggedIn(): bool
{
    return !empty($_SESSION['uid']);
}


function requireAuth(): void
{
    if (!isLoggedIn()) {

        header('Location: index.php');
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Current User
|--------------------------------------------------------------------------
*/

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    try {

        $stmt = db()->prepare(
            "SELECT *
             FROM users
             WHERE id = ?
             LIMIT 1"
        );

        $stmt->execute([
            $_SESSION['uid']
        ]);

        return $stmt->fetch() ?: null;

    } catch (Throwable $e) {

        return null;
    }
}


/*
|--------------------------------------------------------------------------
| VIP Check
|--------------------------------------------------------------------------
*/

function isVIP(array $u): bool
{
    return !empty($u['vip_expires_at'])
        && strtotime($u['vip_expires_at']) > time();
}


/*
|--------------------------------------------------------------------------
| JSON Response
|--------------------------------------------------------------------------
*/

function jsonOut(array $data, int $code = 200): void
{
    http_response_code($code);

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Admin Authentication
|--------------------------------------------------------------------------
*/

function isAdminLoggedIn(): bool
{
    return !empty($_SESSION['admin_auth']);
}


function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {

        header('Location: admin.php');
        exit;
    }
}
