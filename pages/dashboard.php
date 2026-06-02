<?php
require_once __DIR__ . '/../php/config/database.php';
if (!isLoggedIn()) {
    header('Location: /swift-swap/pages/login.php');
    exit;
}
$title = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<?php require_once __DIR__ . '/../includes/head.php'; ?>
<body class="page-wrap">

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <main>
    <div class="container">
      <div class="dash-grid">

        <aside class="dash-sidebar">
          <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid var(--gray-200)">
            <div class="avatar avatar-md" id="sidebar-avatar">?</div>
            <div>
              <div style="font-weight:700" id="sidebar-username">Loading…</div>
              <div class="text-muted" style="font-size:0.8rem">Member</div>
            </div>
          </div>

          <nav>
            <a class="dash-nav-item active" data-tab="overview"><i class="fa-solid fa-chart-pie"></i> Overview</a>
            <a class="dash-nav-item" data-tab="listings"><i class="fa-solid fa-store"></i> My Stall</a>
            <a class="dash-nav-item" data-tab="buying"><i class="fa-solid fa-bag-shopping"></i> Buying</a>
            <a class="dash-nav-item" data-tab="watchlist"><i class="fa-regular fa-heart"></i> Watchlist</a>
            <a class="dash-nav-item" href="/swift-swap/pages/messages.php"><i class="fa-solid fa-comments"></i> Messages</a>
            <hr class="divider">
            <a class="dash-nav-item" data-tab="settings"><i class="fa-solid fa-gear"></i> Settings</a>
          </nav>
        </aside>

        <div class="dash-main">

          <div class="tab-panel active" id="tab-overview">
            <div class="section-header">
              <h1 class="section-title">Overview</h1>
              <a href="/swift-swap/pages/create-listing.php" class="btn btn-accent btn-sm">+ New Listing</a>
            </div>
            <div class="stats-grid" id="stats-grid">
              <div class="stat-card"><div class="stat-value" id="stat-listings">—</div><div class="stat-label">Active Listings</div></div>
              <div class="stat-card"><div class="stat-value" id="stat-sold">—</div><div class="stat-label">Items Sold</div></div>
              <div class="stat-card"><div class="stat-value" id="stat-buying">—</div><div class="stat-label">Purchases</div></div>
              <div class="stat-card"><div class="stat-value" id="stat-watchlist">—</div><div class="stat-label">Watchlist</div></div>
            </div>
            <div class="section-header mt-2"><h2 class="section-title">Recent Activity</h2></div>
            <div id="recent-activity" class="listing-grid"></div>
          </div>

          <div class="tab-panel" id="tab-listings">
            <div class="section-header">
              <h1 class="section-title">My Stall</h1>
              <a href="/swift-swap/pages/create-listing.php" class="btn btn-accent btn-sm">+ Post New</a>
            </div>
            <div id="my-listings-grid" class="listing-grid"><div class="loading">Loading…</div></div>
          </div>

          <div class="tab-panel" id="tab-buying">
            <h1 class="section-title mb-2">Purchases</h1>
            <div id="buying-alert"></div>
            <div id="buying-orders"><div class="loading">Loading…</div></div>
          </div>

          <div class="tab-panel" id="tab-watchlist">
            <h1 class="section-title mb-2">Watchlist</h1>
            <div id="watchlist-grid" class="listing-grid"><div class="loading">Loading…</div></div>
          </div>

          <div class="tab-panel" id="tab-settings">
            <h1 class="section-title mb-2">Account Settings</h1>
            <div id="settings-alert"></div>
            <div class="card card-body" style="max-width:500px">
              <form id="settings-form">
                <div class="form-group">
                  <label>Bio</label>
                  <textarea name="bio" id="settings-bio" placeholder="Tell buyers a bit about yourself…"></textarea>
                </div>
                <div class="form-group">
                  <label>Location</label>
                  <input type="text" name="location" id="settings-location" placeholder="City, Province">
                </div>
                <div class="form-group">
                  <label>Phone</label>
                  <input type="tel" name="phone" id="settings-phone" placeholder="+27 71 000 0000">
                </div>
                <hr class="divider">
                <div class="form-group">
                  <label>New Password <span class="text-muted">(leave blank to keep current)</span></label>
                  <input type="password" name="password" placeholder="••••••••" minlength="8">
                </div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
              </form>
            </div>
          </div>

        </div>
      </div>
    </div>
  </main>

  <script src="/swift-swap/js/main.js"></script>
  <script>
    if (!Auth.canSell) {
      document.querySelector('.dash-nav-item[data-tab="listings"]').style.display = 'none';
      document.querySelectorAll('a[href="/swift-swap/pages/create-listing.php"]').forEach(el => el.style.display = 'none');
    }
    if (!Auth.canBuy) {
      document.querySelector('.dash-nav-item[data-tab="buying"]').style.display = 'none';
    }

    document.querySelectorAll('.dash-nav-item[data-tab]').forEach(link => {
      link.addEventListener('click', e => {
        e.preventDefault();
        const tab = link.dataset.tab;
        document.querySelectorAll('.dash-nav-item').forEach(l => l.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        link.classList.add('active');
        document.getElementById('tab-' + tab)?.classList.add('active');
        loadTab(tab);
      });
    });

    async function loadProfile() {
      try {
        const user = await API.get('/api/users.php');
        document.getElementById('sidebar-username').textContent = user.username;
        document.getElementById('sidebar-avatar').textContent   = user.username[0].toUpperCase();
        document.getElementById('settings-bio').value      = user.bio || '';
        document.getElementById('settings-location').value = user.location || '';
        document.getElementById('settings-phone').value    = user.phone || '';

        const active = user.listings?.filter(l => l.status === 'active').length || 0;
        const sold   = user.listings?.filter(l => l.status === 'sold').length || 0;
        document.getElementById('stat-listings').textContent = active;
        document.getElementById('stat-sold').textContent     = sold;

        const grid = document.getElementById('recent-activity');
        if (user.listings?.length) {
          grid.innerHTML = user.listings.slice(0, 4).map(listingCardHtml).join('');
        } else {
          grid.innerHTML = '<div class="empty-state"><i class="fa-solid fa-tag"></i>No listings yet.<br><a href="/swift-swap/pages/create-listing.php">Post your first item!</a></div>';
        }
      } catch (err) {
        console.error(err);
      }
    }

    function listingCardHtml(l) {
      const img = l.image
        ? `<img class="listing-card-img" src="/swift-swap/uploads/listings/${escapeHtml(l.image)}" alt="${escapeHtml(l.title)}" loading="lazy">`
        : `<div class="listing-card-img-placeholder"><i class="fa-solid fa-image"></i></div>`;
      const statusPill = `<span class="pill pill-${l.status}" style="margin-top:0.25rem;display:inline-block">${l.status}</span>`;
      return `
        <a href="/swift-swap/pages/listing.php?id=${l.id}" class="card listing-card" style="text-decoration:none;color:inherit">
          ${img}
          <div class="listing-card-body">
            <div class="listing-card-title">${escapeHtml(l.title)}</div>
            <div class="listing-card-price">${formatPrice(l.price)}</div>
            ${statusPill}
          </div>
        </a>`;
    }

    const loaded = {};
    async function loadTab(tab) {
      if (loaded[tab]) return;
      loaded[tab] = true;

      if (tab === 'listings') {
        const grid = document.getElementById('my-listings-grid');
        const user = await API.get('/api/users.php').catch(() => ({ listings: [] }));
        grid.innerHTML = user.listings?.length
          ? user.listings.map(listingCardHtml).join('')
          : '<div class="empty-state"><i class="fa-solid fa-tag"></i>No listings yet.</div>';
      }

      if (tab === 'buying') {
        const el = document.getElementById('buying-orders');
        try {
          const data = await API.get('/api/orders.php', { type: 'buying' });
          if (!data.orders?.length) {
            el.innerHTML = '<div class="empty-state"><i class="fa-solid fa-bag-shopping"></i>No orders yet.</div>';
          } else {
            el.innerHTML = `<div style="display:flex;flex-direction:column;gap:0.75rem">` +
              data.orders.map(o => `
                <div class="card card-body" style="display:flex;justify-content:space-between;align-items:center;gap:1rem">
                  <div style="flex:1;min-width:0">
                    <a href="/swift-swap/pages/listing.php?id=${o.listing_id}" style="font-weight:600">${escapeHtml(o.listing_title)}</a>
                    <div class="text-muted" style="font-size:0.8rem">Seller: ${escapeHtml(o.other_party)} · ${timeAgo(o.created_at)}</div>
                  </div>
                  <div style="display:flex;align-items:center;gap:0.75rem">
                    <div style="text-align:right">
                      <div style="font-weight:700;color:var(--primary)">${formatPrice(o.amount)}</div>
                      <span class="pill pill-${o.status}">${o.status}</span>
                    </div>
                    ${['pending','confirmed'].includes(o.status) ? `
                      <button class="btn btn-danger btn-sm" onclick="cancelOrder(${o.id})">Cancel</button>
                    ` : ''}
                  </div>
                </div>`).join('') + '</div>';
            document.getElementById('stat-buying').textContent = data.orders.length;
          }
        } catch {
          el.innerHTML = '<div class="alert alert-error">Could not load orders.</div>';
        }
      }

      if (tab === 'watchlist') {
        const grid = document.getElementById('watchlist-grid');
        try {
          const data = await API.get('/api/watchlist.php');
          document.getElementById('stat-watchlist').textContent = data.watchlist?.length || 0;
          if (!data.watchlist?.length) {
            grid.innerHTML = '<div class="empty-state"><i class="fa-regular fa-heart"></i>Your watchlist is empty.</div>';
          } else {
            grid.innerHTML = data.watchlist.map(l => listingCardHtml({ ...l, seller_name: '', created_at: l.created_at })).join('');
          }
        } catch {
          grid.innerHTML = '<div class="alert alert-error">Could not load watchlist.</div>';
        }
      }
    }

    document.getElementById('settings-form')?.addEventListener('submit', async e => {
      e.preventDefault();
      clearAlert('settings-alert');
      const fd   = new FormData(e.target);
      const body = Object.fromEntries(fd);
      if (!body.password) delete body.password;
      try {
        await API.put('/api/users.php', body);
        showAlert('settings-alert', 'Settings saved!', 'success');
      } catch (err) {
        showAlert('settings-alert', err.message);
      }
    });

    async function cancelOrder(orderId) {
      if (!confirm('Cancel this order? The listing will become available again.')) return;
      clearAlert('buying-alert');
      try {
        await API.put('/api/orders.php?id=' + orderId, { status: 'cancelled' });
        delete loaded['buying'];
        loadTab('buying');
      } catch (err) {
        showAlert('buying-alert', err.message);
      }
    }

    loadProfile();
    loadTab('overview');
  </script>
</body>
</html>
