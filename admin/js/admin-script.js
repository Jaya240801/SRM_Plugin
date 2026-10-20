/**
 * Safe Redirect Masker — Admin Dashboard Script
 * Vanilla JS, no dependencies.
 */
(function () {
  'use strict';

  /* ─── State ─────────────────────────────────────────────────── */
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

  /* ─── DOM refs ───────────────────────────────────────────────── */
  var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); };

  /* ─── AJAX helper ────────────────────────────────────────────── */
  function ajax(action, data, cb) {
    var body = new FormData();
    body.append('action', action);
    body.append('nonce',  srm_data.nonce);
    if (data) {
      Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
    }
    fetch(srm_data.ajax_url, { method: 'POST', body: body })
      .then(function (r) { return r.json(); })
      .then(cb)
      .catch(function (e) { toast('Request failed: ' + e.message, 'error'); });
  }

  /* ─── Toast ──────────────────────────────────────────────────── */
  function toast(msg, type) {
    type = type || 'info';
    var icons = { success: '✅', error: '❌', info: 'ℹ️' };
    var container = $('#srm-toast-container');
    if (!container) return;
    var el = document.createElement('div');
    el.className = 'srm-toast toast-' + type;
    el.innerHTML = '<span>' + icons[type] + '</span><span>' + escHtml(msg) + '</span>';
    container.appendChild(el);
    setTimeout(function () { el.classList.add('show'); }, 10);
    setTimeout(function () {
      el.classList.remove('show');
      setTimeout(function () { el.remove(); }, 350);
    }, 3400);
  }

  /* ─── Utils ──────────────────────────────────────────────────── */
  function escHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }

  function numFmt(n) {
    n = parseInt(n) || 0;
    return n >= 1000 ? (n / 1000).toFixed(1) + 'K' : String(n);
  }

  function slugify(str) {
    return str.toLowerCase().replace(/[^a-z0-9-_/]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
  }

  /* ─── Tab switching ──────────────────────────────────────────── */
  function activateTab(tab) {
    state.tab = tab;
    $$('.srm-tab-btn').forEach(function (b) {
      b.classList.toggle('active', b.dataset.tab === tab);
    });
    $$('.srm-tab-panel').forEach(function (p) {
      p.style.display = p.dataset.panel === tab ? '' : 'none';
    });
    if (tab === 'links')    loadLinks();
    if (tab === 'settings') loadSettings();
  }

  /* ─── Stats ──────────────────────────────────────────────────── */
  function loadStats() {
    ajax('srm_get_stats', {}, function (res) {
      if (!res.success) return;
      var s = res.data;
      state.stats = s;
      setVal('#srm-stat-links',  numFmt(s.total_links));
      setVal('#srm-stat-clicks', numFmt(s.total_clicks));
      setVal('#srm-stat-bots',   numFmt(s.total_bots));
      if (s.top_link) {
        setVal('#srm-stat-top', '/' + escHtml(s.top_link.slug));
      }
    });
  }

  function setVal(sel, val) {
    var el = $(sel);
    if (el) el.textContent = val;
  }

  /* ─── Links list ─────────────────────────────────────────────── */
  function loadLinks() {
    if (state.loading) return;
    state.loading = true;
    var tbody = $('#srm-links-tbody');
    if (tbody) tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:28px;"><span class="srm-spinner"></span></td></tr>';

    ajax('srm_get_links', { search: state.search, page: state.page }, function (res) {
      state.loading = false;
      if (!res.success) { toast('Failed to load links', 'error'); return; }
      state.links      = res.data.links;
      state.total      = res.data.total;
      state.totalPages = res.data.total_pages;

      renderLinksTable();
      renderPagination();
      loadStats();
    });
  }

  function renderLinksTable() {
    var tbody = $('#srm-links-tbody');
    if (!tbody) return;

    if (!state.links || state.links.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6"><div class="srm-empty-state"><span class="icon">🔗</span><h3>No links found</h3><p>Create your first masked link to get started.</p></div></td></tr>';
      return;
    }

    tbody.innerHTML = state.links.map(function (link) {
      var maskedUrl = link.masked_url || (srm_data.base_url + srm_data.prefix + '/' + link.slug);
      return '<tr>' +
        '<td><span class="srm-slug-cell">/' + escHtml(link.slug) + '</span></td>' +
        '<td>' +
          '<div class="srm-masked-url">' +
            '<a href="' + escHtml(maskedUrl) + '" target="_blank">' + escHtml(maskedUrl) + '</a>' +
            '<button class="srm-copy-btn" data-url="' + escHtml(maskedUrl) + '" title="Copy URL">📋</button>' +
          '</div>' +
        '</td>' +
        '<td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' +
          '<a href="' + escHtml(link.real_url) + '" target="_blank" style="color:var(--muted);font-size:.8rem;text-decoration:none;" title="' + escHtml(link.real_url) + '">' + escHtml(link.real_url) + '</a>' +
        '</td>' +
        '<td>' +
          '<div class="srm-click-count">' + numFmt(link.clicks) + ' <span style="font-weight:400;color:var(--muted)">human</span></div>' +
          '<div class="srm-bot-count">🤖 ' + numFmt(link.bot_hits) + ' bot</div>' +
        '</td>' +
        '<td>' +
          '<span class="srm-badge srm-badge-active">⏱ ' + escHtml(link.timer) + 's</span>' +
        '</td>' +
        '<td>' +
          '<div class="srm-actions">' +
            '<button class="srm-btn-ghost srm-edit-btn" data-id="' + link.id + '" title="Edit">✏️</button>' +
            '<button class="srm-btn-danger srm-delete-btn" data-id="' + link.id + '" title="Delete">🗑</button>' +
          '</div>' +
        '</td>' +
      '</tr>';
    }).join('');
  }

  function renderPagination() {
    var pag = $('#srm-pagination');
    if (!pag) return;
    pag.innerHTML = '';

    if (state.totalPages <= 1) return;

    function addBtn(label, page, active, disabled) {
      var btn = document.createElement('button');
      btn.className = 'srm-page-btn' + (active ? ' active' : '');
      btn.textContent = label;
      btn.disabled = !!disabled;
      btn.addEventListener('click', function () {
        state.page = page;
        loadLinks();
      });
      pag.appendChild(btn);
    }

    addBtn('‹', state.page - 1, false, state.page <= 1);
    for (var i = 1; i <= state.totalPages; i++) {
      if (i === 1 || i === state.totalPages || Math.abs(i - state.page) <= 1) {
        addBtn(i, i, i === state.page, false);
      } else if (i === state.page - 2 || i === state.page + 2) {
        var dots = document.createElement('span');
        dots.textContent = '…';
        dots.style.cssText = 'color:var(--muted);font-size:.8rem;padding:0 4px;';
        pag.appendChild(dots);
      }
    }
    addBtn('›', state.page + 1, false, state.page >= state.totalPages);
  }

  /* ─── Modal ──────────────────────────────────────────────────── */
  function openModal(link) {
    state.editLink = link || null;
    var overlay   = $('#srm-modal-overlay');
    var modalTitle = $('#srm-modal-title');
    var form      = $('#srm-link-form');
    if (!overlay || !form) return;

    if (link) {
      modalTitle.textContent = '✏️ Edit Link';
      form.querySelector('[name="id"]').value              = link.id;
      form.querySelector('[name="slug"]').value            = link.slug;
      form.querySelector('[name="real_url"]').value        = link.real_url;
      form.querySelector('[name="article_title"]').value   = link.article_title || '';
      form.querySelector('[name="article_image"]').value   = link.article_image || '';
      form.querySelector('[name="article_content"]').value = link.article_content || '';
      form.querySelector('[name="timer"]').value           = link.timer || 5;
    } else {
      modalTitle.textContent = '➕ Add New Link';
      form.reset();
      form.querySelector('[name="id"]').value  = 0;
      form.querySelector('[name="timer"]').value = state.settings.timer || 5;
    }

    overlay.classList.add('open');
    updateSlugPreview();
  }

  function closeModal() {
    var overlay = $('#srm-modal-overlay');
    if (overlay) overlay.classList.remove('open');
    state.editLink = null;
  }

  function updateSlugPreview() {
    var slugInput  = $('[name="slug"]', $('#srm-link-form'));
    var prefixSpan = $('#srm-slug-display-prefix');
    if (prefixSpan) {
      prefixSpan.textContent = srm_data.base_url + (state.settings.url_prefix || 'go') + '/';
    }
    if (slugInput) {
      var raw = slugInput.value;
      if (!raw) {
        var urlInput = $('[name="real_url"]', $('#srm-link-form'));
        if (urlInput && urlInput.value) {
          try {
            var p = new URL(urlInput.value);
            raw = p.pathname.split('/').filter(Boolean).pop() || '';
          } catch(e) {}
        }
      }
    }
  }

  /* ─── Settings ───────────────────────────────────────────────── */
  function loadSettings() {
    ajax('srm_get_stats', {}, function () {}); // refresh stats silently
    var saved = window.srm_settings_cache;
    if (saved) { populateSettings(saved); return; }
    // Read from localized data (we'll also fetch fresh on demand)
    populateSettings({
      url_prefix:       srm_data.prefix || 'go',
      timer:            5,
      anti_bomb_limit:  5,
      anti_bomb_window: 60,
      bomb_lockout:     300,
      ads_head:         '',
      ads_foot:         '',
    });
  }

  function populateSettings(s) {
    state.settings = s;
    setInputVal('[name="url_prefix"]',       s.url_prefix);
    setInputVal('[name="timer"]',            s.timer);
    setInputVal('[name="anti_bomb_limit"]',  s.anti_bomb_limit);
    setInputVal('[name="anti_bomb_window"]', s.anti_bomb_window);
    setInputVal('[name="bomb_lockout"]',     s.bomb_lockout);
    setInputVal('[name="ads_head"]',         s.ads_head || '');
    setInputVal('[name="ads_foot"]',         s.ads_foot || '');
  }

  function setInputVal(sel, val) {
    var el = $(sel);
    if (el) el.value = val !== undefined && val !== null ? val : '';
  }

  function saveSettings() {
    var form = $('#srm-settings-form');
    if (!form) return;
    var data = {};
    $$('input, textarea, select', form).forEach(function (el) {
      if (el.name) data[el.name] = el.value;
    });
    ajax('srm_save_settings', data, function (res) {
      if (!res.success) { toast(res.data && res.data.message ? res.data.message : 'Save failed', 'error'); return; }
      state.settings = res.data.settings;
      srm_data.prefix = res.data.settings.url_prefix;
      window.srm_settings_cache = res.data.settings;
      toast('Settings saved!', 'success');
    });
  }

  /* ─── Delete confirm ─────────────────────────────────────────── */
  function confirmDelete(id) {
    var overlay = $('#srm-confirm-overlay');
    if (!overlay) return;
    overlay.classList.add('open');
    overlay.dataset.deleteId = id;
  }

  /* ─── Event delegation ───────────────────────────────────────── */
  function bindEvents() {
    // Tab buttons
    $$('.srm-tab-btn').forEach(function (btn) {
      btn.addEventListener('click', function () { activateTab(btn.dataset.tab); });
    });

    // Add link button
    var addBtn = $('#srm-add-link-btn');
    if (addBtn) addBtn.addEventListener('click', function () { openModal(null); });

    // Modal close
    var modalClose  = $('#srm-modal-close');
    var modalCancel = $('#srm-modal-cancel');
    var overlay     = $('#srm-modal-overlay');

    if (modalClose)  modalClose.addEventListener('click',  closeModal);
    if (modalCancel) modalCancel.addEventListener('click', closeModal);
    if (overlay) overlay.addEventListener('click', function (e) {
      if (e.target === overlay) closeModal();
    });

    // Modal form submit
    var linkForm = $('#srm-link-form');
    if (linkForm) {
      linkForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var data = {};
        $$('input, textarea', linkForm).forEach(function (el) {
          if (el.name) data[el.name] = el.value;
        });
        var saveBtn = $('#srm-link-save-btn');
        if (saveBtn) saveBtn.disabled = true;
        ajax('srm_save_link', data, function (res) {
          if (saveBtn) saveBtn.disabled = false;
          if (!res.success) {
            toast((res.data && res.data.message) ? res.data.message : 'Failed to save', 'error');
            return;
          }
          toast(res.data.message, 'success');
          closeModal();
          state.page = 1;
          loadLinks();
        });
      });

      // Live slug preview
      $('[name="real_url"]', linkForm).addEventListener('input', updateSlugPreview);
      $('[name="slug"]',     linkForm).addEventListener('input', updateSlugPreview);
    }

    // Table delegated events
    var tbody = $('#srm-links-tbody');
    if (tbody) {
      tbody.addEventListener('click', function (e) {
        var editBtn   = e.target.closest('.srm-edit-btn');
        var deleteBtn = e.target.closest('.srm-delete-btn');
        var copyBtn   = e.target.closest('.srm-copy-btn');

        if (editBtn) {
          var id = parseInt(editBtn.dataset.id);
          ajax('srm_get_link', { id: id }, function (res) {
            if (!res.success) { toast('Failed to load link', 'error'); return; }
            openModal(res.data.link);
          });
        }

        if (deleteBtn) {
          confirmDelete(parseInt(deleteBtn.dataset.id));
        }

        if (copyBtn) {
          var url = copyBtn.dataset.url;
          if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function () {
              copyBtn.textContent = '✔';
              copyBtn.classList.add('copied');
              setTimeout(function () {
                copyBtn.textContent = '📋';
                copyBtn.classList.remove('copied');
              }, 1500);
            });
          }
        }
      });
    }

    // Confirm delete modal
    var confirmOverlay = $('#srm-confirm-overlay');
    if (confirmOverlay) {
      $('#srm-confirm-cancel').addEventListener('click', function () {
        confirmOverlay.classList.remove('open');
      });
      $('#srm-confirm-ok').addEventListener('click', function () {
        var id = parseInt(confirmOverlay.dataset.deleteId);
        confirmOverlay.classList.remove('open');
        ajax('srm_delete_link', { id: id }, function (res) {
          if (!res.success) { toast((res.data && res.data.message) || 'Delete failed', 'error'); return; }
          toast('Link deleted', 'success');
          loadLinks();
        });
      });
      confirmOverlay.addEventListener('click', function (e) {
        if (e.target === confirmOverlay) confirmOverlay.classList.remove('open');
      });
    }

    // Search
    var searchInput = $('#srm-search-input');
    if (searchInput) {
      var searchTimer;
      searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
          state.search = searchInput.value.trim();
          state.page   = 1;
          loadLinks();
        }, 400);
      });
    }

    // Settings form
    var settingsForm = $('#srm-settings-form');
    if (settingsForm) {
      settingsForm.addEventListener('submit', function (e) {
        e.preventDefault();
        saveSettings();
      });
    }
  }

  /* ─── Init ───────────────────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    if (!document.getElementById('srm-app')) return;
    bindEvents();
    activateTab('links');
  });
})();
