<?php
/**
 * Safelink Page Renderer — Safe Redirect Masker
 *
 * Renders a full standalone HTML page (no WP theme) for real human visitors.
 * Features: article cloaking content, countdown timer, AdSense-friendly layout.
 *
 * @package SafeRedirectMasker
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class SRM_Renderer {

    public static function render( $link, $cookie_name = '' ) {
        if ( empty( $cookie_name ) ) {
            $salt        = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'srm_bypass_salt';
            $cookie_name = 'srm_v_' . substr( hash( 'sha256', ( $link['slug'] ?? '' ) . $salt ), 0, 16 );
        }
        $settings = SRM_DB::get_settings();
        $timer    = max( 1, (int) ( $link['timer'] ?? $settings['timer'] ) );
        $dest     = esc_url( $link['real_url'] );
        $title    = ! empty( $link['article_title']   ) ? esc_html( $link['article_title']   ) : esc_html( get_bloginfo( 'name' ) . ' — Safelink' );
        $content  = ! empty( $link['article_content'] ) ? wp_kses_post( $link['article_content'] ) : '';
        $image    = ! empty( $link['article_image']   ) ? esc_url( $link['article_image'] )    : '';
        $ads_head  = $settings['ads_head'] ?? '';
        $ads_foot  = $settings['ads_foot'] ?? '';
        $ad_slot_1 = $settings['ad_slot_1'] ?? '';
        $ad_slot_2 = $settings['ad_slot_2'] ?? '';
        $ad_slot_3 = $settings['ad_slot_3'] ?? '';
        $ad_modal  = $settings['ad_modal']  ?? '';
        $slug      = esc_html( $link['slug'] );
        $dest_js   = json_encode( $link['real_url'] );

        status_header( 200 );
        nocache_headers();
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $title; ?></title>
  <meta name="robots" content="noindex, follow">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://pagead2.googlesyndication.com" crossorigin>
  <link rel="dns-prefetch" href="https://pagead2.googlesyndication.com">
  <link rel="preconnect" href="https://googleads.g.doubleclick.net" crossorigin>
  <link rel="dns-prefetch" href="https://googleads.g.doubleclick.net">
  <link rel="preconnect" href="https://tpc.googlesyndication.com" crossorigin>
  <link rel="dns-prefetch" href="https://tpc.googlesyndication.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <?php echo $ads_head; ?>
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    :root {
      --bg:       #f8fafc;
      --surface:  #ffffff;
      --surface2: #eff6ff;
      --border:   #e2e8f0;
      --primary:  #2563eb;
      --primary2: #1d4ed8;
      --accent:   #1d4ed8;
      --text:     #0f172a;
      --muted:    #475569;
      --success:  #10b981;
      --warning:  #f59e0b;
      --danger:   #ef4444;
      --radius:   16px;
    }
    html { scroll-behavior: smooth; }
    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
      min-height: 100vh;
      line-height: 1.6;
    }
    /* ─── Layout ─────────────────────────────────────────────────────── */
    .srm-wrap {
      max-width: 860px;
      margin: 0 auto;
      padding: 24px 16px 60px;
    }
    /* ─── Header badge ──────────────────────────────────────────────── */
    .srm-site-badge {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 28px;
    }
    .srm-site-badge .logo {
      width: 38px; height: 38px;
      background: linear-gradient(135deg, var(--primary), #3b82f6);
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 18px;
      color: #fff;
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    }
    .srm-site-badge .site-name {
      font-weight: 700;
      color: var(--accent);
      font-size: .95rem;
      letter-spacing: .02em;
    }
    /* ─── Article card ──────────────────────────────────────────────── */
    .srm-article-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 20px;
      overflow: hidden;
      margin-bottom: 24px;
      box-shadow: 0 10px 30px -10px rgba(15,23,42,0.08), 0 4px 6px -2px rgba(15,23,42,0.04);
    }
    .srm-article-img {
      width: 100%;
      height: 220px;
      object-fit: cover;
      display: block;
    }
    .srm-article-img-placeholder {
      width: 100%;
      height: 220px;
      background: linear-gradient(135deg, #dbeafe, #eff6ff);
      display: flex; align-items: center; justify-content: center;
      font-size: 48px;
      color: var(--primary);
    }
    .srm-article-body {
      padding: 28px 28px 24px;
    }
    .srm-article-tag {
      display: inline-block;
      background: #eff6ff;
      color: var(--accent);
      border: 1px solid #bfdbfe;
      border-radius: 6px;
      padding: 3px 10px;
      font-size: .72rem;
      font-weight: 700;
      letter-spacing: .06em;
      text-transform: uppercase;
      margin-bottom: 12px;
    }
    .srm-article-title {
      font-size: 1.45rem;
      font-weight: 800;
      line-height: 1.3;
      color: var(--text);
      margin-bottom: 14px;
    }
    .srm-article-content {
      font-size: .92rem;
      color: var(--muted);
      line-height: 1.75;
    }
    .srm-article-content h2,
    .srm-article-content h3 { color: var(--text); font-size: 1rem; font-weight: 700; margin: 16px 0 8px; }
    .srm-article-content p  { margin-bottom: 12px; }
    .srm-article-content ul,
    .srm-article-content ol { padding-left: 20px; margin-bottom: 12px; }
    .srm-article-content li { margin-bottom: 6px; }
    .srm-article-content strong { color: #1e3a8a; }
    /* ─── Destination card ──────────────────────────────────────────── */
    .srm-dest-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 28px;
      margin-bottom: 24px;
      box-shadow: 0 10px 30px -10px rgba(15,23,42,0.08), 0 4px 6px -2px rgba(15,23,42,0.04);
    }
    .srm-dest-header {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 22px;
    }
    .srm-dest-icon {
      width: 52px; height: 52px;
      background: #eff6ff;
      border: 2px solid var(--primary);
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 24px;
      flex-shrink: 0;
      color: var(--primary);
    }
    .srm-dest-header-text h2 {
      font-size: 1.1rem;
      font-weight: 800;
      color: var(--text);
    }
    .srm-dest-header-text p {
      font-size: .82rem;
      color: var(--muted);
      margin-top: 2px;
    }
    /* ─── Timer ─────────────────────────────────────────────────────── */
    .srm-timer-wrap {
      background: #eff6ff;
      border: 1px solid #bfdbfe;
      border-radius: 14px;
      padding: 18px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 20px;
    }
    .srm-timer-info {
      flex: 1;
    }
    .srm-timer-label {
      font-size: .72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: var(--accent);
      margin-bottom: 4px;
    }
    .srm-timer-desc {
      font-size: .82rem;
      color: var(--muted);
    }
    .srm-timer-display {
      display: flex;
      align-items: center;
      gap: 6px;
      flex-shrink: 0;
    }
    .srm-countdown {
      font-size: 2.8rem;
      font-weight: 800;
      color: var(--primary);
      font-variant-numeric: tabular-nums;
      line-height: 1;
      min-width: 60px;
      text-align: right;
    }
    .srm-countdown-unit {
      font-size: .7rem;
      color: #64748b;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .05em;
      align-self: flex-end;
      padding-bottom: 4px;
    }
    /* Progress bar */
    .srm-progress-track {
      height: 6px;
      background: #e2e8f0;
      border-radius: 4px;
      margin-bottom: 20px;
      overflow: hidden;
    }
    .srm-progress-bar {
      height: 100%;
      background: linear-gradient(90deg, var(--primary), #3b82f6);
      border-radius: 4px;
      width: 100%;
      transition: width 1s linear;
    }
    /* ─── CTA Button ─────────────────────────────────────────────────── */
    .srm-btn-wrap {
      display: flex;
      justify-content: center;
      align-items: center;
      margin: 8px 0 20px;
      width: 100%;
    }
    .srm-btn {
      display: block;
      width: 100%;
      background: linear-gradient(135deg, var(--primary), var(--primary2));
      color: #fff;
      text-decoration: none;
      text-align: center;
      padding: 16px 24px;
      border-radius: 14px;
      font-weight: 700;
      font-size: 1rem;
      cursor: pointer;
      border: none;
      box-shadow: 0 8px 20px -4px rgba(37,99,235,.4);
      transition: all .2s;
      position: relative;
      overflow: hidden;
    }
    #srm-btn,
    .srm-btn.srm-btn-compact {
      width: auto !important;
      max-width: 100% !important;
      padding: 13px 26px !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      margin: 0 auto !important;
    }
    .srm-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 25px -4px rgba(37,99,235,.55);
    }
    .srm-btn:disabled,
    .srm-btn.disabled {
      opacity: .5;
      cursor: not-allowed;
      transform: none !important;
      pointer-events: none;
    }
    .srm-btn-inner {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      white-space: nowrap;
    }
    .srm-btn-arrow { font-size: 1.1rem; }
    /* ─── Security note ─────────────────────────────────────────────── */
    .srm-security-note {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 14px;
      padding: 10px 14px;
      background: #ecfdf5;
      border: 1px solid #a7f3d0;
      border-radius: 10px;
    }
    .srm-security-note .icon { font-size: 14px; flex-shrink: 0; }
    .srm-security-note span  { font-size: .78rem; color: #065f46; font-weight: 600; }
    /* ─── AdSense Ad Slots (Tailored for In-Feed, In-Article, & Multiplex) ─── */
    .srm-ad-wrapper {
      margin: 18px 0;
      text-align: center;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 14px 12px;
      overflow: visible;
      box-sizing: border-box;
      width: 100%;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
      transition: all 0.2s ease;
      display: block;
    }
    .srm-ad-label {
      display: inline-block;
      font-size: 0.65rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #94a3b8;
      margin-bottom: 8px;
      user-select: none;
      font-weight: 700;
    }
    .srm-ad-content {
      width: 100%;
      margin: 0 auto;
      text-align: center;
      overflow: visible;
      box-sizing: border-box;
    }
    .srm-ad-content ins.adsbygoogle {
      display: block !important;
      width: 100% !important;
      margin: 0 auto;
      background: transparent;
    }

    /* Slot 1: In-Feed Ad (Card Feed Layout) */
    .srm-ad-slot-1 {
      margin: 14px 0 18px !important;
    }
    .srm-ad-slot-1 .srm-ad-content {
      min-height: 100px;
    }
    .srm-ad-slot-1 ins.adsbygoogle {
      min-width: 250px;
    }

    /* Slot 2: In-Article Ad (Inside Article Content, Flowing with Paragraphs) */
    .srm-ad-slot-2 {
      background: transparent !important;
      border: none !important;
      border-top: 1px dashed var(--border) !important;
      border-bottom: 1px dashed var(--border) !important;
      border-radius: 0 !important;
      padding: 18px 0 !important;
      margin: 22px 0 !important;
      box-shadow: none !important;
    }
    .srm-ad-slot-2 .srm-ad-content {
      min-height: 120px;
    }

    /* Slot 3: Multiplex Ad (Content Recommendation Grid) */
    .srm-ad-slot-3 {
      text-align: left;
      padding: 18px 16px 20px;
      margin: 24px 0;
      min-height: 280px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 16px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
      overflow: visible;
      box-sizing: border-box;
    }
    .srm-ad-header-multiplex {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 14px;
      padding-bottom: 10px;
      border-bottom: 1px solid var(--border);
    }
    .srm-ad-multiplex-title {
      font-size: 0.92rem;
      font-weight: 700;
      color: var(--text);
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .srm-ad-slot-3 .srm-ad-content {
      width: 100%;
      min-height: 240px;
      position: relative;
    }

    /* Multiplex Skeleton / Placeholder Grid */
    .srm-multiplex-skeleton {
      width: 100%;
      margin: 4px 0 10px;
      transition: opacity 0.3s ease;
    }
    .srm-mx-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
    }
    @media (min-width: 640px) {
      .srm-mx-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
      }
    }
    .srm-mx-card {
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 10px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .srm-mx-img {
      width: 100%;
      height: 90px;
      background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
      background-size: 200% 100%;
      animation: srmShimmer 1.8s infinite;
      border-radius: 8px;
    }
    .srm-mx-text1 {
      width: 85%;
      height: 12px;
      background: #e2e8f0;
      border-radius: 4px;
    }
    .srm-mx-text2 {
      width: 60%;
      height: 10px;
      background: #f1f5f9;
      border-radius: 4px;
    }
    @keyframes srmShimmer {
      0% { background-position: 200% 0; }
      100% { background-position: -200% 0; }
    }
    .srm-admin-ad-status {
      margin-top: 12px;
      padding: 10px 14px;
      font-size: 0.78rem;
      background: #fffbeb;
      border: 1px solid #fde68a;
      border-radius: 8px;
      color: #92400e;
      line-height: 1.5;
    }
    /* ─── Scroll Lock & Final Card ────────────────────────────────────── */
    html.srm-scroll-locked,
    body.srm-scroll-locked {
      overflow: hidden !important;
      height: 100% !important;
      max-height: 100vh !important;
      touch-action: none !important;
      -webkit-overflow-scrolling: auto !important;
    }
    .srm-final-card {
      margin-top: 14px;
      margin-bottom: 0;
      border: 2px solid var(--primary);
      background: var(--surface);
      transition: all .3s ease;
    }
    .srm-final-card.srm-card-highlight {
      box-shadow: 0 0 0 4px rgba(37,99,235,0.25), 0 15px 35px -5px rgba(37,99,235,0.25) !important;
      transform: translateY(-2px);
    }
    .srm-btn-final {
      background: linear-gradient(135deg, var(--primary), var(--primary2));
      box-shadow: 0 8px 20px -4px rgba(37,99,235,.45);
    }
    .srm-btn-final:not(.disabled):not(:disabled) {
      animation: srm-pulse-btn 2.2s infinite;
    }
    .srm-btn.disabled,
    .srm-btn:disabled {
      opacity: .55 !important;
      cursor: not-allowed !important;
      transform: none !important;
      box-shadow: none !important;
      pointer-events: auto !important;
    }
    @keyframes srm-pulse-btn {
      0%, 100% { transform: scale(1); box-shadow: 0 8px 20px -4px rgba(37,99,235,.4); }
      50% { transform: scale(1.015); box-shadow: 0 12px 28px -2px rgba(37,99,235,.6); }
    }
    /* ─── Back Lock Toast & Card Shake ────────────────────────────────── */
    .srm-back-notice {
      display: none;
      align-items: center;
      gap: 10px;
      margin-top: 0;
      margin-bottom: 16px;
      padding: 11px 16px;
      background: #fef3c7;
      border: 1px solid #fde68a;
      border-radius: 12px;
      color: #92400e;
      font-size: .88rem;
      font-weight: 600;
      line-height: 1.4;
    }
    .srm-back-notice .icon { font-size: 1.1rem; flex-shrink: 0; }
    @keyframes srm-shake {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-6px); }
      40%, 80% { transform: translateX(6px); }
    }
    .srm-card-shake {
      animation: srm-shake 0.45s ease-in-out;
      border-color: #f59e0b !important;
      box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.25) !important;
    }
    /* ─── Centered Floating Popover Ad (HTML5 Popover API / Non-Fixation) ─── */
    .srm-ad-popover {
      /* Reset UA default popover styles */
      border: none;
      padding: 0;
      background: transparent;
      color: inherit;
      position: fixed;
      inset: unset;
      top: 50%;
      left: 50%;
      right: auto;
      bottom: auto;
      margin: 0;
      width: min(92vw, 540px);
      max-width: 540px;
      height: fit-content;
      max-height: 88vh;
      overflow: visible;
      box-sizing: border-box;
      z-index: 2147483647;
      opacity: 0;
      transform: translate(-50%, -46%) scale(0.96);
      transition: opacity 0.22s cubic-bezier(0.16, 1, 0.3, 1), transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), overlay 0.22s allow-discrete, display 0.22s allow-discrete;
    }

    /* Closed / Pre-render state: kept in layout offscreen with concrete dimensions so AdSense can pre-render without unfilled collapse */
    .srm-ad-popover:not(:popover-open):not(.srm-popover-open) {
      display: flex !important;
      position: fixed !important;
      top: -9999px !important;
      left: -9999px !important;
      visibility: hidden !important;
      pointer-events: none !important;
      opacity: 0 !important;
      z-index: -1 !important;
    }

    /* Native HTML5 Popover Open State */
    .srm-ad-popover:popover-open,
    .srm-ad-popover.srm-popover-open {
      inset: unset !important;
      top: 50% !important;
      left: 50% !important;
      right: auto !important;
      bottom: auto !important;
      margin: 0 !important;
      visibility: visible !important;
      pointer-events: auto !important;
      transform: translate(-50%, -50%) scale(1) !important;
      display: flex !important;
      flex-direction: column !important;
      align-items: center !important;
      justify-content: center !important;
      opacity: 1 !important;
      z-index: 2147483647 !important;
    }

    @starting-style {
      .srm-ad-popover:popover-open {
        opacity: 0;
        transform: translate(-50%, -46%) scale(0.96) !important;
      }
    }

    /* Light, non-intrusive backdrop with native light-dismiss */
    .srm-ad-popover::backdrop {
      background: rgba(15, 23, 42, 0.45);
      backdrop-filter: blur(4px);
      -webkit-backdrop-filter: blur(4px);
      opacity: 0;
      transition: opacity 0.22s ease, overlay 0.22s allow-discrete, display 0.22s allow-discrete;
    }

    .srm-ad-popover:popover-open::backdrop {
      opacity: 1;
    }

    @starting-style {
      .srm-ad-popover:popover-open::backdrop {
        opacity: 0;
      }
    }

    /* Fallback backdrop for older browsers lacking ::backdrop */
    .srm-popover-backdrop-fallback {
      position: fixed;
      inset: 0;
      z-index: 2147483646;
      background: rgba(15, 23, 42, 0.45);
      backdrop-filter: blur(4px);
      -webkit-backdrop-filter: blur(4px);
      opacity: 1;
      transition: opacity 0.2s ease;
    }

    /* Popover Card Surface */
    .srm-ad-popover-card {
      width: 100%;
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 20px;
      box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25), 0 0 0 1px rgba(0, 0, 0, 0.04);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      box-sizing: border-box;
    }

    /* Popover Header */
    .srm-ad-popover-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 20px;
      background: #ffffff;
      border-bottom: 1px solid var(--border);
    }

    .srm-ad-popover-badge {
      font-size: 0.78rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.07em;
      color: #64748b;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .srm-ad-popover-badge-dot {
      display: inline-block;
      width: 8px;
      height: 8px;
      background: #3b82f6;
      border-radius: 50%;
      box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
    }

    /* Close Button (Primary action in Popover) */
    .srm-ad-popover-close {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 16px;
      background: #f1f5f9;
      color: #1e293b;
      border: 1px solid #cbd5e1;
      border-radius: 9999px;
      font-size: 0.84rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s ease;
      font-family: inherit;
    }

    .srm-ad-popover-close:hover {
      background: #e2e8f0;
      color: #0f172a;
      border-color: #94a3b8;
      transform: translateY(-1px);
    }

    .srm-ad-popover-close:active {
      transform: translateY(0);
    }

    /* Popover Body & Ad Content */
    .srm-ad-popover-body {
      padding: 20px 16px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: #f8fafc;
      box-sizing: border-box;
      max-height: calc(88vh - 120px);
      overflow-y: auto;
      overflow-x: hidden;
    }

    .srm-ad-popover-ad-wrap {
      width: 100%;
      min-width: 250px;
      min-height: 250px;
      margin: 0 auto;
      text-align: center;
      display: block;
      box-sizing: border-box;
      position: relative;
    }

    .srm-ad-popover-skeleton {
      position: absolute;
      inset: 0;
      background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
      background-size: 200% 100%;
      animation: srm-shimmer 1.5s infinite;
      border-radius: 12px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 8px;
      color: #94a3b8;
      font-size: 0.8rem;
      pointer-events: none;
      z-index: 1;
    }

    @keyframes srm-shimmer {
      0% { background-position: 200% 0; }
      100% { background-position: -200% 0; }
    }

    .srm-ad-popover-ad-wrap ins.adsbygoogle {
      display: block !important;
      width: 100% !important;
      margin: 0 auto !important;
      min-width: 250px !important;
      min-height: 250px !important;
      max-width: 100% !important;
      background: transparent !important;
      position: relative;
      z-index: 2;
    }

    .srm-ad-popover-footer-hint {
      padding: 10px 18px 12px;
      background: #ffffff;
      border-top: 1px solid var(--border);
      font-size: 0.78rem;
      color: var(--muted);
      text-align: center;
      line-height: 1.4;
    }
    /* ─── Responsive ─────────────────────────────────────────────────── */
    @media (max-width: 600px) {
      .srm-article-img, .srm-article-img-placeholder { height: 160px; }
      .srm-article-title { font-size: 1.2rem; }
      .srm-countdown { font-size: 2.2rem; }
      .srm-ad-popover {
        width: calc(100vw - 28px) !important;
        max-width: calc(100vw - 28px) !important;
      }
    }
  </style>
