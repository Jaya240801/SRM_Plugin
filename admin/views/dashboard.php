<?php
/**
 * Admin Dashboard View — Safe Redirect Masker
 *
 * @package SafeRedirectMasker
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$settings = SRM_DB::get_settings();
$prefix   = esc_html( $settings['url_prefix'] );
$base_url = esc_url( home_url( '/' ) );
?>

<div id="srm-app">

  <!-- Toast container -->
  <div id="srm-toast-container"></div>

  <!-- ── Header ─────────────────────────────────────────────────── -->
  <div class="srm-header">
    <div class="srm-header-left">
      <div class="srm-logo">🛡️</div>
      <div class="srm-header-title">
        <h1><?php _e( 'Safe Redirect Masker', 'safe-redirect-masker' ); ?></h1>
        <p><?php _e( 'Bot-aware URL masking with anti-bomb click protection', 'safe-redirect-masker' ); ?></p>
      </div>
    </div>
    <div>
      <span class="srm-version-badge">
        <span>🏷</span> v<?php echo SRM_VERSION; ?>
      </span>
    </div>
  </div>

  <!-- ── Stats ──────────────────────────────────────────────────── -->
  <div class="srm-stats-grid">
    <div class="srm-stat-card">
      <span class="srm-stat-icon">🔗</span>
      <div class="srm-stat-value" id="srm-stat-links">…</div>
      <div class="srm-stat-label"><?php _e( 'Total Links', 'safe-redirect-masker' ); ?></div>
    </div>
    <div class="srm-stat-card">
      <span class="srm-stat-icon">👤</span>
      <div class="srm-stat-value" id="srm-stat-clicks">…</div>
      <div class="srm-stat-label"><?php _e( 'Human Clicks', 'safe-redirect-masker' ); ?></div>
    </div>
    <div class="srm-stat-card">
      <span class="srm-stat-icon">🤖</span>
      <div class="srm-stat-value" id="srm-stat-bots">…</div>
      <div class="srm-stat-label"><?php _e( 'Bot Redirects', 'safe-redirect-masker' ); ?></div>
    </div>
    <div class="srm-stat-card">
      <span class="srm-stat-icon">🏆</span>
      <div class="srm-stat-value" id="srm-stat-top" style="font-size:1rem;font-family:'JetBrains Mono',monospace;">–</div>
      <div class="srm-stat-label"><?php _e( 'Top Link Slug', 'safe-redirect-masker' ); ?></div>
    </div>
  </div>

  <!-- ── Tab Nav ────────────────────────────────────────────────── -->
  <div class="srm-tab-nav">
    <button class="srm-tab-btn active" data-tab="links">
      🔗 <?php _e( 'Links', 'safe-redirect-masker' ); ?>
    </button>
    <button class="srm-tab-btn" data-tab="settings">
      ⚙️ <?php _e( 'Settings', 'safe-redirect-masker' ); ?>
    </button>
    <button class="srm-tab-btn" data-tab="help">
      📖 <?php _e( 'Help', 'safe-redirect-masker' ); ?>
    </button>
  </div>

  <!-- ════════════════════════════════════════════════════════════ -->
  <!--  LINKS PANEL                                                -->
  <!-- ════════════════════════════════════════════════════════════ -->
  <div class="srm-tab-panel" data-panel="links">

    <div class="srm-panel">
      <div class="srm-panel-header">
        <span class="srm-panel-title">🔗 <?php _e( 'Masked Links', 'safe-redirect-masker' ); ?></span>
        <div class="srm-toolbar">
          <div class="srm-search-wrap">
            <span class="srm-search-icon">🔍</span>
            <input id="srm-search-input" class="srm-search-input" type="text"
                   placeholder="<?php esc_attr_e( 'Search slug, URL, title…', 'safe-redirect-masker' ); ?>">
          </div>
          <button id="srm-add-link-btn" class="srm-btn-primary">
            ＋ <?php _e( 'Add Link', 'safe-redirect-masker' ); ?>
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="srm-table-wrap">
        <table class="srm-table">
          <thead>
            <tr>
              <th><?php _e( 'Slug', 'safe-redirect-masker' ); ?></th>
              <th><?php _e( 'Masked URL', 'safe-redirect-masker' ); ?></th>
              <th><?php _e( 'Real URL', 'safe-redirect-masker' ); ?></th>
              <th><?php _e( 'Clicks', 'safe-redirect-masker' ); ?></th>
              <th><?php _e( 'Timer', 'safe-redirect-masker' ); ?></th>
              <th><?php _e( 'Actions', 'safe-redirect-masker' ); ?></th>
            </tr>
          </thead>
          <tbody id="srm-links-tbody">
            <tr>
              <td colspan="6" style="text-align:center;padding:28px;">
                <span class="srm-spinner"></span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div id="srm-pagination" class="srm-pagination"></div>
    </div>

  </div><!-- /links panel -->

  <!-- ════════════════════════════════════════════════════════════ -->
  <!--  SETTINGS PANEL                                             -->
  <!-- ════════════════════════════════════════════════════════════ -->
  <div class="srm-tab-panel" data-panel="settings" style="display:none;">

    <form id="srm-settings-form">

      <!-- General -->
      <div class="srm-settings-section">
        <div class="srm-settings-section-title">
          🌐 <?php _e( 'General Settings', 'safe-redirect-masker' ); ?>
        </div>

        <div class="srm-form-group">
          <label class="srm-form-label">
            <?php _e( 'URL Prefix', 'safe-redirect-masker' ); ?>
            <span class="req">*</span>
          </label>
          <input type="text" name="url_prefix" class="srm-form-input"
                 value="<?php echo $prefix; ?>" placeholder="go">
          <div class="srm-form-hint">
            <?php printf( __( 'Masked links will be: %s<strong>{prefix}/{slug}</strong>', 'safe-redirect-masker' ), '<code>' . $base_url . '</code>' ); ?>
          </div>
        </div>

        <div class="srm-form-group">
          <label class="srm-form-label"><?php _e( 'Default Countdown Timer (seconds)', 'safe-redirect-masker' ); ?></label>
          <div class="srm-number-field">
            <input type="number" name="timer" class="srm-form-input"
                   value="<?php echo esc_attr( $settings['timer'] ); ?>" min="1" max="60" style="max-width:120px;">
            <span class="unit"><?php _e( 'seconds', 'safe-redirect-masker' ); ?></span>
          </div>
          <div class="srm-form-hint"><?php _e( 'Per-link timers override this default.', 'safe-redirect-masker' ); ?></div>
        </div>
      </div>

      <!-- Anti-Bomb -->
      <div class="srm-settings-section">
        <div class="srm-settings-section-title">
          🚫 <?php _e( 'Anti-Bomb Click Protection', 'safe-redirect-masker' ); ?>
        </div>
        <p style="font-size:.83rem;color:var(--muted);margin-bottom:16px;">
          <?php _e( 'Rate-limits real user (human) clicks per IP. If an IP exceeds the limit within the time window they are locked out for the lockout duration. Bot clicks are not subject to this — they are handled separately.', 'safe-redirect-masker' ); ?>
        </p>

        <div class="srm-form-row">
          <div class="srm-form-group">
            <label class="srm-form-label"><?php _e( 'Max Clicks per IP', 'safe-redirect-masker' ); ?></label>
            <div class="srm-number-field">
              <input type="number" name="anti_bomb_limit" class="srm-form-input"
                     value="<?php echo esc_attr( $settings['anti_bomb_limit'] ); ?>" min="1" max="100" style="max-width:120px;">
              <span class="unit"><?php _e( 'clicks', 'safe-redirect-masker' ); ?></span>
            </div>
          </div>
          <div class="srm-form-group">
            <label class="srm-form-label"><?php _e( 'Rate Window', 'safe-redirect-masker' ); ?></label>
            <div class="srm-number-field">
              <input type="number" name="anti_bomb_window" class="srm-form-input"
                     value="<?php echo esc_attr( $settings['anti_bomb_window'] ); ?>" min="10" max="3600" style="max-width:120px;">
              <span class="unit"><?php _e( 'seconds', 'safe-redirect-masker' ); ?></span>
            </div>
          </div>
        </div>

        <div class="srm-form-group">
          <label class="srm-form-label"><?php _e( 'Lockout Duration', 'safe-redirect-masker' ); ?></label>
          <div class="srm-number-field">
            <input type="number" name="bomb_lockout" class="srm-form-input"
                   value="<?php echo esc_attr( $settings['bomb_lockout'] ); ?>" min="30" max="86400" style="max-width:120px;">
            <span class="unit"><?php _e( 'seconds', 'safe-redirect-masker' ); ?></span>
          </div>
          <div class="srm-form-hint">
            <?php printf( __( 'Default: 5 clicks per 60 sec → lockout 300 s (5 min). Current: %d clicks / %ds → lockout %ds.', 'safe-redirect-masker' ),
              $settings['anti_bomb_limit'], $settings['anti_bomb_window'], $settings['bomb_lockout'] ); ?>
          </div>
        </div>
      </div>

      <!-- Ad Injection -->
      <div class="srm-settings-section">
        <div class="srm-settings-section-title">
          💰 <?php _e( 'Ad / Script Injection', 'safe-redirect-masker' ); ?>
        </div>
        <div class="srm-form-group">
          <label class="srm-form-label"><?php _e( 'Custom &lt;head&gt; HTML (AdSense, meta tags…)', 'safe-redirect-masker' ); ?></label>
          <textarea name="ads_head" class="srm-form-textarea" rows="5"
                    placeholder="<!-- paste AdSense auto-ads script here -->"><?php echo esc_textarea( $settings['ads_head'] ); ?></textarea>
          <div class="srm-form-hint"><?php _e( 'Injected inside &lt;head&gt; of every safelink page (human visitors only).', 'safe-redirect-masker' ); ?></div>
        </div>
        <div class="srm-form-group">
          <label class="srm-form-label"><?php _e( 'Custom &lt;/body&gt; HTML', 'safe-redirect-masker' ); ?></label>
          <textarea name="ads_foot" class="srm-form-textarea" rows="4"
                    placeholder="<!-- analytics, chat widgets, etc. -->"><?php echo esc_textarea( $settings['ads_foot'] ); ?></textarea>
        </div>
      </div>

      <div style="text-align:right;">
        <button type="submit" class="srm-btn-primary" style="padding:12px 28px;font-size:.95rem;">
          💾 <?php _e( 'Save Settings', 'safe-redirect-masker' ); ?>
        </button>
      </div>

    </form>
  </div><!-- /settings panel -->

  <!-- ════════════════════════════════════════════════════════════ -->
  <!--  HELP PANEL                                                 -->
  <!-- ════════════════════════════════════════════════════════════ -->
  <div class="srm-tab-panel" data-panel="help" style="display:none;">
    <div class="srm-panel">
      <div class="srm-panel-header">
        <span class="srm-panel-title">📖 <?php _e( 'How It Works', 'safe-redirect-masker' ); ?></span>
      </div>
      <div class="srm-panel-body">

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:18px;margin-bottom:24px;">

          <div style="background:var(--surface2);border:1px solid var(--border);border-radius:14px;padding:20px;">
            <div style="font-size:28px;margin-bottom:12px;">🔗</div>
            <h3 style="font-size:.95rem;font-weight:700;color:var(--text);margin-bottom:8px;"><?php _e( 'URL Masking', 'safe-redirect-masker' ); ?></h3>
            <p style="font-size:.83rem;color:var(--muted);line-height:1.65;"><?php printf( __( 'Visitors access <code>%s{prefix}/{slug}</code>. The real destination URL is hidden from them and only revealed after the countdown.', 'safe-redirect-masker' ), esc_html( home_url('/') ) ); ?></p>
          </div>

          <div style="background:var(--surface2);border:1px solid var(--border);border-radius:14px;padding:20px;">
            <div style="font-size:28px;margin-bottom:12px;">🤖</div>
            <h3 style="font-size:.95rem;font-weight:700;color:var(--text);margin-bottom:8px;"><?php _e( 'Bot Detection', 'safe-redirect-masker' ); ?></h3>
            <p style="font-size:.83rem;color:var(--muted);line-height:1.65;"><?php _e( 'Crawlers (Googlebot, Bing, etc.) are detected via User-Agent patterns, missing browser headers, and Sec-Fetch metadata. They are immediately 301-redirected to the real article URL so search engines index the correct content.', 'safe-redirect-masker' ); ?></p>
          </div>

          <div style="background:var(--surface2);border:1px solid var(--border);border-radius:14px;padding:20px;">
            <div style="font-size:28px;margin-bottom:12px;">👤</div>
            <h3 style="font-size:.95rem;font-weight:700;color:var(--text);margin-bottom:8px;"><?php _e( 'Human User Flow', 'safe-redirect-masker' ); ?></h3>
            <p style="font-size:.83rem;color:var(--muted);line-height:1.65;"><?php _e( 'Real users see a cloaking article page with a countdown timer. After the timer they are automatically redirected to the real URL. AdSense ads can be injected to monetise the wait time.', 'safe-redirect-masker' ); ?></p>
          </div>

          <div style="background:var(--surface2);border:1px solid var(--border);border-radius:14px;padding:20px;">
            <div style="font-size:28px;margin-bottom:12px;">🚫</div>
            <h3 style="font-size:.95rem;font-weight:700;color:var(--text);margin-bottom:8px;"><?php _e( 'Anti-Bomb Click', 'safe-redirect-masker' ); ?></h3>
            <p style="font-size:.83rem;color:var(--muted);line-height:1.65;"><?php _e( 'Protects against click-flooding / click fraud. Each IP is rate-limited: after N clicks in a window, the IP is locked out for a configurable duration. A friendly block page is shown.', 'safe-redirect-masker' ); ?></p>
          </div>

        </div>

        <div style="background:var(--surface2);border:1px solid var(--border);border-left:4px solid var(--primary);border-radius:14px;padding:20px;">
          <h3 style="font-size:.95rem;font-weight:700;color:var(--accent);margin-bottom:10px;">🚀 <?php _e( 'Quick Start', 'safe-redirect-masker' ); ?></h3>
          <ol style="font-size:.85rem;color:var(--muted);line-height:2;padding-left:20px;">
            <li><?php _e( 'Go to <strong>Settings</strong> tab and choose your URL prefix (default: <code>go</code>).', 'safe-redirect-masker' ); ?></li>
            <li><?php _e( 'Click <strong>Add Link</strong>, fill in the Real URL (the article), an optional slug, and a countdown timer.', 'safe-redirect-masker' ); ?></li>
            <li><?php _e( 'Optionally add article content — this is what Googlebot sees, and what real users read while waiting for the countdown.', 'safe-redirect-masker' ); ?></li>
            <li><?php _e( 'Share the <strong>Masked URL</strong> (e.g. <code>https://yoursite.com/go/my-slug</code>).', 'safe-redirect-masker' ); ?></li>
            <li><?php _e( 'Paste your AdSense auto-ads script in Settings → Ad Injection → &lt;head&gt; to monetise the countdown page.', 'safe-redirect-masker' ); ?></li>
          </ol>
        </div>

      </div>
    </div>
  </div><!-- /help panel -->

  <!-- ═══════════════════════════════════════════════════════════ -->
  <!--  LINK EDITOR MODAL                                         -->
  <!-- ═══════════════════════════════════════════════════════════ -->
  <div id="srm-modal-overlay" class="srm-modal-overlay">
    <div class="srm-modal">
      <div class="srm-modal-header">
        <span class="srm-modal-title" id="srm-modal-title">➕ <?php _e( 'Add New Link', 'safe-redirect-masker' ); ?></span>
        <button class="srm-modal-close" id="srm-modal-close" aria-label="Close">✕</button>
      </div>
      <form id="srm-link-form" autocomplete="off">
        <div class="srm-modal-body">

          <input type="hidden" name="id" value="0">

          <div class="srm-form-group">
            <label class="srm-form-label"><?php _e( 'Real URL', 'safe-redirect-masker' ); ?> <span class="req">*</span></label>
            <input type="url" name="real_url" class="srm-form-input"
                   placeholder="https://yourblog.com/your-article/" required>
            <div class="srm-form-hint"><?php _e( 'The actual destination. Bots are redirected here directly; humans see the cloaking page.', 'safe-redirect-masker' ); ?></div>
          </div>

          <div class="srm-form-group">
            <label class="srm-form-label"><?php _e( 'Slug', 'safe-redirect-masker' ); ?></label>
            <div class="srm-slug-preview-wrap">
              <span class="srm-slug-prefix" id="srm-slug-display-prefix"><?php echo $base_url . $prefix . '/'; ?></span>
              <input type="text" name="slug" class="srm-slug-prefix-input"
                     placeholder="<?php esc_attr_e( 'auto-generated', 'safe-redirect-masker' ); ?>">
            </div>
            <div class="srm-form-hint"><?php _e( 'Leave blank to auto-generate from the real URL. Allowed: letters, numbers, hyphens, underscores, slashes.', 'safe-redirect-masker' ); ?></div>
          </div>

          <div class="srm-form-row">
            <div class="srm-form-group">
              <label class="srm-form-label"><?php _e( 'Countdown Timer', 'safe-redirect-masker' ); ?></label>
              <div class="srm-number-field">
                <input type="number" name="timer" class="srm-form-input"
                       value="<?php echo esc_attr( $settings['timer'] ); ?>" min="1" max="120" style="max-width:100px;">
                <span class="unit"><?php _e( 'seconds', 'safe-redirect-masker' ); ?></span>
              </div>
            </div>
            <div class="srm-form-group">
              <label class="srm-form-label"><?php _e( 'Article Image URL', 'safe-redirect-masker' ); ?></label>
              <input type="url" name="article_image" class="srm-form-input"
                     placeholder="https://…/image.jpg">
            </div>
          </div>

          <div class="srm-form-group">
            <label class="srm-form-label"><?php _e( 'Article Title', 'safe-redirect-masker' ); ?></label>
            <input type="text" name="article_title" class="srm-form-input"
                   placeholder="<?php esc_attr_e( 'Appears as the page title and article heading', 'safe-redirect-masker' ); ?>">
          </div>

          <div class="srm-form-group">
            <label class="srm-form-label"><?php _e( 'Article Content (HTML allowed)', 'safe-redirect-masker' ); ?></label>
            <textarea name="article_content" class="srm-form-textarea" rows="6"
                      placeholder="<p>Write article content here. This is what users (and bots) see on the cloaking page.</p>"></textarea>
            <div class="srm-form-hint"><?php _e( 'Supports basic HTML: p, h2, h3, ul, ol, li, strong, em, a.', 'safe-redirect-masker' ); ?></div>
          </div>

        </div>
        <div class="srm-modal-footer">
          <button type="button" class="srm-btn-secondary" id="srm-modal-cancel"><?php _e( 'Cancel', 'safe-redirect-masker' ); ?></button>
          <button type="submit" class="srm-btn-primary" id="srm-link-save-btn">
            💾 <?php _e( 'Save Link', 'safe-redirect-masker' ); ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════════ -->
  <!--  DELETE CONFIRM MODAL                                      -->
  <!-- ═══════════════════════════════════════════════════════════ -->
  <div id="srm-confirm-overlay" class="srm-modal-overlay">
    <div class="srm-modal" style="max-width:380px;">
      <div class="srm-modal-header">
        <span class="srm-modal-title">⚠️ <?php _e( 'Confirm Delete', 'safe-redirect-masker' ); ?></span>
        <button class="srm-modal-close" id="srm-confirm-cancel-x" aria-label="Close">✕</button>
      </div>
      <div class="srm-modal-body">
        <p style="color:var(--muted);font-size:.9rem;line-height:1.6;">
          <?php _e( 'Are you sure you want to delete this link? This action cannot be undone. All click history for this link will also be removed.', 'safe-redirect-masker' ); ?>
        </p>
      </div>
      <div class="srm-modal-footer">
        <button type="button" class="srm-btn-secondary" id="srm-confirm-cancel"><?php _e( 'Cancel', 'safe-redirect-masker' ); ?></button>
        <button type="button" class="srm-btn-danger" id="srm-confirm-ok" style="background:var(--danger);color:#fff;">
          🗑 <?php _e( 'Delete', 'safe-redirect-masker' ); ?>
        </button>
      </div>
    </div>
  </div>

</div><!-- /#srm-app -->
