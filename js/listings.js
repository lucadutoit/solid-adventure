/* Swift Swap — Listing creation & detail logic */

document.addEventListener('DOMContentLoaded', () => {
  const createForm = document.getElementById('create-listing-form');
  if (createForm) initCreateListing(createForm);

  const detailRoot = document.getElementById('listing-detail');
  if (detailRoot) initListingDetail();

  const homeGrid = document.getElementById('home-listings');
  if (homeGrid) initHomeFeed(homeGrid);

  const searchRoot = document.getElementById('search-root');
  if (searchRoot) initSearch(searchRoot);
});

// ── Image upload preview ──────────────────────────────────────
function initImageUpload() {
  const zone      = document.getElementById('upload-zone');
  const fileInput = document.getElementById('image-files');
  const previews  = document.getElementById('upload-previews');
  if (!zone || !fileInput) return;

  let files = [];

  zone.addEventListener('click', () => fileInput.click());
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
  zone.addEventListener('drop', e => {
    e.preventDefault(); zone.classList.remove('drag-over');
    addFiles(Array.from(e.dataTransfer.files));
  });

  fileInput.addEventListener('change', () => {
    addFiles(Array.from(fileInput.files));
    fileInput.value = '';
  });

  function addFiles(newFiles) {
    newFiles.filter(f => f.type.startsWith('image/')).forEach(f => {
      if (files.length >= 6) return;
      files.push(f);
      const div = document.createElement('div');
      div.className = 'upload-preview';
      const img = document.createElement('img');
      img.src = URL.createObjectURL(f);
      const btn = document.createElement('button');
      btn.className = 'upload-preview-remove';
      btn.innerHTML = '✕';
      btn.type = 'button';
      btn.onclick = () => {
        const idx = files.indexOf(f);
        if (idx > -1) files.splice(idx, 1);
        div.remove();
      };
      div.append(img, btn);
      previews.appendChild(div);
    });
  }

  return () => files;
}

function initCreateListing(form) {
  if (!Auth.isLoggedIn) { location.href = '/swift-swap/pages/login.php'; return; }
  if (!Auth.canSell) {
    showAlert('listing-alert', 'Your account role does not have permission to create listings.');
    form.style.display = 'none';
    return;
  }

  const editId = new URLSearchParams(location.search).get('edit');

  if (editId) {
    document.querySelector('h1').textContent = 'Edit Listing';
    form.querySelector('button[type="submit"]').textContent = 'Save Changes';

    API.get('/api/listings.php', { id: editId }).then(l => {
      form.title.value       = l.title;
      form.description.value = l.description;
      form.price.value       = l.price;
      form.category_id.value = l.category_id;
      form.condition.value   = l.condition;
      form.location.value    = l.location || '';
    }).catch(err => showAlert('listing-alert', err.message));
  }

  const getFiles = initImageUpload();

  form.addEventListener('submit', async e => {
    e.preventDefault();
    clearAlert('listing-alert');
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = editId ? 'Saving…' : 'Posting…';

    const payload = {
      title:       form.title.value.trim(),
      description: form.description.value.trim(),
      price:       parseFloat(form.price.value),
      category_id: parseInt(form.category_id.value),
      condition:   form.condition.value,
      location:    form.location.value.trim(),
    };

    try {
      if (editId) {
        await API.put(`/api/listings.php?id=${editId}`, payload);
        location.href = `/swift-swap/pages/listing.php?id=${editId}`;
      } else {
        const result = await API.post('/api/listings.php', payload);

        const files = getFiles ? getFiles() : [];
        if (files.length > 0) {
          const fd = new FormData();
          fd.append('listing_id', result.id);
          files.forEach(f => fd.append('images[]', f));
          await fetch('/swift-swap/php/api/upload.php', {
            method: 'POST', credentials: 'include', body: fd,
          });
        }

        location.href = `/swift-swap/pages/listing.php?id=${result.id}`;
      }
    } catch (err) {
      showAlert('listing-alert', err.message);
      btn.disabled = false;
      btn.textContent = editId ? 'Save Changes' : 'Post Listing';
    }
  });
}