</head>
<body>
<div class="srm-wrap google-anno-skip">

  <!-- Site badge -->
  <div class="srm-site-badge">
    <div class="logo">🛡️</div>
    <span class="site-name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
  </div>

  <!-- Ad Slot 1: In-Feed Ad (Card Feed Layout above Countdown) -->
  <?php if ( ! empty( trim( $ad_slot_1 ) ) ) : ?>
    <div class="srm-ad-wrapper srm-ad-slot-1 srm-ad-infeed">
      <span class="srm-ad-label"><?php esc_html_e( 'Advertisement', 'safe-redirect-masker' ); ?></span>
      <div class="srm-ad-content"><?php echo $ad_slot_1; ?></div>
    </div>
  <?php endif; ?>

  <!-- Destination / Timer card -->
  <div class="srm-dest-card" id="destination-card">
    <div class="srm-dest-header">
      <div class="srm-dest-icon">⚡</div>
      <div class="srm-dest-header-text">
        <h2>Secure Link Redirect</h2>
        <p>Your link is being verified &amp; prepared</p>
      </div>
    </div>

    <!-- CTA button (Step 1: Swapped above countdown, sized to fit text) -->
    <div class="srm-btn-wrap">
      <button type="button" class="srm-btn srm-btn-compact disabled" id="srm-btn" disabled>
        <span class="srm-btn-inner">
          <span id="srm-btn-text">Continue to Destination</span>
          <span class="srm-btn-arrow" id="srm-btn-icon">↓</span>
        </span>
      </button>
    </div>

    <!-- Back lock alert notice -->
    <div class="srm-back-notice" id="srm-back-notice">
      <span class="icon">⏳</span>
      <span id="srm-back-notice-text"><?php esc_html_e( 'Please wait for the countdown to complete to unlock your destination link!', 'safe-redirect-masker' ); ?></span>
    </div>

    <!-- Timer (Countdown section swapped below the first button) -->
    <div class="srm-timer-wrap">
      <div class="srm-timer-info">
        <div class="srm-timer-label">Redirecting in</div>
        <div class="srm-timer-desc">Please wait for the countdown to complete</div>
      </div>
      <div class="srm-timer-display">
        <div class="srm-countdown" id="srm-countdown"><?php echo intval( $timer ); ?></div>
        <div class="srm-countdown-unit">sec</div>
      </div>
    </div>

    <!-- Progress bar -->
    <div class="srm-progress-track">
      <div class="srm-progress-bar" id="srm-progress"></div>
    </div>

    <!-- Security note -->
    <div class="srm-security-note">
      <span class="icon">🔒</span>
      <span>This link is protected by Safe Redirect Masker. Bot crawlers are automatically filtered.</span>
    </div>
  </div>

  <!-- Article cloaking content (Placed under countdown section with In-Article Ad Slot 2) -->
  <div class="srm-article-card">
    <?php if ( $image ) : ?>
      <img class="srm-article-img" src="<?php echo $image; ?>" alt="<?php echo $title; ?>" loading="eager">
    <?php else : ?>
      <div class="srm-article-img-placeholder">📄</div>
    <?php endif; ?>
    <div class="srm-article-body">
      <span class="srm-article-tag">📰 Article</span>
      <h1 class="srm-article-title"><?php echo $title; ?></h1>

      <?php
      $has_ad_2    = ! empty( trim( $ad_slot_2 ) );
      $slot_2_html = '';
      if ( $has_ad_2 ) {
          $slot_2_html = '<div class="srm-ad-wrapper srm-ad-slot-2 srm-ad-inarticle">'
                       . '<span class="srm-ad-label">' . esc_html__( 'Advertisement', 'safe-redirect-masker' ) . '</span>'
                       . '<div class="srm-ad-content">' . $ad_slot_2 . '</div>'
                       . '</div>';
      }

      $raw_article = $content;
      if ( empty( $raw_article ) ) {
          $raw_article = '<p>' . esc_html__( 'Please wait while we verify your connection and prepare your secure redirect to the destination link. This process ensures your safety and prevents automated bot access.', 'safe-redirect-masker' ) . '</p>'
                       . '<p>' . esc_html__( 'Our security gateway monitors traffic patterns and protects destination URLs from automated scraping and spam bots. Thank you for your patience.', 'safe-redirect-masker' ) . '</p>';
      }

      if ( $has_ad_2 && stripos( $raw_article, '</p>' ) !== false ) {
          $p_parts   = explode( '</p>', $raw_article );
          $p_count   = count( $p_parts );
          $split_pos = ( $p_count > 2 ) ? (int) floor( $p_count / 2 ) : 1;
          $split_pos = max( 1, min( $split_pos, $p_count - 1 ) );

          $before_ad = implode( '</p>', array_slice( $p_parts, 0, $split_pos ) ) . '</p>';
          $after_ad  = implode( '</p>', array_slice( $p_parts, $split_pos ) );
          echo '<div class="srm-article-content">' . $before_ad . $slot_2_html . $after_ad . '</div>';
      } else {
          echo '<div class="srm-article-content">' . $raw_article . ( $has_ad_2 ? $slot_2_html : '' ) . '</div>';
      }
      ?>
    </div>
  </div>

  <!-- Ad Slot 3: Multiplex Ad (Content Recommendation Grid under Article) -->
  <?php if ( ! empty( trim( $ad_slot_3 ) ) ) : ?>
    <div class="srm-ad-wrapper srm-ad-slot-3 srm-ad-multiplex" id="srm-multiplex-slot">
      <div class="srm-ad-header-multiplex">
        <span class="srm-ad-multiplex-title">💡 <?php esc_html_e( 'You May Also Like', 'safe-redirect-masker' ); ?></span>
        <span class="srm-ad-label"><?php esc_html_e( 'Sponsored Content', 'safe-redirect-masker' ); ?></span>
      </div>
      <div class="srm-ad-content srm-ad-content-multiplex">
        <div class="srm-multiplex-skeleton" id="srm-multiplex-skeleton">
          <div class="srm-mx-grid">
            <div class="srm-mx-card"><div class="srm-mx-img"></div><div class="srm-mx-text1"></div><div class="srm-mx-text2"></div></div>
            <div class="srm-mx-card"><div class="srm-mx-img"></div><div class="srm-mx-text1"></div><div class="srm-mx-text2"></div></div>
            <div class="srm-mx-card"><div class="srm-mx-img"></div><div class="srm-mx-text1"></div><div class="srm-mx-text2"></div></div>
            <div class="srm-mx-card"><div class="srm-mx-img"></div><div class="srm-mx-text1"></div><div class="srm-mx-text2"></div></div>
          </div>
        </div>
        <div class="srm-ad-actual-code">
          <?php echo $ad_slot_3; ?>
        </div>
      </div>
      <?php if ( current_user_can( 'manage_options' ) ) : ?>
        <div class="srm-admin-ad-status" id="srm-admin-ad-status-3" style="display:none;"></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Final Step Destination Card (Step 2 - Final destination link) -->
  <div class="srm-dest-card srm-final-card" id="srm-final-card">
    <div class="srm-dest-header">
      <div class="srm-dest-icon" id="srm-final-icon">🔒</div>
      <div class="srm-dest-header-text">
        <h2 id="srm-final-title">Final Step: Complete Step 1 First</h2>
        <p id="srm-final-desc">Please complete the countdown at the top to unlock this link</p>
      </div>
    </div>

    <!-- Final CTA button (starts locked, unlocks only after first button is clicked) -->
    <button type="button" class="srm-btn srm-btn-final disabled" id="srm-final-btn" disabled>
      <span class="srm-btn-inner">
        <span id="srm-final-btn-text">Continue to Destination</span>
        <span class="srm-btn-arrow" id="srm-final-btn-icon">🔒</span>
      </span>
    </button>

    <!-- Security note -->
    <div class="srm-security-note" style="margin-top:14px;">
      <span class="icon">🔒</span>
      <span id="srm-final-note">Link is locked. Click "Continue to Destination" at the top once countdown finishes.</span>
    </div>
  </div>

