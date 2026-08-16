<?php
require_once __DIR__ . '/../includes/jalwa-config.php';
require_once __DIR__ . '/../includes/config.php';

function jalwaGenerateRandom(): string
{
    return sprintf(
        '%04x%04x-%04x-4%03x-%04x-%04x%04x%04x',
        mt_rand(0, 65535), mt_rand(0, 65535),
        mt_rand(0, 65535), mt_rand(0, 4095),
        mt_rand(16384, 20479),
        mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)
    );
}

function jalwaCreateSignature(array $data): string
{
    unset($data['signature'], $data['track'], $data['xosoBettingData']);

    foreach ($data as $k => $v) {
        if ($v === '' || $v === null) unset($data[$k]);
    }

    ksort($data);
    return strtoupper(md5(json_encode($data, JSON_UNESCAPED_SLASHES)));
}

function jalwaCookieFile(): string
{
    static $file = null;

    if ($file === null) {
        $file = tempnam(sys_get_temp_dir(), 'jalwa_');
    }

    return $file;
}

function jalwaApiPost(string $endpoint, array $data, ?string $token = null): ?array
{
    if (!JALWA_BASE_URL || !JALWA_USERNAME || !JALWA_PASSWORD) {
        return null;
    }

    $data['language']  = JALWA_LANGUAGE;
    $data['random']    = jalwaGenerateRandom();
    $data['signature'] = jalwaCreateSignature($data);
    $data['timestamp'] = time();

    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $cookieFile = jalwaCookieFile();

    $ch = curl_init(JALWA_BASE_URL . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $res = curl_exec($ch);
    curl_close($ch);

    if (!is_string($res) || $res === '') {
        return null;
    }

    $start = strpos($res, '{');
    if ($start !== false) {
        $res = substr($res, $start);
    }

    $decoded = json_decode($res, true);
    return is_array($decoded) ? $decoded : null;
}

function jalwaCacheGet(string $key): ?array
{
    try {
        $stmt = db()->prepare(
            'SELECT cache_value, expires_at FROM app_cache WHERE cache_key=? LIMIT 1'
        );
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        if (!$row) return null;
        if ($row['expires_at'] !== null && strtotime($row['expires_at']) <= time()) return null;

        $data = json_decode($row['cache_value'], true);
        return is_array($data) ? $data : null;
    } catch (Throwable $e) {
        return null;
    }
}

function jalwaCachePut(string $key, array $data, int $ttl): void
{
    $expires = date('Y-m-d H:i:s', time() + $ttl);

    $stmt = db()->prepare(
        'INSERT INTO app_cache (cache_key, cache_value, expires_at)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE cache_value=VALUES(cache_value), expires_at=VALUES(expires_at)'
    );
    $stmt->execute([$key, json_encode($data), $expires]);
}

function jalwaLogin(): bool
{
    $response = jalwaApiPost('/deepanshu/api/webapi/Login', [
        'username' => JALWA_USERNAME,
        'pwd' => JALWA_PASSWORD,
        'phonetype' => 0,
        'logintype' => 'mobile',
    ]);

    if (!empty($response['data']['token'])) {
        jalwaCachePut('jalwa_token', [
            'token' => $response['data']['token'],
            'saved_at' => time(),
        ], 72000);
        return true;
    }

    return false;
}

function jalwaGetToken(): ?string
{
    $data = jalwaCacheGet('jalwa_token');

    if (!empty($data['token']) && !empty($data['saved_at'])) {
        if ((time() - (int)$data['saved_at']) < 72000) {
            return (string)$data['token'];
        }
    }

    return jalwaLogin() ? (($d = jalwaCacheGet('jalwa_token'))['token'] ?? null) : null;
}
