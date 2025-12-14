<?php
$datetime_str = '2025-12-11 17:29:14';
$tz = date_default_timezone_get();
echo "MySQL DateTime: $datetime_str\n";
echo "PHP Timezone: $tz\n";

// CORRECT: Parse as local timezone and get UNIX timestamp
$dt = new DateTimeImmutable($datetime_str, new DateTimeZone($tz));
$ts_db = $dt->getTimestamp();
$ts_now = time();
$diff = $ts_now - $ts_db;

echo "DB UNIX timestamp: $ts_db\n";
echo "Current UNIX timestamp: $ts_now\n";
echo "Diff: $diff seconds\n";

// Verify with direct PHP strtotime
$ts_strtotime = strtotime($datetime_str);
echo "strtotime result: $ts_strtotime\n";
echo "Diff (strtotime): " . ($ts_now - $ts_strtotime) . " seconds\n";

echo "Status: ";
if ($diff < 900) {
    echo "ONLINE\n";
} else {
    echo "OFFLINE\n";
}
