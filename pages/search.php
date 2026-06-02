<?php $title = 'Search'; ?>
<!DOCTYPE html>
<html lang="en">
<?php require_once __DIR__ . '/../includes/head.php'; ?>
<body class="page-wrap">

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <main>
    <div class="container">
      <div class="search-layout">

        <aside>
          <div class="filter-box">
            <form id="filter-form">
              <div class="filter-title">Category</div>
              <select id="filter-category" name="category" style="margin-bottom:0.75rem">
                <option value="">All Categories</option>
                <option value="1">Electronics</option>
                <option value="2">Clothing &amp; Apparel</option>
                <option value="3">Home &amp; Garden</option>
                <option value="4">Sports &amp; Outdoors</option>
                <option value="5">Books &amp; Media</option>
                <option value="6">Toys &amp; Games</option>
                <option value="7">Vehicles</option>
                <option value="8">Collectibles</option>
                <option value="9">Health &amp; Beauty</option>
                <option value="10">Other</option>
              </select>

              <div class="filter-title">Price Range</div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-bottom:0.75rem">
                <input type="number" name="min_price" placeholder="Min R" min="0" step="1">
                <input type="number" name="max_price" placeholder="Max R" min="0" step="1">
              </div>

              <div class="filter-title">Condition</div>
              <div style="margin-bottom:0.75rem">
                <label style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.35rem;font-weight:400">
                  <input type="checkbox" name="condition" value="new"> New
                </label>
                <label style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.35rem;font-weight:400">
                  <input type="checkbox" name="condition" value="like_new"> Like New
                </label>
                <label style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.35rem;font-weight:400">
                  <input type="checkbox" name="condition" value="good"> Good
                </label>
                <label style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.35rem;font-weight:400">
                  <input type="checkbox" name="condition" value="fair"> Fair
                </label>
              </div>

              <div class="filter-title">Sort By</div>
              <select name="sort" style="margin-bottom:1rem">
                <option value="newest">Newest First</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
              </select>

              <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
            </form>
          </div>
        </aside>

        <div id="search-root">
          <div class="section-header">
            <h1 class="section-title" id="search-heading">Browse Listings</h1>
          </div>
          <div id="search-loading" style="display:none" class="loading">Searching…</div>
          <div class="listing-grid" id="search-results"></div>
        </div>

      </div>
    </div>
  </main>

  <script src="/swift-swap/js/main.js"></script>
  <script src="/swift-swap/js/listings.js"></script>
  <script>
    const q = new URLSearchParams(location.search).get('q');
    if (q) {
      document.getElementById('search-heading').textContent = `Results for "${q}"`;
      document.querySelectorAll('.search-q-input').forEach(el => el.value = q);
    }
  </script>
</body>
</html>