// ── Listing detail ────────────────────────────────────────────
async function initListingDetail() {
  const id = new URLSearchParams(location.search).get('id');
  if (!id) { location.href = '/swift-swap/'; return; }

  const root = document.getElementById('listing-detail');
  root.innerHTML = '<div class="loading">Loading…</div>';

  try {
    const l = await API.get('/api/listings.php', { id });
    renderDetail(l, root);
  } catch (err) {
    root.innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
  }
}

function renderDetail(l, root) {
  const images  = l.images || [];
  const mainSrc = images.find(i => i.is_primary)?.filename || images[0]?.filename;

  root.innerHTML = `
    <div class="listing-detail-grid">
      <div>
        <div class="listing-gallery">
          ${mainSrc
            ? `<img id="gallery-main" class="gallery-main" src="/swift-swap/uploads/listings/${escapeHtml(mainSrc)}" alt="${escapeHtml(l.title)}">`
            : `<div style="background:var(--gray-100);aspect-ratio:4/3;display:flex;align-items:center;justify-content:center;font-size:3rem;color:var(--gray-400)">📷</div>`}
          ${images.length > 1 ? `<div class="gallery-thumbs">${images.map((img, i) =>
            `<img class="gallery-thumb ${i === 0 ? 'active' : ''}" src="/swift-swap/uploads/listings/${escapeHtml(img.filename)}" data-src="${escapeHtml(img.filename)}">`
          ).join('')}</div>` : ''}
        </div>
        <div style="margin-top:1.5rem">
          <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:0.75rem">Description</h2>
          <p style="line-height:1.7;color:var(--gray-600)">${escapeHtml(l.description).replace(/\n/g, '<br>')}</p>
        </div>
      </div>

      <div>
        <div class="card card-body" style="position:sticky;top:80px">
          <div class="listing-price">${formatPrice(l.price)}</div>
          <h1 style="font-size:1.25rem;font-weight:700;margin-top:0.5rem">${escapeHtml(l.title)}</h1>
          <div class="listing-meta" style="margin-top:0.5rem">
            <span class="listing-card-condition cond-${l.condition}">${conditionLabel(l.condition)}</span>
            ${l.location ? ` · 📍 ${escapeHtml(l.location)}` : ''}
          </div>
          <div class="listing-meta" style="margin-top:0.5rem">${timeAgo(l.created_at)} · ${l.view_count} views</div>

          <hr class="divider">

          <div class="flex items-center gap-1" style="margin-bottom:1rem">
            <div class="avatar avatar-md">${escapeHtml(l.seller_name[0].toUpperCase())}</div>
            <div>
              <a href="/swift-swap/pages/profile.php?id=${l.seller_id}" style="font-weight:600">${escapeHtml(l.seller_name)}</a>
              <div class="listing-meta">${starsHtml(l.seller_rating)} ${l.seller_reviews} reviews</div>
            </div>
          </div>

          <div id="action-area">
            ${Auth.isLoggedIn && Auth.userId !== l.seller_id ? `
              ${Auth.canBuy ? `<button class="btn btn-accent btn-block" id="buy-btn">Make an Offer</button>` : ''}
              <button class="btn btn-outline btn-block mt-1" id="msg-btn">Message Seller</button>
              <button class="btn btn-outline btn-block mt-1" id="watch-btn">♡ Save</button>
            ` : Auth.userId === l.seller_id ? `
              <a href="/swift-swap/pages/create-listing.php?edit=${l.id}" class="btn btn-outline btn-block">Edit Listing</a>
              <button class="btn btn-danger btn-block mt-1" id="delete-btn">Remove Listing</button>
            ` : `
              <a href="/swift-swap/pages/login.php" class="btn btn-primary btn-block">Log in to Buy</a>
            `}
          </div>

          <div id="msg-form" style="display:none;margin-top:1rem">
            <textarea id="msg-body" placeholder="Hi, is this still available?" style="margin-bottom:0.5rem"></textarea>
            <button class="btn btn-primary btn-block" id="msg-send-btn">Send Message</button>
          </div>
          <div id="detail-alert" style="margin-top:0.75rem"></div>
        </div>
      </div>
    </div>`;

  wireGallery();
  wireActions(l);
}

