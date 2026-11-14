<?php
/**
 * V12.3 source acceptance *inventory* test. NOT functional, staging, or Live acceptance.
 * Keeping 'PENDING' explicit prevents green source CI from silently promoting 81 rows to PASS.
 */
declare(strict_types=1);
$path = dirname(__DIR__) . '/docs/FILE19-V12.3-ACCEPTANCE-MATRIX.md';
$raw = file_get_contents($path);
if (false === $raw) { fwrite(STDERR, "Missing V12.3 evidence matrix\n"); exit(1); }
preg_match_all('/^\\|\\s*(F19-(?:FR|NFR|CEN|AF)-[0-9]{2,3})\\s*\\|\\s*([^|]*)\\|\\s*([^|]*)\\|/m', $raw, $matches, PREG_SET_ORDER);
$observed = [];
$failures = [];
foreach ($matches as $line) {
    $id = trim($line[1]);
    $description = trim($line[2]);
    $status = trim($line[3]);
    if (array_key_exists($id, $observed)) { $failures[] = "Duplicate {$id}"; }
    if ('' === $description) { $failures[] = "Empty description {$id}"; }
    if ('PENDING-LIVE-AND-STAGING' !== $status && 'BLOCKED-WITH-OWNER' !== $status && 'TESTED-STAGING' !== $status && 'VERIFIED-LIVE' !== $status && 'PROVEN-DISABLED' !== $status) {
        $failures[] = "Invalid status {$id}: {$status}";
    }
    // Only explicit host/runtime evidence may promote a row. Branch source alone cannot.
    if ('VERIFIED-LIVE' === $status && !str_contains($raw, "Verified Live Evidence: {$id}")) {
        $failures[] = "Unsubstantiated Live completion for {$id}";
    }
    $observed[$id] = $status;
}
$expected = [];
foreach (['FR' => 21, 'NFR' => 10, 'CEN' => 2, 'AF' => 48] as $family => $number) {
    for ($i = 1; $i <= $number; ++$i) {
        $expected[] = 'F19-' . $family . '-' . str_pad((string) $i, $family === 'CEN' ? 2 : 3, '0', STR_PAD_LEFT);
    }
}
foreach (array_diff($expected, array_keys($observed)) as $id) { $failures[] = "Missing {$id}"; }
foreach (array_diff(array_keys($observed), $expected) as $id) { $failures[] = "Unknown {$id}"; }
if (81 !== count($observed)) { $failures[] = 'Expected exactly 81 unique evidence rows'; }
if (!str_contains($raw, 'CEN-REL-001') || !str_contains($raw, '#087A4E') || !str_contains($raw, 'S0 Live Reality Freeze')) {
    $failures[] = 'Missing V12.3 cross-cutting governance and S0 references';
}

// The test bootstrap is a synthetic WordPress runtime. It must not silently
// impersonate an older File 19 build while a newer plugin is being tested.
// Source-only guard: this is NOT a deployed-version parity assertion.
$plugin_source = file_get_contents(dirname(__DIR__) . '/19-unified-notifications/19-unified-notifications.php');
$test_bootstrap = file_get_contents(__DIR__ . '/bootstrap.php');
$release_sha_record = file_get_contents(dirname(__DIR__) . '/RELEASE-SHA256.txt');
if (false === $plugin_source || false === $test_bootstrap || false === $release_sha_record) {
    $failures[] = 'Missing runtime, bootstrap or release provenance required for source version parity';
} else {
    $found_header = preg_match('/^\s*\*\s*Version:\s*(\d+\.\d+\.\d+)/m', $plugin_source, $header_match);
    $found_runtime = preg_match("/define\\(\\s*'SUN_VERSION'\\s*,\\s*'([0-9.]+)'\\s*\\)/", $plugin_source, $runtime_match);
    $found_bootstrap = preg_match("/define\\(\\s*'SUN_VERSION'\\s*,\\s*'([0-9.]+)'\\s*\\)/", $test_bootstrap, $bootstrap_match);
    $found_release = preg_match('/^[a-f0-9]{64}\s+19-sabri-unified-notifications-(\d+\.\d+\.\d+)\.zip\s*$/m', $release_sha_record, $release_match);
    if (!$found_header || !$found_runtime || !$found_bootstrap || !$found_release) {
        $failures[] = 'Unable to determine all source version records';
    } elseif (
        $header_match[1] !== $runtime_match[1] ||
        $header_match[1] !== $bootstrap_match[1] ||
        $header_match[1] !== $release_match[1]
    ) {
        $failures[] = sprintf(
            'File 19 source version drift: header=%s runtime=%s bootstrap=%s release=%s',
            $header_match[1], $runtime_match[1], $bootstrap_match[1], $release_match[1]
        );
    }
}

if ($failures) { fwrite(STDERR, "FAIL V12.3 source inventory:\n - " . implode("\n - ", $failures) . "\n"); exit(1); }
printf("PASS: 81 V12.3 evidence-ID source inventory rows; NOT staging or Live acceptance\n");
