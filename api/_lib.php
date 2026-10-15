<?php
/* 공통: 외부 요청 + 파일 캐시. PHP 7.4 ~ 8.4 */
date_default_timezone_set('Asia/Seoul');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function http_get($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true, CURLOPT_USERAGENT => 'dorm-dashboard/1.0']);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($body !== false && $code >= 200 && $code < 300) ? $body : false;
    }
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'header' => "User-Agent: dorm-dashboard/1.0\r\n"]]);
    return @file_get_contents($url, false, $ctx);
}

/* $ttl초 동안 같은 결과를 파일로 재사용. 외부 요청이 실패하면 오래된 캐시라도 돌려줌 */
function cached($key, $ttl, $fetch) {
    $dir = __DIR__ . '/cache';
    if (!is_dir($dir)) @mkdir($dir, 0755);
    $file = $dir . '/' . preg_replace('/[^a-z0-9_\-]/i', '_', $key) . '.json';
    if (is_file($file) && time() - filemtime($file) < $ttl) return file_get_contents($file);
    $fresh = $fetch();
    if ($fresh !== false) { @file_put_contents($file, $fresh); return $fresh; }
    if (is_file($file)) return file_get_contents($file);
    return false;
}

function fail($msg) {
    http_response_code(502);
    echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}
