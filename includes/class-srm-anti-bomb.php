<?php
/**
 * Anti-Bomb Click (Click Fraud / Click Flooding Prevention) — Safe Redirect Masker
 *
 * Protection strategy:
 *   - Per-IP rate-limiting stored in WordPress transients (fast, no extra DB queries)
 *   - Configurable: max N clicks per window (seconds), lockout duration after limit hit
 *   - Bots are handled separately by the Bot Detector and are never subject to human limits
 *   - Stats are recorded in the srm_click_log table for admin visibility
 *
 * @package SafeRedirectMasker
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class SRM_Anti_Bomb {

    /**
     * Transient key prefix for click counters.
     */
    const KEY_PREFIX_COUNTER = 'srm_cb_cnt_';

    /**
     * Transient key prefix for lockout flags.
     */
    const KEY_PREFIX_LOCK    = 'srm_cb_lck_';

    /**
     * Check whether the current IP is allowed to proceed.
     *
     * @param  int    $link_id  The target link ID (for per-link rate-limiting).
     * @param  string $ip       Raw IP address (for key derivation only).
     * @return bool   true = allowed, false = blocked (bomb detected)
     */
    public static function is_allowed( $link_id, $ip ) {
        $settings = SRM_DB::get_settings();
        $limit    = max( 1, (int) $settings['anti_bomb_limit'] );
        $window   = max( 10, (int) $settings['anti_bomb_window'] );
        $lockout  = max( 30, (int) $settings['bomb_lockout'] );

        // Key is hashed so we never expose raw IPs in the options table
        $key_base   = hash( 'sha256', $ip . (string) $link_id . ( defined( 'AUTH_KEY' ) ? AUTH_KEY : '' ) );
        $lock_key   = self::KEY_PREFIX_LOCK    . $key_base;
        $count_key  = self::KEY_PREFIX_COUNTER . $key_base;

        // 1. Check if already locked out
        if ( get_transient( $lock_key ) ) {
            return false;
        }

        // 2. Read / increment click counter
        $count = (int) get_transient( $count_key );
        if ( 0 === $count ) {
            // First click in window — start the counter
            set_transient( $count_key, 1, $window );
            return true;
        }

        $count++;
        if ( $count > $limit ) {
            // Limit exceeded — activate lockout, remove counter
            set_transient( $lock_key, 1, $lockout );
            delete_transient( $count_key );
            return false;
        }

        // Still within limit — update counter
        // We use set (not add) to refresh the value; TTL stays the same as it was set originally.
        // For simplicity we just update the value and leave the existing TTL intact.
        set_transient( $count_key, $count, $window );
        return true;
    }

    /**
     * Render a friendly "blocked" HTML page and exit.
     */
    public static function render_blocked_page() {
        status_header( 429 );
        nocache_headers();
        header( 'Retry-After: 300' );
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Too Many Requests — Safe Redirect</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Plus Jakarta Sans',sans-serif}
    body{background:#f8fafc;color:#0f172a;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
    .card{background:#ffffff;border:1px solid #e2e8f0;border-radius:24px;max-width:440px;width:100%;padding:40px 32px;text-align:center;box-shadow:0 20px 40px -15px rgba(15,23,42,0.1)}
    .icon{font-size:52px;margin-bottom:20px;display:block}
    h1{font-size:1.4rem;font-weight:800;color:#0f172a;margin-bottom:10px}
    p{font-size:.9rem;color:#475569;line-height:1.65;margin-bottom:24px}
    .badge{display:inline-block;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:8px;padding:6px 14px;font-size:.8rem;font-weight:600;letter-spacing:.04em}
    a{display:inline-block;margin-top:20px;background:#2563eb;color:#fff;text-decoration:none;padding:12px 28px;border-radius:12px;font-weight:600;font-size:.9rem;box-shadow:0 4px 14px rgba(37,99,235,0.3);transition:background .2s,transform .15s}
    a:hover{background:#1d4ed8;transform:translateY(-1px)}
  </style>
</head>
<body>
  <div class="card">
    <span class="icon">🚫</span>
    <h1>Too Many Clicks Detected</h1>
    <p>Our system has detected an unusually high number of requests from your connection. This protection is in place to prevent click fraud and ensure a fair experience for everyone.</p>
    <div class="badge">Click Rate Limit Exceeded</div>
    <br>
    <a href="javascript:history.back()">← Go Back</a>
  </div>
</body>
</html>
        <?php
        exit;
    }
}
