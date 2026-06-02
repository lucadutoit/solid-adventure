<?php $title = 'Listing'; ?>
<!DOCTYPE html>
<html lang="en">
<?php require_once __DIR__ . '/../includes/head.php'; ?>
<body class="page-wrap">

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <main>
    <div class="container">
      <a href="javascript:history.back()" class="btn btn-outline btn-sm mb-2">
        <i class="fa-solid fa-arrow-left"></i> Back
      </a>
      <div id="listing-detail">
        <div class="loading">Loading listing…</div>
      </div>
    </div>
  </main>

  <script src="/swift-swap/js/main.js"></script>
  <script src="/swift-swap/js/listings.js"></script>
</body>
</html>
