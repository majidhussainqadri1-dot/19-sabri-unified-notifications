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
if ($failures) { fwrite(STDERR, "FAIL V12.3 source inventory:\n - " . implode("\n - ", $failures) . "\n"); exit(1); }
printf("PASS: 81 V12.3 evidence-ID source inventory rows; NOT staging or Live acceptance\n");
