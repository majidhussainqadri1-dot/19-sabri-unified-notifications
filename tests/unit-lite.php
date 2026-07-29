<?php
declare(strict_types=1);
define('ABSPATH', __DIR__ . '/');
define('AUTH_KEY', 'test-auth-key');
define('SECURE_AUTH_KEY', 'test-secure-auth-key');
function wp_salt(string $scheme = 'auth'): string { return 'test-salt-' . $scheme; }
function wp_json_encode(mixed $value, int $flags = 0): string|false { return json_encode($value, $flags); }
function wp_strip_all_tags(string $value): string { return strip_tags($value); }
function sanitize_key(string $value): string { return strtolower(preg_replace('/[^a-z0-9_\-]/', '', $value) ?? ''); }
function sanitize_text_field(string $value): string { return trim(strip_tags($value)); }
function sanitize_textarea_field(string $value): string { return trim(strip_tags($value)); }
function esc_url_raw(string $value, array $protocols = []): string { return filter_var($value, FILTER_VALIDATE_URL) ? $value : ''; }
function wp_parse_url(string $value, int $component = -1): string|array|int|null|false { return parse_url($value, $component); }
function home_url(string $path = ''): string { return 'https://example.com' . $path; }
function apply_filters(string $hook, mixed $value, mixed ...$args): mixed { return $value; }

require __DIR__ . '/../sabri-unified-notifications/includes/class-sun-utils.php';

$secret = 'Authorization: Bearer test-secret';
$encrypted = SUN_Utils::encrypt_secret($secret);
if ($encrypted === '' || $encrypted === $secret || !SUN_Utils::is_encrypted($encrypted)) throw new RuntimeException('Encryption failed.');
if (SUN_Utils::decrypt_secret($encrypted) !== $secret) throw new RuntimeException('Decryption failed.');

$private = SUN_Utils::external_preview(['sensitivity'=>'clinical','title'=>'Patient A prescription','body'=>'Private dosage']);
if ($private['title'] !== 'Private clinical update' || str_contains($private['body'], 'dosage')) throw new RuntimeException('Clinical preview leaked details.');

$public = SUN_Utils::external_preview(['sensitivity'=>'public','title'=>'Public update','body'=>'Call +92 300 1234567']);
if (!str_contains($public['body'], '[number protected]')) throw new RuntimeException('Public preview masking failed.');

if (SUN_Utils::sanitize_link('https://evil.example/path') !== '') throw new RuntimeException('External link policy failed.');
if (SUN_Utils::sanitize_link('https://example.com/path') === '') throw new RuntimeException('Same-site link policy failed.');

echo "Unit-lite checks passed.\n";
