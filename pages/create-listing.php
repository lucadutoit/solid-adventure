<?php
require_once __DIR__ . '/../php/config/database.php';
if (!isLoggedIn()) {
    header('Location: /swift-swap/pages/login.php');
    exit;
}
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$title  = $editId ? 'Edit Listing' : 'Post a Listing';
?>
<!DOCTYPE html>
<html lang="en">
<?php require_once __DIR__ . '/../includes/head.php'; ?>
<body class="page-wrap">

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <main>
    <div class="container" style="max-width:680px">
      <h1 style="font-size:1.5rem;font-weight:800;margin-bottom:1.5rem"><?= $editId ? 'Edit Listing' : 'Post a Listing' ?></h1>
      <div id="listing-alert"></div>

      <form id="create-listing-form" novalidate>
        <div class="card card-body">
          <h2 style="font-size:1rem;font-weight:700;margin-bottom:1rem">Item Details</h2>

          <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" placeholder="e.g. iPhone 13 Pro Max 256GB" required maxlength="150">
          </div>

          <div class="form-group">
            <label for="description">Description *</label>
            <textarea id="description" name="description" placeholder="Describe the item — condition, any defects, why you're selling, accessories included…" required></textarea>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="form-group">
              <label for="price">Price (ZAR) *</label>
              <input type="number" id="price" name="price" placeholder="0.00" min="0.01" step="0.01" required>
            </div>
            <div class="form-group">
              <label for="condition">Condition *</label>
              <select id="condition" name="condition" required>
                <option value="new">New</option>
                <option value="like_new">Like New</option>
                <option value="good" selected>Good</option>
                <option value="fair">Fair</option>
                <option value="poor">Poor</option>
              </select>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="form-group">
              <label for="category_id">Category *</label>
              <select id="category_id" name="category_id" required>
                <option value="">Choose category…</option>
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
            </div>
            <div class="form-group">
              <label for="location">Location</label>
              <input type="text" id="location" name="location" placeholder="e.g. Sandton, Johannesburg">
            </div>
          </div>
        </div>

        <div class="card card-body mt-2">
          <h2 style="font-size:1rem;font-weight:700;margin-bottom:1rem">Photos <span class="text-muted" style="font-weight:400">(up to 6)</span></h2>

          <div class="upload-zone" id="upload-zone">
            <i class="fa-solid fa-cloud-arrow-up" style="font-size:2rem;margin-bottom:0.5rem"></i>
            <p style="margin:0;font-size:0.9rem">Drag &amp; drop photos here, or click to browse</p>
            <p style="margin:0.25rem 0 0;font-size:0.78rem;color:var(--gray-400)">JPG, PNG, WebP · Max 5 MB each</p>
            <input type="file" id="image-files" accept="image/*" multiple style="display:none">
          </div>
          <div class="upload-previews" id="upload-previews"></div>
        </div>

        <div class="mt-2" style="display:flex;gap:1rem;justify-content:flex-end">
          <a href="<?= $editId ? "/swift-swap/pages/listing.php?id=$editId" : '/swift-swap/' ?>" class="btn btn-outline">Cancel</a>
          <button type="submit" class="btn btn-accent btn-lg"><?= $editId ? 'Save Changes' : 'Post Listing' ?></button>
        </div>
      </form>
    </div>
  </main>

  <script src="/swift-swap/js/main.js"></script>
  <script src="/swift-swap/js/listings.js"></script>
</body>
</html>
