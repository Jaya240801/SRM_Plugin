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
    /* ─── AdSense Ad Slots ───────────────────────────────────────────── */
    .srm-ad-wrapper {
      margin: 18px 0;
      text-align: center;
      background: #f8fafc;
      border: 1px dashed #cbd5e1;
      border-radius: 14px;
      padding: 14px 10px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 50px;
    }
    .srm-ad-label {
      font-size: 0.65rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #64748b;
      margin-bottom: 6px;
      user-select: none;
      font-weight: 600;
    }
    .srm-ad-content {
      width: 100%;
      min-width: 250px;
      margin: 0 auto;
      text-align: center;
      overflow-x: auto;
    }
    .srm-ad-content ins.adsbygoogle {
      display: block !important;
      width: 100% !important;
      min-width: 250px !important;
      min-height: 90px !important;
      margin: 0 auto !important;
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
      margin-top: 14px;
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
    /* ─── In-Feed / Fluid Ad Fullscreen Modal Overlay ─────────────────── */
    .srm-ad-modal-overlay {
      position: fixed;
      inset: 0;
      z-index: 999999;
      background: rgba(15, 23, 42, 0.85);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0;
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
      transition: opacity 0.25s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .srm-ad-modal-overlay.srm-modal-open {
      opacity: 1;
      visibility: visible;
      pointer-events: auto;
    }
    .srm-ad-modal-dialog {
      background: #f8fafc;
      border-radius: 0;
      width: 100vw;
      height: 100vh;
      max-width: 100vw;
      max-height: 100vh;
      overflow-y: auto;
      overflow-x: hidden;
      box-shadow: none;
      display: flex;
      flex-direction: column;
      position: relative;
      transform: scale(0.98);
      transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .srm-ad-modal-overlay.srm-modal-open .srm-ad-modal-dialog {
      transform: scale(1);
    }
    .srm-ad-modal-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 24px;
      border-bottom: 1px solid var(--border);
      background: #ffffff;
      border-radius: 0;
      position: sticky;
      top: 0;
      z-index: 10;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }
    .srm-ad-modal-badge {
      font-size: 0.78rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #64748b;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .srm-ad-modal-badge-dot {
      display: inline-block;
      width: 9px;
      height: 9px;
      background: #3b82f6;
      border-radius: 50%;
      box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
    }
    .srm-ad-modal-close {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 18px;
      background: #f1f5f9;
      color: #1e293b;
      border: 1px solid #cbd5e1;
      border-radius: 9999px;
      font-size: 0.88rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s ease;
      font-family: inherit;
    }
    .srm-ad-modal-close:hover {
      background: #e2e8f0;
      color: #0f172a;
      border-color: #94a3b8;
      transform: translateY(-1px);
    }
    .srm-ad-modal-close:active {
      transform: translateY(0);
    }
    .srm-ad-modal-body {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 32px 20px;
      background: #f8fafc;
      box-sizing: border-box;
      min-height: calc(100vh - 170px);
    }
    /* Feed Card Container for Google In-Feed Ads (Expanded Fullscreen Width) */
    .srm-infeed-card-box {
      width: 100%;
      max-width: 820px;
      margin: auto;
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 16px;
      padding: 24px;
      box-sizing: border-box;
      box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(0, 0, 0, 0.03);
    }
    .srm-ad-modal-ad-wrap {
      width: 100%;
      margin: 0 auto;
      text-align: left;
      display: block;
      overflow: hidden;
      min-height: 250px;
      box-sizing: border-box;
    }
    .srm-ad-modal-ad-wrap ins.adsbygoogle {
      display: block !important;
      width: 100% !important;
      max-width: 100% !important;
      margin: 0 auto !important;
      background: transparent !important;
    }
    .srm-ad-modal-footer {
      padding: 16px 24px;
      background: #ffffff;
      border-top: 1px solid var(--border);
      text-align: center;
      border-radius: 0;
      position: sticky;
      bottom: 0;
      z-index: 10;
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 10px;
      align-items: center;
      box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.03);
    }
    .srm-btn-modal-continue {
      width: 100%;
      max-width: 500px;
      padding: 14px 24px;
      background: linear-gradient(135deg, #2563eb, #1d4ed8);
      color: #ffffff;
      border: none;
      border-radius: 12px;
      font-size: 1rem;
      font-weight: 700;
      letter-spacing: 0.01em;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
      transition: all 0.2s ease;
      font-family: inherit;
    }
    .srm-btn-modal-continue:hover {
      background: linear-gradient(135deg, #1d4ed8, #1e40af);
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(37, 99, 235, 0.45);
    }
    .srm-btn-modal-continue:active {
      transform: translateY(0);
    }
    .srm-ad-modal-footer-note {
      font-size: 0.8rem;
      color: var(--muted);
      line-height: 1.4;
    }
    /* ─── Responsive ─────────────────────────────────────────────────── */
    @media (max-width: 600px) {
      .srm-article-img, .srm-article-img-placeholder { height: 160px; }
      .srm-article-title { font-size: 1.2rem; }
      .srm-countdown { font-size: 2.2rem; }
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

  <!-- Ad Slot 1 (Top Banner - Above Countdown) -->
  <?php if ( ! empty( trim( $ad_slot_1 ) ) ) : ?>
    <div class="srm-ad-wrapper srm-ad-slot-1">
      <span class="srm-ad-label"><?php esc_html_e( 'Advertisement', 'safe-redirect-masker' ); ?></span>
      <div class="srm-ad-content"><?php echo $ad_slot_1; ?></div>
    </div>
  <?php endif; ?>

  <!-- Destination / Timer card (At top so user sees countdown immediately) -->
  <div class="srm-dest-card" id="destination-card">
    <div class="srm-dest-header">
      <div class="srm-dest-icon">⚡</div>
      <div class="srm-dest-header-text">
        <h2>Secure Link Redirect</h2>
        <p>Your link is being verified &amp; prepared</p>
      </div>
    </div>

    <!-- Timer -->
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

    <!-- Back lock alert notice -->
    <div class="srm-back-notice" id="srm-back-notice">
      <span class="icon">⏳</span>
      <span id="srm-back-notice-text"><?php esc_html_e( 'Please wait for the countdown to complete to unlock your destination link!', 'safe-redirect-masker' ); ?></span>
    </div>

    <!-- CTA button (Step 1: scrolls down to bottom final link when countdown ends) -->
    <button type="button" class="srm-btn disabled" id="srm-btn" disabled>
      <span class="srm-btn-inner">
        <span id="srm-btn-text">Continue to Destination</span>
        <span class="srm-btn-arrow" id="srm-btn-icon">↓</span>
      </span>
    </button>

    <!-- Security note -->
    <div class="srm-security-note">
      <span class="icon">🔒</span>
      <span>This link is protected by Safe Redirect Masker. Bot crawlers are automatically filtered.</span>
    </div>
  </div>

  <!-- Ad Slot 2 (Middle Banner - Between Countdown & Article) -->
  <?php if ( ! empty( trim( $ad_slot_2 ) ) ) : ?>
    <div class="srm-ad-wrapper srm-ad-slot-2">
      <span class="srm-ad-label"><?php esc_html_e( 'Advertisement', 'safe-redirect-masker' ); ?></span>
      <div class="srm-ad-content"><?php echo $ad_slot_2; ?></div>
    </div>
  <?php endif; ?>

  <!-- Article cloaking content (Placed under countdown section) -->
  <div class="srm-article-card">
    <?php if ( $image ) : ?>
      <img class="srm-article-img" src="<?php echo $image; ?>" alt="<?php echo $title; ?>" loading="eager">
    <?php else : ?>
      <div class="srm-article-img-placeholder">📄</div>
    <?php endif; ?>
    <div class="srm-article-body">
      <span class="srm-article-tag">📰 Article</span>
      <h1 class="srm-article-title"><?php echo $title; ?></h1>
      <?php if ( $content ) : ?>
        <div class="srm-article-content"><?php echo $content; ?></div>
      <?php else : ?>
        <div class="srm-article-content">
          <p>Please wait while we verify your connection and prepare your secure redirect to the destination link. This process ensures your safety and prevents automated bot access.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Ad Slot 3 (Bottom Banner - Under Article Content) -->
  <?php if ( ! empty( trim( $ad_slot_3 ) ) ) : ?>
    <div class="srm-ad-wrapper srm-ad-slot-3">
      <span class="srm-ad-label"><?php esc_html_e( 'Advertisement', 'safe-redirect-masker' ); ?></span>
      <div class="srm-ad-content"><?php echo $ad_slot_3; ?></div>
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
<!-- In-Feed / Fluid Modal Ad -->
<div id="srm-ad-modal" class="srm-ad-modal-overlay google-anno-skip" aria-modal="true" role="dialog" aria-hidden="true">
  <div class="srm-ad-modal-dialog">
    <div class="srm-ad-modal-header">
      <span class="srm-ad-modal-badge">
        <span class="srm-ad-modal-badge-dot"></span>
        <?php esc_html_e( 'Sponsored Recommendation', 'safe-redirect-masker' ); ?>
      </span>
      <button type="button" class="srm-ad-modal-close" id="srm-modal-close-ad-btn" aria-label="<?php esc_attr_e( 'Close Ad', 'safe-redirect-masker' ); ?>">
        <span><?php esc_html_e( 'Close Ad', 'safe-redirect-masker' ); ?></span>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    <div class="srm-ad-modal-body">
      <div class="srm-infeed-card-box">
        <div class="srm-ad-modal-ad-wrap" id="srm-ad-modal-ad-wrap">
          <!-- In-Feed Ad dynamically injected when modal opens -->
        </div>
      </div>
    </div>
    <div class="srm-ad-modal-footer">
      <button type="button" class="srm-btn-modal-continue" id="srm-modal-continue-btn">
        <span><?php esc_html_e( 'Continue to Destination', 'safe-redirect-masker' ); ?></span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
      </button>
      <span class="srm-ad-modal-footer-note"><?php esc_html_e( 'Click above or close to proceed to your verified link', 'safe-redirect-masker' ); ?></span>
    </div>
  </div>
</div>

<template id="srm-modal-ad-template">
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
      if (timerDesc) timerDesc.textContent = 'Verification complete! Click the button below to continue.';
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

  // Modal handlers
  var adModal          = document.getElementById('srm-ad-modal');
  var closeAdBtn       = document.getElementById('srm-modal-close-ad-btn');
  var modalContinueBtn = document.getElementById('srm-modal-continue-btn');
  var modalAdLoaded    = false;

  function loadModalAdContent() {
    if (modalAdLoaded) return;
    modalAdLoaded = true;

    var tpl = document.getElementById('srm-modal-ad-template');
    var wrap = document.getElementById('srm-ad-modal-ad-wrap');
    if (!tpl || !wrap) return;

    // Inject template HTML into the now-visible container
    wrap.innerHTML = tpl.innerHTML;

    // Ensure AdSense library is present
    if (!window.adsbygoogle && !document.querySelector('script[src*="adsbygoogle.js"]')) {
      var adScript = document.createElement('script');
      adScript.async = true;
      adScript.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js';
      adScript.crossOrigin = 'anonymous';
      document.head.appendChild(adScript);
    }

    // Execute any script tags inside the template (such as push or external script)
    var scripts = Array.prototype.slice.call(wrap.getElementsByTagName('script'));
    scripts.forEach(function (oldScript) {
      var newScript = document.createElement('script');
      Array.prototype.slice.call(oldScript.attributes).forEach(function (attr) {
        newScript.setAttribute(attr.name, attr.value);
      });
      newScript.textContent = oldScript.textContent;
      oldScript.parentNode.replaceChild(newScript, oldScript);
    });

    // For fluid in-feed ads, if ins.adsbygoogle is not yet pushed, call push({})
    var insElements = wrap.querySelectorAll('ins.adsbygoogle');
    insElements.forEach(function (ins) {
      if (!ins.getAttribute('data-adsbygoogle-status')) {
        try {
          (window.adsbygoogle = window.adsbygoogle || []).push({});
        } catch (err) {}
      }
    });

    // Dispatch resize ticks so Google AdSense fluid layout engine measures visible container
    setTimeout(function () {
      try { window.dispatchEvent(new Event('resize')); } catch (e) {}
    }, 50);
    setTimeout(function () {
      try { window.dispatchEvent(new Event('resize')); } catch (e) {}
    }, 200);
  }

  function closeAdModal() {
    if (!adModal) return;
    adModal.classList.remove('srm-modal-open');
    adModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    // Scroll down to the final bottom destination card after closing modal
    scrollToFinalCard();
  }

  if (closeAdBtn) {
    closeAdBtn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      closeAdModal();
    });
  }

  if (modalContinueBtn) {
    modalContinueBtn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      closeAdModal();
    });
  }

  if (adModal) {
    adModal.addEventListener('click', function (e) {
      if (e.target === adModal) {
        closeAdModal();
      }
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && adModal && adModal.classList.contains('srm-modal-open')) {
      closeAdModal();
    }
  });

  // 3. Step 1 Button Click: Show Modal Ad (if present) and scroll smoothly to final destination card
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

      // If Modal Ad is present, pop up in-feed modal ad; otherwise scroll directly to bottom card
      if (adModal) {
        adModal.classList.add('srm-modal-open');
        adModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        loadModalAdContent();
      } else {
        scrollToFinalCard();
      }
    });
  }

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
