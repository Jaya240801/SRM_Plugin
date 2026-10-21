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

    public static function render( $link ) {
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
  <meta name="robots" content="noindex, nofollow">
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
      display: flex;
      justify-content: center;
      align-items: center;
      overflow-x: auto;
    }
    .srm-ad-content ins.adsbygoogle {
      display: block !important;
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
      animation: srm-pulse-btn 2.2s infinite;
    }
    @keyframes srm-pulse-btn {
      0%, 100% { transform: scale(1); box-shadow: 0 8px 20px -4px rgba(37,99,235,.4); }
      50% { transform: scale(1.015); box-shadow: 0 12px 28px -2px rgba(37,99,235,.6); }
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
<div class="srm-wrap">

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
      <div class="srm-dest-icon">🎯</div>
      <div class="srm-dest-header-text">
        <h2>Destination Ready</h2>
        <p>Click below to proceed to your destination website</p>
      </div>
    </div>

    <!-- Final CTA button (navigates to destination) -->
    <a href="<?php echo $dest; ?>" class="srm-btn srm-btn-final" id="srm-final-btn" rel="nofollow noopener">
      <span class="srm-btn-inner">
        <span>Continue to Destination</span>
        <span class="srm-btn-arrow">→</span>
      </span>
    </a>

    <!-- Security note -->
    <div class="srm-security-note" style="margin-top:14px;">
      <span class="icon">🔒</span>
      <span>Safe redirect verified. Thank you for your patience!</span>
    </div>
  </div>

</div>

<?php echo $ads_foot; ?>

<script>
(function () {
  'use strict';
  var slug      = <?php echo json_encode( $link['slug'] ); ?>;
  var dest      = <?php echo $dest_js; ?>;
  var isPreview = <?php echo ( isset( $_GET['preview'] ) || isset( $_GET['srm_reset'] ) ) ? 'true' : 'false'; ?>;

  // Option B: If page was already opened in this session (e.g. user refreshes), bypass directly to destination
  if (!isPreview) {
    var sessionKey = 'srm_visited_' + slug;
    try {
      if (sessionStorage.getItem(sessionKey)) {
        window.location.replace(dest);
        return;
      }
      sessionStorage.setItem(sessionKey, '1');
    } catch (e) {}
  }

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

  // 2. Countdown Timer
  var total     = <?php echo intval( $timer ); ?>;
  var remaining = total;
  var btn       = document.getElementById('srm-btn');
  var cd        = document.getElementById('srm-countdown');
  var prog      = document.getElementById('srm-progress');

  function tick() {
    remaining--;
    if (cd) cd.textContent = Math.max(0, remaining);
    var pct = ((total - remaining) / total) * 100;
    if (prog) prog.style.width = Math.min(100, pct) + '%';

    if (remaining <= 0) {
      clearInterval(timer);
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

  // 3. Step 1 Button Click: Scroll smoothly to the bottom final destination card
  if (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      if (btn.classList.contains('disabled')) return;

      unlockScroll();

      var finalCard = document.getElementById('srm-final-card');
      if (finalCard) {
        finalCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        finalCard.classList.add('srm-card-highlight');
        setTimeout(function () {
          finalCard.classList.remove('srm-card-highlight');
        }, 2500);
      }
    });
  }

  // 4. Step 2 Final Button Click: Redirect to destination
  var finalBtn = document.getElementById('srm-final-btn');
  if (finalBtn) {
    finalBtn.addEventListener('click', function () {
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
