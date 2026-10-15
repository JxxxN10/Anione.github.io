<?php
/* 울산애니원고 급식 (NEIS). meal.php?ymd=20261016&code=1(조식)|2(중식)|3(석식)
   NEIS 원본 JSON을 그대로 돌려줌. 하루치를 3시간 캐시 */
require __DIR__ . '/_lib.php';

$KEY    = '6191b30ce3a3491189bf6001db95569b';   /* NEIS 인증키: 서버에만 두고 화면 코드에는 넣지 않음 */
$OFFICE = 'H10';       /* 울산광역시교육청 */
$SCHOOL = '7480087';   /* 울산애니원고등학교 */

$ymd  = isset($_GET['ymd']) ? preg_replace('/\D/', '', $_GET['ymd']) : date('Ymd');
$code = isset($_GET['code']) ? (int)$_GET['code'] : 0;
if (strlen($ymd) !== 8) fail('bad ymd');

$day = cached('meal_' . $ymd, 3 * 3600, function () use ($KEY, $OFFICE, $SCHOOL, $ymd) {
    $url = 'https://open.neis.go.kr/hub/mealServiceDietInfo?KEY=' . $KEY . '&Type=json&pIndex=1&pSize=10'
         . '&ATPT_OFCDC_SC_CODE=' . $OFFICE . '&SD_SCHUL_CODE=' . $SCHOOL . '&MLSV_YMD=' . $ymd;
    $raw = http_get($url);
    if ($raw === false) return false;
    $j = json_decode($raw, true);
    /* 정상(급식 있음) 또는 INFO-200(해당 날 급식 없음)만 저장. 그 외 오류는 저장하지 않음 */
    if (isset($j['mealServiceDietInfo']) || (isset($j['RESULT']['CODE']) && $j['RESULT']['CODE'] === 'INFO-200')) return $raw;
    return false;
});
if ($day === false) fail('meal unavailable');

$j = json_decode($day, true);
if ($code < 1 || !isset($j['mealServiceDietInfo'][1]['row'])) { echo $day; exit; }

/* 요청한 끼니만 남겨서 화면 코드가 row[0]을 바로 읽게 함 */
$rows = array_values(array_filter($j['mealServiceDietInfo'][1]['row'], function ($r) use ($code) {
    return (int)$r['MMEAL_SC_CODE'] === $code;
}));
if (!$rows) { echo json_encode(['RESULT' => ['CODE' => 'INFO-200', 'MESSAGE' => '해당 끼니 없음']], JSON_UNESCAPED_UNICODE); exit; }
$j['mealServiceDietInfo'][1]['row'] = $rows;
echo json_encode($j, JSON_UNESCAPED_UNICODE);