</div>

<?php if ( ! empty( trim( $ad_modal ) ) ) : ?>
<!-- Centered Floating Popover Ad (HTML5 Popover API with Light Dismiss) -->
<div id="srm-ad-popover" class="srm-ad-popover google-anno-skip" popover="auto" role="dialog" aria-label="<?php esc_attr_e( 'Advertisement', 'safe-redirect-masker' ); ?>">
  <div class="srm-ad-popover-card">
    <div class="srm-ad-popover-header">
      <span class="srm-ad-popover-badge">
        <span class="srm-ad-popover-badge-dot"></span>
        <?php esc_html_e( 'Sponsored / Advertisement', 'safe-redirect-masker' ); ?>
      </span>
      <button type="button" class="srm-ad-popover-close" id="srm-popover-close-btn" aria-label="<?php esc_attr_e( 'Close & Continue', 'safe-redirect-masker' ); ?>">
        <span><?php esc_html_e( 'Close', 'safe-redirect-masker' ); ?></span>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    <div class="srm-ad-popover-body">
      <div class="srm-ad-popover-ad-wrap" id="srm-ad-popover-ad-wrap">
        <div class="srm-ad-popover-skeleton" id="srm-ad-popover-skeleton">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
          <span><?php esc_html_e( 'Advertisement Loading...', 'safe-redirect-masker' ); ?></span>
        </div>
      </div>
    </div>
    <div class="srm-ad-popover-footer-hint">
      <?php esc_html_e( 'Close this window to proceed to your verified destination link', 'safe-redirect-masker' ); ?>
    </div>
  </div>
