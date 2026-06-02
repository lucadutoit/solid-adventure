<?php $title = 'SwiftSwap'; ?>
<!DOCTYPE html>
<html lang="en">
<?php require_once __DIR__ . '/includes/head.php'; ?>
<body class="page-wrap">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

  <section class="hero">
    <div class="container">
      <h1>Buy &amp; Sell Anything,<br>Swiftly.</h1>
      <p>The fastest way to find great deals and turn your stuff into cash.</p>
      <div class="hero-search">
        <input type="text" id="hero-search" placeholder="What are you looking for?" autocomplete="off">
        <button id="hero-search-btn">Search</button>
      </div>
    </div>
  </section>

  <main>
    <div class="container">
      <div class="category-bar" id="category-bar">
        <a href="/swift-swap/pages/search.php" class="category-chip active"><i class="fa-solid fa-border-all"></i> All</a>
      </div>

      <div class="section-header">
        <h2 class="section-title">Recent Listings</h2>
        <a href="/swift-swap/pages/search.php" class="btn btn-outline btn-sm">View all</a>
      </div>
      <div class="listing-grid" id="home-listings">
        <div class="loading">Loading…</div>
      </div>
    </div>
  </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

  <script src="/swift-swap/js/main.js"></script>
  <script src="/swift-swap/js/listings.js"></script>
  <script>
    const heroInput = document.getElementById('hero-search');
    document.getElementById('hero-search-btn').addEventListener('click', () => {
      const q = heroInput.value.trim();
      if (q) location.href = `/swift-swap/pages/search.php?q=${encodeURIComponent(q)}`;
    });
    heroInput.addEventListener('keydown', e => {
      if (e.key === 'Enter') document.getElementById('hero-search-btn').click();
    });

    const categories = [
      { name: 'Electronics',   id: 1, icon: 'fa-laptop' },
      { name: 'Clothing',      id: 2, icon: 'fa-shirt' },
      { name: 'Home & Garden', id: 3, icon: 'fa-house' },
      { name: 'Sports',        id: 4, icon: 'fa-person-running' },
      { name: 'Books & Media', id: 5, icon: 'fa-book' },
      { name: 'Toys & Games',  id: 6, icon: 'fa-gamepad' },
      { name: 'Vehicles',      id: 7, icon: 'fa-car' },
      { name: 'Collectibles',  id: 8, icon: 'fa-star' },
    ];
    const bar = document.getElementById('category-bar');
    categories.forEach(c => {
      const a = document.createElement('a');
      a.href = `/swift-swap/pages/search.php?category=${c.id}`;
      a.className = 'category-chip';
      a.innerHTML = `<i class="fa-solid ${c.icon}"></i> ${c.name}`;
      bar.appendChild(a);
    });
  </script>
</body>
</html>
