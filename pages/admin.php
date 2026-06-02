<?php
require_once __DIR__ . '/../php/config/database.php';
if (!isLoggedIn()) {
    header('Location: /swift-swap/pages/login.php');
    exit;
}
$db   = getDB();
$stmt = $db->prepare('SELECT r.is_admin FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ? AND u.is_active = 1');
$stmt->execute([currentUserId()]);
$row  = $stmt->fetch();
if (!$row || !$row['is_admin']) {
    header('Location: /swift-swap/index.php');
    exit;
}
$title = 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<?php require_once __DIR__ . '/../includes/head.php'; ?>
<style>
  .admin-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
  .admin-table th {
    padding: 0.6rem 0.875rem; text-align: left;
    font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.05em; color: var(--gray-400);
    border-bottom: 1px solid var(--gray-200); background: var(--gray-50);
  }
  .admin-table td { padding: 0.75rem 0.875rem; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
  .admin-table tr:last-child td { border-bottom: none; }
  .admin-table tr:hover td { background: var(--gray-50); }
  .actions-cell { display: flex; gap: 0.35rem; flex-wrap: wrap; }
  .badge-admin    { background: #ede9fe; color: #5b21b6; }
  .badge-banned   { background: #fee2e2; color: #991b1b; }
  .badge-verified { background: #d1fae5; color: #065f46; }
  .search-bar { display: flex; gap: 0.6rem; margin-bottom: 1.25rem; align-items: center; }
  .search-bar input { max-width: 300px; }
  .search-bar select { width: auto; }
  .admin-header { color: var(--danger); font-weight: 700; font-size: 0.9rem; margin-bottom: 1.25rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--gray-200); }
  .modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
  .modal-content { background: var(--white); border-radius: 0.5rem; box-shadow: var(--shadow-md); max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; }
  .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem; border-bottom: 1px solid var(--gray-200); }
  .modal-header h2 { margin: 0; font-size: 1.25rem; }
  .modal-form-body { padding: 1.25rem; }
  .form-actions { display: flex; gap: 0.5rem; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid var(--gray-200); margin-top: 1rem; }
  .empty-state { text-align: center; padding: 2rem; color: var(--gray-400); }
</style>
<body class="page-wrap">

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <main>
    <div class="container">
      <div class="dash-grid">

        <aside class="dash-sidebar">
          <div class="admin-header">
            <i class="fa-solid fa-shield-halved"></i> Admin Panel
          </div>
          <nav>
            <a class="dash-nav-item active" data-tab="overview"><i class="fa-solid fa-chart-pie"></i> Overview</a>
            <a class="dash-nav-item" data-tab="roles"><i class="fa-solid fa-users-gear"></i> Roles</a>
            <a class="dash-nav-item" data-tab="users"><i class="fa-solid fa-users"></i> Users</a>
            <a class="dash-nav-item" data-tab="listings"><i class="fa-solid fa-tag"></i> Listings</a>
            <hr class="divider">
            <a class="dash-nav-item" href="/swift-swap/pages/dashboard.php"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
          </nav>
        </aside>

        <div class="dash-main">

          <!-- Overview -->
          <div class="tab-panel active" id="tab-overview">
            <div class="section-header">
              <h1 class="section-title">Overview</h1>
            </div>
            <div class="stats-grid" id="admin-stats">
              <div class="loading">Loading…</div>
            </div>
          </div>

          <!-- Roles -->
          <div class="tab-panel" id="tab-roles">
            <div class="section-header">
              <h1 class="section-title">Roles</h1>
              <button class="btn btn-primary btn-sm" id="new-role-btn"><i class="fa-solid fa-plus"></i> New Role</button>
            </div>
            <div id="roles-alert"></div>
            <div class="card" style="overflow:auto">
              <div id="roles-table-wrap"><div class="loading">Loading…</div></div>
            </div>

            <div id="role-modal" class="modal" style="display:none">
              <div class="modal-content">
                <div class="modal-header">
                  <h2 id="role-modal-title">New Role</h2>
                  <button class="btn btn-text" onclick="closeRoleModal()">&times;</button>
                </div>
                <form id="role-form" class="modal-form-body">
                  <div class="form-group">
                    <label for="role-name">Role Name *</label>
                    <input type="text" id="role-name" placeholder="e.g., Seller, Curator" required>
                  </div>
                  <div class="form-group">
                    <label for="role-desc">Description</label>
                    <textarea id="role-desc" placeholder="Describe this role…" rows="3"></textarea>
                  </div>
                  <div class="form-group">
                    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                      <input type="checkbox" id="role-is-admin"> Admin Role
                    </label>
                    <small style="color:var(--gray-400)">Admins can manage the platform</small>
                  </div>
                  <div class="form-group">
                    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                      <input type="checkbox" id="role-can-buy" checked> Can Buy
                    </label>
                  </div>
                  <div class="form-group">
                    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                      <input type="checkbox" id="role-can-sell" checked> Can Sell
                    </label>
                  </div>
                  <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeRoleModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="role-submit-btn">Create Role</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- Users -->
          <div class="tab-panel" id="tab-users">
            <div class="section-header">
              <h1 class="section-title">Users</h1>
              <span id="users-count" class="text-muted" style="font-size:0.85rem"></span>
            </div>
            <div class="search-bar">
              <input type="text" id="user-search" placeholder="Search username or email…">
              <button class="btn btn-outline btn-sm" id="user-search-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
            <div id="users-alert"></div>
            <div class="card" style="overflow:auto">
              <div id="users-table-wrap"><div class="loading">Loading…</div></div>
            </div>
            <div id="users-pagination" class="pagination"></div>
          </div>

          <!-- Listings -->
          <div class="tab-panel" id="tab-listings">
            <div class="section-header">
              <h1 class="section-title">Listings</h1>
              <span id="listings-count" class="text-muted" style="font-size:0.85rem"></span>
            </div>
            <div class="search-bar">
              <input type="text" id="listing-search" placeholder="Search by title…">
              <select id="listing-status-filter">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="sold">Sold</option>
                <option value="reserved">Reserved</option>
                <option value="removed">Removed</option>
              </select>
              <button class="btn btn-outline btn-sm" id="listing-search-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
            <div id="listings-alert"></div>
            <div class="card" style="overflow:auto">
              <div id="listings-table-wrap"><div class="loading">Loading…</div></div>
            </div>
            <div id="listings-pagination" class="pagination"></div>
          </div>

        </div>
      </div>
    </div>
  </main>

  <script src="/swift-swap/js/main.js"></script>
  <script>
    const ADM = '/swift-swap/php/api/admin.php';

    let allRoles      = [];
    let editingRoleId = null;

    async function adminReq(method, params = {}, body = null) {
      const qs  = new URLSearchParams(params).toString();
      const url = `${ADM}${qs ? '?' + qs : ''}`;
      const opts = { method, credentials: 'include', headers: { 'Content-Type': 'application/json' } };
      if (body) opts.body = JSON.stringify(body);
      const res  = await fetch(url, opts);
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`);
      return data;
    }

    // ── Tab switching ──────────────────────────────────────────
    document.querySelectorAll('.dash-nav-item[data-tab]').forEach(link => {
      link.addEventListener('click', e => {
        e.preventDefault();
        const tab = link.dataset.tab;
        document.querySelectorAll('.dash-nav-item[data-tab]').forEach(l => l.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        link.classList.add('active');
        document.getElementById('tab-' + tab)?.classList.add('active');
        if (tab === 'roles')    loadRoles();
        if (tab === 'users')    loadUsers(1);
        if (tab === 'listings') loadListings(1);
      });
    });

    // ── Overview ───────────────────────────────────────────────
    async function loadStats() {
      try {
        const s = await adminReq('GET');
        document.getElementById('admin-stats').innerHTML = `
          <div class="stat-card"><div class="stat-value">${s.total_users}</div><div class="stat-label">Total Users</div></div>
          <div class="stat-card"><div class="stat-value">${s.active_users}</div><div class="stat-label">Active Users</div></div>
          <div class="stat-card"><div class="stat-value">${s.total_listings}</div><div class="stat-label">Total Listings</div></div>
          <div class="stat-card"><div class="stat-value">${s.active_listings}</div><div class="stat-label">Active Listings</div></div>
          <div class="stat-card"><div class="stat-value">${s.total_orders}</div><div class="stat-label">Orders</div></div>
        `;
      } catch (err) {
        document.querySelector('.dash-main').innerHTML = `
          <div class="alert alert-error" style="margin-top:2rem">
            <i class="fa-solid fa-lock"></i> ${escapeHtml(err.message)} — admin accounts only.
          </div>`;
      }
    }

    // ── Roles ──────────────────────────────────────────────────
    async function loadAllRoles() {
      try {
        const data = await adminReq('GET', { section: 'roles' });
        allRoles = data.roles || [];
      } catch (_) {}
    }

    async function loadRoles() {
      const wrap = document.getElementById('roles-table-wrap');
      wrap.innerHTML = '<div class="loading">Loading…</div>';
      clearAlert('roles-alert');
      try {
        const data = await adminReq('GET', { section: 'roles' });
        allRoles = data.roles || [];
        renderRolesTable(allRoles);
      } catch (err) {
        wrap.innerHTML = `<div class="alert alert-error" style="margin:1rem">${escapeHtml(err.message)}</div>`;
      }
    }

    function renderRolesTable(roles) {
      const wrap    = document.getElementById('roles-table-wrap');
      const builtIn = ['user', 'admin'];
      const check   = v => parseInt(v) ? '<span style="color:#10b981;font-weight:700">✓</span>' : '<span style="color:var(--gray-300)">✗</span>';
      if (!roles.length) { wrap.innerHTML = '<div class="empty-state">No roles found.</div>'; return; }
      wrap.innerHTML = `
        <table class="admin-table">
          <thead>
            <tr><th>ID</th><th>Name</th><th>Description</th><th style="text-align:center">Admin</th><th style="text-align:center">Can Buy</th><th style="text-align:center">Can Sell</th><th>Actions</th></tr>
          </thead>
          <tbody>
            ${roles.map(r => `
              <tr>
                <td class="text-muted">#${r.id}</td>
                <td style="font-weight:600">
                  ${escapeHtml(r.name)}
                  ${builtIn.includes(r.name) ? '<span class="pill" style="font-size:0.68rem;background:var(--gray-100);color:var(--gray-400);margin-left:4px">built-in</span>' : ''}
                </td>
                <td class="text-muted" style="font-size:0.82rem">${r.description ? escapeHtml(r.description) : '—'}</td>
                <td style="text-align:center">${check(r.is_admin)}</td>
                <td style="text-align:center">${check(r.can_buy)}</td>
                <td style="text-align:center">${check(r.can_sell)}</td>
                <td>
                  <div class="actions-cell">
                    ${!builtIn.includes(r.name)
                      ? `<button class="btn btn-outline btn-sm" onclick="openEditRole(${r.id})">Edit</button>
                         <button class="btn btn-danger btn-sm" onclick="deleteRole(${r.id})">Delete</button>`
                      : '<span class="text-muted" style="font-size:0.8rem">Protected</span>'}
                  </div>
                </td>
              </tr>`).join('')}
          </tbody>
        </table>`;
    }

    document.getElementById('new-role-btn').addEventListener('click', () => {
      editingRoleId = null;
      document.getElementById('role-modal-title').textContent = 'New Role';
      document.getElementById('role-submit-btn').textContent  = 'Create Role';
      document.getElementById('role-name').value              = '';
      document.getElementById('role-desc').value              = '';
      document.getElementById('role-is-admin').checked        = false;
      document.getElementById('role-can-buy').checked         = true;
      document.getElementById('role-can-sell').checked        = true;
      document.getElementById('role-modal').style.display     = 'flex';
    });

    function openEditRole(id) {
      const role = allRoles.find(r => r.id == id);
      if (!role) return;
      editingRoleId = id;
      document.getElementById('role-modal-title').textContent = 'Edit Role';
      document.getElementById('role-submit-btn').textContent  = 'Save Changes';
      document.getElementById('role-name').value              = role.name;
      document.getElementById('role-desc').value              = role.description || '';
      document.getElementById('role-is-admin').checked        = !!parseInt(role.is_admin);
      document.getElementById('role-can-buy').checked         = !!parseInt(role.can_buy);
      document.getElementById('role-can-sell').checked        = !!parseInt(role.can_sell);
      document.getElementById('role-modal').style.display     = 'flex';
    }

    function closeRoleModal() {
      document.getElementById('role-modal').style.display = 'none';
      editingRoleId = null;
    }

    document.getElementById('role-form').addEventListener('submit', async e => {
      e.preventDefault();
      clearAlert('roles-alert');
      const body = {
        name:        document.getElementById('role-name').value.trim(),
        description: document.getElementById('role-desc').value.trim(),
        is_admin:    document.getElementById('role-is-admin').checked ? 1 : 0,
        can_buy:     document.getElementById('role-can-buy').checked  ? 1 : 0,
        can_sell:    document.getElementById('role-can-sell').checked  ? 1 : 0,
      };
      const submitBtn = document.getElementById('role-submit-btn');
      submitBtn.disabled = true;
      try {
        if (editingRoleId) {
          await adminReq('PATCH', { section: 'roles', id: editingRoleId }, body);
        } else {
          await adminReq('POST', { section: 'roles' }, body);
        }
        closeRoleModal();
        loadRoles();
      } catch (err) {
        showAlert('roles-alert', err.message);
      } finally {
        submitBtn.disabled = false;
      }
    });

    async function deleteRole(id) {
      if (!confirm('Delete this role?')) return;
      clearAlert('roles-alert');
      try {
        await adminReq('DELETE', { section: 'roles', id });
        loadRoles();
      } catch (err) {
        showAlert('roles-alert', err.message);
      }
    }

    // ── Users ──────────────────────────────────────────────────
    let usersPage = 1;

    async function loadUsers(page = 1) {
      usersPage = page;
      if (!allRoles.length) await loadAllRoles();
      const search = document.getElementById('user-search').value.trim();
      const wrap   = document.getElementById('users-table-wrap');
      wrap.innerHTML = '<div class="loading">Loading…</div>';
      try {
        const data = await adminReq('GET', { section: 'users', page, search });
        document.getElementById('users-count').textContent = `${data.total} users`;
        renderUsersTable(data.users);
        renderPagination('users-pagination', data.total, page, 30, loadUsers);
      } catch (err) {
        wrap.innerHTML = `<div class="alert alert-error" style="margin:1rem">${escapeHtml(err.message)}</div>`;
      }
    }

    function renderUsersTable(users) {
      const wrap = document.getElementById('users-table-wrap');
      if (!users.length) { wrap.innerHTML = '<div class="empty-state"><i class="fa-solid fa-users"></i>No users found.</div>'; return; }
      wrap.innerHTML = `
        <table class="admin-table">
          <thead>
            <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
          </thead>
          <tbody>
            ${users.map(u => `
              <tr id="user-row-${u.id}">
                <td class="text-muted">#${u.id}</td>
                <td>
                  <a href="/swift-swap/pages/profile.php?id=${u.id}" target="_blank" style="font-weight:600">${escapeHtml(u.username)}</a>
                  ${u.is_admin    ? '<span class="pill badge-admin" style="margin-left:4px">admin</span>'       : ''}
                  ${u.is_verified ? '<span class="pill badge-verified" style="margin-left:4px">verified</span>' : ''}
                  ${!u.is_active  ? '<span class="pill badge-banned" style="margin-left:4px">banned</span>'    : ''}
                </td>
                <td class="text-muted" style="font-size:0.82rem">${escapeHtml(u.email)}</td>
                <td>
                  <div style="display:flex;align-items:center;gap:0.35rem">
                    <select class="user-role-select" data-id="${u.id}"
                      style="padding:0.25rem 0.4rem;font-size:0.8rem;border:1px solid var(--gray-300);border-radius:0.3rem;background:var(--bg)">
                      ${allRoles.map(r =>
                        `<option value="${r.id}"${r.id == u.role_id ? ' selected' : ''}>${escapeHtml(r.name)}</option>`
                      ).join('')}
                    </select>
                    <button class="btn btn-outline btn-sm" onclick="setUserRole(${u.id})">Set</button>
                  </div>
                </td>
                <td>${u.is_active
                    ? '<span class="pill pill-active">Active</span>'
                    : '<span class="pill pill-sold">Banned</span>'}</td>
                <td class="text-muted">${new Date(u.created_at).toLocaleDateString()}</td>
                <td>
                  <div class="actions-cell">
                    ${u.is_active
                      ? `<button class="btn btn-danger btn-sm" onclick="userAction(${u.id},'ban')">Ban</button>`
                      : `<button class="btn btn-accent btn-sm" onclick="userAction(${u.id},'unban')">Unban</button>`}
                    ${u.is_verified
                      ? `<button class="btn btn-outline btn-sm" onclick="userAction(${u.id},'unverify')">Unverify</button>`
                      : `<button class="btn btn-outline btn-sm" onclick="userAction(${u.id},'verify')">Verify</button>`}
                  </div>
                </td>
              </tr>`).join('')}
          </tbody>
        </table>`;
    }

    async function userAction(id, action) {
      clearAlert('users-alert');
      try {
        await adminReq('PATCH', { section: 'users', id }, { action });
        loadUsers(usersPage);
      } catch (err) {
        showAlert('users-alert', err.message);
      }
    }

    async function setUserRole(id) {
      clearAlert('users-alert');
      const sel = document.querySelector(`.user-role-select[data-id="${id}"]`);
      try {
        await adminReq('PATCH', { section: 'users', id }, { action: 'set_role', role_id: parseInt(sel.value) });
        showAlert('users-alert', 'Role updated.', 'success');
        loadUsers(usersPage);
      } catch (err) {
        showAlert('users-alert', err.message);
      }
    }

    // ── Listings ───────────────────────────────────────────────
    let listingsPage = 1;

    async function loadListings(page = 1) {
      listingsPage = page;
      const search = document.getElementById('listing-search').value.trim();
      const status = document.getElementById('listing-status-filter').value;
      const wrap   = document.getElementById('listings-table-wrap');
      wrap.innerHTML = '<div class="loading">Loading…</div>';
      try {
        const data = await adminReq('GET', { section: 'listings', page, search, status });
        document.getElementById('listings-count').textContent = `${data.total} listings`;
        renderListingsTable(data.listings);
        renderPagination('listings-pagination', data.total, page, 30, loadListings);
      } catch (err) {
        wrap.innerHTML = `<div class="alert alert-error" style="margin:1rem">${escapeHtml(err.message)}</div>`;
      }
    }

    function renderListingsTable(listings) {
      const wrap = document.getElementById('listings-table-wrap');
      if (!listings.length) { wrap.innerHTML = '<div class="empty-state"><i class="fa-solid fa-tag"></i>No listings found.</div>'; return; }
      wrap.innerHTML = `
        <table class="admin-table">
          <thead>
            <tr><th>ID</th><th>Title</th><th>Seller</th><th>Price</th><th>Status</th><th>Views</th><th>Created</th><th>Actions</th></tr>
          </thead>
          <tbody>
            ${listings.map(l => `
              <tr id="listing-row-${l.id}">
                <td class="text-muted">#${l.id}</td>
                <td><a href="/swift-swap/pages/listing.php?id=${l.id}" target="_blank" style="font-weight:600">${escapeHtml(l.title)}</a></td>
                <td><a href="/swift-swap/pages/profile.php?id=${l.seller_id}" target="_blank">${escapeHtml(l.seller_name)}</a></td>
                <td style="font-weight:600;color:var(--primary)">${formatPrice(l.price)}</td>
                <td>
                  <select class="listing-status-select" data-id="${l.id}" style="padding:0.25rem 0.5rem;font-size:0.8rem;width:auto">
                    ${['active','sold','reserved','removed'].map(s =>
                      `<option value="${s}"${l.status === s ? ' selected' : ''}>${s}</option>`
                    ).join('')}
                  </select>
                </td>
                <td class="text-muted">${l.view_count}</td>
                <td class="text-muted">${new Date(l.created_at).toLocaleDateString()}</td>
                <td>
                  <div class="actions-cell">
                    <button class="btn btn-primary btn-sm" onclick="saveListingStatus(${l.id})">Save</button>
                    <button class="btn btn-danger btn-sm" onclick="removeListing(${l.id})">Remove</button>
                  </div>
                </td>
              </tr>`).join('')}
          </tbody>
        </table>`;
    }

    async function saveListingStatus(id) {
      clearAlert('listings-alert');
      const sel = document.querySelector(`.listing-status-select[data-id="${id}"]`);
      try {
        await adminReq('PATCH', { section: 'listings', id }, { status: sel.value });
        showAlert('listings-alert', 'Status updated.', 'success');
      } catch (err) {
        showAlert('listings-alert', err.message);
      }
    }

    async function removeListing(id) {
      clearAlert('listings-alert');
      try {
        await adminReq('PATCH', { section: 'listings', id }, { status: 'removed' });
        loadListings(listingsPage);
      } catch (err) {
        showAlert('listings-alert', err.message);
      }
    }

    // ── Pagination ─────────────────────────────────────────────
    function renderPagination(containerId, total, currentPage, limit, callback) {
      const totalPages = Math.ceil(total / limit);
      const container  = document.getElementById(containerId);
      if (totalPages <= 1) { container.innerHTML = ''; return; }
      let html = '';
      if (currentPage > 1)
        html += `<button class="page-btn" onclick="${callback.name}(${currentPage - 1})">‹</button>`;
      for (let p = Math.max(1, currentPage - 2); p <= Math.min(totalPages, currentPage + 2); p++)
        html += `<button class="page-btn${p === currentPage ? ' active' : ''}" onclick="${callback.name}(${p})">${p}</button>`;
      if (currentPage < totalPages)
        html += `<button class="page-btn" onclick="${callback.name}(${currentPage + 1})">›</button>`;
      container.innerHTML = html;
    }

    // ── Search wiring ──────────────────────────────────────────
    document.getElementById('user-search-btn').addEventListener('click', () => loadUsers(1));
    document.getElementById('user-search').addEventListener('keydown', e => { if (e.key === 'Enter') loadUsers(1); });
    document.getElementById('listing-search-btn').addEventListener('click', () => loadListings(1));
    document.getElementById('listing-search').addEventListener('keydown', e => { if (e.key === 'Enter') loadListings(1); });
    document.getElementById('listing-status-filter').addEventListener('change', () => loadListings(1));

    loadStats();
    loadAllRoles();
  </script>
</body>
</html>
