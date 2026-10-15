<?php
/* 울산애니원고 위치 날씨 (Open-Meteo, 인증키 없음). 10분 캐시
   응답: {"temp":14,"sky":"흐림","pop":60,"hi":18,"lo":9} */
require __DIR__ . '/_lib.php';

$LAT = 35.580;   /* 울산 중구 성안11길 21 부근 (대략) */
$LON = 129.306;

$body = cached('weather', 600, function () use ($LAT, $LON) {
    $url = 'https://api.open-meteo.com/v1/forecast?latitude=' . $LAT . '&longitude=' . $LON
         . '&current=temperature_2m,weather_code'
         . '&daily=temperature_2m_max,temperature_2m_min,precipitation_probability_max'
         . '&timezone=Asia%2FSeoul&forecast_days=1';
    $raw = http_get($url);
    if ($raw === false) return false;
    $j = json_decode($raw, true);
    if (!isset($j['current']['temperature_2m'])) return false;
    $c = (int)$j['current']['weather_code'];
    return json_encode([
        'temp' => round($j['current']['temperature_2m']),
        'sky'  => sky_ko($c),
        'code' => $c,
        'pop'  => isset($j['daily']['precipitation_probability_max'][0]) ? (int)$j['daily']['precipitation_probability_max'][0] : 0,
        'hi'   => isset($j['daily']['temperature_2m_max'][0]) ? round($j['daily']['temperature_2m_max'][0]) : null,
        'lo'   => isset($j['daily']['temperature_2m_min'][0]) ? round($j['daily']['temperature_2m_min'][0]) : null,
    ], JSON_UNESCAPED_UNICODE);
});

/* WMO 날씨 코드 → 한국어 */
function sky_ko($c) {
    if ($c === 0) return '맑음';
    if ($c === 1) return '대체로 맑음';
    if ($c === 2) return '구름 조금';
    if ($c === 3) return '흐림';
    if ($c === 45 || $c === 48) return '안개';
    if ($c >= 51 && $c <= 57) return '이슬비';
    if ($c === 66 || $c === 67) return '어는 비';
    if ($c >= 61 && $c <= 65) return '비';
    if ($c >= 71 && $c <= 77) return '눈';
    if ($c >= 80 && $c <= 82) return '소나기';
    if ($c === 85 || $c === 86) return '소낙눈';
    if ($c >= 95) return '뇌우';
    return '흐림';
}

if ($body === false) fail('weather unavailable');
echo $body;