function wireGallery() {
  document.querySelectorAll('.gallery-thumb').forEach(thumb => {
    thumb.addEventListener('click', () => {
      document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
      document.getElementById('gallery-main').src = `/swift-swap/uploads/listings/${thumb.dataset.src}`;
    });
  });
}

function wireActions(l) {
  const id = l.id;

  document.getElementById('msg-btn')?.addEventListener('click', () => {
    const f = document.getElementById('msg-form');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
  });

  document.getElementById('msg-send-btn')?.addEventListener('click', async () => {
    const body = document.getElementById('msg-body').value.trim();
    if (!body) return;
    try {
      await API.post('/api/messages.php', { listing_id: id, receiver_id: l.seller_id, body });
      showAlert('detail-alert', 'Message sent!', 'success');
      document.getElementById('msg-form').style.display = 'none';
    } catch (err) {
      showAlert('detail-alert', err.message);
    }
  });

  document.getElementById('buy-btn')?.addEventListener('click', async () => {
    if (!confirm(`Buy "${l.title}" for ${formatPrice(l.price)}?`)) return;
    try {
      await API.post('/api/orders.php', { listing_id: id, payment_method: 'cash' });
      showAlert('detail-alert', 'Order placed! Check your dashboard.', 'success');
    } catch (err) {
      showAlert('detail-alert', err.message);
    }
  });

  document.getElementById('watch-btn')?.addEventListener('click', async () => {
    try {
      await API.post('/api/watchlist.php', { listing_id: id });
      showAlert('detail-alert', 'Saved to watchlist!', 'success');
    } catch (err) {
      showAlert('detail-alert', err.message);
    }
  });

  document.getElementById('delete-btn')?.addEventListener('click', async () => {
    if (!confirm('Remove this listing?')) return;
    try {
      await API.delete(`/api/listings.php?id=${id}`);
      location.href = '/swift-swap/pages/dashboard.php';
    } catch (err) {
      showAlert('detail-alert', err.message);
    }
  });
}

// ── Home feed ─────────────────────────────────────────────────
async function initHomeFeed(grid) {
  grid.innerHTML = '<div class="loading">Loading listings…</div>';
  try {
    const data = await API.get('/api/listings.php', { sort: 'newest' });
    if (!data.listings?.length) { grid.innerHTML = '<div class="empty-state">No listings yet. Be the first to sell!</div>'; return; }
    grid.innerHTML = data.listings.map(listingCardHtml).join('');
  } catch {
    grid.innerHTML = '<div class="alert alert-error">Could not load listings.</div>';
  }
}

// ── Search ────────────────────────────────────────────────────
async function initSearch() {
  const grid    = document.getElementById('search-results');
  const loading = document.getElementById('search-loading');
  if (!grid) return;

  const params = Object.fromEntries(new URLSearchParams(location.search));

  if (params.q) {
    document.querySelectorAll('.search-q-input').forEach(el => { el.value = params.q; });
  }
  if (params.category) {
    const sel = document.getElementById('filter-category');
    if (sel) sel.value = params.category;
  }

  loading.style.display = 'block';
  grid.innerHTML = '';

  try {
    const data = await API.get('/api/listings.php', {
      q:         params.q || '',
      category:  params.category || '',
      min_price: params.min_price || '',
      max_price: params.max_price || '',
      sort:      params.sort || 'newest',
      page:      params.page || 1,
    });
    loading.style.display = 'none';
    if (!data.listings?.length) {
      grid.innerHTML = '<div class="empty-state">No listings found. Try different keywords.</div>';
      return;
    }
    grid.innerHTML = data.listings.map(listingCardHtml).join('');
  } catch (err) {
    loading.style.display = 'none';
    grid.innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
  }
}

// ── Filter form ────────────────────────────────────────────────
document.getElementById('filter-form')?.addEventListener('submit', e => {
  e.preventDefault();
  const fd  = new FormData(e.target);
  const q   = document.querySelector('.search-q-input')?.value.trim() || '';
  const qs  = new URLSearchParams({ q, ...Object.fromEntries(fd) }).toString();
  location.href = `/swift-swap/pages/search.php?${qs}`;
});