</div>
<template id="srm-popover-ad-raw">
  <?php echo $ad_modal; ?>
</template>
<?php endif; ?>

<?php echo $ads_foot; ?>

<script>
(function () {
  'use strict';
  var slug       = <?php echo json_encode( $link['slug'] ); ?>;
  var dest       = <?php echo $dest_js; ?>;
  var isPreview  = <?php echo ( isset( $_GET['preview'] ) || isset( $_GET['srm_reset'] ) ) ? 'true' : 'false'; ?>;
  var cookieName = <?php echo json_encode( $cookie_name ); ?>;

  // Option B: If page was already completed in this session (e.g. user refreshes after countdown finished),
  // bypass directly to destination
  if (!isPreview) {
    var sessionKey = 'srm_visited_' + slug;
    try {
      if (sessionStorage.getItem(sessionKey)) {
        window.location.replace(dest);
        return;
      }
    } catch (e) {}
  }

  // Track whether Step 1 button was clicked
  var step1Completed = false;

  // 1. Prevent scrolling for the first 5 seconds
  var scrollLocked = true;
  document.documentElement.classList.add('srm-scroll-locked');
  document.body.classList.add('srm-scroll-locked');

  function preventScroll(e) {
    if (scrollLocked) {
      e.preventDefault();
      return false;
    }
  }

  function preventScrollKeys(e) {
    if (!scrollLocked) return;
    var keys = [32, 33, 34, 35, 36, 37, 38, 39, 40];
    if (keys.indexOf(e.keyCode) !== -1) {
      e.preventDefault();
      return false;
    }
  }

  window.addEventListener('wheel', preventScroll, { passive: false });
  window.addEventListener('touchmove', preventScroll, { passive: false });
  window.addEventListener('keydown', preventScrollKeys, false);

  function unlockScroll() {
    if (!scrollLocked) return;
    scrollLocked = false;
    document.documentElement.classList.remove('srm-scroll-locked');
    document.body.classList.remove('srm-scroll-locked');
    window.removeEventListener('wheel', preventScroll);
    window.removeEventListener('touchmove', preventScroll);
    window.removeEventListener('keydown', preventScrollKeys);
  }

  setTimeout(unlockScroll, 5000);

  // 2. Back button retention lock (Active ONLY while countdown is running)
  var backLocked  = true;
  var noticeTimer = null;

  function showBackNotice() {
    var notice   = document.getElementById('srm-back-notice');
    var destCard = document.getElementById('destination-card');
    if (notice) {
      notice.style.display = 'flex';
      notice.style.animation = 'none';
      notice.offsetHeight; /* Trigger reflow to replay shake */
      notice.style.animation = 'srm-shake 0.45s ease-in-out';
    }
    if (destCard) {
      destCard.classList.add('srm-card-shake');
      setTimeout(function () {
        destCard.classList.remove('srm-card-shake');
      }, 500);
    }
    clearTimeout(noticeTimer);
    noticeTimer = setTimeout(function () {
      if (notice && !backLocked) {
        notice.style.display = 'none';
      }
    }, 4000);
  }

  try {
    // Push extra history state to trap early back button presses
    history.pushState({ srm_trap: 1 }, '', window.location.href);
    window.addEventListener('popstate', function (e) {
      if (backLocked && remaining > 0) {
        history.pushState({ srm_trap: 1 }, '', window.location.href);
        showBackNotice();
      }
    });
  } catch (err) {}

  // 3. Countdown Timer
  var total     = <?php echo intval( $timer ); ?>;
  var remaining = total;
  var btn       = document.getElementById('srm-btn');
  var cd        = document.getElementById('srm-countdown');
  var prog      = document.getElementById('srm-progress');
  var finalBtn  = document.getElementById('srm-final-btn');

  function tick() {
    remaining--;
    if (cd) cd.textContent = Math.max(0, remaining);
    var pct = ((total - remaining) / total) * 100;
    if (prog) prog.style.width = Math.min(100, pct) + '%';

    if (remaining <= 0) {
      clearInterval(timer);
      backLocked = false; // Release back button lock once countdown completes!
      var notice = document.getElementById('srm-back-notice');
      if (notice) notice.style.display = 'none';

      // Activate 1-hour bypass cookie and sessionStorage ONLY now (user completed countdown)
      if (!isPreview) {
        var sessionKey = 'srm_visited_' + slug;
        try { sessionStorage.setItem(sessionKey, '1'); } catch (e) {}

        if (cookieName) {
          var expires = new Date(Date.now() + 3600 * 1000).toUTCString();
          document.cookie = cookieName + '=1; expires=' + expires + '; path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
        }
      }

      if (btn) {
        btn.classList.remove('disabled');
        btn.removeAttribute('disabled');
        btn.removeAttribute('aria-disabled');
      }
      var timerDesc = document.querySelector('.srm-timer-desc');
      if (timerDesc) timerDesc.textContent = 'Verification complete! Click the button above to continue.';
    }
  }

  // Kick off progress bar at 0 immediately
  if (prog) prog.style.width = '0%';
  var timer = setInterval(tick, 1000);

  // Helper: Smooth scroll to final destination card and highlight
  function scrollToFinalCard() {
    var finalCard = document.getElementById('srm-final-card');
    if (finalCard) {
      finalCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
      finalCard.classList.add('srm-card-highlight');
      setTimeout(function () {
        finalCard.classList.remove('srm-card-highlight');
      }, 2500);
    }
  }

  // Popover handlers (HTML5 Popover API with Light Dismiss & Fallback)
  var adPopover          = document.getElementById('srm-ad-popover');
  var closePopoverBtn    = document.getElementById('srm-popover-close-btn');
  var popoverAdActivated = false;
  var fallbackBackdrop   = null;
  var hasNativePopover   = !!(adPopover && typeof adPopover.showPopover === 'function');
  var popoverWasOpen     = false;

  function activatePopoverAd() {
    if (popoverAdActivated) return;
    popoverAdActivated = true;

    var rawTpl = document.getElementById('srm-popover-ad-raw');
    var wrap   = document.getElementById('srm-ad-popover-ad-wrap');
    if (!rawTpl || !wrap) return;

    var skel = document.getElementById('srm-ad-popover-skeleton');
    var tempDiv = document.createElement('div');
    tempDiv.innerHTML = rawTpl.innerHTML;

    var isAdSense = tempDiv.querySelector('ins.adsbygoogle') !== null;

    if (isAdSense) {
      // AdSense unit: extract external script to head if needed, strip inline scripts to avoid duplicate push
      var scripts = tempDiv.querySelectorAll('script');
      scripts.forEach(function (s) {
        if (s.src && s.src.indexOf('adsbygoogle') !== -1 && !document.querySelector('script[src*="adsbygoogle.js"]')) {
          var newScript = document.createElement('script');
          newScript.src = s.src;
          newScript.async = true;
          if (s.crossOrigin) newScript.crossOrigin = s.crossOrigin;
          document.head.appendChild(newScript);
        }
        s.remove();
      });

      // Clear skeleton and insert clean markup into wrap
      if (skel) skel.remove();
      wrap.innerHTML = tempDiv.innerHTML;

      // Push to AdSense for each ins tag
      var insList = wrap.querySelectorAll('ins.adsbygoogle');
      insList.forEach(function (ins) {
        try {
          (window.adsbygoogle = window.adsbygoogle || []).push({});
        } catch (err) {
          console.warn('SRM AdSense popover push:', err);
        }
      });
    } else {
      // Non-AdSense / generic ad code
      if (skel) skel.remove();
      wrap.innerHTML = '';
      Array.from(tempDiv.childNodes).forEach(function (node) {
        if (node.tagName === 'SCRIPT') {
          var sc = document.createElement('script');
          Array.from(node.attributes).forEach(function (attr) {
            sc.setAttribute(attr.name, attr.value);
          });
          sc.text = node.innerHTML;
          wrap.appendChild(sc);
        } else {
          wrap.appendChild(node.cloneNode(true));
        }
      });
    }

    // Force layout reflow and AdSense responsive recalculation
    setTimeout(function () {
      try { window.dispatchEvent(new Event('resize')); } catch (err) {}
    }, 60);
    setTimeout(function () {
      try { window.dispatchEvent(new Event('resize')); } catch (err) {}
    }, 250);
  }

  // Pre-render the popover ad offscreen in the background so it's loaded before user opens popover
  if (adPopover) {
    setTimeout(activatePopoverAd, 150);
  }

  function showFallbackBackdrop() {
    if (!fallbackBackdrop) {
      fallbackBackdrop = document.createElement('div');
      fallbackBackdrop.className = 'srm-popover-backdrop-fallback';
      fallbackBackdrop.addEventListener('click', function () {
        closePopover();
      });
      document.body.appendChild(fallbackBackdrop);
    }
    fallbackBackdrop.style.display = 'block';
  }

  function hideFallbackBackdrop() {
    if (fallbackBackdrop) {
      fallbackBackdrop.style.display = 'none';
    }
  }

  function openPopover() {
    if (!adPopover) {
      scrollToFinalCard();
      return;
    }
    popoverWasOpen = true;

    // Ensure ad is activated if not already done
    activatePopoverAd();

    if (hasNativePopover) {
      try {
        adPopover.showPopover();
      } catch (e) {
        adPopover.classList.add('srm-popover-open');
        showFallbackBackdrop();
      }
    } else {
      adPopover.classList.add('srm-popover-open');
      showFallbackBackdrop();
    }

    // Trigger responsive recalculation once visible
    setTimeout(function () {
      try { window.dispatchEvent(new Event('resize')); } catch (err) {}
    }, 50);
  }

  function closePopover() {
    if (!adPopover) return;
    var hadOpen = popoverWasOpen;
    popoverWasOpen = false;

    if (hasNativePopover) {
      try {
        adPopover.hidePopover();
      } catch (e) {
        adPopover.classList.remove('srm-popover-open');
      }
    } else {
      adPopover.classList.remove('srm-popover-open');
    }
    hideFallbackBackdrop();

    // When user closes the popover, trigger smooth scroll down to the final button
    if (hadOpen) {
      scrollToFinalCard();
    }
  }

  // Native HTML5 popover toggle listener (captures light-dismiss when user clicks outside or hits ESC)
  if (adPopover) {
    adPopover.addEventListener('toggle', function (e) {
      if (e.newState === 'closed') {
        if (popoverWasOpen) {
          popoverWasOpen = false;
          hideFallbackBackdrop();
          scrollToFinalCard();
        }
      }
    });
  }

  // Close button listener
  if (closePopoverBtn) {
    closePopoverBtn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      closePopover();
    });
  }

  // Fallback ESC listener for browsers where native popover toggle isn't available
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && popoverWasOpen) {
      closePopover();
    }
  });

  // 3. Step 1 Button Click: Show Popover Ad (if present) or scroll smoothly to final destination card
  if (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (typeof e.stopImmediatePropagation === 'function') {
        e.stopImmediatePropagation();
      }
      if (btn.classList.contains('disabled')) return;

      unlockScroll();
      step1Completed = true;

      // Unlock the second button
      if (finalBtn) {
        finalBtn.classList.remove('disabled');
        finalBtn.removeAttribute('disabled');
        finalBtn.removeAttribute('aria-disabled');
        var finalBtnText = document.getElementById('srm-final-btn-text');
        var finalBtnIcon = document.getElementById('srm-final-btn-icon');
        var finalIcon    = document.getElementById('srm-final-icon');
        var finalTitle   = document.getElementById('srm-final-title');
        var finalDesc    = document.getElementById('srm-final-desc');
        var finalNote    = document.getElementById('srm-final-note');

        if (finalBtnText) finalBtnText.textContent = 'Continue to Destination';
        if (finalBtnIcon) finalBtnIcon.textContent = '→';
        if (finalIcon)    finalIcon.textContent    = '🎯';
        if (finalTitle)   finalTitle.textContent   = 'Destination Ready';
        if (finalDesc)    finalDesc.textContent    = 'Click below to proceed to your destination website';
        if (finalNote)    finalNote.textContent    = 'Safe redirect verified. Thank you for your patience!';
      }

      // If Popover Ad is present, open non-fixation floating popover; otherwise scroll directly to bottom card
      if (adPopover) {
        openPopover();
      } else {
        scrollToFinalCard();
      }
    });
  }

  // Trigger responsive reflow so AdSense calculates In-Feed, In-Article, and Multiplex grids accurately
  [80, 250, 600, 1500].forEach(function (ms) {
    setTimeout(function () {
      try { window.dispatchEvent(new Event('resize')); } catch (e) {}
    }, ms);
  });

  // Monitor AdSense status for Multiplex & ensure slots do not disappear
  (function monitorAdSlots() {
    // 1. Ensure any unpushed visible AdSense slots get pushed
    document.querySelectorAll('.srm-ad-wrapper ins.adsbygoogle').forEach(function (ins) {
      if (!ins.getAttribute('data-adsbygoogle-status')) {
        try {
          (window.adsbygoogle = window.adsbygoogle || []).push({});
        } catch (e) {}
      }
    });

    // 2. Multiplex state tracking
    var mxWrap = document.getElementById('srm-multiplex-slot');
    if (!mxWrap) return;

    var skel = document.getElementById('srm-multiplex-skeleton');
    var adminBadge = document.getElementById('srm-admin-ad-status-3');
    var ins = mxWrap.querySelector('ins.adsbygoogle');
    if (!ins) return;

    var checkCount = 0;
    var interval = setInterval(function () {
      checkCount++;
      var status = ins.getAttribute('data-ad-status');
      var iframe = ins.querySelector('iframe');
      var iframeHeight = iframe ? (iframe.offsetHeight || parseInt(iframe.getAttribute('height') || '0', 10)) : 0;

      if (status === 'filled' || (iframe && iframeHeight > 40)) {
        // Real AdSense ad filled!
        if (skel) skel.style.display = 'none';
        if (adminBadge) adminBadge.style.display = 'none';
        clearInterval(interval);
      } else if (status === 'unfilled') {
        // AdSense returned unfilled
        if (adminBadge) {
          var slotId = ins.getAttribute('data-ad-slot') || 'Multiplex';
          adminBadge.innerHTML = '<strong>⚡ AdSense Status (Slot ' + slotId + '):</strong> Google AdSense queried the auction and returned <code>unfilled</code>. This is normal for new ad units during the 24–48h warm-up period. The container remains active.';
          adminBadge.style.display = 'block';
        }
        clearInterval(interval);
      } else if (checkCount >= 20) {
        clearInterval(interval);
      }
    }, 500);
  })();

  // 4. Step 2 Final Button Click: Redirect to destination (only if step 1 was completed)
  if (finalBtn) {
    finalBtn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (typeof e.stopImmediatePropagation === 'function') {
        e.stopImmediatePropagation();
      }
      if (!step1Completed || finalBtn.classList.contains('disabled')) {
        // If user scrolled down and clicked early, scroll them back up to the top countdown card
        var topCard = document.getElementById('destination-card');
        if (topCard) {
          topCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return false;
      }
      window.location.href = dest;
    });
  }
})();
</script>
</body>
</html>
        <?php
    }
}
