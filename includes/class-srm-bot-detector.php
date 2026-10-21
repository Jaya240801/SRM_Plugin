<?php
/**
 * Bot / Crawler Detection — Safe Redirect Masker
 *
 * Strategy (multi-layer):
 *   1. User-Agent string matching against known bot signatures
 *   2. Absence of typical browser signals (Accept-Language, Referer rules)
 *   3. Fetch Metadata headers (Sec-Fetch-*) — Googlebot & friends never send these
 *   4. Optional: headless browser signal (missing JS challenge outcome cookie)
 *
 * When a crawler is detected we redirect it to the real_url (the original article),
 * so search engines index the actual content and not the safelink cloaking page.
 *
 * @package SafeRedirectMasker
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class SRM_Bot_Detector {

    /**
     * Known bot User-Agent fragments (case-insensitive).
     * Ordered from most common to least to short-circuit quickly.
     */
    const BOT_UA_PATTERNS = array(
        // Search engines (redirected to real_url for organic indexing)
        'googlebot', 'google-inspectiontool', 'google-extended',
        'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
        'yandexbot', 'sogou', 'exabot', 'facebot', 'facebookexternalhit',
        'ia_archiver', 'archive.org_bot', 'mj12bot', 'dotbot', 'rogerbot',
        'semrushbot', 'ahrefsbot', 'majestic', 'blexbot', 'screaming frog',
        // Social / preview crawlers
        'twitterbot', 'linkedinbot', 'whatsapp', 'telegrambot', 'slackbot',
        'discordbot', 'applebot', 'pinterestbot', 'redditbot',
        // Generic signals
        'crawler', 'spider', 'bot/', 'robot', 'fetcher', 'scraper',
        'headlesschrome', 'phantomjs', 'selenium', 'puppeteer',
        // Monitoring / uptime checkers
        'uptimerobot', 'pingdom', 'site24x7', 'newrelic', 'nagios', 'zabbix',
        // Feed readers
        'feedfetcher', 'feedly', 'feedburner', 'rssbot',
        // Misc
        'curl/', 'python-requests', 'python-urllib', 'go-http-client', 'java/',
        'wget/', 'libwww-perl', 'okhttp', 'axios/', 'node-fetch', 'got/',
    );

    /**
     * Evaluate whether the current request is from a bot/crawler.
     *
     * @return bool  true = bot, false = real user
     */
    public static function is_bot() {
        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( (string) $_SERVER['HTTP_USER_AGENT'] ) : '';

        // 1. Empty UA → almost certainly a bot/script
        if ( '' === $ua ) {
            return true;
        }

        // 1b. Google AdSense verification crawlers MUST be allowed to inspect the safelink page
        // so Google can analyze the article content and serve live AdSense ads.
        if ( false !== strpos( $ua, 'mediapartners-google' ) || false !== strpos( $ua, 'adsbot-google' ) ) {
            return false;
        }

        // 2. UA pattern matching
        foreach ( self::BOT_UA_PATTERNS as $pattern ) {
            if ( false !== strpos( $ua, $pattern ) ) {
                return true;
            }
        }

        // 3. Sec-Fetch-Mode header check:
        //    Real browsers always send Sec-Fetch-Mode for navigational requests.
        //    Google/Bing/etc. crawlers never send these headers.
        //    Note: This only fires in modern browsers (Chrome 76+, Edge 79+, Firefox 90+).
        $has_sec_fetch = isset( $_SERVER['HTTP_SEC_FETCH_MODE'] );
        if ( $has_sec_fetch ) {
            // If Sec-Fetch-Dest is "document" or "navigate" it is a real navigation — human
            $dest = isset( $_SERVER['HTTP_SEC_FETCH_DEST'] ) ? strtolower( (string) $_SERVER['HTTP_SEC_FETCH_DEST'] ) : '';
            $mode = strtolower( (string) $_SERVER['HTTP_SEC_FETCH_MODE'] );
            if ( 'document' === $dest || 'navigate' === $mode ) {
                return false; // Definitely a browser navigation
            }
        }

        // 4. Accept-Language absence:
        //    All real browsers send Accept-Language. Most bots don't.
        if ( ! isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) || '' === trim( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
            return true;
        }

        // 5. Accept header check:
        //    Browsers send rich Accept (text/html, application/xhtml+xml …).
        //    Many simple scrapers send "*/*" or nothing.
        $accept = isset( $_SERVER['HTTP_ACCEPT'] ) ? strtolower( (string) $_SERVER['HTTP_ACCEPT'] ) : '';
        if ( '' === $accept || $accept === '*/*' ) {
            // Only flag as bot if it also lacks Sec-Fetch headers (belt-and-suspenders)
            if ( ! $has_sec_fetch ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convenience: get a masked IP hash for logging (never store raw IPs).
     *
     * @return string SHA-256 of IP + secret salt
     */
    public static function get_ip_hash() {
        $ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
        $salt = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'srm-salt-fallback';
        return hash( 'sha256', $ip . $salt );
    }

    /**
     * Raw IP for transient keys (not stored long-term).
     *
     * @return string
     */
    public static function get_ip() {
        return isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
}
