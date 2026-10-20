/**
 * Safe Redirect Masker — Admin Dashboard Script
 * Pure Vanilla JS, zero dependencies, bulletproof error handling.
 */
(function () {
  'use strict';

  /* ── State ─────────────────────────────────────────────────── */
  var state = {
    tab:        'links',
    links:      [],
    page:       1,
    totalPages: 1,
    total:      0,
    search:     '',
    stats:      {},
    settings:   {},
    editLink:   null,
    loading:    false,
  };

  /* ── DOM Selectors ─────────────────────────────────────────── */
  var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); };

  /* ── Robust AJAX Helper ────────────────────────────────────── */
  function ajax(action, data, cb, errCb) {
    var body = new FormData();
    body.append('action', action);
    body.append('nonce',  (window.srm_data && window.srm_data.nonce) ? window.srm_data.nonce : '');

    if (data) {
      Object.keys(data).forEach(function (k) {
        if (data[k] !== undefined && data[k] !== null) {
          body.append(k, data[k]);
        }
      });
    }

    var ajaxUrl = (window.srm_data && window.srm_data.ajax_url) ? window.srm_data.ajax_url : '/wp-admin/admin-ajax.php';

    fetch(ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: body
    })
      .then(function (res) {
        return res.text().then(function (text) {
          try {
            return JSON.parse(text);
          } catch (e) {
            console.error('Non-JSON response received:', text);
            throw new Error('Server returned unexpected output. Check server logs.');
          }
        });
      })
      .then(function (json) {
        if (cb) cb(json);
      })
      .catch(function (err) {
        console.error('AJAX Error:', err);
        toast('Error: ' + err.message, 'error');
        if (errCb) errCb(err);
      });
  }

  /* ── Toast Notifications ───────────────────────────────────── */
  function toast(msg, type) {
    type = type || 'info';
    var icons = {
      success: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>',
      error:   '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
      info:    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
    };

    var container = $('#srm-toast-container');
    if (!container) return;

    var el = document.createElement('div');
    el.className = 'srm-toast srm-toast-' + type;
    el.innerHTML = '<span style="display:flex;align-items:center;">' + (icons[type] || '') + '</span><span>' + escHtml(msg) + '</span>';
    container.appendChild(el);

    setTimeout(function () { el.classList.add('show'); }, 10);
    setTimeout(function () {
      el.classList.remove('show');
      setTimeout(function () { el.remove(); }, 350);
    }, 3800);
  }

  /* ── Utilities ─────────────────────────────────────────────── */
  function escHtml(s) {
    return String(s || '')
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }

  function numFmt(n) {
    n = parseInt(n, 10) || 0;
    return n >= 1000 ? (n / 1000).toFixed(1) + 'K' : String(n);
  }

  function slugify(str) {
    return String(str || '')
      .toLowerCase()
      .replace(/https?:\/\//i, '')
      .replace(/[^a-z0-9\-_/]/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '');
  }

  function getBasePrefix() {
    var base = (window.srm_data && window.srm_data.base_url) ? window.srm_data.base_url : '/';
    var prefix = (state.settings && state.settings.url_prefix) ? state.settings.url_prefix : ((window.srm_data && window.srm_data.prefix) || 'go');
    return base + (prefix ? prefix + '/' : '');
  }

  /* ── Tab Switching ─────────────────────────────────────────── */
  function activateTab(tab) {
    state.tab = tab;

    $$('.srm-tab-item').forEach(function (btn) {
      btn.classList.toggle('active', btn.dataset.tab === tab);
    });

    $$('.srm-page-panel').forEach(function (panel) {
      var isTarget = panel.dataset.panel === tab;
      panel.style.display = isTarget ? '' : 'none';
      panel.classList.toggle('active', isTarget);
    });

    if (tab === 'links')    loadLinks();
    if (tab === 'settings') loadSettings();
  }

  /* ── Stats ─────────────────────────────────────────────────── */
  function loadStats() {
    ajax('srm_get_stats', {}, function (res) {
      if (!res || !res.success) return;
      var s = res.data;
      state.stats = s;

      setVal('#srm-stat-links',  numFmt(s.total_links));
      setVal('#srm-tab-link-count', String(s.total_links || 0));
      setVal('#srm-stat-clicks', numFmt(s.total_clicks));
      setVal('#srm-stat-bots',   numFmt(s.total_bots));

      var topEl = $('#srm-stat-top');
      var topSub = $('#srm-stat-top-clicks');
      if (s.top_link && s.top_link.slug) {
        if (topEl) topEl.textContent = '/' + s.top_link.slug;
        if (topSub) topSub.textContent = numFmt(s.top_link.clicks) + ' human clicks';
      } else {
        if (topEl) topEl.textContent = '–';
        if (topSub) topSub.textContent = 'No traffic logged yet';
      }
    });
  }

  function setVal(sel, val) {
    var el = $(sel);
    if (el) el.textContent = val;
  }

  /* ── Links Management ──────────────────────────────────────── */
  function loadLinks() {
    if (state.loading) return;
    state.loading = true;

    var tbody = $('#srm-links-tbody');
    if (tbody) {
      tbody.innerHTML = '<tr><td colspan="6" class="srm-loading-cell"><div class="srm-spinner-wrap"><span class="srm-spinner"></span><span style="font-size:.85rem;color:var(--srm-text-muted);">Loading masked links…</span></div></td></tr>';
    }

    ajax('srm_get_links', { search: state.search, page: state.page }, function (res) {
      state.loading = false;
      if (!res || !res.success) {
        toast('Failed to load links.', 'error');
        return;
      }

      state.links      = res.data.links || [];
      state.total      = res.data.total || 0;
      state.totalPages = res.data.total_pages || 1;

      renderLinksTable();
      renderPagination();
      loadStats();
    }, function () {
      state.loading = false;
    });
  }

  function renderLinksTable() {
    var tbody = $('#srm-links-tbody');
    if (!tbody) return;

    if (!state.links || state.links.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6"><div class="srm-empty-state"><span class="srm-empty-icon">🔗</span><div class="srm-empty-title">' +
        (state.search ? 'No matching links found' : 'No masked links yet') +
        '</div><p class="srm-empty-desc">' +
        (state.search ? 'Try clearing your search query.' : 'Create your first masked link to protect destinations from bot crawlers.') +
        '</p>' +
        (!state.search ? '<div style="margin-top:14px;"><button type="button" class="srm-btn srm-btn-primary" id="srm-empty-add-btn">＋ Create First Link</button></div>' : '') +
        '</div></td></tr>';

      var emptyAddBtn = $('#srm-empty-add-btn');
      if (emptyAddBtn) {
        emptyAddBtn.addEventListener('click', function () { openModal(null); });
      }
      return;
    }

    var basePrefix = getBasePrefix();

    tbody.innerHTML = state.links.map(function (link) {
      var maskedUrl = link.masked_url || (basePrefix + link.slug);
      var displayTitle = link.article_title ? escHtml(link.article_title) : escHtml(link.slug);

      return '<tr>' +
        '<td>' +
          '<div class="srm-slug-cell">' +
            '<span>/' + escHtml(link.slug) + '</span>' +
          '</div>' +
        '</td>' +
        '<td>' +
          '<div class="srm-link-preview-row">' +
            '<a class="srm-link-anchor" href="' + escHtml(maskedUrl) + '" target="_blank" title="' + escHtml(maskedUrl) + '">' +
              escHtml(maskedUrl) +
            '</a>' +
            '<button type="button" class="srm-icon-btn srm-btn-copy" data-url="' + escHtml(maskedUrl) + '" title="Copy Masked URL">' +
              '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>' +
            '</button>' +
          '</div>' +
        '</td>' +
        '<td>' +
          '<div class="srm-dest-url-wrap">' +
            '<span class="srm-dest-title" title="' + displayTitle + '">' + displayTitle + '</span>' +
            '<a class="srm-dest-url" href="' + escHtml(link.real_url) + '" target="_blank" title="' + escHtml(link.real_url) + '">' +
              escHtml(link.real_url) +
            '</a>' +
          '</div>' +
        '</td>' +
        '<td>' +
          '<div class="srm-traffic-col">' +
            '<div class="srm-traffic-row">' +
              '<span class="srm-traffic-human">👤 ' + numFmt(link.clicks) + '</span>' +
              '<span style="font-size:.7rem;color:var(--srm-text-dim);">human</span>' +
            '</div>' +
            '<div class="srm-traffic-row">' +
              '<span class="srm-traffic-bot">🤖 ' + numFmt(link.bot_hits) + '</span>' +
              '<span style="font-size:.7rem;color:var(--srm-text-dim);">crawlers</span>' +
            '</div>' +
          '</div>' +
        '</td>' +
        '<td>' +
          '<span class="srm-timer-badge">⏱ ' + escHtml(link.timer) + 's</span>' +
        '</td>' +
        '<td>' +
          '<div class="srm-actions-row">' +
            '<button type="button" class="srm-icon-btn srm-btn-test" data-slug="' + escHtml(link.slug) + '" title="Simulate / Test Routing">' +
              '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>' +
            '</button>' +
            '<button type="button" class="srm-icon-btn srm-btn-edit" data-id="' + link.id + '" title="Edit">' +
              '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>' +
            '</button>' +
            '<button type="button" class="srm-icon-btn srm-btn-delete" data-id="' + link.id + '" data-slug="' + escHtml(link.slug) + '" title="Delete">' +
              '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>' +
            '</button>' +
          '</div>' +
        '</td>' +
      '</tr>';
    }).join('');
  }

  function renderPagination() {
    var pagWrap = $('#srm-pagination');
    var infoWrap = $('#srm-pagination-info');
    if (!pagWrap) return;
    pagWrap.innerHTML = '';

    if (infoWrap) {
      infoWrap.textContent = 'Showing page ' + state.page + ' of ' + state.totalPages + ' (' + state.total + ' total links)';
    }

    if (state.totalPages <= 1) return;

    function addBtn(label, page, active, disabled) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'srm-page-btn' + (active ? ' active' : '');
      btn.textContent = label;
      btn.disabled = !!disabled;
      btn.addEventListener('click', function () {
        state.page = page;
        loadLinks();
      });
      pagWrap.appendChild(btn);
    }

    addBtn('‹', state.page - 1, false, state.page <= 1);
    for (var i = 1; i <= state.totalPages; i++) {
      if (i === 1 || i === state.totalPages || Math.abs(i - state.page) <= 1) {
        addBtn(i, i, i === state.page, false);
      } else if (i === state.page - 2 || i === state.page + 2) {
        var dots = document.createElement('span');
        dots.textContent = '…';
        dots.style.cssText = 'color:var(--srm-text-dim);font-size:.8rem;padding:0 4px;';
        pagWrap.appendChild(dots);
      }
    }
    addBtn('›', state.page + 1, false, state.page >= state.totalPages);
  }

  /* ── Modal Manager ─────────────────────────────────────────── */
  function openModal(link) {
    state.editLink = link || null;
    var overlay    = $('#srm-modal-overlay');
    var titleEl    = $('#srm-modal-title');
    var headerIcon = $('#srm-modal-header-icon');
    var form       = $('#srm-link-form');
    var saveBtnText = $('#srm-save-btn-text');
    var saveBtn    = $('#srm-link-save-btn');

    if (!overlay || !form) return;
    if (saveBtn) saveBtn.disabled = false;

    // Reset collapse
    var collapseWrap = $('.srm-collapse-wrap');
    var collapseBody = $('#srm-collapse-body');
    if (collapseWrap) collapseWrap.classList.remove('open');
    if (collapseBody) collapseBody.style.display = 'none';

    var prefixSpan = $('#srm-slug-display-prefix');
    if (prefixSpan) prefixSpan.textContent = getBasePrefix();

    if (link) {
      if (titleEl) titleEl.textContent = 'Edit Masked Link';
      if (headerIcon) headerIcon.textContent = '✏️';
      if (saveBtnText) saveBtnText.textContent = 'Update Link';

      form.querySelector('[name="id"]').value              = link.id || 0;
      form.querySelector('[name="slug"]').value            = link.slug || '';
      form.querySelector('[name="real_url"]').value        = link.real_url || '';
      form.querySelector('[name="timer"]').value           = link.timer || 5;
      form.querySelector('[name="article_image"]').value   = link.article_image || '';
      form.querySelector('[name="article_title"]').value   = link.article_title || '';
      form.querySelector('[name="article_content"]').value = link.article_content || '';

      if (link.article_title || link.article_content) {
        if (collapseWrap) collapseWrap.classList.add('open');
        if (collapseBody) collapseBody.style.display = 'block';
      }
    } else {
      if (titleEl) titleEl.textContent = 'Create New Masked Link';
      if (headerIcon) headerIcon.textContent = '🔗';
      if (saveBtnText) saveBtnText.textContent = 'Save Masked Link';

      form.reset();
      form.querySelector('[name="id"]').value = 0;
      form.querySelector('[name="timer"]').value = (state.settings && state.settings.timer) ? state.settings.timer : 5;
    }

    overlay.classList.add('open');
  }

  function closeModal() {
    var overlay = $('#srm-modal-overlay');
    if (overlay) overlay.classList.remove('open');
    state.editLink = null;
  }

  function autoGenerateSlug() {
    var realUrlInput = $('[name="real_url"]', $('#srm-link-form'));
    var slugInput    = $('[name="slug"]', $('#srm-link-form'));
    if (!realUrlInput || !slugInput) return;

    var val = realUrlInput.value.trim();
    if (!val) {
      toast('Please enter the Real Destination URL first.', 'info');
      realUrlInput.focus();
      return;
    }

    if (!/^https?:\/\//i.test(val)) {
      val = 'https://' + val;
      realUrlInput.value = val;
    }

    try {
      var u = new URL(val);
      var parts = u.pathname.split('/').filter(Boolean);
      var piece = parts.pop() || u.hostname.replace(/^www\./i, '').replace(/\./g, '-');
      slugInput.value = slugify(piece);
      toast('Slug generated!', 'info');
    } catch (e) {
      slugInput.value = slugify(val);
    }
  }

  /* ── Settings Management ───────────────────────────────────── */
  function loadSettings() {
    ajax('srm_get_stats', {}, function () {}); // background refresh

    var prefixInput = $('[name="url_prefix"]', $('#srm-settings-form'));
    if (prefixInput && (!prefixInput.value || prefixInput.value === '')) {
      prefixInput.value = (window.srm_data && window.srm_data.prefix) ? window.srm_data.prefix : 'go';
    }
  }

  function saveSettings() {
    var form = $('#srm-settings-form');
    if (!form) return;

    var btn = $('#srm-save-settings-btn');
    if (btn) btn.disabled = true;

    var data = {};
    $$('input, textarea, select', form).forEach(function (el) {
      if (el.name) data[el.name] = el.value;
    });

    ajax('srm_save_settings', data, function (res) {
      if (btn) btn.disabled = false;
      if (!res || !res.success) {
        toast((res && res.data && res.data.message) ? res.data.message : 'Failed to save settings.', 'error');
        return;
      }
      state.settings = res.data.settings;
      if (window.srm_data) {
        window.srm_data.prefix = res.data.settings.url_prefix;
      }
      toast(res.data.message || 'Settings saved successfully!', 'success');
      loadStats();
    }, function () {
      if (btn) btn.disabled = false;
    });
  }

  /* ── Crawler Simulator ─────────────────────────────────────── */
  function runSimulation(slug, ua) {
    var resultBox = $('#srm-sim-result');
    var runBtn    = $('#srm-sim-run-btn');
    if (!resultBox) return;

    slug = slug || ($('#srm-sim-slug') ? $('#srm-sim-slug').value.trim() : '');
    ua   = ua   || ($('#srm-sim-ua')   ? $('#srm-sim-ua').value        : 'chrome');

    if (!slug) {
      toast('Please enter or select a slug to test.', 'info');
      if ($('#srm-sim-slug')) $('#srm-sim-slug').focus();
      return;
    }

    if (runBtn) runBtn.disabled = true;
    resultBox.style.display = 'block';
    resultBox.innerHTML = '<div class="srm-loading-cell"><span class="srm-spinner"></span><p style="margin-top:10px;font-size:.85rem;color:var(--srm-text-muted);">Simulating request headers & route matching…</p></div>';

    ajax('srm_test_link', { slug: slug, user_agent: ua }, function (res) {
      if (runBtn) runBtn.disabled = false;
      if (!res || !res.success) {
        resultBox.innerHTML = '<div class="srm-sim-result-card" style="border-color:rgba(239,68,68,0.4);padding:20px;color:#fca5a5;">' +
          '<strong>Simulation Failed:</strong> ' + escHtml((res && res.data && res.data.message) ? res.data.message : 'Unknown error') +
          '</div>';
        return;
      }

      var d = res.data;
      var isBot = d.is_bot;

      resultBox.innerHTML =
        '<div class="srm-sim-result-card">' +
          '<div class="srm-sim-result-header ' + (isBot ? 'srm-sim-bot-header' : 'srm-sim-human-header') + '">' +
            '<div style="display:flex;align-items:center;gap:10px;">' +
              '<span style="font-size:1.5rem;">' + (isBot ? '🤖' : '👤') + '</span>' +
              '<div>' +
                '<h4 style="margin:0;font-size:1rem;color:#fff;font-weight:700;">' +
                  (isBot ? 'Detected as Bot / Search Crawler' : 'Detected as Real Human Visitor') +
                '</h4>' +
                '<span style="font-size:.78rem;color:' + (isBot ? '#fca5a5' : '#6ee7b7') + ';">' +
                  escHtml(d.matched_rule) +
                '</span>' +
              '</div>' +
            '</div>' +
            '<span class="srm-badge-pill ' + (isBot ? 'srm-badge-bot' : 'srm-badge-human') + '">' +
              escHtml(d.http_action) +
            '</span>' +
          '</div>' +
          '<div class="srm-sim-result-body">' +
            '<div class="srm-sim-metric">' +
              '<span class="srm-sim-metric-lbl">Masked URL Tested</span>' +
              '<span class="srm-sim-metric-val" style="font-family:var(--srm-font-mono);font-size:.8rem;color:#a5b4fc;">' + escHtml(d.masked_url) + '</span>' +
            '</div>' +
            '<div class="srm-sim-metric">' +
              '<span class="srm-sim-metric-lbl">Target Action</span>' +
              '<span class="srm-sim-metric-val">' + escHtml(d.destination) + '</span>' +
            '</div>' +
            '<div class="srm-sim-metric">' +
              '<span class="srm-sim-metric-lbl">Anti-Bomb Click Status</span>' +
              '<span class="srm-sim-metric-val">' + escHtml(d.anti_bomb) + '</span>' +
            '</div>' +
            '<div class="srm-sim-metric">' +
              '<span class="srm-sim-metric-lbl">Real Destination URL</span>' +
              '<a href="' + escHtml(d.real_url) + '" target="_blank" class="srm-sim-metric-val" style="font-size:.8rem;color:#c7d2fe;text-decoration:none;">' + escHtml(d.real_url) + ' ↗</a>' +
            '</div>' +
          '</div>' +
        '</div>';
    }, function () {
      if (runBtn) runBtn.disabled = false;
    });
  }

  /* ── Delete Confirmation ───────────────────────────────────── */
  function confirmDelete(id) {
    var overlay = $('#srm-confirm-overlay');
    if (!overlay) return;
    overlay.dataset.deleteId = id;
    overlay.classList.add('open');
  }

  function closeConfirm() {
    var overlay = $('#srm-confirm-overlay');
    if (!overlay) return;
    overlay.classList.remove('open');
    delete overlay.dataset.deleteId;
  }

  /* ── Event Bindings ────────────────────────────────────────── */
  function bindEvents() {
    // Top Add Link button
    var topAddBtn = $('#srm-top-add-btn');
    if (topAddBtn) topAddBtn.addEventListener('click', function () { openModal(null); });

    var addLinkBtn = $('#srm-add-link-btn');
    if (addLinkBtn) addLinkBtn.addEventListener('click', function () { openModal(null); });

    // Refresh button
    var refreshBtn = $('#srm-refresh-btn');
    if (refreshBtn) {
      refreshBtn.addEventListener('click', function () {
        refreshBtn.classList.add('spinning');
        loadLinks();
        setTimeout(function () { refreshBtn.classList.remove('spinning'); }, 600);
      });
    }

    // Tabs
    $$('.srm-tab-item').forEach(function (tabBtn) {
      tabBtn.addEventListener('click', function () {
        activateTab(tabBtn.dataset.tab);
      });
    });

    // Modal Close
    var modalClose  = $('#srm-modal-close');
    var modalCancel = $('#srm-modal-cancel');
    var modalOverlay = $('#srm-modal-overlay');

    if (modalClose)  modalClose.addEventListener('click', closeModal);
    if (modalCancel) modalCancel.addEventListener('click', closeModal);
    if (modalOverlay) {
      modalOverlay.addEventListener('click', function (e) {
        if (e.target === modalOverlay) closeModal();
      });
    }

    // Auto-generate slug button
    var autoSlugBtn = $('#srm-auto-slug-btn');
    if (autoSlugBtn) autoSlugBtn.addEventListener('click', autoGenerateSlug);

    // Timer presets
    $$('.srm-preset-btn').forEach(function (preset) {
      preset.addEventListener('click', function () {
        var timerInput = $('[name="timer"]', $('#srm-link-form'));
        if (timerInput) timerInput.value = preset.dataset.sec;
      });
    });

    // Collapsible Article Content in Modal
    var collapseToggle = $('#srm-collapse-toggle');
    if (collapseToggle) {
      collapseToggle.addEventListener('click', function () {
        var wrap = $('.srm-collapse-wrap');
        var body = $('#srm-collapse-body');
        if (wrap && body) {
          var isOpen = wrap.classList.contains('open');
          wrap.classList.toggle('open', !isOpen);
          body.style.display = isOpen ? 'none' : 'block';
        }
      });
    }

    // Form Submit: CREATE OR EDIT LINK (BUG FIXED!)
    var linkForm = $('#srm-link-form');
    if (linkForm) {
      linkForm.addEventListener('submit', function (e) {
        e.preventDefault();

        var realUrlInput = $('[name="real_url"]', linkForm);
        var rawUrl = realUrlInput ? realUrlInput.value.trim() : '';

        if (!rawUrl) {
          toast('Please enter a destination Real URL.', 'error');
          if (realUrlInput) realUrlInput.focus();
          return;
        }

        // Auto-fix missing protocol (e.g. user entered "blog.com/post")
        if (!/^https?:\/\//i.test(rawUrl)) {
          rawUrl = 'https://' + rawUrl;
          if (realUrlInput) realUrlInput.value = rawUrl;
        }

        var saveBtn = $('#srm-link-save-btn');
        var saveBtnText = $('#srm-save-btn-text');
        var origText = saveBtnText ? saveBtnText.textContent : 'Save Link';

        if (saveBtn) saveBtn.disabled = true;
        if (saveBtnText) saveBtnText.textContent = 'Saving Link…';

        var data = {};
        $$('input, textarea, select', linkForm).forEach(function (el) {
          if (el.name) data[el.name] = el.value;
        });

        ajax('srm_save_link', data, function (res) {
          if (saveBtn) saveBtn.disabled = false;
          if (saveBtnText) saveBtnText.textContent = origText;

          if (!res || !res.success) {
            var msg = (res && res.data && res.data.message) ? res.data.message : 'Failed to save link. Please try again.';
            toast(msg, 'error');
            return;
          }

          toast(res.data.message || 'Link saved successfully!', 'success');
          closeModal();
          state.page = 1;
          loadLinks();
        }, function () {
          // Guaranteed error recovery: re-enable save button
          if (saveBtn) saveBtn.disabled = false;
          if (saveBtnText) saveBtnText.textContent = origText;
        });
      });
    }

    // Table Row Actions (Event Delegation)
    var tbody = $('#srm-links-tbody');
    if (tbody) {
      tbody.addEventListener('click', function (e) {
        var copyBtn   = e.target.closest('.srm-btn-copy');
        var editBtn   = e.target.closest('.srm-btn-edit');
        var deleteBtn = e.target.closest('.srm-btn-delete');
        var testBtn   = e.target.closest('.srm-btn-test');

        if (copyBtn) {
          var url = copyBtn.dataset.url;
          if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function () {
              copyBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>';
              toast('Masked URL copied to clipboard!', 'success');
              setTimeout(function () {
                copyBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>';
              }, 1800);
            });
          }
        }

        if (editBtn) {
          var id = parseInt(editBtn.dataset.id, 10);
          ajax('srm_get_link', { id: id }, function (res) {
            if (!res || !res.success) {
              toast('Failed to load link details.', 'error');
              return;
            }
            openModal(res.data.link);
          });
        }

        if (deleteBtn) {
          confirmDelete(parseInt(deleteBtn.dataset.id, 10));
        }

        if (testBtn) {
          var slug = testBtn.dataset.slug;
          activateTab('simulator');
          var simSlugInput = $('#srm-sim-slug');
          if (simSlugInput) simSlugInput.value = slug;
          runSimulation(slug, 'googlebot');
        }
      });
    }

    // Confirm Delete Actions
    var confirmCancel  = $('#srm-confirm-cancel');
    var confirmCancelX = $('#srm-confirm-cancel-x');
    var confirmOk      = $('#srm-confirm-ok');
    var confirmOverlay = $('#srm-confirm-overlay');

    if (confirmCancel)  confirmCancel.addEventListener('click', closeConfirm);
    if (confirmCancelX) confirmCancelX.addEventListener('click', closeConfirm);
    if (confirmOverlay) {
      confirmOverlay.addEventListener('click', function (e) {
        if (e.target === confirmOverlay) closeConfirm();
      });
    }

    if (confirmOk) {
      confirmOk.addEventListener('click', function () {
        var id = parseInt(confirmOverlay.dataset.deleteId, 10);
        if (!id) return;

        confirmOk.disabled = true;
        ajax('srm_delete_link', { id: id }, function (res) {
          confirmOk.disabled = false;
          closeConfirm();
          if (!res || !res.success) {
            toast((res && res.data && res.data.message) || 'Failed to delete link.', 'error');
            return;
          }
          toast('Masked link permanently deleted.', 'success');
          loadLinks();
        }, function () {
          confirmOk.disabled = false;
          closeConfirm();
        });
      });
    }

    // Search Box with Debounce & Clear
    var searchInput = $('#srm-search-input');
    var searchClear = $('#srm-search-clear');
    var searchTimer = null;

    if (searchInput) {
      searchInput.addEventListener('input', function () {
        var val = searchInput.value.trim();
        if (searchClear) searchClear.style.display = val ? 'block' : 'none';

        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
          state.search = val;
          state.page   = 1;
          loadLinks();
        }, 350);
      });
    }

    if (searchClear) {
      searchClear.addEventListener('click', function () {
        if (searchInput) searchInput.value = '';
        searchClear.style.display = 'none';
        state.search = '';
        state.page = 1;
        loadLinks();
      });
    }

    // Settings Form
    var settingsForm = $('#srm-settings-form');
    if (settingsForm) {
      settingsForm.addEventListener('submit', function (e) {
        e.preventDefault();
        saveSettings();
      });
    }

    // Simulator Execute
    var simRunBtn = $('#srm-sim-run-btn');
    if (simRunBtn) {
      simRunBtn.addEventListener('click', function () {
        runSimulation();
      });
    }
  }

  /* ── Bootstrapping ─────────────────────────────────────────── */
  function init() {
    if (!document.getElementById('srm-app')) return;
    bindEvents();
    activateTab('links');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
