<?php
/**
 * Admin Dashboard View — Safe Redirect Masker
 *
 * @package SafeRedirectMasker
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$settings    = SRM_DB::get_settings();
$prefix      = esc_html( $settings['url_prefix'] );
$base_url    = esc_url( home_url( '/' ) );
$site_domain = wp_parse_url( home_url(), PHP_URL_HOST );
?>

<div class="srm-wrap" id="srm-app">

  <!-- Toast Container -->
  <div id="srm-toast-container" class="srm-toast-container"></div>

  <!-- ── TOP NAVBAR ─────────────────────────────────────────────── -->
  <header class="srm-navbar">
    <div class="srm-brand">
      <div class="srm-brand-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          <path d="m9 12 2 2 4-4"/>
        </svg>
      </div>
      <div class="srm-brand-info">
        <div class="srm-brand-title-row">
          <h1 class="srm-brand-title"><?php _e( 'Safe Redirect Masker', 'safe-redirect-masker' ); ?></h1>
          <span class="srm-brand-badge">PRO SHIELD</span>
          <span class="srm-version-pill">v<?php echo SRM_VERSION; ?></span>
        </div>
        <p class="srm-brand-subtitle">
          <span><?php echo esc_html( $site_domain ); ?></span>
          <span class="srm-dot-separator">•</span>
          <span class="srm-status-text">
            <span class="srm-pulse-dot"></span>
            <?php _e( 'Bot Detection & Anti-Bomb Active', 'safe-redirect-masker' ); ?>
          </span>
        </p>
      </div>
    </div>

    <!-- Quick action button -->
    <div class="srm-nav-actions">
      <button type="button" class="srm-btn srm-btn-primary" id="srm-top-add-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
        <span><?php _e( 'Create Masked Link', 'safe-redirect-masker' ); ?></span>
      </button>
    </div>
  </header>

  <div class="srm-container">

    <!-- ── STATS ROW ──────────────────────────────────────────────── -->
    <section class="srm-stats-grid">
      <div class="srm-stat-card srm-stat-card-links">
        <div class="srm-stat-header">
          <span class="srm-stat-label"><?php _e( 'Total Masked Links', 'safe-redirect-masker' ); ?></span>
          <div class="srm-stat-icon-wrap srm-icon-indigo">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
          </div>
        </div>
        <div class="srm-stat-value" id="srm-stat-links">0</div>
        <div class="srm-stat-footer">
          <span class="srm-stat-sub">Prefix: <code>/<?php echo $prefix; ?>/</code></span>
        </div>
      </div>

      <div class="srm-stat-card srm-stat-card-human">
        <div class="srm-stat-header">
          <span class="srm-stat-label"><?php _e( 'Human Visitors', 'safe-redirect-masker' ); ?></span>
          <div class="srm-stat-icon-wrap srm-icon-emerald">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </div>
        </div>
        <div class="srm-stat-value" id="srm-stat-clicks">0</div>
        <div class="srm-stat-footer">
          <span class="srm-badge-pill srm-badge-human">Cloaked & Monetized</span>
        </div>
      </div>

      <div class="srm-stat-card srm-stat-card-bot">
        <div class="srm-stat-header">
          <span class="srm-stat-label"><?php _e( 'Bot Crawlers Filtered', 'safe-redirect-masker' ); ?></span>
          <div class="srm-stat-icon-wrap srm-icon-ruby">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><circle cx="15.5" cy="8.5" r="1.5"/><path d="M9 15h6"/><path d="M12 3V1"/></svg>
          </div>
        </div>
        <div class="srm-stat-value" id="srm-stat-bots">0</div>
        <div class="srm-stat-footer">
          <span class="srm-badge-pill srm-badge-bot">301 Direct Redirect</span>
        </div>
      </div>

      <div class="srm-stat-card srm-stat-card-top">
        <div class="srm-stat-header">
          <span class="srm-stat-label"><?php _e( 'Top Performing Slug', 'safe-redirect-masker' ); ?></span>
          <div class="srm-stat-icon-wrap srm-icon-amber">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>
          </div>
        </div>
        <div class="srm-stat-value srm-stat-mono" id="srm-stat-top">–</div>
        <div class="srm-stat-footer">
          <span class="srm-stat-sub" id="srm-stat-top-clicks">Highest traffic link</span>
        </div>
      </div>
    </section>

    <!-- ── NAVIGATION TABS ────────────────────────────────────────── -->
    <nav class="srm-tabs-bar">
      <button type="button" class="srm-tab-item active" data-tab="links">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        <span><?php _e( 'Masked Links', 'safe-redirect-masker' ); ?></span>
        <span class="srm-tab-count" id="srm-tab-link-count">0</span>
      </button>

      <button type="button" class="srm-tab-item" data-tab="simulator">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m14 12-4-4 4-4"/><path d="M4 12h16"/><path d="M14 20l4-4-4-4"/></svg>
        <span><?php _e( 'Crawler Simulator', 'safe-redirect-masker' ); ?></span>
        <span class="srm-tab-tag">TEST</span>
      </button>

      <button type="button" class="srm-tab-item" data-tab="settings">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
        <span><?php _e( 'Protection & Settings', 'safe-redirect-masker' ); ?></span>
      </button>

      <button type="button" class="srm-tab-item" data-tab="help">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <span><?php _e( 'Documentation', 'safe-redirect-masker' ); ?></span>
      </button>
    </nav>

    <!-- ════════════════════════════════════════════════════════════ -->
    <!--  PANEL 1: LINKS MANAGER                                     -->
    <!-- ════════════════════════════════════════════════════════════ -->
    <div class="srm-page-panel active" data-panel="links">
      <div class="srm-card">
        
        <!-- Toolbar -->
        <div class="srm-card-toolbar">
          <div class="srm-toolbar-left">
            <div class="srm-search-box">
              <svg class="srm-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" id="srm-search-input" placeholder="<?php esc_attr_e( 'Search by slug, destination URL, or article title…', 'safe-redirect-masker' ); ?>" autocomplete="off">
              <button type="button" class="srm-search-clear" id="srm-search-clear" style="display:none;" title="Clear search">✕</button>
            </div>
            <button type="button" class="srm-btn srm-btn-ghost" id="srm-refresh-btn" title="Refresh links">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
              <span><?php _e( 'Refresh', 'safe-redirect-masker' ); ?></span>
            </button>
          </div>

          <div class="srm-toolbar-right">
            <button type="button" class="srm-btn srm-btn-primary" id="srm-add-link-btn">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
              <span><?php _e( 'Add New Link', 'safe-redirect-masker' ); ?></span>
            </button>
          </div>
        </div>

        <!-- Table Container -->
        <div class="srm-table-container">
          <table class="srm-table">
            <thead>
              <tr>
                <th style="width:20%;"><?php _e( 'Masked Slug', 'safe-redirect-masker' ); ?></th>
                <th style="width:28%;"><?php _e( 'Masked Link (Shareable)', 'safe-redirect-masker' ); ?></th>
                <th style="width:24%;"><?php _e( 'Real Destination (Target)', 'safe-redirect-masker' ); ?></th>
                <th style="width:14%;"><?php _e( 'Traffic Breakdown', 'safe-redirect-masker' ); ?></th>
                <th style="width:8%;"><?php _e( 'Timer', 'safe-redirect-masker' ); ?></th>
                <th style="width:6%; text-align:right;"><?php _e( 'Actions', 'safe-redirect-masker' ); ?></th>
              </tr>
            </thead>
            <tbody id="srm-links-tbody">
              <tr>
                <td colspan="6" class="srm-loading-cell">
                  <div class="srm-spinner-wrap">
                    <span class="srm-spinner"></span>
                    <span style="font-size:.85rem;color:var(--srm-text-muted);"><?php _e( 'Loading masked links…', 'safe-redirect-masker' ); ?></span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="srm-card-footer" id="srm-pagination-wrap">
          <div class="srm-pagination-info" id="srm-pagination-info"><?php _e( 'Showing 0 links', 'safe-redirect-masker' ); ?></div>
          <div class="srm-pagination" id="srm-pagination"></div>
        </div>

      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════ -->
    <!--  PANEL 2: CRAWLER SIMULATOR                                 -->
    <!-- ════════════════════════════════════════════════════════════ -->
    <div class="srm-page-panel" data-panel="simulator" style="display:none;">
      <div class="srm-card">
        <div class="srm-card-header">
          <div>
            <h2 class="srm-card-title">🧪 <?php _e( 'Crawler Detection & Redirect Simulator', 'safe-redirect-masker' ); ?></h2>
            <p class="srm-card-desc"><?php _e( 'Test any masked slug to see in real-time whether our multi-layer engine detects it as a bot crawler (301 to article) or a real visitor (302 to safelink page).', 'safe-redirect-masker' ); ?></p>
          </div>
        </div>

        <div class="srm-card-body">
          <div class="srm-simulator-form">
            <div class="srm-form-grid">
              
              <div class="srm-form-group">
                <label class="srm-label"><?php _e( 'Select Masked Slug', 'safe-redirect-masker' ); ?> <span class="srm-req">*</span></label>
                <div class="srm-sim-input-row">
                  <span class="srm-input-addon"><?php echo $base_url . $prefix . '/'; ?></span>
                  <input type="text" id="srm-sim-slug" class="srm-input srm-input-mono" placeholder="my-slug">
                </div>
                <div class="srm-hint"><?php _e( 'Choose a slug you created in the Links tab.', 'safe-redirect-masker' ); ?></div>
              </div>

              <div class="srm-form-group">
                <label class="srm-label"><?php _e( 'Simulate User-Agent', 'safe-redirect-masker' ); ?></label>
                <select id="srm-sim-ua" class="srm-select">
                  <option value="googlebot">🤖 Googlebot (Google Search Crawler)</option>
                  <option value="bingbot">🤖 Bingbot (Microsoft Bing Crawler)</option>
                  <option value="facebook">🤖 Facebookexternalhit (Social Link Preview)</option>
                  <option value="curl">🤖 cURL / Python Script (Generic Scraper)</option>
                  <option value="chrome" selected>👤 Google Chrome / Edge / Firefox (Real Human User)</option>
                </select>
                <div class="srm-hint"><?php _e( 'Emulate search engines or real browser navigation.', 'safe-redirect-masker' ); ?></div>
              </div>

            </div>

            <div style="margin-top: 18px;">
              <button type="button" class="srm-btn srm-btn-primary" id="srm-sim-run-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                <span><?php _e( 'Execute Simulation Test', 'safe-redirect-masker' ); ?></span>
              </button>
            </div>
          </div>

          <!-- Simulation Result Box -->
          <div id="srm-sim-result" class="srm-sim-result" style="display:none;"></div>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════ -->
    <!--  PANEL 3: SETTINGS PANEL                                    -->
    <!-- ════════════════════════════════════════════════════════════ -->
    <div class="srm-page-panel" data-panel="settings" style="display:none;">
      <form id="srm-settings-form">
        <div class="srm-settings-grid">
          
          <!-- General Settings -->
          <div class="srm-card srm-settings-card">
            <div class="srm-card-header">
              <div>
                <h3 class="srm-card-title">🌐 <?php _e( 'URL Masking Routing', 'safe-redirect-masker' ); ?></h3>
                <p class="srm-card-desc"><?php _e( 'Configure how visitors and crawlers access your masked URLs.', 'safe-redirect-masker' ); ?></p>
              </div>
            </div>
            <div class="srm-card-body">
              <div class="srm-form-group">
                <label class="srm-label"><?php _e( 'URL Prefix', 'safe-redirect-masker' ); ?> <span class="srm-req">*</span></label>
                <div class="srm-sim-input-row" style="max-width:360px;">
                  <span class="srm-input-addon"><?php echo $base_url; ?></span>
                  <input type="text" name="url_prefix" class="srm-input srm-input-mono" value="<?php echo $prefix; ?>" placeholder="go" required>
                  <span class="srm-input-addon">/slug</span>
                </div>
                <div class="srm-hint"><?php printf( __( 'Default is <code>go</code>. Links will appear as <code>%s<strong>%s</strong>/your-slug</code>.', 'safe-redirect-masker' ), $base_url, $prefix ); ?></div>
              </div>

              <div class="srm-form-group">
                <label class="srm-label"><?php _e( 'Default Countdown Timer', 'safe-redirect-masker' ); ?></label>
                <div class="srm-number-group">
                  <input type="number" name="timer" class="srm-input" value="<?php echo esc_attr( $settings['timer'] ); ?>" min="1" max="120" style="max-width:120px;">
                  <span class="srm-unit"><?php _e( 'seconds', 'safe-redirect-masker' ); ?></span>
                </div>
                <div class="srm-hint"><?php _e( 'Duration real human users view the safelink cloaking page before redirection.', 'safe-redirect-masker' ); ?></div>
              </div>
            </div>
          </div>

          <!-- Anti-Bomb Click Security -->
          <div class="srm-card srm-settings-card">
            <div class="srm-card-header">
              <div>
                <h3 class="srm-card-title">🚫 <?php _e( 'Anti-Bomb Click Protection (IP Rate-Limiting)', 'safe-redirect-masker' ); ?></h3>
                <p class="srm-card-desc"><?php _e( 'Protects your AdSense and advertising account against click-fraud, rapid clicking, and automated bots.', 'safe-redirect-masker' ); ?></p>
              </div>
            </div>
            <div class="srm-card-body">
              <div class="srm-form-row">
                <div class="srm-form-group">
                  <label class="srm-label"><?php _e( 'Max Human Clicks per IP', 'safe-redirect-masker' ); ?></label>
                  <div class="srm-number-group">
                    <input type="number" name="anti_bomb_limit" class="srm-input" value="<?php echo esc_attr( $settings['anti_bomb_limit'] ); ?>" min="1" max="100" style="max-width:110px;">
                    <span class="srm-unit"><?php _e( 'clicks', 'safe-redirect-masker' ); ?></span>
                  </div>
                  <div class="srm-hint"><?php _e( 'Threshold before IP is locked out.', 'safe-redirect-masker' ); ?></div>
                </div>

                <div class="srm-form-group">
                  <label class="srm-label"><?php _e( 'Rate-Limit Window', 'safe-redirect-masker' ); ?></label>
                  <div class="srm-number-group">
                    <input type="number" name="anti_bomb_window" class="srm-input" value="<?php echo esc_attr( $settings['anti_bomb_window'] ); ?>" min="10" max="3600" style="max-width:110px;">
                    <span class="srm-unit"><?php _e( 'seconds', 'safe-redirect-masker' ); ?></span>
                  </div>
                  <div class="srm-hint"><?php _e( 'Time interval to count IP clicks (e.g. 60s).', 'safe-redirect-masker' ); ?></div>
                </div>
              </div>

              <div class="srm-form-group">
                <label class="srm-label"><?php _e( 'Lockout Duration', 'safe-redirect-masker' ); ?></label>
                <div class="srm-number-group">
                  <input type="number" name="bomb_lockout" class="srm-input" value="<?php echo esc_attr( $settings['bomb_lockout'] ); ?>" min="30" max="86400" style="max-width:120px;">
                  <span class="srm-unit"><?php _e( 'seconds', 'safe-redirect-masker' ); ?></span>
                </div>
                <div class="srm-hint"><?php printf( __( 'How long a blocked IP cannot access safelinks (e.g. 300s = 5 minutes). Current: %d clicks in %ds → locked for %ds.', 'safe-redirect-masker' ), $settings['anti_bomb_limit'], $settings['anti_bomb_window'], $settings['bomb_lockout'] ); ?></div>
              </div>
            </div>
          </div>

          <!-- AdSense / Ad Injection -->
          <div class="srm-card srm-settings-card" style="grid-column: 1 / -1;">
            <div class="srm-card-header">
              <div>
                <h3 class="srm-card-title">💰 <?php _e( 'AdSense & Custom Script Injection', 'safe-redirect-masker' ); ?></h3>
                <p class="srm-card-desc"><?php _e( 'Monetise the countdown wait time by inserting Google AdSense auto-ads, banners, or analytics scripts into safelink cloaking pages.', 'safe-redirect-masker' ); ?></p>
              </div>
            </div>
            <div class="srm-card-body">
              <div class="srm-form-group">
                <label class="srm-label"><?php _e( 'Header HTML Code (&lt;head&gt; injection)', 'safe-redirect-masker' ); ?></label>
                <textarea name="ads_head" class="srm-textarea srm-textarea-code" rows="4" placeholder="<!-- Paste Google AdSense <script async src=...> or meta tags here -->"><?php echo esc_textarea( $settings['ads_head'] ); ?></textarea>
                <div class="srm-hint"><?php _e( 'Executed only for real human visitors on safelink countdown pages. Never shown to search crawlers.', 'safe-redirect-masker' ); ?></div>
              </div>

              <div class="srm-form-group">
                <label class="srm-label"><?php _e( 'Footer HTML Code (&lt;/body&gt; injection)', 'safe-redirect-masker' ); ?></label>
                <textarea name="ads_foot" class="srm-textarea srm-textarea-code" rows="3" placeholder="<!-- Analytics, tracking pixels, or chat widgets -->"><?php echo esc_textarea( $settings['ads_foot'] ); ?></textarea>
              </div>

              <div class="srm-form-actions-bar">
                <button type="submit" class="srm-btn srm-btn-primary" id="srm-save-settings-btn">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                  <span><?php _e( 'Save All Settings', 'safe-redirect-masker' ); ?></span>
                </button>
              </div>
            </div>
          </div>

        </div>
      </form>
    </div>

    <!-- ════════════════════════════════════════════════════════════ -->
    <!--  PANEL 4: DOCUMENTATION & FLOW                              -->
    <!-- ════════════════════════════════════════════════════════════ -->
    <div class="srm-page-panel" data-panel="help" style="display:none;">
      <div class="srm-card">
        <div class="srm-card-header">
          <div>
            <h2 class="srm-card-title">📖 <?php _e( 'Architecture & How It Works', 'safe-redirect-masker' ); ?></h2>
            <p class="srm-card-desc"><?php _e( 'Understand the dual-path smart routing that keeps your site SEO-healthy while maximizing ad revenue safely.', 'safe-redirect-masker' ); ?></p>
          </div>
        </div>

        <div class="srm-card-body">
          
          <div class="srm-flow-grid">
            <div class="srm-flow-card">
              <div class="srm-flow-icon srm-icon-indigo">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
              </div>
              <h4 class="srm-flow-title"><?php _e( '1. URL Masking', 'safe-redirect-masker' ); ?></h4>
              <p class="srm-flow-text"><?php printf( __( 'Share short, clean links like <code>%s%s/{slug}</code>. The real destination is concealed from direct inspection until safe validation.', 'safe-redirect-masker' ), $base_url, $prefix ); ?></p>
            </div>

            <div class="srm-flow-card">
              <div class="srm-flow-icon srm-icon-ruby">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><circle cx="15.5" cy="8.5" r="1.5"/><path d="M9 15h6"/></svg>
              </div>
              <h4 class="srm-flow-title"><?php _e( '2. Bot Crawler Path', 'safe-redirect-masker' ); ?></h4>
              <p class="srm-flow-text"><?php _e( 'Googlebot, Bing, and search spiders are recognized instantly. They receive an immediate <strong>301 Moved Permanently</strong> straight to the real article, indexing your authentic content with 100% SEO safety.', 'safe-redirect-masker' ); ?></p>
            </div>

            <div class="srm-flow-card">
              <div class="srm-flow-icon srm-icon-emerald">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              </div>
              <h4 class="srm-flow-title"><?php _e( '3. Real Human Visitor Path', 'safe-redirect-masker' ); ?></h4>
              <p class="srm-flow-text"><?php _e( 'Legitimate browser visitors view a cloaking article page with a countdown timer. Once the timer completes, they are automatically forwarded to the destination article URL.', 'safe-redirect-masker' ); ?></p>
            </div>

            <div class="srm-flow-card">
              <div class="srm-flow-icon srm-icon-amber">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              </div>
              <h4 class="srm-flow-title"><?php _e( '4. Anti-Bomb Click Guard', 'safe-redirect-masker' ); ?></h4>
              <p class="srm-flow-text"><?php _e( 'If an attacker floods your links with rapid clicks, the transient rate-limiter locks their IP address for the configured lockout duration, shielding your ads from invalid traffic penalties.', 'safe-redirect-masker' ); ?></p>
            </div>
          </div>

          <!-- Quick Setup Steps -->
          <div class="srm-guide-box">
            <h4 class="srm-guide-title">🚀 <?php _e( '3-Step Quick Start Guide', 'safe-redirect-masker' ); ?></h4>
            <ol class="srm-guide-list">
              <li><?php _e( 'Click <strong>Create Masked Link</strong> above.', 'safe-redirect-masker' ); ?></li>
              <li><?php _e( 'Paste your target article URL (e.g. <code>https://yourblog.com/tips/how-to-grow</code>) and enter or auto-generate a slug.', 'safe-redirect-masker' ); ?></li>
              <li><?php _e( 'Copy your new masked URL and share it anywhere. Test it anytime using the <strong>Crawler Simulator</strong> tab!', 'safe-redirect-masker' ); ?></li>
            </ol>
          </div>

        </div>
      </div>
    </div>

  </div><!-- /.srm-container -->

  <!-- ════════════════════════════════════════════════════════════ -->
  <!--  MODAL: ADD / EDIT LINK                                     -->
  <!-- ════════════════════════════════════════════════════════════ -->
  <div id="srm-modal-overlay" class="srm-modal-overlay">
    <div class="srm-modal">
      <div class="srm-modal-header">
        <div class="srm-modal-title-wrap">
          <span class="srm-modal-icon" id="srm-modal-header-icon">🔗</span>
          <h3 class="srm-modal-title" id="srm-modal-title"><?php _e( 'Create New Masked Link', 'safe-redirect-masker' ); ?></h3>
        </div>
        <button type="button" class="srm-modal-close" id="srm-modal-close" aria-label="Close modal">✕</button>
      </div>

      <form id="srm-link-form" autocomplete="off">
        <div class="srm-modal-body">
          <input type="hidden" name="id" value="0">

          <!-- Destination URL -->
          <div class="srm-form-group">
            <label class="srm-label">
              <?php _e( 'Real Destination URL (Target Article)', 'safe-redirect-masker' ); ?>
              <span class="srm-req">*</span>
            </label>
            <div class="srm-input-wrap">
              <input type="text" name="real_url" class="srm-input srm-input-mono"
                     placeholder="https://yourblog.com/your-destination-article/" required>
            </div>
            <div class="srm-hint"><?php _e( 'Bots and search crawlers are redirected here immediately. Real users arrive here after the countdown.', 'safe-redirect-masker' ); ?></div>
          </div>

          <!-- Slug Field with Live Prefix & Auto-Generate -->
          <div class="srm-form-group">
            <div class="srm-label-row">
              <label class="srm-label"><?php _e( 'Masked Slug', 'safe-redirect-masker' ); ?></label>
              <button type="button" class="srm-auto-slug-btn" id="srm-auto-slug-btn" title="Generate from target URL">
                ⚡ <?php _e( 'Auto Generate', 'safe-redirect-masker' ); ?>
              </button>
            </div>
            <div class="srm-sim-input-row">
              <span class="srm-input-addon" id="srm-slug-display-prefix"><?php echo $base_url . $prefix . '/'; ?></span>
              <input type="text" name="slug" class="srm-input srm-input-mono"
                     placeholder="e.g. tech-review-2026">
            </div>
            <div class="srm-hint"><?php _e( 'Leave empty to auto-derive from the real destination URL. Letters, numbers, hyphens allowed.', 'safe-redirect-masker' ); ?></div>
          </div>

          <!-- Timer & Image Row -->
          <div class="srm-form-row">
            <div class="srm-form-group">
              <label class="srm-label"><?php _e( 'Countdown Timer', 'safe-redirect-masker' ); ?></label>
              <div class="srm-number-group">
                <input type="number" name="timer" class="srm-input"
                       value="<?php echo esc_attr( $settings['timer'] ); ?>" min="1" max="120" style="max-width:100px;">
                <span class="srm-unit"><?php _e( 'seconds', 'safe-redirect-masker' ); ?></span>
              </div>
              <div class="srm-timer-presets">
                <button type="button" class="srm-preset-btn" data-sec="3">3s</button>
                <button type="button" class="srm-preset-btn" data-sec="5">5s</button>
                <button type="button" class="srm-preset-btn" data-sec="10">10s</button>
                <button type="button" class="srm-preset-btn" data-sec="15">15s</button>
              </div>
            </div>

            <div class="srm-form-group">
              <label class="srm-label"><?php _e( 'Featured Image URL (Optional)', 'safe-redirect-masker' ); ?></label>
              <input type="text" name="article_image" class="srm-input"
                     placeholder="https://yourblog.com/wp-content/uploads/cover.jpg">
              <div class="srm-hint"><?php _e( 'Displayed at the top of the safelink cloaking page.', 'safe-redirect-masker' ); ?></div>
            </div>
          </div>

          <!-- Cloaking Article Details (Collapsible) -->
          <div class="srm-collapse-wrap">
            <button type="button" class="srm-collapse-toggle" id="srm-collapse-toggle">
              <span>📝 <?php _e( 'Cloaking Article Content (Optional)', 'safe-redirect-masker' ); ?></span>
              <svg class="srm-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div class="srm-collapse-body" id="srm-collapse-body" style="display:none;">
              <div class="srm-form-group" style="margin-top:14px;">
                <label class="srm-label"><?php _e( 'Article Title', 'safe-redirect-masker' ); ?></label>
                <input type="text" name="article_title" class="srm-input"
                       placeholder="e.g. The Complete Guide to High-Performance Cloud Architecture">
              </div>

              <div class="srm-form-group">
                <label class="srm-label"><?php _e( 'Article Content (HTML allowed)', 'safe-redirect-masker' ); ?></label>
                <textarea name="article_content" class="srm-textarea" rows="5"
                          placeholder="<p>Write or paste your article teaser here. This is displayed on the safelink page while users wait for the countdown.</p>"></textarea>
                <div class="srm-hint"><?php _e( 'Basic HTML supported: &lt;p&gt;, &lt;h2&gt;, &lt;h3&gt;, &lt;strong&gt;, &lt;a&gt;, &lt;ul&gt;, &lt;li&gt;.', 'safe-redirect-masker' ); ?></div>
              </div>
            </div>
          </div>

        </div>

        <div class="srm-modal-footer">
          <button type="button" class="srm-btn srm-btn-secondary" id="srm-modal-cancel"><?php _e( 'Cancel', 'safe-redirect-masker' ); ?></button>
          <button type="submit" class="srm-btn srm-btn-primary" id="srm-link-save-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            <span id="srm-save-btn-text"><?php _e( 'Save Masked Link', 'safe-redirect-masker' ); ?></span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ════════════════════════════════════════════════════════════ -->
  <!--  MODAL: DELETE CONFIRMATION                                 -->
  <!-- ════════════════════════════════════════════════════════════ -->
  <div id="srm-confirm-overlay" class="srm-modal-overlay">
    <div class="srm-modal srm-modal-sm">
      <div class="srm-modal-header srm-modal-danger-header">
        <div class="srm-modal-title-wrap">
          <span class="srm-modal-icon">⚠️</span>
          <h3 class="srm-modal-title"><?php _e( 'Delete Masked Link?', 'safe-redirect-masker' ); ?></h3>
        </div>
        <button type="button" class="srm-modal-close" id="srm-confirm-cancel-x">✕</button>
      </div>
      <div class="srm-modal-body">
        <p style="color:var(--srm-text-muted); font-size:.9rem; line-height:1.65; margin:0;">
          <?php _e( 'Are you sure you want to permanently delete this masked link? Visitors requesting this slug will receive a 404 error and all associated click analytics will be removed.', 'safe-redirect-masker' ); ?>
        </p>
      </div>
      <div class="srm-modal-footer">
        <button type="button" class="srm-btn srm-btn-secondary" id="srm-confirm-cancel"><?php _e( 'Keep Link', 'safe-redirect-masker' ); ?></button>
        <button type="button" class="srm-btn srm-btn-danger" id="srm-confirm-ok">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
          <span><?php _e( 'Delete Permanently', 'safe-redirect-masker' ); ?></span>
        </button>
      </div>
    </div>
  </div>

</div><!-- /#srm-app -->
