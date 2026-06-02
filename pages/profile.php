<?php $title = 'Profile'; ?>
<!DOCTYPE html>
<html lang="en">
<?php require_once __DIR__ . '/../includes/head.php'; ?>
<body class="page-wrap">

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <main>
    <div class="container" style="max-width:900px">
      <div id="profile-root"><div class="loading">Loading profile…</div></div>
    </div>
  </main>

  <script src="/swift-swap/js/main.js"></script>
  <script>
    async function loadProfile() {
      const id   = new URLSearchParams(location.search).get('id');
      const root = document.getElementById('profile-root');
      if (!id) { root.innerHTML = '<div class="alert alert-error">No user ID specified.</div>'; return; }

      try {
        const user = await API.get('/api/users.php', { id });
        document.title = `${user.username} — Swift Swap`;

        const joinDate = new Date(user.created_at).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        const isOwn    = Auth.userId === user.id;

        root.innerHTML = `
          <div class="card card-body" style="display:flex;gap:1.5rem;align-items:flex-start;margin-bottom:2rem">
            <div class="avatar avatar-lg">${user.username[0].toUpperCase()}</div>
            <div style="flex:1">
              <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap">
                <h1 style="font-size:1.5rem;font-weight:800">${escapeHtml(user.username)}</h1>
                ${user.is_verified ? '<span class="pill pill-active">✓ Verified</span>' : ''}
                ${isOwn ? '<a href="/swift-swap/pages/dashboard.php" class="btn btn-outline btn-sm">Edit Profile</a>' : ''}
              </div>
              <div class="listing-meta" style="margin-top:0.35rem">
                ${starsHtml(user.rating)} <strong>${user.rating.toFixed(1)}</strong> · ${user.review_count} reviews · Joined ${joinDate}
                ${user.location ? ` · <i class="fa-solid fa-location-dot"></i> ${escapeHtml(user.location)}` : ''}
              </div>
              ${user.bio ? `<p style="margin-top:0.75rem;color:var(--gray-600)">${escapeHtml(user.bio)}</p>` : ''}
            </div>
          </div>

          <div class="section-header">
            <h2 class="section-title">${escapeHtml(user.username)}'s Listings (${user.listings?.length || 0})</h2>
          </div>
          <div class="listing-grid">
            ${user.listings?.length
              ? user.listings.map(l => {
                  const img = l.image
                    ? `<img class="listing-card-img" src="/swift-swap/uploads/listings/${escapeHtml(l.image)}" loading="lazy">`
                    : `<div class="listing-card-img-placeholder"><i class="fa-solid fa-image"></i></div>`;
                  return `
                    <a href="/swift-swap/pages/listing.php?id=${l.id}" class="card listing-card" style="text-decoration:none;color:inherit">
                      ${img}
                      <div class="listing-card-body">
                        <div class="listing-card-title">${escapeHtml(l.title)}</div>
                        <div class="listing-card-price">${formatPrice(l.price)}</div>
                        <span class="listing-card-condition cond-${l.condition}">${conditionLabel(l.condition)}</span>
                      </div>
                    </a>`;
                }).join('')
              : '<div class="empty-state" style="grid-column:1/-1">No active listings.</div>'
            }
          </div>`;
      } catch (err) {
        root.innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
      }
    }

    loadProfile();
  </script>
</body>
</html>
