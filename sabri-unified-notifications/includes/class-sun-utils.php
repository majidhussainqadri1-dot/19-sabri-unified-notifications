<?php
defined('ABSPATH') || exit;

final class SUN_Utils {
    private const ENCRYPTED_PREFIX = 'sunenc:v1:';

    public static function now(): string { return current_time('mysql', true); }

    public static function json_encode(array $value): string {
        $encoded = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($encoded) ? $encoded : '{}';
    }

    public static function json_decode(mixed $value, array $default = []): array {
        if (is_array($value)) return $value;
        if (!is_string($value) || trim($value) === '') return $default;
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : $default;
    }

    public static function client_ip(): string {
        return substr(sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? '')), 0, 64);
    }

    public static function audit(string $action, string $object_type = '', int $object_id = 0, array $details = []): void {
        global $wpdb;
        if (!SUN_DB::table_exists('audit_log')) return;
        $wpdb->insert(SUN_DB::table('audit_log'), [
            'actor_user_id'=>get_current_user_id(), 'action'=>sanitize_key($action), 'object_type'=>sanitize_key($object_type),
            'object_id'=>$object_id, 'details'=>self::json_encode(self::redact_array($details)), 'ip_address'=>self::client_ip(), 'created_at'=>self::now(),
        ], ['%d','%s','%s','%d','%s','%s','%s']);
    }

    public static function page_url(): string {
        $page_id = (int) get_option('sun_page_id', 0);
        if ($page_id && get_post_status($page_id) === 'publish') {
            $url = get_permalink($page_id);
            if ($url) return (string) $url;
        }
        return add_query_arg('sun_notifications_app', '1', home_url('/'));
    }

    public static function allowed_categories(): array {
        return ['messages'=>'Messages','marketplace'=>'Marketplace','appointments'=>'Appointments','social'=>'Social','security'=>'Security','administration'=>'Administration','system'=>'System'];
    }
    public static function allowed_priorities(): array { return ['low','normal','high','critical']; }
    public static function allowed_sensitivities(): array { return ['public','private','clinical','identity','security']; }
    public static function normalize_category(string $category): string { $category=sanitize_key($category); return array_key_exists($category,self::allowed_categories())?$category:'system'; }
    public static function normalize_priority(string $priority): string { $priority=sanitize_key($priority); return in_array($priority,self::allowed_priorities(),true)?$priority:'normal'; }
    public static function normalize_sensitivity(string $sensitivity): string { $sensitivity=sanitize_key($sensitivity); return in_array($sensitivity,self::allowed_sensitivities(),true)?$sensitivity:'private'; }

    public static function sanitize_link(string $link): string {
        if ($link === '') return '';
        $link = esc_url_raw($link, ['http','https']);
        if (!$link) return '';
        $home_host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        $host = strtolower((string) wp_parse_url($link, PHP_URL_HOST));
        return ($host === '' || $host === $home_host || apply_filters('sun_allow_external_notification_link', false, $link)) ? $link : '';
    }

    public static function notification_icon(string $category, string $type = ''): string {
        $icons=['messages'=>'💬','marketplace'=>'🛍️','appointments'=>'📅','social'=>'👥','security'=>'🔐','administration'=>'🛡️','system'=>'🔔'];
        return (string) apply_filters('sun_notification_icon',$icons[$category]??'🔔',$category,$type);
    }

    public static function get_user_phone(int $user_id): string {
        foreach (['sn_phone','smp_buyer_phone','billing_phone','phone','mobile','user_phone'] as $key) {
            $value=trim((string)get_user_meta($user_id,$key,true)); if($value!=='') return $value;
        }
        global $wpdb; $table=$wpdb->prefix.'smp_sellers';
        if (SUN_DB::external_table_exists($table)) {
            $value=(string)$wpdb->get_var($wpdb->prepare("SELECT phone FROM $table WHERE user_id=%d LIMIT 1",$user_id)); if(trim($value)!=='') return trim($value);
        }
        return '';
    }

    public static function mask_sensitive_text(string $text): string {
        $text=wp_strip_all_tags($text);
        $text=preg_replace('/\b\d{5}-?\d{7}-?\d\b/','[identity protected]',$text)??$text;
        $text=preg_replace('/\b(?:\+?\d[\d\s\-()]{8,}\d)\b/','[number protected]',$text)??$text;
        $text=preg_replace('/\b\d{4,8}\b/','[code protected]',$text)??$text;
        return trim($text);
    }

    public static function external_preview(array $notification): array {
        $sensitivity=self::normalize_sensitivity((string)($notification['sensitivity']??'private'));
        $title=trim((string)($notification['external_title']??''));
        $body=trim((string)($notification['external_body']??''));
        if ($title==='' || $body==='') {
            if ($sensitivity==='public') {
                $title=$title?:self::mask_sensitive_text((string)($notification['title']??'Notification'));
                $body=$body?:self::mask_sensitive_text((string)($notification['body']??''));
            } else {
                $map=['clinical'=>['Private clinical update','Sign in to view this confidential clinical notification.'],'identity'=>['Identity verification update','Sign in to review this protected identity notification.'],'security'=>['Security alert','Sign in to review this security notification.'],'private'=>['Private notification','Sign in to view this notification.']];
                [$default_title,$default_body]=$map[$sensitivity]??$map['private'];
                $title=$title?:$default_title; $body=$body?:$default_body;
            }
        }
        return ['title'=>sanitize_text_field($title),'body'=>sanitize_textarea_field($body)];
    }

    public static function format_relative_time(string $utc): string { $timestamp=strtotime($utc.' UTC'); return $timestamp?human_time_diff($timestamp,time()).' ago':''; }
    public static function rate_limit(string $key,int $limit,int $window_seconds): bool { $key='sun_rl_'.md5($key.'|'.self::client_ip()); $value=get_transient($key); $count=is_array($value)?(int)($value['count']??0):0; if($count>=$limit)return false; set_transient($key,['count'=>$count+1],$window_seconds); return true; }

    private static function current_key(): string {
        $material = defined('SUN_NOTIFICATION_ENCRYPTION_KEY') && is_string(SUN_NOTIFICATION_ENCRYPTION_KEY) && SUN_NOTIFICATION_ENCRYPTION_KEY !== ''
            ? SUN_NOTIFICATION_ENCRYPTION_KEY
            : (defined('AUTH_KEY') ? AUTH_KEY : '') . (defined('SECURE_AUTH_KEY') ? SECURE_AUTH_KEY : '') . wp_salt('auth') . '|sabri-unified-notifications';
        return hash('sha256', $material, true);
    }
    private static function decryption_keys(): array {
        $keys = [self::current_key()];
        if (defined('SUN_NOTIFICATION_PREVIOUS_ENCRYPTION_KEY') && is_string(SUN_NOTIFICATION_PREVIOUS_ENCRYPTION_KEY) && SUN_NOTIFICATION_PREVIOUS_ENCRYPTION_KEY !== '') {
            $keys[] = hash('sha256', SUN_NOTIFICATION_PREVIOUS_ENCRYPTION_KEY, true);
        }
        return array_values(array_unique($keys, SORT_REGULAR));
    }
    public static function is_encrypted(string $value): bool { return str_starts_with($value,self::ENCRYPTED_PREFIX); }
    private static function encrypt_plain(string $plain): string {
        if ($plain==='' || !function_exists('openssl_encrypt')) return '';
        $iv=random_bytes(12); $tag='';
        $cipher=openssl_encrypt($plain,'aes-256-gcm',self::current_key(),OPENSSL_RAW_DATA,$iv,$tag,'sun-v1');
        if (!is_string($cipher)) return '';
        return self::ENCRYPTED_PREFIX . base64_encode($iv.$tag.$cipher);
    }
    public static function encrypt_secret(string $plain): string {
        if ($plain==='') return '';
        if (self::is_encrypted($plain)) return $plain;
        return self::encrypt_plain($plain);
    }
    public static function decrypt_secret(string $encoded): string {
        if ($encoded==='') return '';
        if (!self::is_encrypted($encoded)) return $encoded;
        $raw=base64_decode(substr($encoded,strlen(self::ENCRYPTED_PREFIX)),true);
        if (!is_string($raw)||strlen($raw)<29||!function_exists('openssl_decrypt')) return '';
        $iv=substr($raw,0,12); $tag=substr($raw,12,16); $cipher=substr($raw,28);
        foreach (self::decryption_keys() as $key) {
            $plain=openssl_decrypt($cipher,'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag,'sun-v1');
            if (is_string($plain)) return $plain;
        }
        return '';
    }
    public static function reencrypt_secret(string $encoded): string {
        $plain=self::decrypt_secret($encoded);
        return $plain!=='' ? self::encrypt_plain($plain) : '';
    }

    public static function validate_webhook_url(string $url): string {
        $url=trim($url); if($url==='') return '';
        $url=esc_url_raw($url,['https']); if(!$url || strtolower((string)wp_parse_url($url,PHP_URL_SCHEME))!=='https') return '';
        $host=(string)wp_parse_url($url,PHP_URL_HOST); if($host==='') return '';
        if (filter_var($host,FILTER_VALIDATE_IP)) return self::is_public_ip($host)?$url:'';
        $records=@dns_get_record($host,DNS_A|DNS_AAAA); if(!is_array($records)||$records===[]) return '';
        foreach($records as $record){$ip=(string)($record['ip']??$record['ipv6']??''); if($ip===''||!self::is_public_ip($ip)) return '';}
        return $url;
    }
    private static function is_public_ip(string $ip): bool { return filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)!==false; }

    public static function redact_array(array $data): array {
        $secret_keys=['token','auth','authorization','secret','password','key','credential'];
        foreach($data as $key=>$value){$lower=strtolower((string)$key); foreach($secret_keys as $needle){if(str_contains($lower,$needle)){$data[$key]='[redacted]';continue 2;}} if(is_array($value))$data[$key]=self::redact_array($value);}
        return $data;
    }
}
