<?php
/**
 * V12.3 F19-FR-011/012: real PHP quiet-hour and digest scheduling assertions.
 * Synthetic time zones and preference row only. No WordPress installation,
 * external provider, user data, DB write, or Live operation.
 */
declare(strict_types=1);
define('ARRAY_A', 'ARRAY_A');
require __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/19-unified-notifications/includes/class-sun-preferences.php';

final class SUN_Test_Preference_DB {
    public string $prefix = 'wp_';
    public function prepare($query, ...$args) { return $query; }
    public function get_row($query, $format = null) { return $GLOBALS['sun_digest_preference']; }
}
$GLOBALS['wpdb'] = new SUN_Test_Preference_DB();
$GLOBALS['sun_digest_preference'] = array(
    'enabled' => 1,
    'digest_frequency' => 'weekly',
    'quiet_enabled' => 1,
    'quiet_start' => '22:00:00',
    'quiet_end' => '07:00:00',
    'timezone' => 'Asia/Karachi',
    'version' => 1,
);
$preferences = new SUN_Preferences(new SUN_Auth());
$tests = 0;
$failures = array();
function digest_assert(bool $value, string $message): void {
    global $tests, $failures;
    ++$tests;
    if (!$value) { $failures[] = $message; }
}
function local_clock(string $local, string $zone): DateTimeImmutable {
    return new DateTimeImmutable($local, new DateTimeZone($zone));
}
function digest_utc(DateTimeImmutable $base): string {
    global $preferences;
    return $preferences->digest_schedule(5, 'learning', 'email', $base)['time']->format('Y-m-d H:i:s');
}
function due_utc(DateTimeImmutable $now, bool $mandatory = false): string {
    global $preferences;
    return $preferences->next_delivery_time(5, 'learning', 'email', $mandatory, $now)->format('Y-m-d H:i:s');
}
$zone = 'Asia/Karachi';
digest_assert('2026-11-16 03:00:00' === digest_utc(local_clock('2026-11-15 22:00:00', $zone)), 'Sunday evening weekly digest should run Monday 08:00 local');
digest_assert('2026-11-16 03:00:00' === digest_utc(local_clock('2026-11-16 07:00:00', $zone)), 'Monday pre-08:00 weekly digest must run SAME Monday, not next week');
digest_assert('2026-11-16 03:00:00' === digest_utc(local_clock('2026-11-16 07:59:59', $zone)), 'Monday 07:59:59 weekly digest must run SAME Monday');
digest_assert('2026-11-23 03:00:00' === digest_utc(local_clock('2026-11-16 08:00:00', $zone)), 'Monday at 08:00 is next weekly cycle');
digest_assert('2026-11-23 03:00:00' === digest_utc(local_clock('2026-11-17 12:00:00', $zone)), 'Tuesday weekly digest schedules next Monday');
$GLOBALS['sun_digest_preference']['digest_frequency'] = 'daily';
digest_assert('2026-11-16 03:00:00' === digest_utc(local_clock('2026-11-16 07:00:00', $zone)), 'Daily digest before 08:00 schedules same day');
digest_assert('2026-11-17 03:00:00' === digest_utc(local_clock('2026-11-16 08:00:00', $zone)), 'Daily digest at 08:00 schedules next day');
$GLOBALS['sun_digest_preference']['digest_frequency'] = 'weekly';
digest_assert('2026-11-16 02:00:00' === due_utc(local_clock('2026-11-16 06:00:00', $zone)), 'Quiet hours defer nonmandatory delivery until 07:00 local');
digest_assert('2026-11-16 01:00:00' === due_utc(local_clock('2026-11-16 06:00:00', $zone), true), 'Mandatory alert bypasses quiet-hours deferral');
digest_assert('2026-11-16 05:00:00' === due_utc(local_clock('2026-11-16 10:00:00', $zone)), 'Outside quiet-hours messages are not delayed');
$GLOBALS['sun_digest_preference']['timezone'] = 'Europe/Berlin';
$GLOBALS['sun_digest_preference']['quiet_start'] = '22:00:00';
$GLOBALS['sun_digest_preference']['quiet_end'] = '07:00:00';
digest_assert('2026-03-29 05:00:00' === due_utc(local_clock('2026-03-29 01:30:00', 'Europe/Berlin')), 'DST spring forward quiet-hours end must resolve to 07:00 CEST');
digest_assert('2026-10-25 06:00:00' === due_utc(local_clock('2026-10-25 02:15:00', 'Europe/Berlin')), 'DST autumn fall back quiet-hours end must resolve to 07:00 CET');
if ($failures) {
    fwrite(STDERR, 'FAIL (' . count($failures) . '/' . $tests . "):\n - " . implode("\n - ", $failures) . "\n");
    exit(1);
}
echo 'PASS: ' . $tests . " functional digest/quiet-hours and DST PHP assertions (source only)\n";
