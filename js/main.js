/* Swift Swap — Shared Utilities */

// ── Theme (run immediately to prevent flash) ─────────────
(function () {
  const t = localStorage.getItem('ss_theme');
  if (t === 'light') document.documentElement.setAttribute('data-theme', 'light');
})();

const Theme = {
  get current() { return localStorage.getItem('ss_theme') || 'dark'; },

  apply(theme) {
    if (theme === 'light') {
      document.documentElement.setAttribute('data-theme', 'light');
    } else {
      document.documentElement.removeAttribute('data-theme');
    }
  },

  set(theme) {
    localStorage.setItem('ss_theme', theme);
    this.apply(theme);
    const btn = document.getElementById('theme-toggle');
    if (btn) btn.innerHTML = theme === 'dark'
      ? '<i class="fa-solid fa-sun"></i>'
      : '<i class="fa-solid fa-moon"></i>';
  },

  toggle() { this.set(this.current === 'dark' ? 'light' : 'dark'); },
};

const API = {
  base: '/swift-swap/php',

  async request(path, options = {}) {
    const res = await fetch(this.base + path, {
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
      ...options,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`);
    return data;
  },

  get:    (path, params = {}) => {
    const qs = new URLSearchParams(params).toString();
    return API.request(`${path}${qs ? '?' + qs : ''}`);
  },
  post:   (path, body) => API.request(path, { method: 'POST', body: JSON.stringify(body) }),
  put:    (path, body) => API.request(path, { method: 'PUT',  body: JSON.stringify(body) }),
  delete: (path)       => API.request(path, { method: 'DELETE' }),
};

// ── Auth state ──────────────────────────────────────────────
const Auth = {
  _user: (() => { try { return JSON.parse(localStorage.getItem('ss_user') || 'null'); } catch { localStorage.removeItem('ss_user'); return null; } })(),

  get user()      { return this._user; },
  get isLoggedIn(){ return !!this._user; },
  get userId()    { return this._user?.id ?? null; },
  get username()  { return this._user?.username ?? null; },
  get canBuy()    { return this._user?.can_buy  ?? true; },
  get canSell()   { return this._user?.can_sell ?? true; },

  save(user)  { this._user = user; localStorage.setItem('ss_user', JSON.stringify(user)); },
  clear()     { this._user = null; localStorage.removeItem('ss_user'); },

  async logout() {
    await API.post('/auth/logout.php', {}).catch(() => {});
    this.clear();
    location.href = '/swift-swap/';
  },
};

// ── Navbar ──────────────────────────────────────────────────
function renderNavbar() {
  const nav = document.getElementById('navbar-actions');
  if (!nav) return;

  const themeIcon = Theme.current === 'dark' ? 'fa-sun' : 'fa-moon';
  const toggleBtn = `<button id="theme-toggle" class="nav-link" title="Toggle theme"><i class="fa-solid ${themeIcon}"></i></button>`;

  if (Auth.isLoggedIn) {
    const adminLink = Auth.user?.is_admin
      ? `<a href="/swift-swap/pages/admin.php" class="nav-link" style="color:var(--danger)" title="Admin panel"><i class="fa-solid fa-shield-halved"></i></a>`
      : '';
    nav.innerHTML = `
      <a href="/swift-swap/pages/messages.php" class="nav-link">Messages</a>
      <a href="/swift-swap/pages/meetup-spots.php" class="nav-link" title="Safe Meetup Spots"><i class="fa-solid fa-shield-halved" style="color:var(--primary)"></i> Safe Spots</a>
      ${Auth.canSell ? `<a href="/swift-swap/pages/create-listing.php" class="btn-sell">+ Sell</a>` : ''}
      ${adminLink}
      ${toggleBtn}
      <a href="/swift-swap/pages/dashboard.php" class="nav-link">${escapeHtml(Auth.username)}</a>
      <button class="btn-sell" id="logout-btn">Log out</button>
    `;
    document.getElementById('logout-btn').addEventListener('click', () => Auth.logout());
  } else {
    nav.innerHTML = `
      <a href="/swift-swap/pages/meetup-spots.php" class="nav-link" title="Safe Meetup Spots"><i class="fa-solid fa-shield-halved" style="color:var(--primary)"></i> Safe Spots</a>
      ${toggleBtn}
      <a href="/swift-swap/pages/login.php" class="nav-link">Log in</a>
      <a href="/swift-swap/pages/register.php" class="btn btn-primary btn-sm">Sign up</a>
    `;
  }

  document.getElementById('theme-toggle').addEventListener('click', () => Theme.toggle());

  // ── Mobile nav ──────────────────────────────────────────
  const mobileLinks = document.getElementById('mobile-nav-links');
  if (mobileLinks) {
    if (Auth.isLoggedIn) {
      mobileLinks.innerHTML = `
        <a href="/swift-swap/pages/dashboard.php" class="mobile-nav-link"><i class="fa-solid fa-gauge" style="width:1.1rem"></i> Dashboard</a>
        <a href="/swift-swap/pages/messages.php" class="mobile-nav-link"><i class="fa-solid fa-comments" style="width:1.1rem"></i> Messages</a>
        <a href="/swift-swap/pages/meetup-spots.php" class="mobile-nav-link"><i class="fa-solid fa-shield-halved" style="width:1.1rem"></i> Safe Spots</a>
        ${Auth.canSell ? `<a href="/swift-swap/pages/create-listing.php" class="mobile-nav-link sell"><i class="fa-solid fa-plus" style="width:1.1rem"></i> Sell an Item</a>` : ''}
        ${Auth.user?.is_admin ? `<a href="/swift-swap/pages/admin.php" class="mobile-nav-link" style="color:var(--danger)"><i class="fa-solid fa-shield-halved" style="width:1.1rem"></i> Admin Panel</a>` : ''}
        <hr class="divider" style="margin:0.5rem 0">
        <button class="mobile-nav-link" id="mobile-logout-btn"><i class="fa-solid fa-right-from-bracket" style="width:1.1rem"></i> Log out</button>
      `;
      document.getElementById('mobile-logout-btn')?.addEventListener('click', () => Auth.logout());
    } else {
      mobileLinks.innerHTML = `
        <a href="/swift-swap/pages/login.php" class="mobile-nav-link"><i class="fa-solid fa-right-to-bracket" style="width:1.1rem"></i> Log in</a>
        <a href="/swift-swap/pages/register.php" class="mobile-nav-link sell"><i class="fa-solid fa-user-plus" style="width:1.1rem"></i> Sign up</a>
        <a href="/swift-swap/pages/meetup-spots.php" class="mobile-nav-link"><i class="fa-solid fa-shield-halved" style="width:1.1rem"></i> Safe Spots</a>
      `;
    }
  }

  const overlay    = document.getElementById('mobile-nav-overlay');
  const hamburger  = document.getElementById('nav-hamburger');
  const closeBtn   = document.getElementById('nav-hamburger-close');
  if (overlay && hamburger) {
    const openMenu  = () => { overlay.classList.add('open');    document.body.style.overflow = 'hidden'; };
    const closeMenu = () => { overlay.classList.remove('open'); document.body.style.overflow = ''; };
    hamburger.addEventListener('click', openMenu);
    closeBtn?.addEventListener('click', closeMenu);
    overlay.addEventListener('click', e => { if (e.target === overlay) closeMenu(); });
  }

  const searchToggle    = document.getElementById('mobile-search-toggle');
  const mobileSearchBar = document.getElementById('mobile-search-bar');
  const mobileInput     = document.getElementById('mobile-search-input');
  const mobileBtn       = document.getElementById('mobile-search-btn');
  if (searchToggle && mobileSearchBar) {
    searchToggle.addEventListener('click', () => {
      mobileSearchBar.classList.toggle('open');
      if (mobileSearchBar.classList.contains('open')) mobileInput?.focus();
    });
    const doMobileSearch = () => {
      const q = mobileInput?.value.trim();
      if (q) location.href = `/swift-swap/pages/search.php?q=${encodeURIComponent(q)}`;
    };
    mobileBtn?.addEventListener('click', doMobileSearch);
    mobileInput?.addEventListener('keydown', e => { if (e.key === 'Enter') doMobileSearch(); });
  }
}

// ── Flash messages ──────────────────────────────────────────
function showAlert(container, message, type = 'error') {
  const el = typeof container === 'string' ? document.getElementById(container) : container;
  if (!el) return;
  el.innerHTML = `<div class="alert alert-${type}">${escapeHtml(message)}</div>`;
  el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function clearAlert(container) {
  const el = typeof container === 'string' ? document.getElementById(container) : container;
  if (el) el.innerHTML = '';
}

// ── Utilities ───────────────────────────────────────────────
function escapeHtml(str) {
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}

function formatPrice(price) {
  return new Intl.NumberFormat('en-ZA', { style: 'currency', currency: 'ZAR' }).format(price);
}

function timeAgo(dateStr) {
  const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
  if (diff < 60)       return 'just now';
  if (diff < 3600)     return `${Math.floor(diff/60)}m ago`;
  if (diff < 86400)    return `${Math.floor(diff/3600)}h ago`;
  if (diff < 2592000)  return `${Math.floor(diff/86400)}d ago`;
  return new Date(dateStr).toLocaleDateString();
}

function conditionLabel(c) {
  return { new: 'New', like_new: 'Like New', good: 'Good', fair: 'Fair', poor: 'Poor' }[c] || c;
}

function starsHtml(rating, max = 5) {
  const full  = Math.round(rating);
  const empty = max - full;
  return '<span class="stars">' + '★'.repeat(full) + '</span>' +
         '<span class="stars-empty">' + '★'.repeat(empty) + '</span>';
}

// ── Listing card HTML ────────────────────────────────────────
function listingCardHtml(l) {
  const img = l.image
    ? `<img class="listing-card-img" src="/swift-swap/uploads/listings/${escapeHtml(l.image)}" alt="${escapeHtml(l.title)}" loading="lazy">`
    : `<div class="listing-card-img-placeholder"><i class="fa-solid fa-image"></i></div>`;

  return `
    <a href="/swift-swap/pages/listing.php?id=${l.id}" class="card listing-card" style="text-decoration:none;color:inherit">
      ${img}
      <div class="listing-card-body">
        <div class="listing-card-title">${escapeHtml(l.title)}</div>
        <div class="listing-card-price">${formatPrice(l.price)}</div>
        <div class="listing-card-meta">${escapeHtml(l.seller_name)} · ${timeAgo(l.created_at)}</div>
        <span class="listing-card-condition cond-${l.condition}">${conditionLabel(l.condition)}</span>
      </div>
    </a>`;
}

// ── Navbar search wiring ─────────────────────────────────────
function wireSearchBar(inputId, buttonId) {
  const input  = document.getElementById(inputId);
  const button = document.getElementById(buttonId);
  if (!input || !button) return;

  const go = () => {
    const q = input.value.trim();
    if (q) location.href = `/swift-swap/pages/search.php?q=${encodeURIComponent(q)}`;
  };

  button.addEventListener('click', go);
  input.addEventListener('keydown', e => { if (e.key === 'Enter') go(); });
}

// ── Init ────────────────────────────────────────────────────
function _initPage() {
  renderNavbar();
  wireSearchBar('nav-search-input', 'nav-search-btn');
}
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', _initPage);
} else {
  _initPage();
}
