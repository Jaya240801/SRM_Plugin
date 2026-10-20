# Safe Redirect Masker — WordPress Plugin

A smart WordPress URL masking plugin that detects real users vs. bot crawlers and handles each differently, with built-in **anti-bomb click** protection.

---

## Features

| Feature | Detail |
|---|---|
| **URL Masking** | Configurable prefix (e.g. `/go/slug`). The real destination URL is hidden from users. |
| **Bot Detection** | Multi-layer: UA string, `Accept-Language`, `Accept` header, `Sec-Fetch-*` metadata |
| **Bot → Real URL** | Crawlers (Googlebot, Bing, etc.) receive a `301` redirect straight to the real article — they index the right content |
| **Human → Safelink** | Real users see a cloaking article page with a countdown timer, then are redirected to the real URL |
| **Anti-Bomb Click** | IP-based rate-limiting via WordPress transients: configurable max-clicks / time-window / lockout duration |
| **AdSense Ready** | Inject custom `<head>` / `<body>` HTML (ad scripts) per-page on the safelink countdown page |
| **Admin Dashboard** | Full CRUD for links, statistics (human clicks, bot hits, top link), settings panel |

---

## How It Works

```
Visitor hits /{prefix}/{slug}
       │
       ├─ Bot detected? ──YES──► 301 redirect to real_url (SEO-safe)
       │
       └─ Human
              │
              ├─ Anti-bomb check: IP rate-limit exceeded? ──YES──► 429 Block page
              │
              └─ OK ──► Render safelink page (article + countdown)
                              │
                              └─ After countdown ──► JS redirect to real_url
```

### Bot Detection Layers
1. **User-Agent matching** — 50+ known crawlers, bots, scrapers, headless browsers
2. **Empty UA** — always a bot
3. **Sec-Fetch-Mode/Dest** — real browser navigations send these; crawlers never do
4. **Accept-Language** — browsers always send this; many bots don't
5. **Accept header** — `*/*` only + no Sec-Fetch = likely a bot

### Anti-Bomb Click (Click Fraud Protection)
- Stored in WordPress transients (fast, no extra DB queries)
- Per-IP, per-link counter (hashed IP, never stored raw)
- **Default:** 5 clicks per 60 seconds → 5-minute lockout
- All thresholds are configurable from the admin settings panel
- Bot clicks are **not** counted against the human rate limit

---

## Installation

1. Copy the `SafeRedirect-WP-Plugin` folder into `wp-content/plugins/`
2. Activate **Safe Redirect Masker** from the WordPress Plugins page
3. Visit **Safe Redirect** in the left admin menu
4. Set your **URL Prefix** (Settings tab), then **Add Links**

---

## Database Tables

| Table | Purpose |
|---|---|
| `{prefix}_srm_links` | Stores all masked links (slug, real_url, article content, timer, click stats) |
| `{prefix}_srm_click_log` | Per-click log for analytics and anti-bomb (ip_hash, is_bot, timestamp) |

Tables are created on activation and preserved on deactivation (data safety).

---

## File Structure

```
SafeRedirect-WP-Plugin/
├── safe-redirect-masker.php        ← Plugin entry point
├── includes/
│   ├── class-srm-db.php            ← Database & storage
│   ├── class-srm-bot-detector.php  ← Bot/crawler detection
│   ├── class-srm-anti-bomb.php     ← Anti-bomb click (rate limiting)
│   ├── class-srm-router.php        ← Front-end request routing
│   ├── class-srm-renderer.php      ← Safelink page renderer
│   └── class-srm-ajax.php          ← Admin AJAX endpoints
├── admin/
│   ├── css/admin-style.css         ← Admin dashboard styles
│   ├── js/admin-script.js          ← Admin dashboard JS
│   └── views/dashboard.php         ← Admin dashboard HTML
└── README.md
```

---

## Requirements

- WordPress 5.6+
- PHP 7.4+
- MySQL 5.6+ / MariaDB 10.1+

---

## License

GPL v2 or later
